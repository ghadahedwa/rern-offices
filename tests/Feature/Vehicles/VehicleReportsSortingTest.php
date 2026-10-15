<?php

use App\Exports\VehicleFridayWorkExport;
use App\Exports\VehicleStatisticsExport;
use App\Livewire\Reports\VehicleFridayWork;
use App\Livewire\Reports\VehicleStatistics;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleFridayWork as FridayReport;
use App\Reports\VehicleStatistics as StatsReport;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 12:00', 'Africa/Cairo'));
    $this->admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    // الترتيب المعتمد يخالف الأبجدي: «يي» أولاً
    $this->yy = Governorate::factory()->create(['name' => 'يي', 'order' => 1]);
    $this->aa = Governorate::factory()->create(['name' => 'أأ', 'order' => 2]);

    $make = fn ($gov, $name, $friday, ?float $tx) => tap(
        Vehicle::create(['governorate_id' => $gov->id, 'name' => $name, 'works_on_friday' => $friday]),
        fn ($v) => $tx === null ? null : VehicleStat::create(['vehicle_id' => $v->id, 'stat_type_id' => 1, 'year' => 2025, 'month' => null, 'value' => $tx]),
    );

    $make($this->yy, 'ب', true, 100);
    $make($this->yy, 'أ', false, 300);
    $make($this->yy, 'ج', null, null);     // بلا رقم
    $make($this->aa, 'د', true, 50);
    $make($this->aa, 'ه', false, 900);
});

/** أسماء السيارات لكل محافظة بترتيب العرض */
function statsOrder(StatsReport $r): array
{
    return $r->groups()->map(fn ($g) => array_map(fn ($x) => $x['vehicle']->name, $g['vehicles']))->all();
}

// ── إحصائيات السيارات: الترتيب داخل كل محافظة ──

it('sorts by transactions inside each governorate, keeping governorate order', function () {
    expect(statsOrder(StatsReport::make(null, ['year' => '2025', 'sort' => 'tx', 'dir' => 'desc'])))
        ->toBe([['أ', 'ب', 'ج'], ['ه', 'د']]);

    expect(statsOrder(StatsReport::make(null, ['year' => '2025', 'sort' => 'tx', 'dir' => 'asc'])))
        ->toBe([['ب', 'أ', 'ج'], ['د', 'ه']]);
});

it('keeps vehicles without numbers last in both directions', function () {
    foreach (['asc', 'desc'] as $dir) {
        expect(statsOrder(StatsReport::make(null, ['year' => '2025', 'sort' => 'tx', 'dir' => $dir]))[0][2])->toBe('ج');
    }
});

it('keeps the subtotals per governorate whatever the sort', function () {
    $r = StatsReport::make(null, ['year' => '2025', 'sort' => 'tx', 'dir' => 'desc']);

    expect($r->groups()->map(fn ($g) => [$g['governorate']->name, $g['totals']['tx']])->all())
        ->toBe([['يي', 400.0], ['أأ', 950.0]]);
});

it('ignores an unknown sort column', function () {
    expect(statsOrder(StatsReport::make(null, ['year' => '2025', 'sort' => 'governorate_id; drop', 'dir' => 'desc'])))
        ->toBe([['أ', 'ب', 'ج'], ['د', 'ه']]);   // الاسم داخل المحافظة
});

it('applies the header click immediately and passes the sort to the excel', function () {
    $test = Livewire::actingAs($this->admin)->test(VehicleStatistics::class)
        ->set('year', '2025')->call('search')
        ->call('sort', 'tx')->call('sort', 'tx');   // تنازلي

    expect(statsOrder($test->viewData('report')))->toBe([['أ', 'ب', 'ج'], ['ه', 'د']]);

    $rows = (new VehicleStatisticsExport($test->viewData('report')))->array();
    expect(array_column($rows, 1))->toBe(['أ', 'ب', 'ج', null, 'ه', 'د', null, null]);
});

it('sends the sort to the pdf', function () {
    Livewire::actingAs($this->admin)->test(VehicleStatistics::class)
        ->set('year', '2025')->call('search')->call('sort', 'tx')->call('exportPdf');

    expect(session('vehicle_statistics_filters'))->toMatchArray(['sort' => 'tx', 'dir' => 'asc']);
});

// ── العمل أيام الجمع: ترتيب القائمة ──

it('lists by governorate order then name by default', function () {
    expect(FridayReport::make(null, [])->vehicles()->pluck('name')->all())->toBe(['أ', 'ب', 'ج', 'د', 'ه']);
});

it('sorts the friday list by name, governorate and category', function () {
    expect(FridayReport::make(null, ['sort' => 'name', 'dir' => 'desc'])->vehicles()->pluck('name')->all())
        ->toBe(['ه', 'د', 'ج', 'ب', 'أ']);

    expect(FridayReport::make(null, ['sort' => 'governorate', 'dir' => 'desc'])->vehicles()->pluck('name')->take(2)->all())
        ->toEqualCanonicalizing(['د', 'ه']);   // «أأ» order=2 أولاً تنازلياً

    expect(FridayReport::make(null, ['sort' => 'friday', 'dir' => 'desc'])->vehicles()->pluck('works_on_friday')->take(2)->all())
        ->toBe([true, true]);
});

it('falls back to the default for an unknown friday sort column', function () {
    expect(FridayReport::make(null, ['sort' => 'id desc, (select 1)', 'dir' => 'desc'])->vehicles()->pluck('name')->all())
        ->toBe(['أ', 'ب', 'ج', 'د', 'ه']);
});

it('applies the friday header click on screen and in the excel', function () {
    $test = Livewire::actingAs($this->admin)->test(VehicleFridayWork::class)
        ->call('search')->call('sort', 'name')->call('sort', 'name');

    expect($test->viewData('vehicles')->pluck('name')->all())->toBe(['ه', 'د', 'ج', 'ب', 'أ']);

    $list = (new VehicleFridayWorkExport(FridayReport::make(null, ['sort' => 'name', 'dir' => 'desc'])))->sheets()[1]->array();
    expect(array_column($list, 2))->toBe(['ه', 'د', 'ج', 'ب', 'أ']);
});
