<?php

use App\Livewire\Feedback\DigitalPlatforms;
use App\Livewire\Feedback\Rating;
use App\Models\FeedbackDigitalRating;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\Office;
use App\Services\FeedbackGate;
use App\Support\FeedbackDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/*
 * بوابة «تقييم المنصات الرقمية» — الفورم الثالث. الأسئلة ٢٠١–٢١٣ من استمارة الوزارة،
 * تظهر حسب الإجابات (عمود «انتقل إلى»)، والمخفي منها لا يُتحقق منه ولا يُحفظ.
 */

uses(RefreshDatabase::class);

const DIGITAL_DEVICE = 'dddddddddddddddddddddddddddddddd';

/** الفورم من جهاز بعينه وقد اختير المقر. */
function digitalForm(Office $office, string $device = DIGITAL_DEVICE)
{
    return Livewire::withCookies([FeedbackDevice::COOKIE => $device])
        ->test(DigitalPlatforms::class)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id);
}

/** مسار «حجزت» كاملاً: مشاكل + درجة + انتظار بعد الميعاد + شاهد الإعلان. */
function digitalBookedPath($component)
{
    return $component
        ->set('q201', 'booked')
        ->set('q202', 'misr_digital')
        ->set('q203', 'yes')
        ->set('q204', '  الموقع وقع وأنا بحجز  ')
        ->set('q205', 7)
        ->set('q206', 'waited')
        ->set('q207', '15_30')
        ->set('q211', 'yes')
        ->set('q212', ['register_no_delay', 'tawkeel_app'])
        ->set('q213', 'good');
}

/* ===================== البوابة ===================== */

it('تعرض صفحة البوابة الكروت الثلاثة ومنها تقييم المنصات الرقمية', function () {
    $this->get(route('feedback'))
        ->assertOk()
        ->assertSee(route('feedback.rating'))
        ->assertSee(route('feedback.suggestion'))
        ->assertSee(route('feedback.digital'))
        ->assertSee('تقييم المنصات الرقمية');
});

it('لا تُعرض الأسئلة قبل اختيار المقر، وأول سؤال وحده يظهر بعده', function () {
    $office = Office::factory()->public()->create();

    Livewire::test(DigitalPlatforms::class)->assertDontSee('يا ترى حجزت قبل ما تيجي');

    expect(digitalForm($office)->instance()->visibleQuestions())->toBe(['q201']);
});

/* ===================== المسار (انتقل إلى) ===================== */

it('يُظهر لكل مسار أسئلته وحدها كما في الاستمارة', function (array $answers, array $expected) {
    expect(FeedbackDigitalRating::visibleQuestions($answers))->toBe($expected);
})->with([
    'حجزت بلا مشاكل ودخل في ميعاده ولم يشاهد الإعلان' => [
        ['q201' => 'booked', 'q203' => 'no', 'q206' => 'on_time', 'q211' => 'no'],
        ['q201', 'q202', 'q203', 'q205', 'q206', 'q211'],
    ],
    'حجزت بمشاكل وانتظر بعد ميعاده وشاهد الإعلان' => [
        ['q201' => 'booked', 'q203' => 'yes', 'q206' => 'waited', 'q211' => 'yes'],
        ['q201', 'q202', 'q203', 'q204', 'q205', 'q206', 'q207', 'q211', 'q212', 'q213'],
    ],
    'بدون حجز ويعرف المنصة' => [
        ['q201' => 'walk_in', 'q208' => 'yes', 'q211' => 'no'],
        ['q201', 'q208', 'q209', 'q210', 'q211'],
    ],
    'بدون حجز ولا يعرف المنصة' => [
        ['q201' => 'walk_in', 'q208' => 'no'],
        ['q201', 'q208', 'q211'],
    ],
    // إجابات بقيت في حالة الفورم من مسار تُرك لا تُظهر أسئلة ذلك المسار
    'بدون حجز وإجابات قديمة من مسار الحجز' => [
        ['q201' => 'walk_in', 'q203' => 'yes', 'q206' => 'waited', 'q208' => 'no'],
        ['q201', 'q208', 'q211'],
    ],
    'لم يُجب أول سؤال — لا يظهر شيء بعده ولو بقيت إجابة q211' => [
        ['q211' => 'yes'],
        ['q201'],
    ],
]);

/* ===================== الحفظ ===================== */

it('يحفظ مسار الحجز كاملاً بالنص مشذَّباً والاختيارات المتعددة صفوفاً', function () {
    $office = Office::factory()->public()->create();

    digitalBookedPath(digitalForm($office))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $rating = FeedbackDigitalRating::with('choices')->sole();

    expect($rating)
        ->q201->toBe('booked')
        ->q202->toBe('misr_digital')
        ->q204->toBe('الموقع وقع وأنا بحجز')
        ->q205->toBe(7)
        ->q207->toBe('15_30')
        ->q213->toBe('good')
        // أسئلة المسار الآخر لم تُسأل: NULL لا «لا»
        ->q208->toBeNull()
        ->q210->toBeNull()
        ->device_token->toBe(DIGITAL_DEVICE)
        ->governorate_id->toBe($office->governorate_id)
        ->and($rating->choicesFor('q212'))->toEqualCanonicalizing(['register_no_delay', 'tawkeel_app'])
        ->and($rating->choicesFor('q209'))->toBe([]);
});

it('يحفظ مسار بدون حجز باختيار متعدد في q209', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)
        ->set('q201', 'walk_in')
        ->set('q208', 'yes')
        ->set('q209', ['misr_digital', 'tawkeel_app'])
        ->set('q210', 'مكنتش أعرف أستخدمه')
        ->set('q211', 'no')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $rating = FeedbackDigitalRating::with('choices')->sole();

    expect($rating)
        ->q202->toBeNull()
        ->q205->toBeNull()
        ->q213->toBeNull()
        ->q210->toBe('مكنتش أعرف أستخدمه')
        ->and($rating->choicesFor('q209'))->toEqualCanonicalizing(['misr_digital', 'tawkeel_app']);
});

it('لا يحفظ إجابات مسارٍ تُرك — مَن غيّر «حجزت» إلى «بدون حجز»', function () {
    $office = Office::factory()->public()->create();

    digitalBookedPath(digitalForm($office))
        ->set('q209', ['misr_digital'])            // لم يظهر أصلاً في مسار الحجز
        ->set('q201', 'walk_in')
        ->set('q208', 'no')
        ->set('q211', 'no')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $rating = FeedbackDigitalRating::with('choices')->sole();

    foreach (['q202', 'q203', 'q204', 'q205', 'q206', 'q207', 'q213'] as $hidden) {
        expect($rating->{$hidden})->toBeNull();
    }
    expect($rating->choices)->toBeEmpty();
});

/* ===================== التحقق ===================== */

it('يرفض الإرسال بلا إجابة أول سؤال', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)->call('submit')->assertHasErrors('q201')->assertSet('submitted', false);

    expect(FeedbackDigitalRating::count())->toBe(0);
});

it('يُلزم الأسئلة الظاهرة وحدها', function () {
    $office = Office::factory()->public()->create();

    // مسار الحجز بلا إجابات: كل ما ظهر مطلوب، وأسئلة المسار الآخر لا
    digitalForm($office)
        ->set('q201', 'booked')
        ->call('submit')
        ->assertHasErrors(['q202', 'q203', 'q205', 'q206', 'q211'])
        ->assertHasNoErrors(['q204', 'q207', 'q208', 'q209', 'q212']);
});

it('النص الحر اختياري حين يظهر', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)
        ->set('q201', 'walk_in')->set('q208', 'yes')->set('q209', ['misr_digital'])->set('q211', 'no')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FeedbackDigitalRating::sole()->q210)->toBeNull();
});

it('يقبل الصفر درجةً ويرفض ما فوق العشرة', function () {
    $office = Office::factory()->public()->create();

    digitalBookedPath(digitalForm($office))->set('q205', 11)->call('submit')->assertHasErrors('q205');

    // الصفر إجابة صحيحة لا «فارغ» — يُحفظ صفراً
    digitalBookedPath(digitalForm($office))->set('q205', 0)->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    expect(FeedbackDigitalRating::sole()->q205)->toBe(0);
});

it('يُلزم الاختيار المتعدد حين يظهر ويرفض اختياراً خارج القائمة', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)
        ->set('q201', 'walk_in')->set('q208', 'yes')->set('q209', [])->set('q211', 'no')
        ->call('submit')
        ->assertHasErrors('q209');

    digitalForm($office)
        ->set('q201', 'walk_in')->set('q208', 'yes')->set('q209', ['hacked'])->set('q211', 'no')
        ->call('submit')
        ->assertHasErrors('q209.0');

    expect(FeedbackDigitalRating::count())->toBe(0);
});

it('يرفض إجابة خارج قائمة السؤال', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)->set('q201', 'maybe')->call('submit')->assertHasErrors('q201');
});

it('يرفض مقراً غير ظاهر للمواطن', function () {
    $office = Office::factory()->create();   // نوع غير عام

    digitalForm($office)->set('q201', 'walk_in')->set('q208', 'no')->set('q211', 'no')
        ->call('submit')
        ->assertHasErrors('office_id');

    expect(FeedbackDigitalRating::count())->toBe(0);
});

/* ===================== الحماية ===================== */

it('يحجب تقييماً ثانياً للمنصات من نفس الجهاز لنفس المقر خلال الأسبوع', function () {
    $office = Office::factory()->public()->create();
    FeedbackDigitalRating::factory()->create(['office_id' => $office->id, 'device_token' => DIGITAL_DEVICE]);

    digitalForm($office)->assertSet('gateBlocked', true);
});

it('القفل منفصل بين الأنواع — تقييم الخدمة لا يحجب تقييم المنصات والعكس', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'device_token' => DIGITAL_DEVICE]);

    digitalForm($office)->assertSet('gateBlocked', false);

    FeedbackDigitalRating::factory()->create(['office_id' => $office->id, 'device_token' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee']);

    Livewire::withCookies([FeedbackDevice::COOKIE => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee'])
        ->test(Rating::class)
        ->set('governorate_id', $office->governorate_id)
        ->set('office_id', $office->id)
        ->assertSet('gateBlocked', false);
});

it('الرأي المحذوف إدارياً يظل حارساً للنافذة', function () {
    $office = Office::factory()->public()->create();
    FeedbackDigitalRating::factory()->create(['office_id' => $office->id, 'device_token' => DIGITAL_DEVICE])->delete();

    digitalForm($office)->assertSet('gateBlocked', true);
});

it('يرفض نوع رأي مجهول بدل السقوط إلى جدول التقييمات', function () {
    app(FeedbackGate::class)->duplicateRetryDate('unknown', null, null, DIGITAL_DEVICE, 1);
})->throws(InvalidArgumentException::class);

it('يسجّل الرفض بنوع digital ويعرضه باسمه في شاشة المرفوضات', function () {
    $office = Office::factory()->public()->create();

    digitalForm($office)->set('website', 'http://spam.example')->call('submit')->assertSet('submitted', true);

    expect(FeedbackRejectedAttempt::sole()->type)->toBe('digital')
        ->and(__('home.fr_type_digital'))->not->toBe('home.fr_type_digital')
        ->and(FeedbackGate::TYPES)->toContain('digital');
});

/* ===================== الانتقال بين الفورمات ===================== */

it('يعرض كل فورم رابطي الفورمين الآخرين', function (string $component, array $expectedRoutes) {
    $urls = collect(Livewire::test($component)->instance()->otherForms())->pluck('url')->all();

    expect($urls)->toBe(array_map(fn ($r) => route($r, ['resume' => 1]), $expectedRoutes));
})->with([
    'من التقييم'  => [Rating::class, ['feedback.suggestion', 'feedback.digital']],
    'من المقترح'  => [\App\Livewire\Feedback\Suggestion::class, ['feedback.rating', 'feedback.digital']],
    'من المنصات' => [DigitalPlatforms::class, ['feedback.rating', 'feedback.suggestion']],
]);

it('ينقل الهوية والمقر إلى فورم المنصات مع resume=1', function () {
    $office = Office::factory()->public()->create();

    session()->put('feedback.carry', [
        'name' => 'مواطن', 'national_id' => '', 'phone' => '01012345678',
        'governorate_id' => $office->governorate_id, 'office_id' => $office->id,
    ]);

    Livewire::withQueryParams(['resume' => 1])->test(DigitalPlatforms::class)
        ->assertSet('phone', '01012345678')
        ->assertSet('office_id', $office->id);
});
