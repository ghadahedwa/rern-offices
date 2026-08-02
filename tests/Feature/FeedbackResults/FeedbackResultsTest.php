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

it('يعرض المقر المحذوف بلافتة بديلة بدل الانهيار', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);
    $office->delete();   // FK nullOnDelete — يبقى التقييم بمقر فارغ

    Livewire::actingAs(superAdmin())->test(Ratings::class)
        ->assertOk()
        ->assertSee(__('home.fr_deleted_office'));
});
