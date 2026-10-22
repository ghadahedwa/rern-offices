<?php

use App\Exports\VehicleStatisticsBreakdownDataSheet;
use App\Exports\VehicleStatisticsBreakdownTableSheet;
use App\Livewire\Reports\VehicleStatisticsBreakdown;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleColumns;
use App\Reports\VehicleStatisticsBreakdown as Report;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function vbAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

function vbScopedUser(array $govIds): User
{
    Permission::findOrCreate('vehicles.export', 'web');
    $role = Role::findOrCreate('vb-export', 'web');
    $role->givePermissionTo('vehicles.export');
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

function vbStat(Vehicle $v, int $type, int $year, ?int $month, float $value): void
{
    VehicleStat::create(['vehicle_id' => $v->id, 'stat_type_id' => $type, 'year' => $year, 'month' => $month, 'value' => $value]);
}

/** صفوف السيارات: اسم => [cells, total] */
function vbVehicleRows(Report $r): array
{
    return $r->groups()->flatMap(fn ($g) => $g['vehicleRows'])
        ->mapWithKeys(fn ($row) => [$row['vehicle']->name => [$row['cells'], $row['total']]])
        ->all();
}

beforeEach(function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 12:00', 'Africa/Cairo'));
    $this->trips   = VehicleColumns::tripsTypeId();
    $this->instant = VehicleColumns::instantTripsTypeId();

    $this->cairo = Governorate::factory()->create(['name' => 'القاهرة', 'order' => 1]);
    $this->giza  = Governorate::factory()->create(['name' => 'الجيزة', 'order' => 2]);

    $this->a = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'أ']);
    $this->b = Vehicle::create(['governorate_id' => $this->cairo->id, 'name' => 'ب']);
    $this->c = Vehicle::create(['governorate_id' => $this->giza->id, 'name' => 'ج']);

    vbStat($this->a, 1, 2026, null, 900);   // يناير–سبتمبر
    vbStat($this->a, 1, 2026, 10, 100);
    vbStat($this->a, 1, 2025, null, 4000);
    vbStat($this->c, 1, 2025, null, 500);
    vbStat($this->c, 1, 2024, null, 300);

    vbStat($this->a, $this->trips, 2026, 10, 20);
    vbStat($this->b, $this->trips, 2026, 10, 0);   // صفر مسجَّل ≠ لم يُسجَّل
    vbStat($this->a, $this->instant, 2026, 9, 2);
    vbStat($this->a, $this->instant, 2026, 10, 5);
    vbStat($this->c, $this->instant, 2026, 10, 4);
});

it('compares years per governorate, starting from the first year with a number', function () {
    $r = Report::make(null, ['metric' => 'tx', 'level' => 'governorate', 'period' => 'yearly']);

    expect($r->columns())->toBe([2024 => '2024', 2025 => '2025', 2026 => '2026 (حتى الآن)']);

    [$cairo, $giza] = $r->groups()->map(fn ($g) => $g['total'])->all();

    expect($cairo)->toBe(['cells' => [2024 => null, 2025 => 4000.0, 2026 => 1000.0], 'total' => 5000.0, 'vehicles' => 2, 'reporting' => 1])
        ->and($giza)->toBe(['cells' => [2024 => 300.0, 2025 => 500.0, 2026 => null], 'total' => 800.0, 'vehicles' => 1, 'reporting' => 1])
        ->and($r->grandTotal())->toBe(['cells' => [2024 => 300.0, 2025 => 4500.0, 2026 => 1000.0], 'total' => 5800.0, 'vehicles' => 3, 'reporting' => 2]);
});

it('shows 2026 transactions as January–September and then each month', function () {
    $r = Report::make(null, ['metric' => 'tx', 'level' => 'vehicle', 'period' => 'monthly', 'year' => '2026']);

    expect($r->columns())->toBe([0 => 'يناير – سبتمبر', 10 => 'أكتوبر'])
        ->and(vbVehicleRows($r)['أ'])->toBe([[0 => 900.0, 10 => 100.0], 1000.0])
        ->and(vbVehicleRows($r)['ب'])->toBe([[0 => null, 10 => null], null]);
});

it('refuses monthly transactions for a year recorded as one number', function () {
    $r = Report::make(null, ['metric' => 'tx', 'period' => 'monthly', 'year' => '2025']);

    expect($r->isUnavailable())->toBeTrue()
        ->and($r->columns())->toBe([])
        ->and($r->dataRows())->toBe([]);

    Livewire::actingAs(vbAdmin())->test(VehicleStatisticsBreakdown::class)
        ->set('metric', 'tx')->set('period', 'monthly')->set('year', '2025')->call('search')
        ->assertSee(__('home.vbd_tx_monthly_unavailable'));

    // باقي المؤشرات شهرية من الأصل فلا تُرفض
    expect(Report::make(null, ['metric' => 'trips', 'period' => 'monthly', 'year' => '2025'])->isUnavailable())->toBeFalse();
});

it('adds regular and instant trips month by month, up to the current month', function () {
    vbStat($this->a, $this->trips, 2026, 11, 99);   // شهرٌ لم يأتِ بعد — لا عمود له

    $r = Report::make(null, ['metric' => 'trips_all', 'level' => 'vehicle', 'period' => 'monthly', 'year' => '2026']);
    $rows = vbVehicleRows($r);

    expect(array_keys($r->columns()))->toBe(range(1, 10))
        ->and($rows['أ'][0][9])->toBe(2.0)
        ->and($rows['أ'][0][10])->toBe(25.0)
        ->and($rows['أ'][1])->toBe(27.0)
        ->and($rows['ب'][1])->toBe(0.0)
        ->and($rows['ج'][1])->toBe(4.0)
        ->and($r->grandTotal()['total'])->toBe(31.0)
        ->and($r->grandTotal()['reporting'])->toBe(3);
});

it('keeps each trips type alone when asked', function () {
    $regular = Report::make(null, ['metric' => 'trips', 'level' => 'vehicle', 'period' => 'yearly']);
    $instant = Report::make(null, ['metric' => 'instant', 'level' => 'vehicle', 'period' => 'yearly']);

    expect($regular->columns())->toBe([2026 => '2026 (حتى الآن)'])
        ->and($regular->grandTotal()['total'])->toBe(20.0)
        ->and($instant->grandTotal()['total'])->toBe(11.0);
});

it('falls back to safe defaults for forged filter values', function () {
    $r = Report::make(null, ['metric' => 'x; drop', 'level' => ['vehicle'], 'period' => 'weekly', 'year' => '2050', 'governorateIds' => 'abc']);

    expect([$r->metric(), $r->level(), $r->period(), $r->year()])->toBe(['tx', 'governorate', 'yearly', 2026])
        ->and($r->groups())->toHaveCount(2);
});

it('shows only the current year when nothing is recorded for the metric', function () {
    expect(Report::make(null, ['metric' => 'forms', 'period' => 'yearly'])->columns())->toBe([2026 => '2026 (حتى الآن)']);
});

it('keeps a scoped user inside their governorates even with a forged filter', function () {
    $user = vbScopedUser([$this->giza->id]);

    $own = Livewire::actingAs($user)->test(VehicleStatisticsBreakdown::class)->call('search')->viewData('report');
    expect($own->groups()->map(fn ($g) => $g['governorate']->name)->all())->toBe(['الجيزة'])
        ->and($own->dataRows())->each(fn ($row) => $row->{0}->toBe('الجيزة'));

    $forged = Livewire::actingAs($user)->test(VehicleStatisticsBreakdown::class)
        ->set('governorateIds', [(string) $this->cairo->id])->call('search')->viewData('report');
    expect($forged->groups())->toHaveCount(0);
});

it('shows nothing to a user with the permission but no governorates', function () {
    $r = Livewire::actingAs(vbScopedUser([]))->test(VehicleStatisticsBreakdown::class)->call('search')->viewData('report');

    expect($r->groups())->toHaveCount(0)
        ->and($r->dataRows())->toBe([])
        ->and($r->grandTotal()['total'])->toBeNull();
});

it('forbids the screen and the pdf without vehicles.export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reports.vehicle-statistics-breakdown'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.vehicle-statistics-breakdown.pdf'))->assertForbidden();
});

it('exports the table as on the screen and the data long, one row per recorded number', function () {
    $r = Report::make(null, ['metric' => 'trips_all', 'level' => 'governorate', 'period' => 'monthly', 'year' => '2026']);

    $table = new VehicleStatisticsBreakdownTableSheet($r);
    $rows  = $table->array();

    expect($table->headings())->toBe(['المحافظة', 'عدد السيارات', 'سُجِّل لها', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'الإجمالي'])
        ->and($rows[0])->toBe(['القاهرة', 2, 2, null, null, null, null, null, null, null, null, 2.0, 25.0, 27.0])
        ->and(end($rows))->toBe(['الإجمالي العام', 3, 3, null, null, null, null, null, null, null, null, 2.0, 29.0, 31.0]);

    // إجمالي الانتقالات يخرج نوعين منفصلين، فيجمعهما الجدول المحوري
    expect((new VehicleStatisticsBreakdownDataSheet($r))->array())->toBe([
        ['القاهرة', 'أ', 'الانتقالات الفورية', 2026, 'سبتمبر', 2.0],
        ['القاهرة', 'أ', 'الانتقالات العادية', 2026, 'أكتوبر', 20.0],
        ['القاهرة', 'أ', 'الانتقالات الفورية', 2026, 'أكتوبر', 5.0],
        ['القاهرة', 'ب', 'الانتقالات العادية', 2026, 'أكتوبر', 0.0],
        ['الجيزة', 'ج', 'الانتقالات الفورية', 2026, 'أكتوبر', 4.0],
    ]);
});

it('writes the vehicle level with subtotal rows, a recorded zero as 0 and nothing as an empty cell', function () {
    $file = tempnam(sys_get_temp_dir(), 'vb') . '.xlsx';
    file_put_contents($file, \Maatwebsite\Excel\Facades\Excel::raw(
        new \App\Exports\VehicleStatisticsBreakdownExport(Report::make(null, ['metric' => 'trips', 'level' => 'vehicle', 'period' => 'monthly', 'year' => '2026'])),
        \Maatwebsite\Excel\Excel::XLSX
    ));
    $book  = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $sheet = $book->getSheet(0);

    // الأعمدة: المحافظة · السيارة · يناير(C) … أكتوبر(L) · الإجمالي(M)
    expect($book->getSheetCount())->toBe(2)
        ->and($sheet->getCell('B3')->getValue())->toBe('ب')
        ->and($sheet->getCell('L3')->getValue())->not->toBeNull()->toEqual(0)
        ->and($sheet->getCell('K3')->getValue())->toBeNull()
        ->and($sheet->getCell('A4')->getValue())->toBe('إجمالي القاهرة')
        ->and($sheet->getCell('B4')->getValue())->toBe('سُجِّل لـ 2 من 2')
        ->and($sheet->getCell('M4')->getValue())->toEqual(20);
    @unlink($file);
});

it('builds the pdf from the same filters, also when monthly transactions are unavailable', function (array $filters) {
    $response = $this->actingAs(vbAdmin())
        ->withSession(['vehicle_statistics_breakdown_filters' => $filters])
        ->get(route('reports.vehicle-statistics-breakdown.pdf'))
        ->assertOk();

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
})->with([
    'yearly by governorate' => [['metric' => 'tx', 'level' => 'governorate', 'period' => 'yearly']],
    'monthly by vehicle'    => [['metric' => 'trips_all', 'level' => 'vehicle', 'period' => 'monthly', 'year' => '2026']],
    'unavailable'           => [['metric' => 'tx', 'period' => 'monthly', 'year' => '2024']],
]);

it('renders the table on the screen and links it in the sidebar', function () {
    $this->actingAs(vbAdmin())->get(route('reports.vehicle-statistics-breakdown'))
        ->assertOk()
        ->assertSee(route('reports.vehicle-statistics-breakdown'), false);

    Livewire::actingAs(vbAdmin())->test(VehicleStatisticsBreakdown::class)
        ->set('metric', 'tx')->set('level', 'vehicle')->set('period', 'monthly')->set('year', '2026')->call('search')
        ->assertSee('يناير – سبتمبر')
        ->assertSee(__('home.vbd_reporting_of', ['n' => 1, 'm' => 2]))
        ->assertSeeHtml('>1,000</td>');
});

// ── الشكل الرأسي: سيارةٌ واحدة أو محافظةٌ واحدة ──

it('turns vertical for one vehicle, with the three trips columns side by side', function () {
    $r = Report::make(null, ['metric' => 'trips', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id]);
    $vt = $r->vertical();

    expect($r->isVertical())->toBeTrue()
        ->and($r->series())->toBe(['trips', 'instant', 'trips_all'])
        ->and($r->subjectLabel())->toBe('أ')
        ->and(array_keys($vt['rows']))->toBe(range(1, 10))
        ->and($vt['rows'][9]['values'])->toBe(['trips' => null, 'instant' => 2.0, 'trips_all' => 2.0])
        ->and($vt['rows'][10]['values'])->toBe(['trips' => 20.0, 'instant' => 5.0, 'trips_all' => 25.0])
        ->and($vt['rows'][1]['values'])->toBe(['trips' => null, 'instant' => null, 'trips_all' => null])
        ->and($vt['total'])->toBe(['trips' => 20.0, 'instant' => 7.0, 'trips_all' => 27.0])
        ->and([$vt['vehicles'], $vt['reporting']])->toBe([1, 1]);
});

it('turns vertical for one governorate, summing its vehicles year by year', function () {
    $r = Report::make(null, ['metric' => 'tx', 'level' => 'governorate', 'period' => 'yearly', 'governorateIds' => [(string) $this->cairo->id]]);
    $vt = $r->vertical();

    expect($r->isVertical())->toBeTrue()
        ->and($r->series())->toBe(['tx'])
        ->and($r->subjectLabel())->toBe('القاهرة')
        ->and(array_map(fn ($row) => $row['values']['tx'], $vt['rows']))->toBe([2025 => 4000.0, 2026 => 1000.0])
        ->and([$vt['vehicles'], $vt['reporting']])->toBe([2, 1]);
});

it('keeps January–September as its own row for one vehicle in 2026', function () {
    $vt = Report::make(null, ['metric' => 'tx', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id])->vertical();

    expect(array_map(fn ($row) => [$row['label'], $row['values']['tx']], $vt['rows']))
        ->toBe([0 => ['يناير – سبتمبر', 900.0], 10 => ['أكتوبر', 100.0]]);
});

it('puts forms and folders side by side', function () {
    vbStat($this->a, 2, 2026, 10, 30);
    vbStat($this->a, 3, 2026, 10, 12);

    $r = Report::make(null, ['metric' => 'folders', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id]);

    expect($r->series())->toBe(['forms', 'folders'])
        ->and($r->vertical()['rows'][10]['values'])->toBe(['forms' => 30.0, 'folders' => 12.0]);
});

it('stays wide for several governorates, or for the vehicles of one governorate', function () {
    expect(Report::make(null, ['level' => 'governorate'])->isVertical())->toBeFalse()
        ->and(Report::make(null, ['level' => 'vehicle', 'governorateIds' => [(string) $this->cairo->id]])->isVertical())->toBeFalse()
        ->and(Report::make(null, ['level' => 'vehicle'])->series())->toBe(['tx']);
});

it('ignores a forged vehicle outside the user scope', function () {
    $user = vbScopedUser([$this->giza->id]);

    $r = Livewire::actingAs($user)->test(VehicleStatisticsBreakdown::class)
        ->set('vehicleId', (string) $this->a->id)->call('search')->viewData('report');

    expect($r->groups())->toHaveCount(0)
        ->and($r->vertical()['total'])->toBe(['tx' => null])
        ->and($r->dataRows())->toBe([]);
});

it('drops the chosen vehicle when its governorate is unselected, and lists only scoped vehicles', function () {
    $test = Livewire::actingAs(vbAdmin())->test(VehicleStatisticsBreakdown::class)
        ->set('vehicleId', (string) $this->a->id)
        ->set('governorateIds', [(string) $this->giza->id])
        ->assertSet('vehicleId', '');

    expect($test->viewData('vehicles')->flatten()->pluck('name')->all())->toBe(['ج']);

    $own = Livewire::actingAs(vbScopedUser([$this->giza->id]))->test(VehicleStatisticsBreakdown::class);
    expect($own->viewData('vehicles')->flatten()->pluck('name')->all())->toBe(['ج']);
});

it('shows the vertical table on the screen, in the excel sheet and in the pdf', function () {
    Livewire::actingAs(vbAdmin())->test(VehicleStatisticsBreakdown::class)
        ->set('metric', 'trips')->set('period', 'monthly')->set('year', '2026')
        ->set('vehicleId', (string) $this->a->id)->call('search')
        ->assertSee(__('home.vbd_series_trips_all'))
        ->assertSeeHtml('>25</td>')
        ->assertSeeHtml('>27</td>');

    $r     = Report::make(null, ['metric' => 'trips', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id]);
    $sheet = new VehicleStatisticsBreakdownTableSheet($r);
    $rows  = $sheet->array();

    expect($sheet->headings())->toBe(['الشهر', 'الانتقالات العادية', 'الانتقالات الفورية', 'إجمالي الانتقالات'])
        ->and($sheet->title())->toBe('أ')
        ->and($rows[9])->toBe(['أكتوبر', 20.0, 5.0, 25.0])
        ->and(end($rows))->toBe(['الإجمالي', 20.0, 7.0, 27.0]);

    $html = preg_replace('/\s+/', '', view('print.vehicle-statistics-breakdown-pdf', ['report' => $r, 'logoBase64' => null])->render());
    expect($html)->toContain('<tdclass="name">أكتوبر</td><td>20</td><td>5</td><tdclass="tot">25</td>');

    $this->actingAs(vbAdmin())
        ->withSession(['vehicle_statistics_breakdown_filters' => ['metric' => 'trips', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id]])
        ->get(route('reports.vehicle-statistics-breakdown.pdf'))
        ->assertOk();
});

it('turns vertical for a chosen vehicle whatever the grouping left in the form', function () {
    $r = Report::make(null, ['metric' => 'trips', 'level' => 'vehicle', 'period' => 'monthly', 'year' => '2026', 'vehicleId' => (string) $this->a->id]);

    expect($r->isVertical())->toBeTrue()
        ->and($r->vertical()['total']['trips_all'])->toBe(27.0);
});

it('uses the contractors governorate picker with search and select all', function () {
    $test = Livewire::actingAs(vbAdmin())->test(VehicleStatisticsBreakdown::class)
        ->assertSee(__('home.ct_rep_select_all'))
        ->assertSee(__('home.ct_rep_all_governorates'));

    // البحث مطبَّع (الجيزه = الجيزة)، والمختارة تبقى ظاهرةً ولو لم تطابق
    $test->set('governorateIds', [(string) $this->cairo->id])->set('governorateSearch', 'الجيزه');
    expect(array_column($test->viewData('governorateChoices'), 'label'))->toBe(['القاهرة', 'الجيزة']);

    $test->set('governorateSearch', 'جيز')->set('governorateIds', []);
    expect(array_column($test->viewData('governorateChoices'), 'label'))->toBe(['الجيزة']);

    // «تحديد الكل» يحدّد الظاهر بعد البحث
    $test->assertSeeHtml("\$set('governorateIds', " . \Illuminate\Support\Js::from([(string) $this->giza->id]) . ')');
});

it('lists only the scoped governorates in the picker', function () {
    $test = Livewire::actingAs(vbScopedUser([$this->giza->id]))->test(VehicleStatisticsBreakdown::class);

    expect(array_column($test->viewData('governorateChoices'), 'label'))->toBe(['الجيزة']);
});
