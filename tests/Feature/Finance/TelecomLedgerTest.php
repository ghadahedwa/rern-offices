<?php

use App\Livewire\Finance\MonthEntry;
use App\Livewire\Finance\Telecom;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
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

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // تاريخٌ في الماضي — انظر MonthEntryTest (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function telUser(array $abilities = ['finance.view', 'finance.create', 'finance.edit', 'finance.delete']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-tel-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

function telAccount(): FinanceAccount
{
    return FinanceAccount::byKey('telecom');
}

function setOpening(int $value = 5000): void
{
    telAccount()->update(['opening_balance' => $value]);
}

function telEntry(string $income, string $expense, ?string $period = null)
{
    $params = ['account' => 'telecom'] + ($period ? ['period' => $period] : []);
    $c = Livewire::test(MonthEntry::class, $params)->set('income', $income)->set('expense', $expense);

    if ($period === null) {
        $c->set('orders.0.number', '5080')
            ->set('orders.0.issued_on', '2026-11-02')
            ->set('picked.0', [UploadedFile::fake()->image('o.jpg')]);
    }

    return $c;
}

// ── الرصيد الافتتاحي ─────────────────────────────────────

it('يطلب الرصيد الافتتاحي قبل أول شهر', function () {
    $this->actingAs(telUser());

    $this->get(route('finance.telecom'))->assertSee(__('home.fin_opening_needed_title'));

    Livewire::test(MonthEntry::class, ['account' => 'telecom'])
        ->assertSet('month', null)
        ->assertSee(__('home.fin_opening_needed_first'));

    $this->get(route('finance.telecom'))->assertDontSee(route('finance.entry', 'telecom'), false);
});

it('يحفظ الرصيد الافتتاحي مرة واحدة ولا يستبدله', function () {
    $user = telUser();
    $this->actingAs($user);

    Livewire::test(Telecom::class)->set('openingInput', '٥٬٠٠٠')->call('saveOpening')->assertHasNoErrors();

    $account = telAccount();
    expect($account->opening_balance)->toBe(5000)
        ->and($account->opening_set_by)->toBe($user->id);

    Livewire::test(Telecom::class)->set('openingInput', '9000')->call('saveOpening');
    expect(telAccount()->opening_balance)->toBe(5000);
});

it('يمنع كتابة الرصيد الافتتاحي بلا صلاحية الإدخال', function () {
    $this->actingAs(telUser(['finance.view']));

    Livewire::test(Telecom::class)->set('openingInput', '5000')->call('saveOpening')->assertForbidden();
    expect(telAccount()->opening_balance)->toBeNull();
});

// ── الإدخال ──────────────────────────────────────────────

it('يحفظ أكتوبر بإيراده ومنصرفه ويحسب الرصيد من الافتتاحي', function () {
    $this->actingAs(telUser());
    setOpening();

    telEntry('10000', '7000')
        ->assertSee(__('home.fin_carried_from', ['from' => __('home.fin_opening_balance')]))
        ->call('review')->assertHasNoErrors()
        ->call('save')->assertRedirect();

    $month = FinanceMonth::sole();
    expect($month->income)->toBe(10000)
        ->and($month->amount)->toBe(7000)
        ->and(TelecomLedger::closingBalance(telAccount()))->toBe(8000);

    $this->get(route('finance.telecom'))->assertSeeInOrder(['5,000', '10,000', '7,000', '8,000']);
});

it('يرفض منصرفاً أكبر من المتاح ويقبل ما يساويه', function () {
    $this->actingAs(telUser());
    setOpening();

    telEntry('10000', '15001')->call('review')
        ->assertHasErrors('expense')
        ->assertSee(__('home.fin_expense_over_available', ['available' => '15,000']));
    expect(FinanceMonth::count())->toBe(0);

    telEntry('10000', '15000')->call('save')->assertHasNoErrors();
    expect(TelecomLedger::closingBalance(telAccount()))->toBe(0);
});

it('يحسب نوفمبر من مرحَّل أكتوبر', function () {
    $this->actingAs(telUser());
    setOpening();
    telEntry('10000', '7000')->call('save');

    telEntry('1000', '9001')->call('review')->assertHasErrors('expense');
    telEntry('1000', '9000')->call('save')->assertHasNoErrors();

    expect(TelecomLedger::closingBalance(telAccount()))->toBe(0);
});

it('يحفظ شهراً منصرفه صفر بلا أمر دفع', function () {
    $this->actingAs(telUser());
    setOpening();

    Livewire::test(MonthEntry::class, ['account' => 'telecom'])
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

    expect(TelecomLedger::closingBalance(telAccount()))->toBe(7000);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['before']['income'])->toBe(10000)
        ->and($log->properties['after']['income'])->toBe(11000);

    $this->get(route('finance.telecom'))->assertSeeInOrder([__('home.fin_income').':', '10,000', '11,000']);
});

it('يصحّح الرصيد الافتتاحي بسببٍ ويرفض ما يُنتج سالباً', function () {
    $this->actingAs(telUser());
    setOpening();
    telEntry('10000', '15000')->call('save'); // أكتوبر يصير صفراً

    Livewire::test(Telecom::class)
        ->call('startOpeningCorrection')
        ->set('openingInput', '4000')
        ->set('openingReason', 'الرصيد الصحيح أقل')
        ->call('correctOpening')
        ->assertHasErrors('openingInput');
    expect(telAccount()->opening_balance)->toBe(5000);

    Livewire::test(Telecom::class)
        ->call('startOpeningCorrection')
        ->set('openingInput', '6000')
        ->call('correctOpening')
        ->assertHasErrors('openingReason');

    Livewire::test(Telecom::class)
        ->call('startOpeningCorrection')
        ->set('openingInput', '6000')
        ->set('openingReason', 'كشف البنك بتاريخ 30 سبتمبر')
        ->call('correctOpening')
        ->assertHasNoErrors();

    expect(telAccount()->opening_balance)->toBe(6000)
        ->and(TelecomLedger::closingBalance(telAccount()))->toBe(1000);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['month'])->toBe(FinanceHistory::OPENING);

    $this->get(route('finance.telecom'))->assertSee('كشف البنك بتاريخ 30 سبتمبر');
});

it('يمنع تصحيح الرصيد الافتتاحي بلا finance.edit', function () {
    $this->actingAs(telUser(['finance.view', 'finance.create']));
    setOpening();

    Livewire::test(Telecom::class)->call('startOpeningCorrection')->assertForbidden();
    Livewire::test(Telecom::class)
        ->set('openingInput', '1')->set('openingReason', 'سببٌ كافٍ')
        ->call('correctOpening')->assertForbidden();

    expect(telAccount()->opening_balance)->toBe(5000);
});
