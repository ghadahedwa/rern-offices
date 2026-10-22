<?php

use App\Exports\VehicleStatisticsExport;
use App\Livewire\Reports\VehicleStatistics;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleColumns;
use App\Reports\VehicleStatistics as Report;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function vsAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

function vsScopedUser(array $govIds): User
{
    Permission::findOrCreate('vehicles.export', 'web');
    $role = Role::findOrCreate('vs-export', 'web');
    $role->givePermissionTo('vehicles.export');
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

function vsStat(Vehicle $v, int $type, int $year, ?int $month, float $value): void
{
    VehicleStat::create(['vehicle_id' => $v->id, 'stat_type_id' => $type, 'year' => $year, 'month' => $month, 'value' => $value]);
}

/** صفوف السيارات مسطّحة: اسم => [trips, instant, tripsAll, tx, txPrev] */
function vsRows(Report $r): array
{
    return $r->groups()->flatMap(fn ($g) => $g['vehicles'])
        ->mapWithKeys(fn ($row) => [$row['vehicle']->name => [$row['trips'], $row['instant'], $row['tripsAll'], $row['tx'], $row['txPrev']]])
        ->all();
}

beforeEach(function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 12:00', 'Africa/Cairo'));
    $this->trips   = VehicleColumns::tripsTypeId();
    $this->instant = VehicleColumns::instantTripsTypeId();

    $this->cairo = Governorate::factory()->create(['name' => 'القاهرة', 'order' => 1]);
    $this->giza  = Governorate::factory()->create(['name' => 'الجيزة', 'order' => 2]);

    $this->a = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'أ', 'avg_daily_transactions' => 40, 'avg_daily_support' => 7, 'avg_daily_trips' => 3]);
    $this->b = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'ب', 'avg_daily_transactions' => 20]);
    $this->c = Vehicle::create(['governorate_id' => $this->giza->id, 'name' => 'ج']);

    vsStat($this->a, 1, 2026, null, 900);   // يناير–سبتمبر
    vsStat($this->a, 1, 2026, 10, 100);
    vsStat($this->a, 1, 2025, null, 4000);
    vsStat($this->a, $this->trips, 2026, 10, 20);
    vsStat($this->b, $this->trips, 2026, 10, 0); // صفر مسجَّل ≠ لم يُسجَّل
    vsStat($this->a, $this->instant, 2026, 9, 2);
    vsStat($this->a, $this->instant, 2026, 10, 5);
    vsStat($this->c, $this->instant, 2026, 10, 4);
    vsStat($this->c, 1, 2025, null, 500);
    vsStat($this->c, 1, 2024, null, 300);
});

it('sums the current year so far, with dashes for nothing recorded and 0 for a recorded zero', function () {
    $r = Report::make(null, ['year' => '2026']);

    expect($r->isPartial())->toBeTrue()
        ->and(vsRows($r))->toBe([
            'أ' => [20.0, 7.0, 27.0, 1000.0, 4000.0],
            'ب' => [0.0, null, 0.0, null, null],   // الصفر المسجَّل يبقى صفراً في الإجمالي
            'ج' => [null, 4.0, 4.0, null, 500.0],
        ])
        ->and($r->groups()->first()['totals'])->toBe(['trips' => 20.0, 'instant' => 7.0, 'tripsAll' => 27.0, 'tx' => 1000.0, 'txPrev' => 4000.0])
        ->and($r->grandTotals())->toBe(['trips' => 20.0, 'instant' => 11.0, 'tripsAll' => 31.0, 'tx' => 1000.0, 'txPrev' => 4500.0]);
});

it('compares a finished year with the one before', function () {
    $r = Report::make(null, ['year' => '2025']);

    expect($r->isPartial())->toBeFalse()
        ->and(vsRows($r)['ج'])->toBe([null, null, null, 500.0, 300.0])
        ->and(vsRows($r)['أ'])->toBe([null, null, null, 4000.0, null]);
});

it('hides the previous-year column for the current year only', function () {
    $test = Livewire::actingAs(vsAdmin())->test(VehicleStatistics::class)
        ->set('year', '2026')->call('search')
        ->assertSee(__('home.vstat_report_partial_note', ['year' => 2026]))
        ->assertDontSee(__('home.vstat_col_tx', ['year' => 2025]));

    $test->set('year', '2025')->call('search')
        ->assertSee(__('home.vstat_col_tx', ['year' => 2024]))
        ->assertDontSee(__('home.vstat_report_partial_note', ['year' => 2025]));
});

it('falls back to the current year for a bad year value', function (mixed $year) {
    expect(Report::make(null, ['year' => $year])->year())->toBe(2026);
})->with(['abc', '2050', '2019', '20 26', null, [['2025']]]);

it('groups by governorate order and lists vehicles without numbers', function () {
    $r = Report::make(null, ['year' => '2026']);

    expect($r->groups()->map(fn ($g) => [$g['governorate']->name, array_map(fn ($x) => $x['vehicle']->name, $g['vehicles'])])->all())
        ->toBe([['القاهرة', ['أ', 'ب']], ['الجيزة', ['ج']]]);
});

it('keeps a scoped user inside their governorates even with a forged filter', function () {
    $user = vsScopedUser([$this->giza->id]);

    $own = Livewire::actingAs($user)->test(VehicleStatistics::class)->call('search')->viewData('report');
    expect(array_keys(vsRows($own)))->toBe(['ج']);

    $forged = Livewire::actingAs($user)->test(VehicleStatistics::class)
        ->set('governorateIds', [(string) $this->cairo->id])->call('search')->viewData('report');
    expect($forged->groups())->toHaveCount(0);
});

it('shows nothing to a user with the permission but no governorates', function () {
    $r = Livewire::actingAs(vsScopedUser([]))->test(VehicleStatistics::class)->call('search')->viewData('report');

    expect($r->groups())->toHaveCount(0)
        ->and($r->grandTotals())->toBe(['trips' => null, 'instant' => null, 'tripsAll' => null, 'tx' => null, 'txPrev' => null]);
});

it('forbids the screen and the pdf without vehicles.export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reports.vehicle-statistics'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.vehicle-statistics.pdf'))->assertForbidden();
});

it('exports numbers only, with subtotal rows and no previous year for the current year', function () {
    $export = new VehicleStatisticsExport(Report::make(null, ['year' => '2026']));

    expect($export->headings())->toBe([
        'المحافظة', 'السيارة', 'متوسط المعاملات اليومي', 'متوسط الدعم اليومي', 'متوسط الانتقال اليومي',
        'الانتقالات العادية 2026', 'الانتقالات الفورية 2026', 'إجمالي الانتقالات 2026', 'معاملات التوثيق 2026 (حتى الآن)',
    ])->and($export->array())->toBe([
        ['القاهرة', 'أ', 40, 7, 3, 20.0, 7.0, 27.0, 1000.0],
        ['القاهرة', 'ب', 20, null, null, 0.0, null, 0.0, null],
        ['إجمالي القاهرة', null, null, null, null, 20.0, 7.0, 27.0, 1000.0],
        ['الجيزة', 'ج', null, null, null, null, 4.0, 4.0, null],
        ['إجمالي الجيزة', null, null, null, null, null, 4.0, 4.0, null],
        ['الإجمالي العام', null, null, null, null, 20.0, 11.0, 31.0, 1000.0],
    ]);
});

it('adds the previous-year column to the export for a finished year', function () {
    $export = new VehicleStatisticsExport(Report::make(null, ['year' => '2025']));
    $rows   = $export->array();

    expect($export->headings())->toContain('معاملات التوثيق 2024')
        ->and(end($rows))->toBe(['الإجمالي العام', null, null, null, null, null, null, null, 4500.0, 300.0]);
});

it('builds the pdf from the same filters', function () {
    $response = $this->actingAs(vsAdmin())
        ->withSession(['vehicle_statistics_filters' => ['governorateIds' => [], 'year' => '2025']])
        ->get(route('reports.vehicle-statistics.pdf'))
        ->assertOk();

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('renders the screen with a link in the sidebar', function () {
    $this->actingAs(vsAdmin())->get(route('reports.vehicle-statistics'))
        ->assertOk()
        ->assertSee(route('reports.vehicle-statistics'), false);
});

it('writes a recorded zero as 0 and nothing recorded as an empty cell in the file', function () {
    $file = tempnam(sys_get_temp_dir(), 'vs') . '.xlsx';
    file_put_contents($file, \Maatwebsite\Excel\Facades\Excel::raw(
        new VehicleStatisticsExport(Report::make(null, ['year' => '2026'])), \Maatwebsite\Excel\Excel::XLSX
    ));
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();

    expect($sheet->getCell('F3')->getValue())->not->toBeNull()->toEqual(0)   // ب · الانتقالات: صفر مسجَّل
        ->and($sheet->getCell('G3')->getValue())->toBeNull()   // ب · الفورية: لم يُسجَّل
        ->and($sheet->getCell('H3')->getValue())->toEqual(0)     // ب · إجمالي الانتقالات: صفر + لا شيء = صفر
        ->and($sheet->getCell('I3')->getValue())->toBeNull()   // ب · المعاملات: لم يُسجَّل
        ->and($sheet->getCell('G2')->getValue())->toEqual(7);  // أ · الفورية: سبتمبر + أكتوبر
    @unlink($file);
});

it('sorts by instant trips inside each governorate, with dashes last', function () {
    $d = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'د']);
    vsStat($d, $this->instant, 2026, 10, 3);

    $names = fn (string $dir) => array_map(
        fn ($x) => $x['vehicle']->name,
        Report::make(null, ['year' => '2026', 'sort' => 'instant', 'dir' => $dir])->groups()->first()['vehicles']
    );

    expect($names('asc'))->toBe(['د', 'أ', 'ب'])
        ->and($names('desc'))->toBe(['أ', 'د', 'ب']);
});
it('shows the instant trips column with its totals on the screen and in the pdf', function () {
    Livewire::actingAs(vsAdmin())->test(VehicleStatistics::class)
        ->set('year', '2026')->call('search')
        ->assertSee(__('home.vstat_col_trips', ['year' => 2026]))
        ->assertSee(__('home.vstat_col_instant_trips', ['year' => 2026]))
        ->assertSeeHtml('>11</td>');   // الإجمالي العام للفورية: ٧ + ٤

    $html = view('print.vehicle-statistics-pdf', [
        'report' => Report::make(null, ['year' => '2026']), 'logoBase64' => null,
    ])->render();

    expect($html)->toContain('الانتقالات الفورية 2026')
        ->and(preg_replace('/\s+/', '', $html))->toContain('<td>20</td><td>11</td><td>31</td><td>1,000</td>');
});

it('adds regular and instant trips into one total, sortable and shown on the screen', function () {
    $d = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'د']);
    vsStat($d, $this->trips, 2026, 10, 25);   // عادية أكثر من «أ» (٢٠)، وإجماليها أقل (٢٥ < ٢٧)

    $names = fn (string $dir) => array_map(
        fn ($x) => $x['vehicle']->name,
        Report::make(null, ['year' => '2026', 'sort' => 'trips_all', 'dir' => $dir])->groups()->first()['vehicles']
    );

    // بالعادية وحدها «د» أولاً تنازلياً، وبالإجمالي «أ» (٢٧) قبله (٢٥)
    expect($names('desc'))->toBe(['أ', 'د', 'ب'])
        ->and($names('asc'))->toBe(['ب', 'د', 'أ']);

    Livewire::actingAs(vsAdmin())->test(VehicleStatistics::class)
        ->set('year', '2026')->call('search')
        ->assertSee(__('home.vstat_col_trips_all', ['year' => 2026]))
        ->assertSeeHtml('>56</td>');   // الإجمالي العام: ٤٥ عادية + ١١ فورية
});

it('shows every column on phones in a sideways-scrolling frame with the name pinned', function () {
    // بلاغ المستخدمة 2026-10-22: على التطبيق ظهر «إجمالي الانتقالات» و«المعاملات» وحدهما (الباقي max-sm:hidden)
    // ولا شيء يُتمرَّر إليه — فالأعمدة كلها ظاهرة، والإطار يتمرّر، والاسم ثابت يميناً
    $html = Livewire::actingAs(vsAdmin())->test(VehicleStatistics::class)
        ->set('year', '2026')->call('search')->html();

    $table = substr($html, strpos($html, '<table'), strpos($html, '</table>') - strpos($html, '<table'));

    expect($table)->not->toContain('max-sm:hidden')
        ->and($table)->not->toContain('sm:hidden block')
        ->and($table)->toContain('max-sm:sticky max-sm:right-0')
        ->and($html)->toContain('shadow-sm overflow-x-auto');
});
