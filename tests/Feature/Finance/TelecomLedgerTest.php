<?php

use App\Livewire\Finance\MonthEntry;
use App\Livewire\Finance\Unit;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceOpening;
use App\Models\FinanceUnit;
use App\Models\User;
use App\Support\Finance\FinanceHistory;
use App\Support\Finance\TelecomLedger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * اتصالات على الوحدات الحسابية (المرحلة ٢ · 2026-11-15): لكل وحدة رصيدها الافتتاحي وسلسلة أرصدتها.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // تاريخٌ في الماضي — انظر MonthEntryTest (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function telUnit(string $city = 'بالقاهرة'): FinanceUnit
{
    return FinanceUnit::where('name', 'like', '%'.$city)->sole();
}

function telUser(array $abilities = ['finance.units.view', 'finance.units.create', 'finance.edit', 'finance.delete'], array $cities = ['بالقاهرة']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-tel-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->financeUnits()->sync(collect($cities)->map(fn ($c) => telUnit($c)->id)->all());

    return $user;
}

function telAccount(): FinanceAccount
{
    return FinanceAccount::byKey('telecom');
}

function setOpening(int $value = 5000, string $city = 'بالقاهرة'): void
{
    FinanceOpening::create(['finance_account_id' => telAccount()->id, 'finance_unit_id' => telUnit($city)->id, 'amount' => $value]);
}

function openingOf(string $city = 'بالقاهرة'): ?int
{
    return telAccount()->openingFor(telUnit($city)->id)?->amount;
}

function telPage(string $city = 'بالقاهرة')
{
    return Livewire::test(Unit::class, ['financeUnit' => telUnit($city), 'account' => 'telecom']);
}

function telUrl(string $city = 'بالقاهرة'): string
{
    return route('finance.unit', [telUnit($city), 'telecom']);
}

function telClosing(string $city = 'بالقاهرة'): int
{
    return TelecomLedger::closingBalance(telAccount(), telUnit($city)->id);
}

function telEntry(string $income, string $expense, ?string $period = null, string $city = 'بالقاهرة')
{
    $params = ['account' => 'telecom', 'financeUnit' => telUnit($city)] + ($period ? ['period' => $period] : []);
    $c = Livewire::test(MonthEntry::class, $params)->set('income', $income)->set('expense', $expense);

    if ($period === null) {
        $c->set('orders.0.number', '5080')
            ->set('orders.0.issued_on', '2026-11-02')
            ->set('picked.0', [UploadedFile::fake()->image('o.jpg')]);
    }

    return $c;
}

// ── الرصيد الافتتاحي ─────────────────────────────────────

it('يطلب رصيد الوحدة الافتتاحي قبل أول شهر', function () {
    $this->actingAs(telUser());

    $this->get(telUrl())->assertSee(__('home.fin_opening_needed_title'));

    Livewire::test(MonthEntry::class, ['account' => 'telecom', 'financeUnit' => telUnit()])
        ->assertSet('month', null)
        ->assertSee(__('home.fin_opening_needed_first'));

    $this->get(telUrl())->assertDontSee(route('finance.unit-entry', [telUnit(), 'telecom']), false);
});

it('يحفظ رصيد الوحدة الافتتاحي مرة واحدة ولا يستبدله — ولا يمسّ وحدةً أخرى', function () {
    $user = telUser(cities: ['بالقاهرة', 'بطنطا']);
    $this->actingAs($user);

    telPage()->set('openingInput', '٥٬٠٠٠')->call('saveOpening')->assertHasNoErrors();

    $opening = telAccount()->openingFor(telUnit()->id);
    expect($opening->amount)->toBe(5000)
        ->and($opening->set_by)->toBe($user->id)
        ->and(openingOf('بطنطا'))->toBeNull();

    telPage()->set('openingInput', '9000')->call('saveOpening');
    expect(openingOf())->toBe(5000);

    // طنطا ما زالت تطلب رصيدها
    $this->get(telUrl('بطنطا'))->assertSee(__('home.fin_opening_needed_title'));
    $this->get(telUrl())->assertDontSee(__('home.fin_opening_needed_title'));
});

it('يمنع كتابة الرصيد الافتتاحي بلا إدخال الوحدات، ولا تُفتح وحدةٌ ليست للمستخدم', function () {
    $this->actingAs(telUser(['finance.units.view']));

    telPage()->set('openingInput', '5000')->call('saveOpening')->assertForbidden();
    expect(openingOf())->toBeNull();

    telPage('بطنطا')->assertForbidden();
});

it('لا يُكتب رصيدٌ افتتاحي في صفحة إجمالي اتصالات (لا دفتر بلا وحدة)', function () {
    Permission::findOrCreate('finance.total.view', 'web');
    Permission::findOrCreate('finance.units.create', 'web');
    $user = tap(User::factory()->create())->givePermissionTo(['finance.total.view', 'finance.units.create']);
    $this->actingAs($user);

    // صفحة الإجمالي العام بلا خانة رصيد ولا إجراءٍ له
    $this->get(route('finance.total'))->assertOk()->assertDontSee(__('home.fin_opening_save'));
    expect(method_exists(\App\Livewire\Finance\Total::class, 'saveOpening'))->toBeFalse();

    expect(FinanceOpening::count())->toBe(0);
});

it('لا تُحذف وحدةٌ لها رصيدٌ افتتاحي', function () {
    setOpening();

    expect(telUnit()->isInUse())->toBeTrue()
        ->and(telUnit('بطنطا')->isInUse())->toBeFalse();
});

// ── الإدخال ──────────────────────────────────────────────

it('يحفظ أكتوبر بإيراده ومنصرفه ويحسب الرصيد من افتتاحي الوحدة', function () {
    $this->actingAs(telUser());
    setOpening();

    telEntry('10000', '7000')
        ->assertSee(__('home.fin_carried_from', ['from' => __('home.fin_opening_balance')]))
        ->call('review')->assertHasNoErrors()
        ->call('save')->assertRedirect();

    $month = FinanceMonth::sole();
    expect($month->income)->toBe(10000)
        ->and($month->amount)->toBe(7000)
        ->and($month->finance_unit_id)->toBe(telUnit()->id)
        ->and(telClosing())->toBe(8000);

    $this->get(telUrl())->assertSeeInOrder(['5,000', '10,000', '7,000', '8,000']);
});

it('سلسلة كل وحدة مستقلة: رصيد القاهرة لا يغطي منصرف طنطا', function () {
    $this->actingAs(telUser(cities: ['بالقاهرة', 'بطنطا']));
    setOpening(50000);
    setOpening(100, 'بطنطا');

    telEntry('0', '201', city: 'بطنطا')->call('review')
        ->assertHasErrors('expense')
        ->assertSee(__('home.fin_expense_over_available', ['available' => '100']));

    telEntry('50', '150', city: 'بطنطا')->call('save')->assertHasNoErrors();
    telEntry('0', '1000')->call('save')->assertHasNoErrors();

    expect(telClosing('بطنطا'))->toBe(0)
        ->and(telClosing())->toBe(49000);
});

it('يرفض منصرفاً أكبر من المتاح ويقبل ما يساويه', function () {
    $this->actingAs(telUser());
    setOpening();

    telEntry('10000', '15001')->call('review')
        ->assertHasErrors('expense')
        ->assertSee(__('home.fin_expense_over_available', ['available' => '15,000']));
    expect(FinanceMonth::count())->toBe(0);

    telEntry('10000', '15000')->call('save')->assertHasNoErrors();
    expect(telClosing())->toBe(0);
});

it('يحسب نوفمبر من مرحَّل أكتوبر', function () {
    $this->actingAs(telUser());
    setOpening();
    telEntry('10000', '7000')->call('save');

    telEntry('1000', '9001')->call('review')->assertHasErrors('expense');
    telEntry('1000', '9000')->call('save')->assertHasNoErrors();

    expect(telClosing())->toBe(0);
});

it('يحفظ شهراً منصرفه صفر بلا أمر دفع', function () {
    $this->actingAs(telUser());
    setOpening();

    Livewire::test(MonthEntry::class, ['account' => 'telecom', 'financeUnit' => telUnit()])
        ->set('income', '3000')->set('expense', '0')->set('noOrder', true)
        ->call('save')->assertHasNoErrors();

    expect(FinanceMonth::sole()->paymentOrders)->toHaveCount(0);
});

// ── التصحيح ──────────────────────────────────────────────

it('يرفض تصحيح أكتوبر إن صار نوفمبر سالباً، ويسمّيه', function () {
    $this->actingAs(telUser());
    setOpening();
    telEntry('10000', '7000')->call('save');   // 8,000
    telEntry('12000', '14000')->call('save');  // 6,000

    telEntry('3000', '7000', '2026-10')
        ->set('reason', 'الإيراد الصحيح أقل')
        ->call('save')
        ->assertHasErrors('expense')
        ->assertSee(__('home.fin_correction_negative', ['month' => 'نوفمبر 2026', 'balance' => '-1,000']));

    expect(FinanceMonth::orderBy('month')->first()->income)->toBe(10000);
});

it('يصحّح أكتوبر فيتبعه رصيد نوفمبر، والسجلّ يسمّي الإيراد', function () {
    $this->actingAs(telUser());
    setOpening();
    telEntry('10000', '7000')->call('save');
    telEntry('12000', '14000')->call('save');

    telEntry('11000', '7000', '2026-10')->set('reason', 'الإيراد كُتب ناقصاً')->call('save')->assertHasNoErrors();

    expect(telClosing())->toBe(7000);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['before']['income'])->toBe(10000)
        ->and($log->properties['after']['income'])->toBe(11000);

    $this->get(telUrl())->assertSeeInOrder([__('home.fin_income').':', '10,000', '11,000']);
});

it('يصحّح رصيد الوحدة الافتتاحي بسببٍ ويرفض ما يُنتج سالباً', function () {
    $this->actingAs(telUser(cities: ['بالقاهرة', 'بطنطا']));
    setOpening();
    setOpening(777, 'بطنطا');
    telEntry('10000', '15000')->call('save'); // أكتوبر يصير صفراً

    telPage()
        ->call('startOpeningCorrection')
        ->set('openingInput', '4000')
        ->set('openingReason', 'الرصيد الصحيح أقل')
        ->call('correctOpening')
        ->assertHasErrors('openingInput');
    expect(openingOf())->toBe(5000);

    telPage()
        ->call('startOpeningCorrection')
        ->set('openingInput', '6000')
        ->call('correctOpening')
        ->assertHasErrors('openingReason');

    telPage()
        ->call('startOpeningCorrection')
        ->set('openingInput', '6000')
        ->set('openingReason', 'كشف البنك بتاريخ 30 سبتمبر')
        ->call('correctOpening')
        ->assertHasNoErrors();

    expect(openingOf())->toBe(6000)
        ->and(openingOf('بطنطا'))->toBe(777)
        ->and(telClosing())->toBe(1000);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['month'])->toBe(FinanceHistory::OPENING)
        ->and($log->properties['unit'])->toBe(telUnit()->id);

    $this->get(telUrl())->assertSee('كشف البنك بتاريخ 30 سبتمبر');
    // سجلّ رصيد القاهرة لا يظهر في طنطا
    $this->get(telUrl('بطنطا'))->assertDontSee('كشف البنك بتاريخ 30 سبتمبر');
});

it('يمنع تصحيح الرصيد الافتتاحي بلا finance.edit', function () {
    $this->actingAs(telUser(['finance.units.view', 'finance.units.create']));
    setOpening();

    telPage()->call('startOpeningCorrection')->assertForbidden();
    telPage()
        ->set('openingInput', '1')->set('openingReason', 'سببٌ كافٍ')
        ->call('correctOpening')->assertForbidden();

    expect(openingOf())->toBe(5000);
});
