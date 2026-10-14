<?php

use App\Exports\VehicleFridayWorkExport;
use App\Livewire\Reports\VehicleFridayWork;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Reports\VehicleFridayWork as Report;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function fridayAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

/** صاحب `vehicles.export` مربوط بمحافظات بعينها */
function fridayScopedUser(array $govIds): User
{
    Permission::findOrCreate('vehicles.export', 'web');
    $role = Role::findOrCreate('vh-export', 'web');
    $role->givePermissionTo('vehicles.export');
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

function fridayVehicle(Governorate $gov, string $name, ?bool $friday): Vehicle
{
    return Vehicle::create(['governorate_id' => $gov->id, 'name' => $name, 'works_on_friday' => $friday]);
}

/** قائمة أسماء السيارات المعروضة */
function fridayNames($test): array
{
    return $test->viewData('vehicles')->pluck('name')->all();
}

beforeEach(function () {
    $this->cairo = Governorate::factory()->create(['name' => 'القاهرة', 'order' => 1]);
    $this->giza  = Governorate::factory()->create(['name' => 'الجيزة', 'order' => 2]);

    fridayVehicle($this->cairo, 'ق١ تعمل', true);
    fridayVehicle($this->cairo, 'ق٢ تعمل', true);
    fridayVehicle($this->cairo, 'ق٣ لا تعمل', false);
    fridayVehicle($this->cairo, 'ق٤ لم تحدد', null);
    fridayVehicle($this->giza, 'ج١ لا تعمل', false);
});

it('counts works, not works and unset per governorate', function () {
    $test = Livewire::actingAs(fridayAdmin())->test(VehicleFridayWork::class)->call('search');

    expect($test->viewData('counts'))->toEqual([
        $this->cairo->id => ['works' => 2, 'not_works' => 1, 'unset' => 1],
        $this->giza->id  => ['not_works' => 1],
    ]);
    $test->assertSee(__('home.report_friday_unset_note', ['count' => 1]));
});

it('lists the vehicles of the chosen category only', function (string $friday, array $names) {
    $test = Livewire::actingAs(fridayAdmin())->test(VehicleFridayWork::class)
        ->set('friday', $friday)->call('search');

    expect(fridayNames($test))->toBe($names);
})->with([
    'works'     => ['works', ['ق١ تعمل', 'ق٢ تعمل']],
    'not works' => ['not_works', ['ق٣ لا تعمل', 'ج١ لا تعمل']],
    'unset'     => ['unset', ['ق٤ لم تحدد']],
    'all'       => ['', ['ق١ تعمل', 'ق٢ تعمل', 'ق٣ لا تعمل', 'ق٤ لم تحدد', 'ج١ لا تعمل']],
]);

it('ignores an unknown category instead of returning an empty list', function () {
    $test = Livewire::actingAs(fridayAdmin())->test(VehicleFridayWork::class)
        ->set('friday', "works' OR 1=1")->call('search');

    expect(fridayNames($test))->toHaveCount(5);
});

it('filters by governorate', function () {
    $test = Livewire::actingAs(fridayAdmin())->test(VehicleFridayWork::class)
        ->set('governorateIds', [(string) $this->giza->id])->call('search');

    expect(fridayNames($test))->toBe(['ج١ لا تعمل'])
        ->and($test->viewData('rows')->pluck('id')->all())->toBe([$this->giza->id]);
});

it('keeps a scoped user inside their governorates even with a forged filter', function () {
    $user = fridayScopedUser([$this->giza->id]);

    $test = Livewire::actingAs($user)->test(VehicleFridayWork::class)
        ->set('governorateIds', [(string) $this->cairo->id])->call('search');

    expect(fridayNames($test))->toBe([])
        ->and($test->viewData('counts'))->toBe([]);
});

it('shows nothing to a user with the permission but no governorates', function () {
    $test = Livewire::actingAs(fridayScopedUser([]))->test(VehicleFridayWork::class)->call('search');

    expect(fridayNames($test))->toBe([])
        ->and($test->viewData('rows'))->toHaveCount(0);
});

it('forbids the screen and the pdf without vehicles.export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reports.vehicle-friday'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.vehicle-friday.pdf'))->assertForbidden();
});

it('builds the pdf from the same filters', function () {
    $response = $this->actingAs(fridayAdmin())
        ->withSession(['vehicle_friday_filters' => ['governorateIds' => [], 'friday' => 'works']])
        ->get(route('reports.vehicle-friday.pdf'))
        ->assertOk();

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('exports the summary and the list to excel', function () {
    $report = Report::make(null, ['friday' => 'not_works']);
    $sheets = (new VehicleFridayWorkExport($report))->sheets();

    expect($sheets[0]->array())->toBe([
        ['القاهرة', 2, 1, 1, 4],
        ['الجيزة', 0, 1, 0, 1],
        ['الإجمالي', 2, 2, 1, 5],
    ])->and($sheets[1]->array())->toBe([
        [1, 'القاهرة', 'ق٣ لا تعمل', '—', 'لا تعمل'],
        [2, 'الجيزة', 'ج١ لا تعمل', '—', 'لا تعمل'],
    ]);
});

it('writes zero cells as 0 in the downloaded file', function () {
    Excel::fake();

    Livewire::actingAs(fridayAdmin())->test(VehicleFridayWork::class)
        ->call('search')->call('exportExcel');

    Excel::assertDownloaded('vehicle-friday-work-' . now()->format('Ymd-His') . '.xlsx');
});

it('writes zero as 0 not an empty cell', function () {
    $file = tempnam(sys_get_temp_dir(), 'fw') . '.xlsx';
    file_put_contents($file, Excel::raw(new VehicleFridayWorkExport(Report::make(null, [])), \Maatwebsite\Excel\Excel::XLSX));
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getSheetByName('الملخص');
    expect($sheet->getCell('B3')->getValue())->toBe(0);   // الجيزة · تعمل
    @unlink($file);
});

it('appears in the sidebar', function () {
    $this->actingAs(fridayAdmin())->get(route('reports.vehicle-friday'))
        ->assertOk()
        ->assertSee(route('reports.vehicle-friday'), false);
});
