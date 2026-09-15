<?php

use App\Exports\FeedbackDigitalRatingsExport;
use App\Exports\FeedbackDigitalSummaryExport;
use App\Livewire\FeedbackResults\Dashboard;
use App\Livewire\FeedbackResults\DigitalRatings;
use App\Livewire\FeedbackResults\DigitalSummary;
use App\Models\FeedbackDigitalChoice;
use App\Models\FeedbackDigitalRating;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Support\FeedbackResults\DashboardReport;
use App\Support\FeedbackResults\DigitalRatingsQuery;
use App\Support\FeedbackResults\DigitalReport;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\FeedbackFilterSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * نتائج «تقييم المنصات الرقمية» — القائمة والملخص والتصدير.
 * القاعدة الحاكمة: مقام كل نسبة = مَن سُئل السؤال، لا كل الآراء.
 */

uses(RefreshDatabase::class);

function dgAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function dgUser(array $abilities, array $governorates = []): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($abilities);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

/** رأي بمسار «حجز» — الافتراضي: مصر الرقمية · بلا مشاكل · درجة ٨ · في الميعاد · لم يشاهد الإعلان. */
function dgBooked(Office $office, array $attrs = []): FeedbackDigitalRating
{
    return FeedbackDigitalRating::factory()->create(array_merge([
        'office_id' => $office->id, 'q201' => 'booked', 'q202' => 'misr_digital', 'q203' => 'no',
        'q205' => 8, 'q206' => 'on_time', 'q208' => null, 'q211' => 'no',
    ], $attrs));
}

/** رأي بمسار «بدون حجز» مع اختيارات q209 إن وُجدت. */
function dgWalkIn(Office $office, array $attrs = [], array $knows = []): FeedbackDigitalRating
{
    $row = FeedbackDigitalRating::factory()->create(array_merge([
        'office_id' => $office->id, 'q201' => 'walk_in', 'q208' => $knows ? 'yes' : 'no', 'q211' => 'no',
    ], $attrs));

    foreach ($knows as $option) {
        $row->choices()->create(['question' => 'q209', 'option' => $option]);
    }

    return $row;
}

/** صفوف ورقة Excel بعينها (سطر الرؤوس أولها). */
function dgSheet(object $export, int $sheet = 0): array
{
    $file = tempnam(sys_get_temp_dir(), 'dg').'.xlsx';
    file_put_contents($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
    $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $rows = $book->getSheet($sheet)->toArray();
    $book->disconnectWorksheets();
    @unlink($file);

    return $rows;
}

/* ===================== الدخول والنطاق ===================== */

it('تُفتح الشاشتان لصاحب feedback.view وتُحجبان عمّن لا يملكها', function (string $route) {
    $gov = Governorate::factory()->create();

    $this->actingAs(dgUser([FeedbackAccess::VIEW], [$gov]))->get(route($route))->assertOk();
    $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
})->with(['feedback-results.digital', 'feedback-results.digital-summary']);

it('يرى صاحب المحافظة آراءها وحدها في القائمة والملخص', function () {
    $mine  = Office::factory()->public()->create(['name' => 'مقر في محافظتي']);
    $other = Office::factory()->public()->create(['name' => 'مقر في محافظة أخرى']);
    dgBooked($mine);
    dgBooked($other);
    dgWalkIn($other);

    $user = dgUser([FeedbackAccess::VIEW], [$mine->governorate]);

    Livewire::actingAs($user)->test(DigitalRatings::class)
        ->assertSee('مقر في محافظتي')
        ->assertDontSee('مقر في محافظة أخرى');

    expect((new DigitalReport(new FeedbackFilterSet, $user))->headline()['total'])->toBe(1);
});

/* ===================== القائمة ===================== */

it('يفلتر بالمسار ويتجاهل قيمة مسار مجهولة', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office);
    dgWalkIn($office);
    $admin = dgAdmin();

    expect(DigitalRatingsQuery::build(new FeedbackFilterSet, $admin, path: 'booked')->count())->toBe(1)
        ->and(DigitalRatingsQuery::build(new FeedbackFilterSet, $admin, path: 'walk_in')->count())->toBe(1)
        ->and(DigitalRatingsQuery::build(new FeedbackFilterSet, $admin, path: 'hacked')->count())->toBe(2);
});

it('يبحث في النصين الحرّين بالتطبيع العربي', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q203' => 'yes', 'q204' => 'مشكلة في الموقع']);
    dgWalkIn($office, ['q210' => 'مكنتش أعرف أستخدم التطبيق'], ['misr_digital']);
    dgBooked($office);
    $admin = dgAdmin();

    expect(DigitalRatingsQuery::build(new FeedbackFilterSet, $admin, 'مشكله')->count())->toBe(1)
        ->and(DigitalRatingsQuery::build(new FeedbackFilterSet, $admin, 'اعرف')->count())->toBe(1);
});

it('يعرض صف التفاصيل الأسئلة التي سُئلها المواطن وحدها', function () {
    $office = Office::factory()->public()->create();
    $row = dgBooked($office, ['q203' => 'yes', 'q204' => 'الموقع وقع']);

    Livewire::actingAs(dgAdmin())->test(DigitalRatings::class)
        ->call('toggle', $row->id)
        ->assertSee(FeedbackDigitalRating::QUESTIONS['q202'][1])
        ->assertSee('الموقع وقع')
        ->assertDontSee(FeedbackDigitalRating::QUESTIONS['q208'][1]);
});

it('الحذف للسلة يُبقي الاختيارات، والحذف النهائي يحذفها معه', function () {
    $office = Office::factory()->public()->create();
    $row = dgWalkIn($office, [], ['misr_digital', 'tawkeel_app']);

    Livewire::actingAs(dgAdmin())->test(DigitalRatings::class)
        ->set('selected', [(string) $row->id])->call('deleteSelected');

    expect(FeedbackDigitalRating::withTrashed()->find($row->id)->trashed())->toBeTrue()
        ->and(FeedbackDigitalChoice::count())->toBe(2);

    Livewire::actingAs(dgAdmin())->test(DigitalRatings::class)
        ->set('showTrashed', true)->set('selected', [(string) $row->id])->call('forceDeleteSelected');

    expect(FeedbackDigitalRating::withTrashed()->count())->toBe(0)
        ->and(FeedbackDigitalChoice::count())->toBe(0);
});

/* ===================== الملخص: المقامات ===================== */

it('نسبة مشاكل الحجز مقامها الحاجزون لا كل الآراء', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q203' => 'yes', 'q204' => 'مشكلة']);
    dgBooked($office);
    dgBooked($office);
    dgBooked($office);
    foreach (range(1, 6) as $_) {
        dgWalkIn($office);
    }

    $report = new DigitalReport(new FeedbackFilterSet, dgAdmin());
    $q203   = $report->distribution('q203');

    expect($q203['base'])->toBe(4)
        ->and($q203['rows'][0])->toMatchArray(['label' => 'نعم', 'count' => 1, 'percent' => 25.0])
        // النص الحر مقامه مَن قال «نعم» وحده
        ->and($report->texts('q204'))->toMatchArray(['base' => 1, 'written' => 1])
        ->and($report->headline())->toMatchArray(['total' => 10, 'booked' => 4, 'booked_percent' => 40.0]);
});

it('مقام السؤال مشتقّ بسلسلة شروطه كاملة', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q206' => 'waited', 'q207' => '15_30']);
    dgBooked($office);   // دخل في ميعاده — لم يُسأل q207
    dgWalkIn($office);

    $admin = dgAdmin();

    expect(FeedbackDigitalRating::query()->asked('q207')->count())->toBe(1)
        ->and(FeedbackDigitalRating::query()->asked('q212')->count())->toBe(0)
        ->and((new DigitalReport(new FeedbackFilterSet, $admin))->distribution('q207')['base'])->toBe(1);
});

it('الاختيار المتعدد نسبته من المسؤولين ومجموعها قد يتجاوز ١٠٠٪', function () {
    $office = Office::factory()->public()->create();
    dgWalkIn($office, [], ['misr_digital', 'tawkeel_app']);
    dgWalkIn($office, [], ['misr_digital']);
    dgWalkIn($office);                 // لا يعرف المنصة — لم يُسأل q209
    dgBooked($office);

    $q209 = (new DigitalReport(new FeedbackFilterSet, dgAdmin()))->distribution('q209');

    expect($q209['base'])->toBe(2)
        ->and($q209['multi'])->toBeTrue()
        ->and(collect($q209['rows'])->pluck('percent')->all())->toBe([100.0, 50.0]);
});

it('الصفر درجة داخل المتوسط، ومَن لم يُسأل خارجه، والمقارنة بين المنصتين', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q205' => 0, 'q202' => 'misr_digital']);
    dgBooked($office, ['q205' => 10, 'q202' => 'tawkeel_app']);
    dgWalkIn($office);

    $score = (new DigitalReport(new FeedbackFilterSet, dgAdmin()))->scale('q205');

    expect($score['base'])->toBe(2)
        ->and($score['avg'])->toBe(5.0)
        ->and($score['distribution'][0])->toMatchArray(['score' => 0, 'count' => 1, 'percent' => 50.0])
        ->and(collect($score['platforms'])->pluck('avg')->all())->toBe([0.0, 10.0]);
});

it('يرتّب المقرات بنسبة الحجز والعينة الناقصة بعدها في الاتجاهين', function () {
    config(['feedback.min_ratings_for_ranking' => 5]);
    $high  = Office::factory()->public()->create(['name' => 'مقر الأعلى']);
    $low   = Office::factory()->public()->create(['name' => 'مقر الأقل']);
    $small = Office::factory()->public()->create(['name' => 'مقر العينة الصغيرة']);

    foreach (range(1, 4) as $_) { dgBooked($high); }
    dgWalkIn($high);
    dgBooked($low);
    foreach (range(1, 4) as $_) { dgWalkIn($low); }
    dgBooked($small);
    dgBooked($small);

    $report = new DigitalReport(new FeedbackFilterSet, dgAdmin());

    expect($report->officesTable('desc')->pluck('office')->all())->toBe(['مقر الأعلى', 'مقر الأقل', 'مقر العينة الصغيرة'])
        ->and($report->officesTable('asc')->pluck('office')->all())->toBe(['مقر الأقل', 'مقر الأعلى', 'مقر العينة الصغيرة'])
        ->and($report->officesTable()->first())->toMatchArray(['count' => 5, 'booked' => 4, 'booked_percent' => 80.0, 'enough' => true]);
});

it('تُعرض صفحة الملخص ببياناتها كاملة: المحاور والدرجة والنصوص والاتجاه والمقرات', function () {
    $office = Office::factory()->public()->create(['name' => 'مقر الملخص']);
    dgBooked($office, ['q203' => 'yes', 'q204' => 'نص مشكلة للعرض', 'q206' => 'waited', 'q207' => '30_60', 'q211' => 'yes', 'q213' => 'good'])
        ->choices()->create(['question' => 'q212', 'option' => 'register_no_delay']);
    dgWalkIn($office, ['q210' => 'نص سبب للعرض'], ['tawkeel_app']);

    Livewire::actingAs(dgAdmin())->test(DigitalSummary::class)
        ->assertOk()
        ->assertSee(__('home.fr_dg_q203'))
        ->assertSee(__('home.fr_dg_q212'))
        ->assertSee(__('home.fr_dg_by_platform'))
        ->assertSee('نص مشكلة للعرض')
        ->assertSee('نص سبب للعرض')
        ->assertSee('مقر الملخص')
        ->assertSee(__('home.fr_dg_trend'));
});

it('يُهمل اتجاه ترتيب مجهولاً من الرابط', function () {
    Livewire::actingAs(dgAdmin())->test(DigitalSummary::class)
        ->set('officeOrder', 'hacked')
        ->assertOk()
        ->call('toggleOfficeOrder')
        ->assertSet('officeOrder', 'asc');
});

it('يحسب الاتجاه الشهري لنسبة الحجز', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['created_at' => now()->startOfMonth()->addDay()]);
    dgWalkIn($office, ['created_at' => now()->startOfMonth()->addDay()]);
    dgWalkIn($office, ['created_at' => now()->startOfMonth()->addDays(2)]);

    $trend = (new DigitalReport(new FeedbackFilterSet, dgAdmin()))->monthlyTrend();

    expect($trend)->toHaveCount(1)
        ->and($trend[0])->toMatchArray(['count' => 3, 'booked' => 1, 'booked_percent' => 33.3]);
});

it('فلتر الهوية يسري على الملخص', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['phone' => '01012345678']);
    dgBooked($office);

    $report = new DigitalReport(new FeedbackFilterSet(identity: 'anonymous'), dgAdmin());

    expect($report->headline()['total'])->toBe(1);
});

/* ===================== التصدير ===================== */

it('ملف الآراء: رأس برقم السؤال · خلية ما لم يُسأل فارغة · بيانات المواطن مقفولة افتراضياً', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['name' => 'مواطن للاختبار', 'phone' => '01012345678', 'q205' => 0]);

    $rows = dgSheet(new FeedbackDigitalRatingsExport(DigitalRatingsQuery::build(new FeedbackFilterSet, dgAdmin())->orderBy('id')));
    [$head, $data] = $rows;
    $col = fn (string $key) => array_search('208 — '.__('home.fr_dg_q208'), $head, true);

    expect($head)->toContain('202 — '.__('home.fr_dg_q202'))
        ->and(implode('|', $data))->not->toContain('مواطن للاختبار')
        ->and($data[$col('q208')])->toBeNull()
        ->and($data[array_search('205 — '.__('home.fr_dg_q205'), $head, true)])->toEqual(0);

    $withPersonal = dgSheet(new FeedbackDigitalRatingsExport(DigitalRatingsQuery::build(new FeedbackFilterSet, dgAdmin()), true));
    expect(implode('|', $withPersonal[1]))->toContain('مواطن للاختبار');
});

it('ملف الملخص: ورقة التوزيعات بمقام كل سؤال', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q203' => 'yes']);
    dgBooked($office);
    dgWalkIn($office);

    $rows = dgSheet(new FeedbackDigitalSummaryExport(new DigitalReport(new FeedbackFilterSet, dgAdmin())), 1);
    $q203Yes = collect($rows)->first(fn ($r) => str_starts_with((string) $r[0], '203') && $r[1] === 'نعم');

    // القارئ يعيد الأرقام نصوصاً — المقارنة بالقيمة
    expect($q203Yes)->toEqual(['203 — '.__('home.fr_dg_q203'), 'نعم', 1, 2, 50]);
});

it('تصدير القائمة وتقرير الملخص يشترطان feedback.export', function () {
    $gov  = Governorate::factory()->create();
    $user = dgUser([FeedbackAccess::VIEW], [$gov]);

    $this->actingAs($user)->get(route('feedback-results.digital-summary.pdf'))->assertForbidden();
    Livewire::actingAs($user)->test(DigitalRatings::class)->call('exportExcel')->assertForbidden();
});

it('يولّد تقرير الملخص PDF فعلياً', function () {
    $office = Office::factory()->public()->create();
    dgBooked($office, ['q203' => 'yes', 'q204' => 'مشكلة']);
    dgWalkIn($office, ['q210' => 'سبب'], ['misr_digital']);

    $response = $this->actingAs(dgAdmin())->get(route('feedback-results.digital-summary.pdf', ['order' => 'asc']))->assertOk();

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

/* ===================== اللوحة الرئيسية ===================== */

it('اللوحة الرئيسية: كرت المنصات ونسبة المجهول ومؤشرات التدقيق تشمل آراء المنصات', function () {
    config(['feedback.ip_cluster_alert' => 2]);
    $office = Office::factory()->public()->create();
    dgBooked($office, ['ip_address' => '41.9.9.9']);
    dgBooked($office, ['ip_address' => '41.9.9.9', 'phone' => '01012345678']);

    $admin  = dgAdmin();
    $report = new DashboardReport(new FeedbackFilterSet, $admin);

    expect($report->digitalHeadline())->toMatchArray(['total' => 2, 'booked' => 2])
        ->and($report->identityShare()['digital'])->toBe(['total' => 2, 'anonymous' => 1, 'percent' => 50.0])
        ->and($report->ipClusters()->firstWhere('type', 'digital'))->toMatchArray(['ip' => '41.9.9.9', 'total' => 2]);

    Livewire::actingAs($admin)->test(Dashboard::class)->assertOk()->assertSee(__('home.fr_dg_card_hint'));
});
