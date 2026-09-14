<?php

use App\Livewire\Feedback\Rating;
use App\Livewire\Feedback\Suggestion;
use App\Livewire\FeedbackResults\Dashboard;
use App\Livewire\FeedbackResults\Ratings;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\User;
use App\Services\FeedbackGate;
use App\Support\FeedbackDevice;
use App\Support\FeedbackResults\DashboardReport;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\RatingsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * الهوية الاختيارية (2026-09-14): الاسم والرقم القومي والهاتف اختيارية،
 * ومنع التكرار للمجهول ببصمة جهاز في كوكي + سقف يومي للـIP لكل مقر.
 */

uses(RefreshDatabase::class);

const DEVICE_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const DEVICE_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

/** مكوّن التقييم من «جهاز» بعينه — الكوكي هو ما يرسله المتصفح مع كل طلب. */
function anonRating(?string $device = DEVICE_A)
{
    return ($device ? Livewire::withCookies([FeedbackDevice::COOKIE => $device]) : Livewire::getFacadeRoot())
        ->test(Rating::class);
}

/** يختار المقر ويملأ البنود بلا أي هوية. */
function anonFill($component, Office $office)
{
    return $component
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 4)->set('rating_queue', 3)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 4)->set('overall_rating', 4);
}

function anonAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

/* ===================== الإرسال بلا هوية ===================== */

it('يقبل تقييماً بلا اسم ولا رقم قومي ولا هاتف ويحفظ الحقول فارغة', function () {
    $office = Office::factory()->public()->create();

    anonFill(anonRating(), $office)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackRating::sole())
        ->name->toBeNull()
        ->national_id->toBeNull()
        ->phone->toBeNull()
        ->device_token->toBe(DEVICE_A)
        ->isAnonymous()->toBeTrue();
});

it('يقبل مقترحاً مجهولاً', function () {
    $office = Office::factory()->public()->create();

    Livewire::withCookies([FeedbackDevice::COOKIE => DEVICE_A])->test(Suggestion::class)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('other_suggestion', 'زيادة عدد الشبابيك')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackSuggestion::sole())
        ->national_id->toBeNull()
        ->device_token->toBe(DEVICE_A);
});

it('يُصدر للمتصفح بصمة جهاز جديدة حين لا يحملها أو يحمل قيمة تالفة', function () {
    $this->withCookie(FeedbackDevice::COOKIE, 'not-a-token');
    request()->cookies->set(FeedbackDevice::COOKIE, 'not-a-token');

    $token = app(FeedbackDevice::class)->token();

    expect($token)->toMatch('/^[a-f0-9]{32}$/')->not->toBe('not-a-token')
        // نفس الطلب لا يُصدر رمزين
        ->and(app(FeedbackDevice::class)->token())->toBe($token);
});

it('تُصدر صفحتا الفورم كوكي بصمة الجهاز مع التحميل نفسه', function (string $route) {
    // الاختبارات الأخرى تحقن الكوكي يدوياً — هذا وحده يثبت أن المتصفح يتسلّمه فعلاً
    $this->get(route($route))
        ->assertOk()
        ->assertCookie(FeedbackDevice::COOKIE);
})->with(['feedback.rating', 'feedback.suggestion', 'feedback.digital']);

/* ===================== قفل بصمة الجهاز ===================== */

it('يحجب رأياً مجهولاً ثانياً من نفس الجهاز لنفس المقر خلال المدة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => null, 'phone' => null, 'name' => null,
        'device_token' => DEVICE_A, 'created_at' => now()->subDays(2),
    ]);

    // الحجب يظهر لحظة اختيار المقر — قبل البنود
    anonRating()
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->assertSet('gateBlocked', true);

    // والإرسال المباشر محجوب أيضاً
    anonFill(anonRating(), $office)->call('submit')->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(1);
});

it('لا يحجب جهازاً آخر لنفس المقر', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => null, 'phone' => null, 'device_token' => DEVICE_A,
    ]);

    anonFill(anonRating(DEVICE_B), $office)
        ->assertSet('gateBlocked', false)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(FeedbackRating::count())->toBe(2);
});

it('يحجب مَن أرسل بهويته ثم عاد مجهولاً من نفس الجهاز', function () {
    $office = Office::factory()->public()->create();

    // الإرسال الأول بهوية كاملة — وبصمة الجهاز تُكتب عليه أيضاً
    anonFill(anonRating(), $office)
        ->set('national_id', '29001010101234')
        ->set('phone', '01012345678')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(FeedbackRating::sole()->device_token)->toBe(DEVICE_A);

    anonRating()
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->assertSet('gateBlocked', true);
});

it('يسمح للجهاز نفسه بعد انتهاء المدة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => null, 'phone' => null, 'device_token' => DEVICE_A,
        'created_at' => now()->subDays((int) config('feedback.window_days') + 1),
    ]);

    anonFill(anonRating(), $office)->assertSet('gateBlocked', false);
});

it('بلا أي مفتاح لا يحجب أحداً — لا شرط فارغاً يطابق كل الصفوف', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);

    expect(app(FeedbackGate::class)->duplicateRetryDate('rating', null, '', null, $office->id))->toBeNull();
});

/* ===================== السقف اليومي للـIP لكل مقر ===================== */

it('يوقف الإرسال من خط واحد لمقر واحد عند بلوغ السقف اليومي ويسجّله', function () {
    config(['feedback.ip_max_per_day_per_office' => 2]);
    $office = Office::factory()->public()->create();
    $other  = Office::factory()->public()->create();

    anonFill(anonRating(DEVICE_A), $office)->call('submit')->assertSet('submitted', true);
    anonFill(anonRating(DEVICE_B), $office)->call('submit')->assertSet('submitted', true);

    // جهاز ثالث (كوكيز ممسوحة) من نفس الخط
    anonFill(anonRating('cccccccccccccccccccccccccccccccc'), $office)
        ->call('submit')
        ->assertHasErrors('gate')
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(2)
        ->and(FeedbackRejectedAttempt::where('reason', 'ip_daily_cap')->count())->toBe(1);

    // السقف لكل مقر: المقر الآخر متاح من نفس الخط
    anonFill(anonRating('dddddddddddddddddddddddddddddddd'), $other)->call('submit')->assertSet('submitted', true);
});

it('السقف اليومي يسري على المُعرَّف أيضاً — الهاتف المختلَق لا يتخطّاه', function () {
    config(['feedback.ip_max_per_day_per_office' => 2]);
    $office = Office::factory()->public()->create();

    foreach (['01011111111', '01022222222', '01033333333'] as $i => $phone) {
        $component = anonFill(anonRating(str_repeat((string) $i, 32)), $office)
            ->set('phone', $phone)
            ->call('submit');
    }

    $component->assertHasErrors('gate');
    expect(FeedbackRating::count())->toBe(2);
});

/* ===================== النتائج: التمييز والفلتر والمؤشرات ===================== */

it('فلتر الهوية يفصل المجهول عن المُعرَّف في الشاشة والاستعلام المشترك', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'مواطن معرَّف']);
    // الهاتف وحده يُعرّف — «مجهول» = بلا رقم قومي **وبلا** هاتف
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'هاتف فقط', 'national_id' => null]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'اسم فقط', 'national_id' => null, 'phone' => null]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => null, 'national_id' => null, 'phone' => null]);

    $admin = anonAdmin();

    expect(RatingsQuery::build(new FeedbackFilterSet(identity: 'anonymous'), $admin)->count())->toBe(2)
        ->and(RatingsQuery::build(new FeedbackFilterSet(identity: 'identified'), $admin)->count())->toBe(2)
        // قيمة خارج القائمة البيضاء تُهمَل ولا تُفرغ الشاشة
        ->and(RatingsQuery::build(new FeedbackFilterSet(identity: 'x'), $admin)->count())->toBe(4);

    Livewire::actingAs($admin)->test(Ratings::class)
        ->set('identity', 'anonymous')
        ->assertSee('اسم فقط')
        ->assertSee(__('home.fr_anonymous'))
        ->assertDontSee('مواطن معرَّف')
        ->assertDontSee('هاتف فقط');
});

it('المجهول داخل المتوسطات، ونسبته في اللوحة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 5]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 1, 'national_id' => null, 'phone' => null]);

    $report = new DashboardReport(new FeedbackFilterSet, anonAdmin());

    expect($report->kpis()['avg_overall'])->toEqual(3)
        ->and($report->identityShare()['ratings'])->toBe(['total' => 2, 'anonymous' => 1, 'percent' => 50.0]);

    Livewire::actingAs(anonAdmin())->test(Dashboard::class)->assertOk()->assertSee(__('home.fr_identity_share'));
});

it('يكشف تجمّع الآراء من خط واحد بعدد الأجهزة المختلفة', function () {
    config(['feedback.ip_cluster_alert' => 3]);
    $office = Office::factory()->public()->create();

    foreach (range(1, 4) as $i) {
        FeedbackRating::factory()->create([
            'office_id' => $office->id, 'ip_address' => '41.1.1.1', 'device_token' => str_repeat((string) $i, 32),
        ]);
    }
    // خط آخر دون الحد
    FeedbackRating::factory()->create(['office_id' => $office->id, 'ip_address' => '41.2.2.2']);

    $clusters = (new DashboardReport(new FeedbackFilterSet, anonAdmin()))->ipClusters();

    expect($clusters)->toHaveCount(1)
        ->and($clusters->first())->toMatchArray(['ip' => '41.1.1.1', 'total' => 4, 'devices' => 4, 'type' => 'rating']);
});

it('مؤشرات التدقيق محجوبة عمّن لا يملك feedback.rejected — في الشاشة والملف معاً', function () {
    config(['feedback.ip_cluster_alert' => 2]);
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->count(3)->create(['office_id' => $office->id, 'ip_address' => '41.1.1.1']);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo([FeedbackAccess::VIEW]);
    $viewer->governorates()->sync([$office->governorate_id]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect((new DashboardReport(new FeedbackFilterSet, $viewer->fresh()))->ipClusters())->toBeEmpty();

    $this->actingAs($viewer->fresh())->get(route('feedback-results.dashboard'))
        ->assertOk()
        ->assertDontSee('41.1.1.1');
});

it('رابط الـPDF يحمل فلتر الهوية كالشاشة', function () {
    $filters = FeedbackFilterSet::fromRequest(Request::create('/', 'GET', ['identity' => 'anonymous']));

    expect($filters->identity)->toBe('anonymous')
        ->and($filters->toQuery())->toBe(['identity' => 'anonymous'])
        ->and($filters->isActive())->toBeTrue();
});
