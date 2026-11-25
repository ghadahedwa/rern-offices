<?php

use App\Livewire\Finance\MonthEntry;
use App\Livewire\Finance\Total;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceNote;
use App\Models\FinanceOpening;
use App\Models\FinanceUnit;
use App\Models\User;
use App\Support\Finance\UnitsTotal;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * «الإجمالي العام» (المرحلة ٣ · 2026-11-17): جداول الحسابات الأربعة في صفحة واحدة، واتصالات بالشكل «ب».
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // ٥ نوفمبر ٢٠٢٦ — تاريخٌ في الماضي (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function gtUnit(string $city): FinanceUnit
{
    return FinanceUnit::where('name', 'like', '%'.$city)->sole();
}

function gtUser(array $abilities, array $cities = []): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }
    $role = Role::findOrCreate('gt-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->financeUnits()->sync(collect($cities)->map(fn ($c) => gtUnit($c)->id)->all());

    return $user;
}

function gtTelecom(string $city, string $income, string $expense): void
{
    Livewire::test(MonthEntry::class, ['account' => 'telecom', 'financeUnit' => gtUnit($city)])
        ->set('income', $income)->set('expense', $expense)
        ->set('orders.0.number', '5080')
        ->set('orders.0.issued_on', '2026-11-02')
        ->set('picked.0', [UploadedFile::fake()->image('o.jpg')])
        ->call('save')->assertHasNoErrors();
}

function gtOpening(string $city, int $amount): void
{
    FinanceOpening::create([
        'finance_account_id' => FinanceAccount::byKey('telecom')->id,
        'finance_unit_id'    => gtUnit($city)->id,
        // بالجنيه ← قروش (Money)
        'amount'             => $amount * 100,
    ]);
}

it('يعرض جداول الحسابات الأربعة بالترتيب وكارتاً لكلٍّ منها', function () {
    $this->actingAs(gtUser(['finance.total.view']));

    $this->get(route('finance.total'))->assertOk()
        ->assertSeeInOrder([
            __('home.fin_tab_court_buildings'), __('home.fin_tab_mechanization'),
            __('home.fin_tab_telecom'), __('home.fin_tab_ministry_of_finance'),
            // ثم الجداول بالترتيب نفسه
            'id="fin-court-buildings"', 'id="fin-mechanization"', 'id="fin-telecom"', 'id="fin-ministry-of-finance"',
        ], false);
});

it('اتصالات: الخلية رصيد آخر الشهر وتحته الإيراد والمنصرف، وعمود الافتتاحي، والرصيد الآن مجموع الوحدات', function () {
    $this->actingAs(gtUser(['finance.units.view', 'finance.units.create'], ['بالقاهرة']));
    gtOpening('بالقاهرة', 5000);
    gtOpening('بطنطا', 100);
    gtTelecom('بالقاهرة', '10000', '7000'); // أكتوبر: 8,000

    $t = UnitsTotal::build(FinanceAccount::byKey('telecom'), 2026, FinanceUnit::ordered()->get());
    $cairo = gtUnit('بالقاهرة')->id;
    $tanta = gtUnit('بطنطا')->id;

    // القيم بالقروش
    expect($t['ledger']['start'][$cairo])->toBe(500000)
        ->and($t['ledger']['start'][$tanta])->toBe(10000)
        ->and($t['ledger']['startTotal'])->toBe(510000)
        ->and($t['rows'][0]['ledger'][$cairo])->toMatchArray(['balance' => 800000, 'income' => 1000000, 'expense' => 700000])
        // طنطا لم تُدخل أكتوبر: لا خلية لها، ورصيدها في عمود السنة افتتاحيُّها
        ->and($t['rows'][0]['ledger'])->not->toHaveKey($tanta)
        ->and($t['ledger']['year'][$tanta])->toMatchArray(['balance' => 10000, 'entered' => false])
        ->and($t['ledger']['yearTotal'])->toBe(['balance' => 810000, 'income' => 1000000, 'expense' => 700000])
        // وحدةٌ بلا افتتاحي «—» لا صفر
        ->and($t['ledger']['start'][gtUnit('بأسيوط')->id])->toBeNull();

    $this->actingAs(gtUser(['finance.total.view']));
    $this->get(route('finance.total'))->assertOk()
        ->assertSee(__('home.fin_opening_col'))
        ->assertSeeInOrder(['id="fin-telecom"', '5,000', '8,000', '+ 10,000', '− 7,000'], false)
        ->assertSeeInOrder([__('home.fin_tab_telecom').' — '.__('home.fin_balance_now_label'), '8,100'])
        ->assertSee(__('home.fin_ledger_cell_legend'));
});

it('رصيد أول السنة التالية مرحَّلٌ من آخر شهرٍ قبلها لا الافتتاحي', function () {
    $this->actingAs(gtUser(['finance.units.view', 'finance.units.create'], ['بالقاهرة']));
    gtOpening('بالقاهرة', 5000);
    gtTelecom('بالقاهرة', '10000', '7000');

    $t = UnitsTotal::build(FinanceAccount::byKey('telecom'), 2027, FinanceUnit::ordered()->get());

    expect($t['ledger']['start'][gtUnit('بالقاهرة')->id])->toBe(800000)
        ->and($t['ledger']['firstYear'])->toBeFalse();
});

it('الكارت يسمّي أقدم شهرٍ منتهٍ لم يكتمل، أو آخر شهرٍ مكتمل', function () {
    $this->actingAs(gtUser(['finance.units.view', 'finance.units.create'], ['بالقاهرة']));
    gtOpening('بالقاهرة', 5000);
    gtTelecom('بالقاهرة', '10000', '7000');

    $this->actingAs(gtUser(['finance.total.view']));
    $this->get(route('finance.total'))
        ->assertSee(__('home.fin_card_month_of', ['month' => 'أكتوبر 2026', 'entered' => 1, 'count' => 6]))
        ->assertSee(__('home.fin_card_month_of', ['month' => 'أكتوبر 2026', 'entered' => 0, 'count' => 6]));
});

it('ملاحظة الإجمالي على حسابها وشهرها بمفتاح «حساب@شهر»، والحساب المجهول ٤٠٤', function () {
    $this->actingAs(gtUser(['finance.total.view', 'finance.units.create']));

    Livewire::test(Total::class)
        ->set('noteDrafts.telecom@2026-10', 'ملاحظة اتصالات')
        ->call('addNote', 'telecom@2026-10')
        ->assertHasNoErrors()
        ->assertSee('ملاحظة اتصالات');

    $note = FinanceNote::with('account')->sole();
    expect($note->account->key)->toBe('telecom')
        ->and($note->finance_unit_id)->toBeNull()
        ->and($note->month->format('Y-m'))->toBe('2026-10');

    Livewire::test(Total::class)->call('addNote', 'offices@2026-10')->assertNotFound();
    Livewire::test(Total::class)->call('addNote', 'telecom@bad')->assertForbidden();
});

it('لا يفتح الإجمالي العام إلا عارضه', function () {
    $this->actingAs(gtUser(['finance.units.view', 'finance.units.create'], ['بالقاهرة']));

    $this->get(route('finance.total'))->assertForbidden();
});

it('ملاحظة شهر الوحدة لا تظهر في الإجمالي العام', function () {
    $user = gtUser(['finance.total.view', 'finance.units.view', 'finance.units.create'], ['بالقاهرة']);
    $this->actingAs($user);

    FinanceNote::create([
        'finance_account_id' => FinanceAccount::byKey('telecom')->id,
        'finance_unit_id'    => gtUnit('بالقاهرة')->id,
        'month'              => '2026-10-01',
        'body'               => 'ملاحظة وحدة القاهرة',
        'created_by'         => $user->id,
    ]);

    $this->get(route('finance.unit', [gtUnit('بالقاهرة'), 'telecom']))->assertSee('ملاحظة وحدة القاهرة');
    $this->get(route('finance.total'))->assertDontSee('ملاحظة وحدة القاهرة');
});
