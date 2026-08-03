<?php

use App\Livewire\FeedbackResults\Dashboard;
use App\Livewire\FeedbackResults\Ratings;
use App\Livewire\FeedbackResults\RejectedAttempts;
use App\Livewire\FeedbackResults\Suggestions;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\SuggestionDomain;
use App\Models\SuggestionTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function superAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function topic(string $name = 'زيادة مقاعد الانتظار'): SuggestionTopic
{
    $domain = SuggestionDomain::firstOrCreate(['key' => 'building'], ['name' => 'المبنى والتجهيزات', 'order' => 1]);

    return SuggestionTopic::create([
        'suggestion_domain_id' => $domain->id,
        'key'                  => 'topic_'.uniqid(),
        'name'                 => $name,
        'order'                => 1,
    ]);
}

/* ===================== الوصول ===================== */

it('يمنع غير السوبر أدمن من كل شاشات النتائج', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with([
    'feedback-results.dashboard',
    'feedback-results.ratings',
    'feedback-results.suggestions',
    'feedback-results.rejected',
]);

it('يمنع الزائر غير المسجّل', function () {
    $this->get(route('feedback-results.dashboard'))->assertRedirect(route('login'));
});

it('يفتح الشاشات للسوبر أدمن', function (string $route) {
    $this->actingAs(superAdmin())->get(route($route))->assertOk();
})->with([
    'feedback-results.dashboard',
    'feedback-results.ratings',
    'feedback-results.suggestions',
    'feedback-results.rejected',
]);

/* ===================== متوسط المحاور ===================== */

it('يحسب متوسط المحور الاختياري على المجيبين فقط', function () {
    $office = Office::factory()->public()->create();

    FeedbackRating::factory()->create(['office_id' => $office->id, 'rating_accessibility' => 2]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'rating_accessibility' => null]);

    $criteria = collect(Livewire::actingAs(superAdmin())->test(Dashboard::class)->viewData('criteria'))
        ->firstWhere('label', FeedbackRating::CRITERIA['rating_accessibility'][0]);

    // لو حُسب NULL كصفر لكان المتوسط 1 والعدد 2
    expect($criteria['avg'])->toBe(2.0)
        ->and($criteria['count'])->toBe(1);
});

it('يحسب متوسط محاور الصف على المحاور المُجابة فقط', function () {
    $rating = FeedbackRating::factory()->make([
        'rating_speed' => 5, 'rating_staff' => 5, 'rating_queue' => 5,
        'rating_cleanliness' => 5, 'rating_clarity' => 5,
        'rating_accessibility' => null,
    ]);

    expect($rating->criteriaAverage())->toBe(5.0);
});

/* ===================== الحد الأدنى للعينة ===================== */

it('يستبعد المقرات دون الحد الأدنى للعينة من الترتيب', function () {
    config(['feedback.min_ratings_for_ranking' => 5]);

    $enough = Office::factory()->public()->create(['name' => 'مقر بعينة كافية']);
    $few    = Office::factory()->public()->create(['name' => 'مقر بعينة قليلة']);

    FeedbackRating::factory()->count(5)->create(['office_id' => $enough->id, 'overall_rating' => 4]);
    FeedbackRating::factory()->count(2)->create(['office_id' => $few->id, 'overall_rating' => 5]);

    $ranking = Livewire::actingAs(superAdmin())->test(Dashboard::class)->viewData('ranking');

    expect($ranking['ranked_count'])->toBe(1)
        ->and($ranking['top']->pluck('office')->all())->toBe(['مقر بعينة كافية'])
        ->and($ranking['insufficient']->pluck('office')->all())->toBe(['مقر بعينة قليلة']);
});

/* ===================== الفلاتر ===================== */

it('يفلتر الداشبورد بالفترة الزمنية', function () {
    $office = Office::factory()->public()->create();

    FeedbackRating::factory()->create(['office_id' => $office->id, 'created_at' => now()->subDays(100)]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'created_at' => now()->subDays(2)]);

    $kpis = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->set('from', now()->subDays(10)->toDateString())
        ->viewData('kpis');

    expect($kpis['ratings'])->toBe(1);
});

it('يشمل نطاق الفترة يومَي الطرفين بالكامل', function () {
    $office = Office::factory()->public()->create();
    $day = now()->subDays(5)->toDateString();

    // آخر دقيقة في يوم النهاية وأول دقيقة في يوم البداية — لا بد أن يدخلا
    FeedbackRating::factory()->create(['office_id' => $office->id, 'created_at' => $day.' 23:59:30']);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'created_at' => $day.' 00:00:30']);
    // اليوم التالي — خارج النطاق
    FeedbackRating::factory()->create(['office_id' => $office->id, 'created_at' => now()->subDays(4)->startOfDay()->addMinute()]);

    $kpis = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->set('from', $day)
        ->set('to', $day)
        ->viewData('kpis');

    expect($kpis['ratings'])->toBe(2);
});

it('يتجاهل تاريخاً تالفاً في الرابط بدل الانهيار', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->count(2)->create(['office_id' => $office->id]);

    $kpis = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->set('from', 'ليس تاريخاً')
        ->set('to', '../../etc/passwd')
        ->assertOk()
        ->viewData('kpis');

    expect($kpis['ratings'])->toBe(2);   // الفلتر التالف يُهمَل، لا يُطبَّق ولا ينهار
});

it('يفلتر الداشبورد بالمحافظة', function () {
    $a = Office::factory()->public()->create();
    $b = Office::factory()->public()->create();

    FeedbackRating::factory()->count(3)->create(['office_id' => $a->id]);
    FeedbackRating::factory()->create(['office_id' => $b->id]);

    $kpis = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->set('governorate_id', (string) $a->governorate_id)
        ->viewData('kpis');

    expect($kpis['ratings'])->toBe(3);
});

it('يصفّر المقر المختار عند تغيير المحافظة', function () {
    $office = Office::factory()->public()->create();

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->set('governorate_id', (string) $office->governorate_id)
        ->set('office_id', (string) $office->id)
        ->set('governorate_id', (string) Office::factory()->public()->create()->governorate_id)
        ->assertSet('office_id', '');
});

/* ===================== أولويات المقترحات ===================== */

it('يعدّ عناوين المقترحات ويحترم فلتر المقر', function () {
    $wanted = topic('زيادة مقاعد الانتظار');
    $other  = topic('تحسين التهوية');

    $a = Office::factory()->public()->create();
    $b = Office::factory()->public()->create();

    FeedbackSuggestion::factory()->count(3)->create(['office_id' => $a->id])
        ->each(fn ($s) => $s->topics()->sync([$wanted->id]));
    FeedbackSuggestion::factory()->create(['office_id' => $b->id])->topics()->sync([$other->id]);

    // بلا فلتر: العنوانان معاً، الأكثر أولاً
    $all = Livewire::actingAs(superAdmin())->test(Dashboard::class)->viewData('priority');
    expect($all['topics']->pluck('count')->all())->toBe([3, 1])
        ->and($all['topics']->first()['name'])->toBe('زيادة مقاعد الانتظار');

    // بفلتر المقر الثاني: عنوانه وحده
    $filtered = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->set('governorate_id', (string) $b->governorate_id)
        ->set('office_id', (string) $b->id)
        ->viewData('priority');

    expect($filtered['topics']->pluck('name')->all())->toBe(['تحسين التهوية']);
});

/* ===================== البحث ===================== */

it('يبحث في اسم المواطن بتوحيد الألف والياء', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'أحمد مصطفى']);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'سارة حسن']);

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->set('search', 'احمد مصطفي')      // ألف ممدودة وياء بدل الألف المقصورة
        ->assertSee('أحمد مصطفى')
        ->assertDontSee('سارة حسن');
});

it('يبحث بالرقم القومي والهاتف', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'صاحب الرقم', 'phone' => '01099887766']);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'مواطن آخر', 'phone' => '01011112222']);

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->set('search', '01099887766')
        ->assertSee('صاحب الرقم')
        ->assertDontSee('مواطن آخر');
});

/* ===================== البحث في النصوص الحرة ===================== */

it('يبحث داخل نص ملاحظة التقييم', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'صاحب الشكوى', 'notes' => 'التكييف لا يعمل منذ شهر']);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'مواطن راضٍ', 'notes' => 'الخدمة ممتازة']);

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->set('search', 'التكييف')
        ->assertSee('صاحب الشكوى')
        ->assertDontSee('مواطن راضٍ');
});

it('يبحث داخل الاقتراح الحر وداخل عناوين الكتالوج', function () {
    $office = Office::factory()->public()->create();

    FeedbackSuggestion::factory()->create(['office_id' => $office->id, 'name' => 'صاحب النص الحر', 'other_suggestion' => 'إتاحة الدفع الإلكتروني']);

    $withTopic = FeedbackSuggestion::factory()->create(['office_id' => $office->id, 'name' => 'صاحب العنوان', 'other_suggestion' => null]);
    $withTopic->topics()->sync([topic('زيادة مقاعد الانتظار')->id]);

    // نص حر
    Livewire::actingAs(superAdmin())->test(Suggestions::class)
        ->set('search', 'الدفع الإلكتروني')
        ->assertSee('صاحب النص الحر')
        ->assertDontSee('صاحب العنوان');

    // عنوان من الكتالوج
    Livewire::actingAs(superAdmin())->test(Suggestions::class)
        ->set('search', 'مقاعد الانتظار')
        ->assertSee('صاحب العنوان')
        ->assertDontSee('صاحب النص الحر');
});

/* ===================== الترتيب ===================== */

it('يرتّب التقييمات بالتقييم العام ويعكس الاتجاه عند إعادة الضغط', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 1]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 5]);

    $component = Livewire::actingAs(superAdmin())->test(Ratings::class)->call('sort', 'overall_rating');
    expect($component->viewData('ratings')->first()->overall_rating)->toBe(5);   // تنازلي أولاً

    $component->call('sort', 'overall_rating');
    expect($component->viewData('ratings')->first()->overall_rating)->toBe(1);   // انعكس
});

it('يتجاهل عمود ترتيب غير مسموح به قادماً من الرابط', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->count(2)->create(['office_id' => $office->id]);

    // حقن عبر الرابط: لا يُمرَّر لـ orderBy ولا ينهار — يعود للترتيب الافتراضي
    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->set('sortBy', 'national_id) --')
        ->assertOk()
        ->call('sort', 'national_id')
        ->assertSet('sortBy', 'national_id) --');   // sort() رفض التغيير أصلاً
});

/* ===================== اختصارات الفترة ===================== */

it('يضبط الفترة من الاختصار ويلغيها عند إعادة الضغط', function () {
    $component = Livewire::actingAs(superAdmin())->test(Dashboard::class)
        ->call('setPeriod', 'this_month');

    expect($component->get('from'))->toBe(now()->startOfMonth()->toDateString())
        ->and($component->get('to'))->toBe(now()->toDateString());

    $component->call('setPeriod', 'this_month');
    expect($component->get('from'))->toBe('')->and($component->get('to'))->toBe('');
});

/* ===================== الاتجاه الزمني ومقارنة المحافظات ===================== */

it('يجمّع الاتجاه شهرياً بمتوسط كل شهر على حدة', function () {
    $office = Office::factory()->public()->create();

    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 2, 'created_at' => now()->subMonths(2)->startOfMonth()->addDays(3)]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 4, 'created_at' => now()->subMonths(2)->startOfMonth()->addDays(9)]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 5, 'created_at' => now()->startOfMonth()->addDay()]);

    $trend = Livewire::actingAs(superAdmin())->test(Dashboard::class)->viewData('trend');

    expect($trend)->toHaveCount(2)
        ->and($trend[0]['avg'])->toBe(3.0)      // متوسط الشهر الأقدم (2 و4)
        ->and($trend[0]['count'])->toBe(2)
        ->and($trend[1]['avg'])->toBe(5.0);
});

it('يرتّب المحافظات ويُعلِّم ذات العينة الناقصة', function () {
    config(['feedback.min_ratings_for_ranking' => 5]);

    $strong = Office::factory()->public()->create();
    $weak   = Office::factory()->public()->create();

    FeedbackRating::factory()->count(5)->create(['office_id' => $strong->id, 'overall_rating' => 5]);
    FeedbackRating::factory()->count(2)->create(['office_id' => $weak->id, 'overall_rating' => 1]);

    $rows = Livewire::actingAs(superAdmin())->test(Dashboard::class)->viewData('govRanking');

    expect($rows)->toHaveCount(2)
        ->and($rows->first()['avg'])->toBe(5.0)
        ->and($rows->first()['enough'])->toBeTrue()
        ->and($rows->last()['enough'])->toBeFalse();
});

/* ===================== عرض الوقت ===================== */

it('يعرض الوقت بنظام ١٢ ساعة محوَّلاً لتوقيت مصر', function () {
    $office = Office::factory()->public()->create();

    // مخزَّن UTC؛ أغسطس في مصر = UTC+3 → 20:30 محلياً
    FeedbackRating::factory()->create([
        'office_id'  => $office->id,
        'created_at' => '2026-08-03 17:30:00',
    ]);

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->assertSee('8:30 م')          // ١٢ ساعة + توقيت محلي
        ->assertDontSee('17:30');      // لا الصيغة الأربع والعشرينية ولا قيمة UTC الخام
});

it('يفرّق بين ص وم', function () {
    expect(\App\Support\LocalTime::time(\Carbon\CarbonImmutable::parse('2026-08-03 05:00:00')))->toBe('8:00 ص')
        ->and(\App\Support\LocalTime::time(\Carbon\CarbonImmutable::parse('2026-08-03 17:00:00')))->toBe('8:00 م')
        ->and(\App\Support\LocalTime::time(null))->toBe('—');
});

/* ===================== المحاولات المرفوضة ===================== */

it('يفلتر المحاولات المرفوضة بالمحافظة عبر علاقة المقر', function () {
    $a = Office::factory()->public()->create();
    $b = Office::factory()->public()->create();

    FeedbackRejectedAttempt::create(['type' => 'rating', 'office_id' => $a->id, 'reason' => 'duplicate_window', 'ip_address' => '10.0.0.1']);
    FeedbackRejectedAttempt::create(['type' => 'rating', 'office_id' => $b->id, 'reason' => 'honeypot', 'ip_address' => '10.0.0.2']);

    $counts = Livewire::actingAs(superAdmin())->test(RejectedAttempts::class)
        ->set('governorate_id', (string) $a->governorate_id)
        ->viewData('reasonCounts');

    expect($counts->toArray())->toBe(['duplicate_window' => 1]);
});

it('يفلتر المحاولات المرفوضة بالسبب', function () {
    $office = Office::factory()->public()->create();

    FeedbackRejectedAttempt::create(['type' => 'rating', 'office_id' => $office->id, 'reason' => 'duplicate_window', 'ip_address' => '10.0.0.1']);
    FeedbackRejectedAttempt::create(['type' => 'suggestion', 'office_id' => $office->id, 'reason' => 'honeypot', 'ip_address' => '10.0.0.2']);

    Livewire::actingAs(superAdmin())->test(RejectedAttempts::class)
        ->set('reason', 'honeypot')
        ->assertSee('10.0.0.2')
        ->assertDontSee('10.0.0.1');
});

/* ===================== المقترحات ===================== */

it('يعرض عدد العناوين لكل مقترح', function () {
    $office = Office::factory()->public()->create();
    $suggestion = FeedbackSuggestion::factory()->create(['office_id' => $office->id]);
    $suggestion->topics()->sync([topic('عنوان أول')->id, topic('عنوان ثانٍ')->id]);

    $rows = Livewire::actingAs(superAdmin())->test(Suggestions::class)
        ->assertSee($office->name)
        ->viewData('suggestions');

    expect($rows->first()->topics_count)->toBe(2);
});

it('يخرج الملاحظات من الجدول ويعرضها مع المحاور في صف التفاصيل', function () {
    $office = Office::factory()->public()->create();
    $rating = FeedbackRating::factory()->create([
        'office_id' => $office->id,
        'notes'     => 'ملاحظة المواطن التجريبية',
    ]);

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->assertDontSee('ملاحظة المواطن التجريبية')          // لا عمود للملاحظات في الجدول
        ->call('toggle', $rating->id)
        ->assertSee('ملاحظة المواطن التجريبية')              // تظهر بعد فتح التفاصيل
        ->assertSee(FeedbackRating::CRITERIA['rating_speed'][0])
        ->assertSee(FeedbackRating::WAIT_TIMES[$rating->wait_time]);   // مدة الانتظار نزلت للتفاصيل
});

it('يعرض المقر المحذوف بلافتة بديلة بدل الانهيار', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);
    $office->delete();   // FK nullOnDelete — يبقى التقييم بمقر فارغ

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->assertOk()
        ->assertSee(__('home.fr_deleted_office'));
});
