<?php

use App\Livewire\Reports\DeviceCount;
use App\Livewire\Reports\MultiOffice;
use App\Livewire\Reports\OfficePdf;
use App\Livewire\Reports\OfficesByType;
use App\Livewire\Reports\PhoneDirectory;
use App\Livewire\Reports\StatsComparison;
use App\Models\DeviceType;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\Office;
use App\Models\OfficeBrokenDevice;
use App\Models\OfficeType;
use App\Models\StatType;
use App\Models\User;
use App\Support\OfficeScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * المرحلة الثانية لقيد النوع/الوصف على الدور: التقارير ودليل الهاتف وملفاتها.
 * كل اختبار يقارن مقرّين «حياة كريمة» بمقرٍّ عادي أرقامه كبيرة عمداً — لو تسرّب ظهر في المجموع.
 */
beforeEach(function () {
    $this->gov    = Governorate::factory()->create(['name' => 'محافظتي']);
    $this->typeA  = OfficeType::factory()->create(['name' => 'مكتب توثيق']);
    $this->typeB  = OfficeType::factory()->create(['name' => 'مكتب شهر']);
    $this->karima = LocationDescription::create(['name' => 'حياة كريمة']);
    $this->normal = LocationDescription::create(['name' => 'مبنى حكومي']);

    $make = fn ($type, $desc, $name, $computers) => Office::factory()->create([
        'governorate_id' => $this->gov->id, 'type_id' => $type->id, 'location_description_id' => $desc->id,
        'name' => $name, 'computers_count' => $computers, 'head_name' => 'رئيس '.$name,
    ]);
    $this->karimaA = $make($this->typeA, $this->karima, 'مقر كريمة توثيق', 3);
    $this->karimaB = $make($this->typeB, $this->karima, 'مقر كريمة شهر', 4);
    $this->normalA = $make($this->typeA, $this->normal, 'مقر عادي', 100);

    foreach (OfficeScope::OFFICE_PERMISSIONS as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $role = Role::findOrCreate('karima', 'web');
    $role->syncPermissions(['offices.index', 'offices.view', 'offices.export', 'offices.phone-directory']);
    DB::table('location_description_role')->insert(['role_id' => $role->id, 'location_description_id' => $this->karima->id]);

    $this->user = tap(User::factory()->create())->assignRole($role);
    $this->user->governorates()->sync([$this->gov->id]);
    $this->user = $this->user->fresh();
});

/** يلتقط بيانات قالب الـPDF قبل أن يرسمه mpdf */
function rosrCapture(string $view, string $key): Closure
{
    $captured = null;
    View::composer($view, function ($v) use (&$captured, $key) { $captured = $v->getData()[$key]; });

    return function () use (&$captured) { return $captured; };
}

it('limits the multi-office report and its options', function () {
    Livewire::actingAs($this->user)->test(MultiOffice::class)
        ->call('search')
        ->assertViewHas('offices', fn ($p) => collect($p->items())->pluck('name')->sort()->values()->all() === ['مقر كريمة توثيق', 'مقر كريمة شهر'])
        ->assertViewHas('officeOptions', fn ($o) => ! $o->pluck('name')->contains('مقر عادي'))
        ->assertViewHas('locations', fn ($l) => $l->pluck('id')->all() === [$this->karima->id]);
});

it('re-checks the restriction on multi-office PDFs whatever ids the session holds', function (string $route, string $view) {
    $offices = rosrCapture($view, 'offices');

    $this->actingAs($this->user)
        ->withSession(['report_office_ids' => [$this->karimaA->id, $this->normalA->id], 'report_custom_columns' => ['name']])
        ->get(route($route))->assertOk();

    expect($offices()->pluck('name')->all())->toBe(['مقر كريمة توثيق']);
})->with([
    'pdf'        => ['reports.multi-office.pdf', 'print.multi-office-pdf'],
    'custom pdf' => ['reports.multi-office.custom-pdf', 'print.multi-office-custom-pdf'],
]);

it('counts only allowed offices in offices-by-type, screen and PDF', function () {
    Livewire::actingAs($this->user)->test(OfficesByType::class)
        ->call('search')
        ->assertViewHas('matrix', fn ($m) => array_sum($m['typeCounts'][$this->gov->id] ?? []) === 2
            && $m['locations']->pluck('id')->all() === [$this->karima->id]);

    $counts = rosrCapture('print.offices-by-type-pdf', 'typeCounts');
    $this->actingAs($this->user)->withSession(['offices_by_type_filters' => []])
        ->get(route('reports.offices-by-type.pdf'))->assertOk();

    expect(array_sum($counts()[$this->gov->id]))->toBe(2);
});

it('sums only allowed offices in the device count, screen and PDF', function () {
    $type = DeviceType::create(['name' => 'طابعة']);
    OfficeBrokenDevice::create(['office_id' => $this->karimaA->id, 'device_type_id' => $type->id, 'count' => 2]);
    OfficeBrokenDevice::create(['office_id' => $this->normalA->id, 'device_type_id' => $type->id, 'count' => 50]);

    Livewire::actingAs($this->user)->test(DeviceCount::class)
        ->call('search')
        ->assertViewHas('matrix', fn ($m) => $m['sums'][$this->gov->id]['computers_count'] === 7
            && $m['brokenSums'][$this->gov->id][$type->id] === 2);

    $sums = rosrCapture('print.device-count-pdf', 'sums');
    $this->actingAs($this->user)->withSession(['device_count_filters' => []])
        ->get(route('reports.device-count.pdf'))->assertOk();

    expect($sums()[$this->gov->id]['computers_count'])->toBe(7);
});

it('sums only allowed offices in the statistics comparison', function () {
    $stat = StatType::create(['name' => 'معاملات', 'period' => 'yearly', 'value_type' => 'count', 'group_key' => 'transactions', 'order' => 1]);
    foreach ([[$this->karimaA, 10], [$this->karimaB, 5], [$this->normalA, 1000]] as [$office, $value]) {
        // `stat_type` عمود قديم ما زال NOT NULL في بنية الاختبار — يُكتب مباشرة
        DB::table('office_statistics')->insert([
            'office_id' => $office->id, 'stat_type_id' => $stat->id, 'stat_type' => 'transactions', 'year' => 2025, 'value' => $value,
        ]);
    }

    Livewire::actingAs($this->user)->test(StatsComparison::class)
        ->set('groupKeys', ['transactions'])
        ->set('year1', 2024)
        ->set('year2', 2025)
        ->call('search')
        ->assertViewHas('matrix', fn ($m) => ($m['data'][$this->gov->id]['transactions'][2025] ?? null) === 15);
});

it('limits the phone directory options and refuses a tampered office', function () {
    Livewire::actingAs($this->user)->test(PhoneDirectory::class)
        ->set('selectedGovernorateId', $this->gov->id)
        ->assertViewHas('offices', fn ($o) => ! $o->pluck('name')->contains('مقر عادي') && $o->count() === 2)
        ->set('selectedOfficeId', $this->normalA->id)
        ->call('search')
        ->assertViewHas('results', fn ($r) => $r->isEmpty());
});

it('limits the single-office PDF picker', function () {
    Livewire::actingAs($this->user)->test(OfficePdf::class)
        ->set('selectedGovernorateId', $this->gov->id)
        ->assertViewHas('offices', fn ($o) => $o->count() === 2 && ! $o->pluck('name')->contains('مقر عادي'));
});

it('counts only allowed offices on the governorates screen', function () {
    Permission::findOrCreate('governorates.index', 'web');
    $this->user->roles->first()->givePermissionTo('governorates.index');

    Livewire::actingAs($this->user->fresh())->test(\App\Livewire\Governorates\Index::class)
        ->assertViewHas('governorates', fn ($p) => collect($p->items())->firstWhere('id', $this->gov->id)->offices_count === 2);
});
