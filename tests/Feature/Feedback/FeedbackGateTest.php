<?php

use App\Livewire\Feedback\Rating;
use App\Livewire\Feedback\Suggestion;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** رقم قومي صحيح البنية (يمرّ من EgyptianNationalId). */
const NID = '29001010101234';
const NID_OTHER = '29505050201234';
const PHONE = '01012345678';

/** يملأ حقول الهوية ويختار المقر — نقطة البداية لكل اختبار. */
function identify($component, Office $office, string $nid = NID, string $phone = PHONE)
{
    return $component
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', $nid)
        ->set('phone', $phone)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id);
}

/* ===================== قاعدة منع التكرار ===================== */

it('يحجب تقييماً ثانياً لنفس المقر بنفس الرقم القومي خلال المدة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id'   => $office->id,
        'national_id' => NID,
        'phone'       => '01099999999',   // هاتف مختلف: الحجب من الرقم القومي وحده
        'created_at'  => now()->subDays(3),
    ]);

    identify(Livewire::test(Rating::class), $office)
        ->assertSet('gateBlocked', true)
        ->assertDontSee('سرعة إنجاز المعاملة');   // البنود لا تظهر أصلاً
});

it('يحجب بالهاتف حتى لو اختلف الرقم القومي', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id'   => $office->id,
        'national_id' => NID_OTHER,
        'phone'       => PHONE,
        'created_at'  => now()->subDays(3),
    ]);

    identify(Livewire::test(Rating::class), $office)
        ->assertSet('gateBlocked', true);
});

it('يسمح بالتقييم بعد انتهاء المدة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id'   => $office->id,
        'national_id' => NID,
        'phone'       => PHONE,
        'created_at'  => now()->subDays(config('feedback.window_days') + 1),
    ]);

    identify(Livewire::test(Rating::class), $office)
        ->assertSet('gateBlocked', false)
        ->assertSee('سرعة إنجاز المعاملة');       // البنود ظهرت
});

it('يحجب لمقر واحد فقط — المقر الآخر يظل متاحاً', function () {
    $officeA = Office::factory()->public()->create();
    $officeB = Office::factory()->public()->create(['governorate_id' => $officeA->governorate_id]);
    FeedbackRating::factory()->create([
        'office_id' => $officeA->id, 'national_id' => NID, 'phone' => PHONE,
    ]);

    identify(Livewire::test(Rating::class), $officeB)
        ->assertSet('gateBlocked', false);
});

it('لا يخلط بين نوعي الإرسال — تقييم سابق لا يمنع المقترح', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => NID, 'phone' => PHONE,
    ]);

    identify(Livewire::test(Suggestion::class), $office)
        ->assertSet('gateBlocked', false);
});

it('يسجّل الرفض مرة واحدة فقط مهما تكرر الفحص التفاعلي', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => NID, 'phone' => PHONE,
    ]);

    identify(Livewire::test(Rating::class), $office)
        ->assertSet('gateBlocked', true)
        ->set('phone', PHONE)          // إعادة فحص
        ->set('national_id', NID);     // وإعادة فحص أخرى

    expect(FeedbackRejectedAttempt::where('reason', 'duplicate_window')->count())->toBe(1);
});

/* ===================== الإرسال ===================== */

it('يحفظ التقييم الصحيح', function () {
    $office = Office::factory()->public()->create();

    identify(Livewire::test(Rating::class), $office)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)
        ->set('rating_staff', 4)
        ->set('rating_queue', 3)
        ->set('rating_cleanliness', 5)
        ->set('rating_clarity', 4)
        ->set('overall_rating', 4)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackRating::count())->toBe(1);
    expect(FeedbackRating::first())
        ->office_id->toBe($office->id)
        ->governorate_id->toBe($office->governorate_id)
        ->national_id->toBe(NID)
        ->rating_accessibility->toBeNull();      // المحور الاختياري
});

it('يمنع الحفظ عند التكرار حتى لو وصل الإرسال مباشرة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id, 'national_id' => NID, 'phone' => PHONE,
    ]);

    identify(Livewire::test(Rating::class), $office)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertSet('gateBlocked', true)
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(1);    // لم يُضف صف جديد
});

it('حقل المصيدة يُظهر شكراً بلا حفظ ويسجّل المحاولة', function () {
    $office = Office::factory()->public()->create();

    identify(Livewire::test(Rating::class), $office)
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('submitted', true);          // البوت لا يعرف أنه انكشف

    expect(FeedbackRating::count())->toBe(0);
    expect(FeedbackRejectedAttempt::where('reason', 'honeypot')->count())->toBe(1);
});

it('يمنع الإرسال عند تجاوز حد الجهاز في الدقيقة', function () {
    $office = Office::factory()->public()->create();
    $max = (int) config('feedback.ip_max_per_minute');
    for ($i = 0; $i < $max; $i++) {
        RateLimiter::hit('feedback:127.0.0.1', 60);
    }

    identify(Livewire::test(Rating::class), $office)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasErrors('gate')
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(0);
    expect(FeedbackRejectedAttempt::where('reason', 'rate_limit')->count())->toBe(1);
});

it('يرفض التقييم بمحور ناقص', function () {
    $office = Office::factory()->public()->create();

    identify(Livewire::test(Rating::class), $office)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)
        ->call('submit')
        ->assertHasErrors(['rating_staff' => 'required'])
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(0);
});

/* ===================== المقترحات ===================== */

it('يرفض المقترح الفارغ ويقبل المقترح بعنوان', function () {
    $this->seed(Database\Seeders\SuggestionCatalogSeeder::class);
    $office = Office::factory()->public()->create();

    identify(Livewire::test(Suggestion::class), $office)
        ->call('submit')
        ->assertHasErrors('topics')
        ->assertSet('submitted', false);

    expect(FeedbackSuggestion::count())->toBe(0);

    identify(Livewire::test(Suggestion::class), $office)
        ->set('topics', ['more_seats', 'ventilation'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackSuggestion::first()->topics)->toHaveCount(2);
});

it('يقبل المقترح الحر بدون اختيار عناوين', function () {
    $this->seed(Database\Seeders\SuggestionCatalogSeeder::class);
    $office = Office::factory()->public()->create();

    identify(Livewire::test(Suggestion::class), $office)
        ->set('other_suggestion', 'اقتراح حر من المواطن')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackSuggestion::first()->other_suggestion)->toBe('اقتراح حر من المواطن');
});

/* ===================== الإظهار للمواطن (is_public) ===================== */

it('لا يعرض إلا مقرات الأنواع العامة ومحافظاتها', function () {
    $publicOffice = Office::factory()->public()->create(['name' => 'مقر ظاهر للمواطن']);
    $privateOffice = Office::factory()->create([
        'name'           => 'مقر إداري داخلي',
        'governorate_id' => $publicOffice->governorate_id,
    ]);

    // محافظة ليس بها إلا مقر غير عام — يجب ألا تظهر أصلاً
    $hiddenGov = Governorate::factory()->create(['name' => 'محافظة مخفية']);
    Office::factory()->create([
        'governorate_id' => $hiddenGov->id,
        'type_id'        => OfficeType::factory()->create(['is_public' => false]),
    ]);

    Livewire::test(Rating::class)
        ->assertSee($publicOffice->governorate->name)
        ->assertDontSee('محافظة مخفية')
        ->set('governorate_id', $publicOffice->governorate_id)
        ->assertSee('مقر ظاهر للمواطن')
        ->assertDontSee('مقر إداري داخلي');
});

/* ===================== نقل الهوية بين الفورمين ===================== */

it('لا يعبّئ الهوية المحفوظة إلا مع resume=1', function () {
    $office = Office::factory()->public()->create();
    $carry = [
        'name' => 'مواطن سابق', 'national_id' => NID, 'phone' => PHONE,
        'governorate_id' => $office->governorate_id, 'office_id' => $office->id,
    ];

    // فتح عادي: لا تعبئة (أمان الأجهزة المشتركة)
    session()->put('feedback.carry', $carry);
    Livewire::test(Suggestion::class)->assertSet('national_id', '');
    expect(session()->has('feedback.carry'))->toBeTrue();   // ولم تُستهلك

    // مع resume=1: تعبئة ثم مسح
    Livewire::withQueryParams(['resume' => 1])->test(Suggestion::class)
        ->assertSet('name', 'مواطن سابق')
        ->assertSet('national_id', NID)
        ->assertSet('office_id', $office->id);

    expect(session()->has('feedback.carry'))->toBeFalse();  // تُقرأ مرة واحدة
});
