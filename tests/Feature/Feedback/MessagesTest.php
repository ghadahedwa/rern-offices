<?php

use App\Livewire\Feedback\Message;
use App\Livewire\Feedback\Rating;
use App\Models\FeedbackMessage;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * رسائل المواطنين النصية — الجزء الثاني: «شكر موظف أو الإشادة بمتميز».
 * الهوية والمقر واسم الموظف إلزامية فيه (قرار العميل)، والحماية نفسها
 * (قفل الأسبوع لكل نوع ومقر) من InteractsWithFeedbackGate.
 */
function thanksForm(Office $office, array $overrides = [])
{
    $values = $overrides + [
        'name'          => 'مواطن شاكر',
        'national_id'   => '29001010101234',
        'phone'         => '01012345678',
        'employee_name' => 'أحمد محمود',
        'body'          => 'أنهى معاملتي بسرعة وأحسن معاملتي.',
    ];

    $component = Livewire::test(Message::class, ['messageType' => 'thanks'])
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id);

    foreach ($values as $key => $value) {
        $component->set($key, $value);
    }

    return $component;
}

it('تعرض الصفحة الرئيسية كارت شكر الموظف وتفتح صفحته', function () {
    $this->get(route('feedback'))
        ->assertOk()
        ->assertSee(route('feedback.message', 'thanks'))
        ->assertSee('شكر موظف أو الإشادة بمتميز');

    $this->get(route('feedback.message', 'thanks'))->assertOk()->assertSee('رسالة الشكر', false);
});

it('كل فورمات البوابة فيها رابط رجوع إلى الكروت', function (string $route, array $params) {
    $html = $this->get(route($route, $params))->assertOk()->getContent();

    expect($html)->toContain('class="fb-back"')
        ->and($html)->toMatch('#href="'.preg_quote(route('feedback'), '#').'"[^>]*class="fb-back"#');
})->with([
    'التقييم'    => ['feedback.rating', []],
    'الاقتراح'   => ['feedback.suggestion', []],
    'الإلكترونية' => ['feedback.digital', []],
    'الشكر'      => ['feedback.message', ['thanks']],
]);

it('نوع رسالة مجهول في الرابط لا يفتح صفحة', function () {
    $this->get('/feedback/unknown')->assertNotFound();

    // والمكوّن نفسه يرفضه لو وصله بغير الراوت
    Livewire::test(Message::class, ['messageType' => 'unknown'])->assertStatus(404);
});

it('يحفظ رسالة الشكر بنوعها ومحافظة مقرها', function () {
    $office = Office::factory()->public()->create();

    thanksForm($office)->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    $msg = FeedbackMessage::sole();
    expect($msg->type)->toBe('thanks')
        ->and($msg->office_id)->toBe($office->id)
        ->and($msg->governorate_id)->toBe($office->governorate_id)
        ->and($msg->employee_name)->toBe('أحمد محمود')
        ->and($msg->national_id)->toBe('29001010101234')
        ->and($msg->device_token)->not->toBeNull();
});

it('الهوية واسم الموظف ونص الرسالة إلزامية في الشكر', function (string $field) {
    $office = Office::factory()->public()->create();

    thanksForm($office, [$field => ''])->call('submit')->assertHasErrors($field);

    expect(FeedbackMessage::count())->toBe(0);
})->with(['name', 'national_id', 'phone', 'employee_name', 'body']);

it('المحافظة تُشتق من المقر لا مما يرسله المتصفح', function () {
    $office = Office::factory()->public()->create();
    $other = Governorate::factory()->create();

    $component = thanksForm($office);
    $component->set('governorate_id', $other->id)->set('office_id', $office->id)->call('submit');

    expect(FeedbackMessage::sole()->governorate_id)->toBe($office->governorate_id);
});

it('لا يقبل مقراً من نوع غير ظاهر للمواطن', function () {
    $hidden = Office::factory()->create(['type_id' => OfficeType::factory()->create(['is_public' => false])->id]);

    thanksForm($hidden)->call('submit')->assertHasErrors('office_id');

    expect(FeedbackMessage::count())->toBe(0);
});

it('نوع الرسالة مقفول لا يُغيَّر من المتصفح', function () {
    $office = Office::factory()->public()->create();

    expect(fn () => thanksForm($office)->set('messageType', 'rating'))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
});

it('يحجب رسالة شكر ثانية لنفس المقر خلال الأسبوع ويسجّل الرفض بنوعها', function () {
    $office = Office::factory()->public()->create();
    FeedbackMessage::factory()->create([
        'office_id' => $office->id, 'national_id' => '29001010101234', 'phone' => '01099999999',
        'created_at' => now()->subDays(2),
    ]);

    thanksForm($office)->assertSet('gateBlocked', true)->assertDontSee('إرسال رسالة الشكر');

    expect(FeedbackRejectedAttempt::sole()->type)->toBe('thanks');
});

it('قفل الشكر مستقل عن تقييم الخدمة في المقر نفسه — والعكس', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'national_id' => '29001010101234', 'created_at' => now()->subDay()]);

    thanksForm($office)->assertSet('gateBlocked', false)->call('submit')->assertSet('submitted', true);

    // وتقييمٌ جديد برقمٍ آخر لا يحجبه الشكر المرسَل للتو
    Livewire::test(Rating::class)
        ->set('national_id', '29505050201234')->set('phone', '01055555555')
        ->set('governorate_id', $office->governorate_id)->set('office_id', $office->id)
        ->assertSet('gateBlocked', false);
});

it('الرسالة المحذوفة إدارياً تظل حارسة للأسبوع', function () {
    $office = Office::factory()->public()->create();
    FeedbackMessage::factory()->create(['office_id' => $office->id, 'created_at' => now()->subDay()])->delete();

    thanksForm($office)->assertSet('gateBlocked', true);
});

it('صفحة الشكر تُصدر كوكي بصمة الجهاز مع التحميل', function () {
    $this->get(route('feedback.message', 'thanks'))->assertCookie('feedback_device');
});
