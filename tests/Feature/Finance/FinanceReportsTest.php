<?php

use App\Exports\FinanceReportDataSheet;
use App\Exports\FinanceReportTableSheet;
use App\Livewire\Finance\Reports;
use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceMonthItem;
use App\Models\FinanceOpening;
use App\Models\FinanceUnit;
use App\Models\User;
use App\Support\Finance\FinanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * تقارير الشئون المالية (المرحلة ٥ · نموذج وافقت عليه المستخدمة 2026-11-17).
 * ⚠️ الشهور تُنشأ بالموديل لا بالفورم (لا رفع ملفات) فيصحّ تقديم الساعة إلى ديسمبر.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    // ٥ ديسمبر ٢٠٢٦: أكتوبر ونوفمبر منتهيان، وديسمبر الجاري
    $this->travelTo(CarbonImmutable::parse('2026-12-05 10:00:00', 'UTC'));
});

function frUnit(string $city): FinanceUnit
{
    return FinanceUnit::where('name', 'like', '%'.$city)->sole();
}

function frUser(array $abilities = ['finance.reports']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    return tap(User::factory()->create())->givePermissionTo($abilities);
}

function frMonth(string $account, string $city, string $month, int $amount, ?int $income = null, int $orders = 0): FinanceMonth
{
    $m = FinanceMonth::create([
        'finance_account_id' => FinanceAccount::byKey($account)->id,
        'finance_unit_id'    => frUnit($city)->id,
        'month'              => $month.'-01',
        'amount'             => $amount,
        'income'             => $income,
    ]);
    for ($i = 1; $i <= $orders; $i++) {
        $m->paymentOrders()->create(['number' => (string) $i, 'issued_on' => $month.'-02', 'position' => $i]);
    }

    return $m;
}

/** القاهرة: أبنية أكتوبر بعنصرين · ميكنة أكتوبر ونوفمبر · اتصالات أكتوبر — وطنطا رصيدها الافتتاحي وحده */
function frSeed(): void
{
    $court = frMonth('court_buildings', 'بالقاهرة', '2026-10', 1000, orders: 1);
    $items = CourtBuildingItem::ordered()->take(2)->get();
    FinanceMonthItem::create(['finance_month_id' => $court->id, 'court_building_item_id' => $items[0]->id, 'amount' => 700]);
    FinanceMonthItem::create(['finance_month_id' => $court->id, 'court_building_item_id' => $items[1]->id, 'amount' => 300]);

    frMonth('mechanization', 'بالقاهرة', '2026-10', 500, orders: 2);
    frMonth('mechanization', 'بالقاهرة', '2026-11', 600, orders: 1);

    $telecom = FinanceAccount::byKey('telecom')->id;
    FinanceOpening::create(['finance_account_id' => $telecom, 'finance_unit_id' => frUnit('بالقاهرة')->id, 'amount' => 5000]);
    FinanceOpening::create(['finance_account_id' => $telecom, 'finance_unit_id' => frUnit('بطنطا')->id, 'amount' => 100]);
    frMonth('telecom', 'بالقاهرة', '2026-10', 7000, income: 10000, orders: 1);
}

function frReport(array $in): FinanceReport
{
    return FinanceReport::fromInput($in);
}

// ── الحراسة ──────────────────────────────────────────────

it('لا يفتح التقارير ولا الـPDF ولا Excel إلا صاحب finance.reports', function () {
    $this->actingAs(frUser(['finance.total.view', 'finance.units.view']));

    $this->get(route('finance.reports'))->assertForbidden();
    $this->get(route('finance.reports.pdf', ['kind' => 'total']))->assertForbidden();

    $this->actingAs(frUser());
    $c = Livewire::test(Reports::class);

    $this->actingAs(frUser(['finance.total.view']));
    $c->call('exportExcel')->assertForbidden();
});

// ── تقرير الوحدة ─────────────────────────────────────────

it('تقرير الوحدة: الشهور صفوف × الحسابات، واتصالات بإيرادها ومنصرفها ورصيدها', function () {
    frSeed();
    $r = frReport(['kind' => 'unit', 'unit' => frUnit('بالقاهرة')->id, 'period' => 'range', 'from' => '2026-10', 'to' => '2026-11']);
    $d = $r->unitReport();

    expect($d['rows'])->toHaveCount(2)
        ->and($d['rows'][0]['cells'])->toMatchArray(['court_buildings' => 1000, 'mechanization' => 500, 'ministry_of_finance' => null])
        ->and($d['rows'][0]['telecom'])->toBe(['income' => 10000, 'expense' => 7000, 'balance' => 8000])
        ->and($d['rows'][0]['orders'])->toBe(4)
        // نوفمبر: اتصالات لم تُدخَل
        ->and($d['rows'][1]['telecom'])->toBeNull()
        ->and($d['rows'][1]['cells']['mechanization'])->toBe(600)
        // الإجمالي: المبالغ تُجمع، ورصيد اتصالات آخر شهرٍ مُدخَل، و«—» لما لم يُسجَّل
        ->and($d['totals']['cells'])->toMatchArray(['court_buildings' => 1000, 'mechanization' => 1100, 'ministry_of_finance' => null])
        ->and($d['totals']['telecom'])->toBe(['income' => 10000, 'expense' => 7000, 'balance' => 8000])
        ->and($d['totals']['orders'])->toBe(5);
});

it('تقرير الوحدة لوحدةٍ أخرى لا يرى أرقام القاهرة', function () {
    frSeed();
    $d = frReport(['kind' => 'unit', 'unit' => frUnit('بطنطا')->id, 'period' => 'month', 'month' => '2026-10'])->unitReport();

    expect($d['rows'][0]['cells'])->toMatchArray(['court_buildings' => null, 'mechanization' => null])
        ->and($d['totals']['telecom']['balance'])->toBe(100);
});

it('يعرض تقرير الوحدة على الشاشة بشهوره و«لم يُدخَل» للشهر المنتهي', function () {
    frSeed();
    $this->actingAs(frUser());

    $html = Livewire::withQueryParams(['kind' => 'unit', 'unit' => frUnit('بالقاهرة')->id, 'period' => 'range', 'from' => '2026-10', 'to' => '2026-11'])
        ->test(Reports::class)
        ->assertSee(__('home.fin_rep_unit_title', ['unit' => frUnit('بالقاهرة')->name]))
        ->assertSeeInOrder(['أكتوبر 2026', '1,000', '500', '10,000', '7,000', '8,000'])
        ->assertSee(__('home.fin_rep_items_title'))
        ->html();

    // «لم يُدخَل» أربع مرات: مالية أكتوبر · أبنية نوفمبر · اتصالات نوفمبر · مالية نوفمبر
    expect(substr_count($html, __('home.fin_not_entered')))->toBe(4);
});

// ── التقرير الإجمالي ─────────────────────────────────────

it('التقرير الإجمالي: الوحدات صفوف بمجموع الفترة، ورصيد آخر الفترة، والمُدخَل من المطلوب', function () {
    frSeed();
    $d = frReport(['kind' => 'total', 'period' => 'range', 'from' => '2026-10', 'to' => '2026-11'])->totalReport();

    $rows  = collect($d['rows'])->keyBy(fn ($r) => $r['unit']->id);
    $cairo = $rows[frUnit('بالقاهرة')->id];
    $tanta = $rows[frUnit('بطنطا')->id];

    expect($rows)->toHaveCount(6)
        ->and($cairo['cells'])->toMatchArray(['court_buildings' => 1000, 'mechanization' => 1100, 'ministry_of_finance' => null])
        ->and($cairo['telecom']['balance'])->toBe(8000)
        // ٢ شهر × ٤ حسابات = ٨، والمسجَّل ٤
        ->and($cairo['entered'])->toBe(4)->and($cairo['required'])->toBe(8)
        ->and($tanta['cells']['mechanization'])->toBeNull()
        ->and($tanta['telecom']['balance'])->toBe(100)
        // رصيد الوحدات بلا افتتاحي «—» فلا يدخل المجموع
        ->and($rows[frUnit('بأسيوط')->id]['telecom']['balance'])->toBeNull()
        ->and($d['totals']['telecom']['balance'])->toBe(8100)
        ->and($d['totals']['cells']['mechanization'])->toBe(1100);
});

it('العناصر الثمانية مجموعةً في الفترة — للوحدة وللإجمالي', function () {
    frSeed();
    [$a, $b] = CourtBuildingItem::ordered()->take(2)->pluck('name')->all();

    expect(frReport(['kind' => 'total', 'period' => 'year', 'year' => 2026])->items())->toBe([$a => 700, $b => 300])
        ->and(frReport(['kind' => 'unit', 'unit' => frUnit('بطنطا')->id, 'period' => 'year', 'year' => 2026])->items())->toBe([]);
});

// ── الفترة من الرابط ─────────────────────────────────────

it('يفحص مدخلات الرابط: النوع والفترة بقائمة بيضاء، والشهر داخل المتاح، والمقلوب يُعدَل', function () {
    $cairo = frUnit('بالقاهرة')->id;

    $bad = frReport(['kind' => 'evil', 'unit' => $cairo, 'period' => 'x', 'month' => '2030-01']);
    expect($bad->kind)->toBe('unit')
        ->and($bad->period)->toBe('month')
        // الافتراضي آخر شهرٍ منتهٍ
        ->and($bad->from->format('Y-m'))->toBe('2026-11');

    $swapped = frReport(['kind' => 'total', 'period' => 'range', 'from' => '2026-11', 'to' => '2026-10']);
    expect([$swapped->from->format('Y-m'), $swapped->to->format('Y-m')])->toBe(['2026-10', '2026-11']);

    // السنة المالية حتى الشهر الجاري
    $year = frReport(['kind' => 'total', 'period' => 'year', 'year' => '2026']);
    expect([$year->from->format('Y-m'), $year->to->format('Y-m')])->toBe(['2026-10', '2026-12']);

    // تقرير وحدةٍ بلا وحدةٍ صالحة
    expect(FinanceReport::fromInput(['kind' => 'unit', 'unit' => '12x']))->toBeNull()
        ->and(FinanceReport::fromInput(['kind' => 'unit', 'unit' => '99999']))->toBeNull();
});

// ── الملفّان ──────────────────────────────────────────────

it('يولّد PDF التقريرين', function () {
    frSeed();
    $this->actingAs(frUser());

    foreach ([['kind' => 'total', 'period' => 'year', 'year' => 2026], ['kind' => 'unit', 'unit' => frUnit('بالقاهرة')->id, 'period' => 'range', 'from' => '2026-10', 'to' => '2026-12']] as $q) {
        $res = $this->get(route('finance.reports.pdf', $q))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        expect(substr($res->getContent(), 0, 4))->toBe('%PDF');
    }
});

it('Excel: ورقة الجدول كالشاشة وورقة بيانات صفٌّ لكل (وحدة × حساب × شهر)', function () {
    frSeed();
    $r = frReport(['kind' => 'total', 'period' => 'range', 'from' => '2026-10', 'to' => '2026-11']);

    $table = (new FinanceReportTableSheet($r))->array();
    $total = collect($table)->first(fn ($row) => ($row[0] ?? null) === __('home.fin_total_label'));
    // الوحدة · أبنية · ميكنة · إيراد · منصرف · رصيد · مالية · المُدخَل
    expect($total)->toBe([__('home.fin_total_label'), 1000, 1100, 10000, 7000, 8100, null, null]);

    $data = (new FinanceReportDataSheet($r))->array();
    expect($data)->toHaveCount(4)
        ->and(collect($data)->first(fn ($row) => $row[1] === __('home.fin_tab_telecom')))
        ->toBe([frUnit('بالقاهرة')->name, __('home.fin_tab_telecom'), 'أكتوبر 2026', 7000, 10000, 8000, 1]);

    $this->actingAs(frUser());
    Livewire::withQueryParams(['kind' => 'total'])->test(Reports::class)->call('exportExcel')->assertFileDownloaded();
});

it('القائمة: بند التقارير لصاحبها وحده', function () {
    $this->actingAs(frUser(['finance.total.view', 'finance.reports']));
    $this->get(route('finance.total'))->assertSee('href="'.route('finance.reports').'"', false);

    $this->actingAs(frUser(['finance.total.view']));
    $this->get(route('finance.total'))->assertDontSee('href="'.route('finance.reports').'"', false);
});
