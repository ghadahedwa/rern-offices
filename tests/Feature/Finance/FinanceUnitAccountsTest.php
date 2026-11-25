<?php

use App\Livewire\Finance\Total;
use App\Livewire\Finance\MonthEntry;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * الحسابات الثلاثة على الوحدات (طلب العميل 2026-11-15 — المرحلة ١): الميكنة ووزارة المالية تُدخلهما
 * الوحدات كأبنية المحاكم، وصفحة الوحدة تبويبٌ لكل حساب، وصفحة الحساب إجمالي الوحدات.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // ٥ نوفمبر ٢٠٢٦ — تاريخٌ في الماضي (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function uaUnit(string $city): FinanceUnit
{
    return FinanceUnit::where('name', 'like', '%'.$city)->sole();
}

function uaUser(array $abilities, array $cities = []): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }
    $role = Role::findOrCreate('ua-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->financeUnits()->sync(collect($cities)->map(fn ($c) => uaUnit($c)->id)->all());

    return $user;
}

function uaEnter(FinanceUnit $unit, string $account = 'mechanization', string $amount = '1000', string $order = '4471')
{
    return Livewire::test(MonthEntry::class, ['account' => $account, 'financeUnit' => $unit])
        ->set('amount', $amount)
        ->set('orders.0.number', $order)
        ->set('orders.0.issued_on', '2026-11-03')
        ->set('picked.0', [UploadedFile::fake()->image('o.jpg')])
        ->call('save');
}

const UA_ENTRY = ['finance.units.view', 'finance.units.create'];

// ── الإدخال ──────────────────────────────────────────────

it('تُدخل الوحدة الميكنة ووزارة المالية لنفسها وتعود إلى تبويب حسابها', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بطنطا']));
    $tanta = uaUnit('بطنطا');

    uaEnter($tanta, 'mechanization', '1500')
        ->assertHasNoErrors()
        ->assertRedirect(route('finance.unit', ['financeUnit' => $tanta, 'account' => 'mechanization', 'year' => 2026]));
    uaEnter($tanta, 'ministry-of-finance', '700', '99')->assertHasNoErrors();

    $months = FinanceMonth::with('account')->get();
    expect($months)->toHaveCount(2)
        ->and($months->pluck('finance_unit_id')->unique()->all())->toBe([$tanta->id])
        ->and($months->pluck('account.key')->sort()->values()->all())->toBe(['mechanization', 'ministry_of_finance']);
});

it('لا تُدخل وحدةٌ حساباً لوحدةٍ ليست لها', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بطنطا']));

    $this->get(route('finance.unit-entry', [uaUnit('بالقاهرة'), 'mechanization']))->assertForbidden();
    $this->get(route('finance.unit', [uaUnit('بالقاهرة'), 'mechanization']))->assertForbidden();
});

it('لكل وحدة شهورها في كل حساب — والحسابات منفصلة', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بالقاهرة', 'بطنطا']));

    uaEnter(uaUnit('بالقاهرة'), 'mechanization')->assertHasNoErrors();

    Livewire::test(MonthEntry::class, ['account' => 'mechanization', 'financeUnit' => uaUnit('بالقاهرة')])->assertSet('month', '2026-11-01');
    Livewire::test(MonthEntry::class, ['account' => 'mechanization', 'financeUnit' => uaUnit('بطنطا')])->assertSet('month', '2026-10-01');
    Livewire::test(MonthEntry::class, ['account' => 'ministry-of-finance', 'financeUnit' => uaUnit('بالقاهرة')])->assertSet('month', '2026-10-01');
});

it('لا يُدخَل حساب وحداتٍ بلا وحدة', function () {
    $this->actingAs(uaUser([...UA_ENTRY, 'finance.total.view', 'finance.units.create'], ['بالقاهرة']));

    $this->get(route('finance.entry', 'mechanization'))->assertNotFound();
    $this->get(route('finance.entry', 'ministry-of-finance'))->assertNotFound();
    $this->get(route('finance.entry', 'telecom'))->assertNotFound();
});

// ── صفحة الوحدة ──────────────────────────────────────────

it('صفحة الوحدة تبويبٌ لكل حساب، وكل تبويب أرقام وحدته في حسابه وحده', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بالقاهرة', 'بطنطا']));
    uaEnter(uaUnit('بالقاهرة'), 'mechanization', '111111')->assertHasNoErrors();
    uaEnter(uaUnit('بطنطا'), 'mechanization', '222222')->assertHasNoErrors();
    uaEnter(uaUnit('بالقاهرة'), 'ministry-of-finance', '333333', '55')->assertHasNoErrors();

    $cairo = uaUnit('بالقاهرة');
    $this->get(route('finance.unit', [$cairo, 'mechanization']))
        ->assertOk()
        ->assertSee('href="'.route('finance.unit', [$cairo, 'court-buildings']).'"', false)
        ->assertSee('href="'.route('finance.unit', [$cairo, 'ministry-of-finance']).'"', false)
        ->assertSee('111,111')
        ->assertDontSee('222,222')
        ->assertDontSee('333,333');

    $this->get(route('finance.unit', [$cairo, 'ministry-of-finance']))->assertSee('333,333')->assertDontSee('111,111');
});

it('زرّ الإدخال وروابط التصحيح في تبويب الوحدة على راوتاتها', function () {
    $this->actingAs(uaUser([...UA_ENTRY, 'finance.edit'], ['بالقاهرة']));
    $cairo = uaUnit('بالقاهرة');

    $this->get(route('finance.unit', [$cairo, 'mechanization']))
        ->assertSee(route('finance.unit-entry', [$cairo, 'mechanization']), false);

    uaEnter($cairo, 'mechanization')->assertHasNoErrors();
    $this->get(route('finance.unit', [$cairo, 'mechanization']))
        ->assertSee(route('finance.unit-correct', [$cairo, 'mechanization', '2026-10']), false);
});

// ── صفحة الحساب = إجمالي الوحدات ─────────────────────────

it('صفحة الميكنة إجمالي الوحدات بالشهور، ورابط الوحدة على تبويب الميكنة لمن يفتحها', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بالقاهرة', 'بطنطا']));
    uaEnter(uaUnit('بالقاهرة'), 'mechanization', '8000')->assertHasNoErrors();
    uaEnter(uaUnit('بطنطا'), 'mechanization', '4000', '9')->assertHasNoErrors();

    $this->actingAs(uaUser(['finance.total.view', 'finance.units.view'], ['بالقاهرة']));
    Livewire::test(Total::class)
        ->assertSee('8,000')->assertSee('4,000')->assertSee('12,000')
        ->assertSee(__('home.fin_units_of', ['entered' => 2, 'count' => 6]))
        ->assertSeeHtml('href="'.route('finance.unit', [uaUnit('بالقاهرة'), 'mechanization']).'"')
        ->assertDontSeeHtml('href="'.route('finance.unit', [uaUnit('بطنطا'), 'mechanization']).'"');

    // وزارة المالية بلا أرقام الميكنة
    $units = \App\Models\FinanceUnit::ordered()->get();
    expect(\App\Support\Finance\UnitsTotal::build(\App\Models\FinanceAccount::byKey('ministry_of_finance'), 2026, $units)['yearTotal'])->toBe(0)
        ->and(\App\Support\Finance\UnitsTotal::build(\App\Models\FinanceAccount::byKey('mechanization'), 2026, $units)['yearTotal'])->toBe(1200000);
});

it('صورة أمر دفع شهر وحدةٍ في الميكنة: لعارض الإجمالي ولصاحب الوحدة وحدهما', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بالقاهرة']));
    uaEnter(uaUnit('بالقاهرة'), 'mechanization')->assertHasNoErrors();
    $file = FinanceAttachment::sole();

    $this->actingAs(uaUser(['finance.total.view']));
    $this->get(route('finance.attachments.show', $file))->assertOk();

    $this->actingAs(uaUser(['finance.units.view'], ['بطنطا']));
    $this->get(route('finance.attachments.show', $file))->assertForbidden();

    $this->actingAs(uaUser(['finance.units.view'], ['بالقاهرة']));
    $this->get(route('finance.attachments.show', $file))->assertOk();
});

it('ملاحظات صفحة إجمالي الميكنة بلا وحدة، ولا تظهر في صفحة الوحدة', function () {
    $this->actingAs(uaUser([...UA_ENTRY, 'finance.total.view'], ['بالقاهرة']));

    Livewire::test(Total::class)
        ->set('noteDrafts.mechanization@2026-10', 'ملاحظة الإجمالي')
        ->call('addNote', 'mechanization@2026-10')
        ->assertHasNoErrors()
        // الإجمالي حتى الشهر الجاري — لا «الشهر التالي» لدفترٍ بلا وحدة (لا شهور له فيبقى أكتوبر)
        ->set('noteDrafts.mechanization@2026-11', 'ملاحظة نوفمبر')
        ->call('addNote', 'mechanization@2026-11')
        ->assertHasNoErrors();

    expect(\App\Models\FinanceNote::whereNotNull('finance_unit_id')->exists())->toBeFalse()
        ->and(\App\Models\FinanceNote::count())->toBe(2);
    $this->get(route('finance.unit', [uaUnit('بالقاهرة'), 'mechanization']))->assertDontSee('ملاحظة الإجمالي');
});

// ── شريط «المطلوب إدخاله الآن» ───────────────────────────

it('شريط الوحدة: كارتٌ لكل حساب بحالة دفتره وزرّ إدخاله', function () {
    $this->actingAs(uaUser(UA_ENTRY, ['بطنطا']));
    $tanta = uaUnit('بطنطا');

    uaEnter($tanta, 'mechanization', '1000')->assertHasNoErrors();
    uaEnter($tanta, 'mechanization', '2345', '4472')->assertHasNoErrors(); // حتى نوفمبر (الشهر الجاري)

    $page = $this->get(route('finance.unit', $tanta))->assertOk()
        ->assertSee(__('home.fin_strip_title'))
        ->assertSee(__('home.fin_strip_count', ['done' => 1, 'count' => 4]))
        // الميكنة مُدخَلة حتى الشهر الجاري
        ->assertSee(__('home.fin_strip_through', ['month' => 'نوفمبر 2026']))
        ->assertSee('2,345')
        ->assertSee(__('home.fin_strip_opens', ['month' => 'ديسمبر 2026']))
        // اتصالات بلا رصيد افتتاحي
        ->assertSee(__('home.fin_strip_opening_first'))
        ->assertSee(__('home.fin_strip_opening_write'));

    // الثلاثة الباقية تنتظر أكتوبر بزرّ إدخالها
    foreach (['court-buildings', 'ministry-of-finance'] as $slug) {
        $page->assertSee('href="'.route('finance.unit-entry', [$tanta, $slug]).'"', false);
    }
    $page->assertDontSee('href="'.route('finance.unit-entry', [$tanta, 'mechanization']).'"', false)
        ->assertDontSee('href="'.route('finance.unit-entry', [$tanta, 'telecom']).'"', false);
});

it('شريط الوحدة للعارض بلا أزرار، ولا شريط في صفحة الإجمالي', function () {
    $this->actingAs(uaUser(['finance.units.view', 'finance.total.view'], ['بطنطا']));
    $tanta = uaUnit('بطنطا');

    $this->get(route('finance.unit', $tanta))->assertOk()
        ->assertSee(__('home.fin_strip_pending'))
        ->assertDontSee(__('home.fin_strip_enter', ['month' => 'أكتوبر 2026']))
        ->assertDontSee(__('home.fin_strip_opening_write'));

    $this->get(route('finance.total'))->assertOk()->assertDontSee(__('home.fin_strip_title'));
});
