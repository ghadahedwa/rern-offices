<?php

use App\Livewire\Feedback\Rating;
use App\Livewire\Feedback\Suggestion;
use App\Livewire\FeedbackResults\RejectedAttempts;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\User;
use App\Services\FeedbackGate;
use App\Support\FeedbackDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * سجل رفض «التكرار» (2026-09-15): بلاغ من السيرفر — رفضٌ برقم قومي وهاتف لا يوجد لهما رأي.
 * كانت المطابقة ببصمة الجهاز مع رأي بهوية أخرى، والشاشة لا تقول ذلك. وضغطات
 * الانتقال بين الفورمات بعد الإرسال كانت تُسجَّل محاولات مرفوضة.
 */

uses(RefreshDatabase::class);

const MATCH_DEVICE = 'cccccccccccccccccccccccccccccccc';

function matchRating(Office $office, array $attributes = []): FeedbackRating
{
    return FeedbackRating::factory()->create(array_merge([
        'office_id'    => $office->id,
        'national_id'  => '29001010101234',
        'phone'        => '01012345678',
        'device_token' => MATCH_DEVICE,
        'created_at'   => now()->subHours(2),
    ], $attributes));
}

it('يسجّل أن الحجب ببصمة الجهاز ومع أي رأي حين تختلف الهوية المكتوبة', function () {
    $office = Office::factory()->public()->create();
    $prior = matchRating($office);

    Livewire::withCookies([FeedbackDevice::COOKIE => MATCH_DEVICE])->test(Rating::class)
        ->set('national_id', '29505050201234')
        ->set('phone', '01099999999')
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->assertSet('gateBlocked', true);

    $attempt = FeedbackRejectedAttempt::sole();
    expect($attempt->matchedKeys())->toBe(['device_token'])
        ->and($attempt->matched_id)->toBe($prior->id)
        ->and($attempt->matched_at->timestamp)->toBe($prior->created_at->timestamp)
        // الهوية المسجَّلة هي المكتوبة في المحاولة — ولهذا يلزم سبب المطابقة
        ->and($attempt->national_id)->toBe('29505050201234');
});

it('يسجّل كل المفاتيح التي طابقت، وفي رفض الإرسال المباشر أيضاً', function () {
    $office = Office::factory()->public()->create();
    $prior = matchRating($office, ['phone' => '01011111111']);

    $match = app(FeedbackGate::class)->duplicateMatch(
        FeedbackGate::TYPE_RATING, '29001010101234', '01012345678', MATCH_DEVICE, $office->id,
    );
    expect($match['match']['matched_by'])->toBe('national_id,device_token')
        ->and($match['match']['matched_id'])->toBe($prior->id);

    Livewire::withCookies([FeedbackDevice::COOKIE => MATCH_DEVICE])->test(Rating::class)
        ->set('office_id', $office->id)           // حجبٌ تفاعلي يُسجَّل أول مرة
        ->set('gateBlocked', false)               // طلب متلاعَب يصل للإرسال مباشرة
        ->call('submit');

    expect(FeedbackRejectedAttempt::latest('id')->first()->matched_id)->toBe($prior->id);
});

it('لا يسجّل محاولة مرفوضة عند الوصول بزرّ الانتقال بين الفورمات — ويبقى الحجب', function () {
    $office = Office::factory()->public()->create();
    FeedbackSuggestion::factory()->create([
        'office_id' => $office->id, 'national_id' => '29001010101234', 'device_token' => MATCH_DEVICE,
    ]);

    session()->put('feedback.carry', [
        'name' => 'مواطن', 'national_id' => '29001010101234', 'phone' => '',
        'governorate_id' => $office->governorate_id, 'office_id' => $office->id,
    ]);

    Livewire::withQueryParams(['resume' => 1])->withCookies([FeedbackDevice::COOKIE => MATCH_DEVICE])
        ->test(Suggestion::class)
        ->assertSet('office_id', $office->id)
        ->assertSet('gateBlocked', true);

    expect(FeedbackRejectedAttempt::count())->toBe(0);
});

it('تعرض الشاشة المحافظة بجانب المقر وسبب المطابقة برابط لشاشة النوع', function () {
    $office = Office::factory()->public()->create(['name' => 'مقر الاختبار']);
    $prior = matchRating($office);
    FeedbackRejectedAttempt::create([
        'type' => 'rating', 'office_id' => $office->id, 'reason' => 'duplicate_window',
        'matched_by' => 'device_token', 'matched_id' => $prior->id, 'matched_at' => $prior->created_at,
    ]);

    Role::findOrCreate('super-admin', 'web');
    $admin = tap(User::factory()->create())->assignRole('super-admin');

    Livewire::actingAs($admin)->test(RejectedAttempts::class)
        ->assertSee('مقر الاختبار')
        // بجانب المقر نفسه — الاسم وحده يظهر في منسدلة الفلتر وفي الـtitle فلا يثبت شيئاً
        ->assertSee('· '.$office->governorate->name)
        ->assertSee(__('home.fr_match_device_token'))
        ->assertSee(\App\Support\LocalTime::stamp($prior->created_at))
        ->assertSee(route('feedback-results.ratings', ['gov' => $office->governorate_id, 'office' => $office->id]));
});
