<?php

use App\Livewire\Feedback\Rating;
use App\Livewire\Feedback\Suggestion;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Governorate;
use App\Models\Office;
use App\Rules\EgyptianNationalId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** تشغيل قاعدة الرقم القومي وحدها — ترجع true لو الرقم مقبول. */
function nationalIdPasses(string $value): bool
{
    return Validator::make(
        ['national_id' => $value],
        ['national_id' => [new EgyptianNationalId]],
    )->passes();
}

/* ===================== الرقم القومي ===================== */
/*
 * بنية الرقم: [1] القرن · [2-3] السنة · [4-5] الشهر · [6-7] اليوم
 * · [8-9] كود المحافظة · [10-13] تسلسل · [14] خانة تحقق.
 * كل حالة رفض في App\Rules\EgyptianNationalId لها اختبار هنا.
 */

it('يقبل رقماً قومياً صحيح البنية', function () {
    expect(nationalIdPasses('29001010101234'))->toBeTrue();   // 1990-01-01، محافظة 01
});

it('يرفض الرقم القومي غير الصحيح', function (string $value) {
    expect(nationalIdPasses($value))->toBeFalse();
})->with([
    'أقل من ١٤ رقماً'      => '2900101010123',
    'أكثر من ١٤ رقماً'     => '290010101012345',
    'يحتوي حروفاً'         => '2900101010123a',
    // القيمة الفارغة خارج نطاق هذه القاعدة — Laravel يتخطّى القواعد المخصّصة
    // للقيم الفارغة، ويمسكها required في الفورم (انظر الاختبار التالي).
    'خانة قرن غير ٢ أو ٣'  => '19001010101234',
    'شهر ١٣'               => '29013010101234',
    'يوم ٣٠ في فبراير'     => '29002300101234',
    'تاريخ ميلاد مستقبلي'  => '39901010101234',   // 2099
    'كود محافظة غير معروف' => '29001010901234',   // 09
]);

it('يرفض الفورم الرقم القومي غير الصحيح أو الفارغ ولا يحفظ', function (string $nationalId) {
    $office = Office::factory()->public()->create();

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', $nationalId)
        ->set('phone', '01012345678')
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasErrors('national_id')
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(0);
})->with(['بنية غير صحيحة' => '19001010101234', 'فارغ' => '']);

/* ===================== رقم الهاتف ===================== */

it('يقبل أرقام المحمول المصرية الصحيحة', function (string $phone) {
    $office = Office::factory()->public()->create();

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', $phone)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasNoErrors();
})->with(['010' => '01012345678', '011' => '01112345678', '012' => '01212345678', '015' => '01512345678']);

it('يرفض رقم الهاتف غير الصحيح', function (string $phone) {
    $office = Office::factory()->public()->create();

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', $phone)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasErrors('phone')
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(0);
})->with([
    'شبكة غير موجودة (013)' => '01312345678',
    'أقل من ١١ رقماً'       => '0101234567',
    'أكثر من ١١ رقماً'      => '010123456789',
    'بدون صفر في البداية'   => '1012345678',
    'أرقام أرضي'            => '0223456789',
    'فارغ'                  => '',
]);

/* ===================== سلامة المقر والمحافظة ===================== */
/*
 * scopePublicFeedback يُطبَّق على قوائم العرض فقط، والمحافظة كانت تُحفظ من
 * مُدخَل المستخدم — فطلب متلاعَب فيه (خارج الواجهة) كان يقدر يعلّق رأياً على
 * مقر داخلي أو يحفظه تحت محافظة لا تخصّ المقر.
 */

it('يرفض التقييم لمقر من نوع غير ظاهر للمواطن', function () {
    $office = Office::factory()->create();   // نوع غير عام

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', '01012345678')
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasErrors('office_id')
        ->assertSet('submitted', false);

    expect(FeedbackRating::count())->toBe(0);
});

it('يرفض المقترح لمقر من نوع غير ظاهر للمواطن', function () {
    $office = Office::factory()->create();

    Livewire::test(Suggestion::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', '01012345678')
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->set('other_suggestion', 'اقتراح')
        ->call('submit')
        ->assertHasErrors('office_id')
        ->assertSet('submitted', false);

    expect(FeedbackSuggestion::count())->toBe(0);
});

it('يرفض مقراً غير موجود أصلاً', function () {
    $office = Office::factory()->public()->create();

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', '01012345678')
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', 999999)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasErrors('office_id');

    expect(FeedbackRating::count())->toBe(0);
});

it('يحفظ محافظة المقر لا المحافظة المرسَلة', function () {
    $office = Office::factory()->public()->create();
    $otherGovernorate = Governorate::factory()->create();

    Livewire::test(Rating::class)
        ->set('name', 'مواطن للاختبار')
        ->set('national_id', '29001010101234')
        ->set('phone', '01012345678')
        ->set('governorate_id', $otherGovernorate->id)   // محافظة لا تخصّ المقر
        ->set('office_id', $office->id)
        ->set('wait_time', 'under_15')
        ->set('rating_speed', 5)->set('rating_staff', 5)->set('rating_queue', 5)
        ->set('rating_cleanliness', 5)->set('rating_clarity', 5)->set('overall_rating', 5)
        ->call('submit')
        ->assertHasNoErrors();

    expect(FeedbackRating::first()->governorate_id)
        ->toBe($office->governorate_id)
        ->not->toBe($otherGovernorate->id);
});

/* ===================== أمر التنظيف التلقائي ===================== */

it('يحذف المحاولات المرفوضة الأقدم من مدة الاحتفاظ ويُبقي الأحدث', function () {
    $days = (int) config('feedback.rejected_retention_days');

    $old = FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'duplicate_window']);
    $old->forceFill(['created_at' => now()->subDays($days + 1)])->save();

    $onEdge = FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'honeypot']);
    $onEdge->forceFill(['created_at' => now()->subDays($days - 1)])->save();

    $fresh = FeedbackRejectedAttempt::create(['type' => 'suggestion', 'reason' => 'rate_limit']);

    $this->artisan('feedback:prune-rejected')->assertSuccessful();

    expect(FeedbackRejectedAttempt::pluck('id')->all())
        ->not->toContain($old->id)          // القديم اتحذف
        ->toContain($onEdge->id)            // اللي لسه داخل المدة باقي
        ->toContain($fresh->id);
});

it('لا يفشل أمر التنظيف على جدول فارغ', function () {
    $this->artisan('feedback:prune-rejected')->assertSuccessful();

    expect(FeedbackRejectedAttempt::count())->toBe(0);
});
