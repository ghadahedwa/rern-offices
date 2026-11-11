<?php

use App\Livewire\Finance\Mechanization;
use App\Livewire\Finance\MonthEntry;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\User;
use App\Support\Finance\FinanceHistory;
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

function correctionUser(array $abilities = ['finance.accounts.view', 'finance.court-total.view', 'finance.accounts.create', 'finance.units.create', 'finance.edit', 'finance.delete']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-corr-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

/** يحفظ الشهر التالي بأمرٍ وصورتين ويُرجعه. */
function savedMonth(string $amount = '150000', string $number = '1234'): FinanceMonth
{
    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', $amount)
        ->set('orders.0.number', $number)
        ->set('orders.0.issued_on', '2026-11-02')
        ->set('picked.0', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    return FinanceMonth::with('paymentOrders.attachments')->latest('id')->first();
}

function correction(string $period = '2026-10')
{
    return Livewire::test(MonthEntry::class, ['account' => 'mechanization', 'period' => $period]);
}

// ── التصحيح ──────────────────────────────────────────────

it('يفتح التصحيح بالقيم المحفوظة لصاحب finance.edit وحده', function () {
    $this->actingAs(correctionUser());
    savedMonth();

    correction()
        ->assertSet('amount', '150000')
        ->assertSet('orders.0.number', '1234')
        ->assertSet('orders.0.issued_on', '2026-11-02')
        ->assertSee(__('home.fin_correction_for', ['month' => 'أكتوبر 2026']));

    $this->actingAs(correctionUser(['finance.accounts.view', 'finance.court-total.view', 'finance.accounts.create', 'finance.units.create']));
    $this->get(route('finance.correct', ['mechanization', '2026-10']))->assertForbidden();
});

it('يردّ 404 لشهرٍ لم يُدخَل', function () {
    $this->actingAs(correctionUser());

    $this->get(route('finance.correct', ['mechanization', '2026-10']))->assertNotFound();
});

it('يصحّح المبلغ ويسجّل القيمة قبله وبعده والسبب', function () {
    $user = correctionUser();
    $this->actingAs($user);
    $month = savedMonth();

    correction()
        ->set('amount', '160000')
        ->set('reason', 'المبلغ كُتب خطأً')
        ->call('review')
        ->assertHasNoErrors()
        ->assertSee('150,000')
        ->call('save')
        ->assertRedirect();

    expect($month->fresh()->amount)->toBe(160000);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->event)->toBe(FinanceHistory::EVENT_CORRECTED)
        ->and($log->causer_id)->toBe($user->id)
        ->and($log->subject_type)->toBeNull()
        ->and($log->properties['month'])->toBe('2026-10')
        ->and($log->properties['reason'])->toBe('المبلغ كُتب خطأً')
        ->and($log->properties['before']['amount'])->toBe(150000)
        ->and($log->properties['after']['amount'])->toBe(160000);

    $this->get(route('finance.mechanization'))
        ->assertSee(__('home.fin_history_badge', ['count' => 1]))
        ->assertSee('المبلغ كُتب خطأً');
});

it('يعرض السجلّ الأحدث أولاً بأرقام وقوعه وسطر الأصل في آخره', function () {
    $this->actingAs(correctionUser());
    savedMonth();

    correction()->set('amount', '160000')->set('reason', 'التصحيح الأول')->call('save')->assertHasNoErrors();
    correction()->set('amount', '165000')->set('reason', 'التصحيح الثاني')->call('save')->assertHasNoErrors();

    $this->get(route('finance.mechanization'))->assertSeeInOrder([
        '2', __('home.fin_history_latest'), 'التصحيح الثاني', '165,000',
        '1', 'التصحيح الأول', '160,000',
        __('home.fin_history_origin'), '150,000',
    ]);

    // الرقم داخل دائرته — «2» و«1» مجرّدَين يطابقان أي مكان في الصفحة
    $this->get(route('finance.mechanization'))->assertSeeInOrder([
        'text-amber-300">2</span>', 'التصحيح الثاني',
        'text-amber-300">1</span>', 'التصحيح الأول',
    ], false);
});

it('يضع الأصل بين تصحيحات الصفّ الحالي وحذف الإدخال السابق', function () {
    $this->actingAs(correctionUser());
    $first = savedMonth('70000', '1111');

    Livewire::test(Mechanization::class)
        ->call('confirmDelete', $first->id)
        ->set('deleteReason', 'الحساب الخطأ')
        ->call('deleteRow');

    savedMonth('80000', '2222');
    correction()->set('amount', '81000')->set('reason', 'تصحيح المبلغ')->call('save')->assertHasNoErrors();

    $this->get(route('finance.mechanization'))->assertSeeInOrder([
        'تصحيح المبلغ', '81,000',
        __('home.fin_history_origin'), '80,000',
        'الحساب الخطأ', '70,000',
    ]);
});

it('يرفض التصحيح بلا سبب', function () {
    $this->actingAs(correctionUser());
    savedMonth();

    correction()->set('amount', '160000')->call('review')->assertHasErrors('reason');

    expect(FinanceMonth::sole()->amount)->toBe(150000);
});

it('يرفض تصحيحاً لا يغيّر شيئاً ولا يسجّله', function () {
    $this->actingAs(correctionUser());
    savedMonth();

    correction()->set('reason', 'مراجعة فقط')->call('save')->assertHasErrors('reason');

    expect(Activity::where('log_name', FinanceHistory::LOG)->count())->toBe(0);
});

it('يحذف الصورة المُزالة من القرص ويضيف الجديدة', function () {
    $this->actingAs(correctionUser());
    $month   = savedMonth();
    $removed = $month->paymentOrders[0]->attachments[0];

    correction()
        ->call('removeKept', 0, $removed->id)
        ->set('picked.0', [UploadedFile::fake()->image('c.jpg')])
        ->set('reason', 'استبدال صورة غير واضحة')
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(FinanceAttachment::DISK)->assertMissing($removed->path);
    $names = FinanceAttachment::pluck('original_name')->sort()->values()->all();
    expect($names)->toBe(['b.jpg', 'c.jpg']);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['images_added'])->toBe(1)
        ->and($log->properties['images_removed'])->toBe(1);
});

it('يرفض أمراً بلا أي صورة بعد الإزالة', function () {
    $this->actingAs(correctionUser());
    $month = savedMonth();

    $c = correction();
    foreach ($month->paymentOrders[0]->attachments as $a) {
        $c->call('removeKept', 0, $a->id);
    }
    $c->set('reason', 'إزالة الصور')->call('review')->assertHasErrors('orderFiles.0');

    expect(FinanceAttachment::count())->toBe(2);
});

it('لا يعدّ صورةً مدسوسة من شهرٍ آخر صورةً لهذا الأمر', function () {
    $this->actingAs(correctionUser());
    $october  = savedMonth();
    $november = savedMonth('90000', '5678');
    $foreign  = $november->paymentOrders[0]->attachments[0];

    $c = correction();
    foreach ($october->paymentOrders[0]->attachments as $a) {
        $c->call('removeKept', 0, $a->id);
    }
    $c->set('kept.0', [$foreign->id])
        ->set('reason', 'محاولة')
        ->call('save')
        ->assertHasErrors('orderFiles.0');

    expect($foreign->fresh())->not->toBeNull();
});

it('يحذف الأمر المُزال بصوره، ويضيف أمراً جديداً', function () {
    $this->actingAs(correctionUser());
    $month = savedMonth();
    $paths = $month->paymentOrders[0]->attachments->pluck('path');

    correction()
        ->call('addOrder')
        ->set('orders.1.number', '999')
        ->set('orders.1.issued_on', '2026-11-03')
        ->set('picked.1', [UploadedFile::fake()->image('d.jpg')])
        ->call('removeOrder', 0)
        ->set('reason', 'أمر الدفع الصحيح غير المسجَّل')
        ->call('save')
        ->assertHasNoErrors();

    $order = $month->fresh()->paymentOrders->sole();
    expect($order->number)->toBe('999');
    foreach ($paths as $path) {
        Storage::disk(FinanceAttachment::DISK)->assertMissing($path);
    }
});

it('يصحّح بنفسه حتى بلا finance.create', function () {
    $this->actingAs(correctionUser());
    savedMonth();

    $this->actingAs(correctionUser(['finance.accounts.view', 'finance.court-total.view', 'finance.edit']));
    correction()->set('amount', '1')->set('reason', 'تصحيح المبلغ')->call('save')->assertHasNoErrors();

    expect(FinanceMonth::sole()->amount)->toBe(1);
});

it('يرفض حفظ التصحيح إن سُحبت الصلاحية والصفحة مفتوحة', function () {
    $user = correctionUser();
    $this->actingAs($user);
    savedMonth();

    $c = correction()->set('amount', '1')->set('reason', 'تصحيح المبلغ');

    $user->roles->each(fn ($r) => $r->revokePermissionTo('finance.edit'));
    $user->unsetRelation('roles')->unsetRelation('permissions');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $c->call('save')->assertForbidden();
    expect(FinanceMonth::sole()->amount)->toBe(150000);
});

// ── الحذف ────────────────────────────────────────────────

it('يحذف آخر شهرٍ بصوره ويسجّله بسببه', function () {
    $this->actingAs(correctionUser());
    savedMonth();
    $last  = savedMonth('90000', '5678');
    $paths = $last->paymentOrders[0]->attachments->pluck('path');

    Livewire::test(Mechanization::class)
        ->call('confirmDelete', $last->id)
        ->assertSet('showDelete', true)
        ->set('deleteReason', 'أُدخل في الحساب الخطأ')
        ->call('deleteRow')
        ->assertHasNoErrors();

    expect(FinanceMonth::count())->toBe(1)
        ->and(FinanceAttachment::count())->toBe(2);
    foreach ($paths as $path) {
        Storage::disk(FinanceAttachment::DISK)->assertMissing($path);
    }

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->event)->toBe(FinanceHistory::EVENT_DELETED)
        ->and($log->properties['month'])->toBe('2026-11')
        ->and($log->properties['before']['amount'])->toBe(90000)
        ->and($log->properties['after'])->toBeNull();

    // السجلّ يبقى ظاهراً على الشهر الذي ينتظر إعادة إدخاله
    $this->get(route('finance.mechanization'))
        ->assertSee('أُدخل في الحساب الخطأ')
        ->assertSee(__('home.fin_history_badge_deleted', ['count' => 1]));

    // أُعيد إدخاله ثم صُحِّح: العلامة تعدّ الاثنين لا التصحيح وحده
    savedMonth('90000', '5678');
    correction('2026-11')->set('amount', '91000')->set('reason', 'تصحيح المبلغ')->call('save')->assertHasNoErrors();

    $this->get(route('finance.mechanization'))
        ->assertSee(__('home.fin_history_badge', ['count' => 1]).' · '.__('home.fin_history_badge_deleted', ['count' => 1]));
});

it('لا يحذف شهراً في الوسط', function () {
    $this->actingAs(correctionUser());
    $first = savedMonth();
    savedMonth('90000', '5678');

    Livewire::test(Mechanization::class)->call('confirmDelete', $first->id)->assertForbidden();

    expect(FinanceMonth::count())->toBe(2);
});

it('يرفض الحذف بلا سبب', function () {
    $this->actingAs(correctionUser());
    $month = savedMonth();

    Livewire::test(Mechanization::class)
        ->call('confirmDelete', $month->id)
        ->call('deleteRow')
        ->assertHasErrors('deleteReason');

    expect(FinanceMonth::count())->toBe(1);
});

it('لا يحذف إن أُدخل شهرٌ بعده والمودال مفتوح', function () {
    $this->actingAs(correctionUser());
    $october = savedMonth();

    $page = Livewire::test(Mechanization::class)->call('confirmDelete', $october->id);

    savedMonth('90000', '5678');

    $page->set('deleteReason', 'سببٌ كافٍ')->call('deleteRow');

    expect(FinanceMonth::count())->toBe(2);
});

it('يمنع الحذف بلا finance.delete', function () {
    $this->actingAs(correctionUser());
    $month = savedMonth();

    $this->actingAs(correctionUser(['finance.accounts.view', 'finance.court-total.view', 'finance.edit']));
    Livewire::test(Mechanization::class)->call('confirmDelete', $month->id)->assertForbidden();

    expect(FinanceMonth::count())->toBe(1);
});

it('يُظهر التصحيح لصاحبه، والحذف على آخر شهرٍ وحده', function () {
    $this->actingAs(correctionUser());
    $october  = savedMonth();
    $november = savedMonth('90000', '5678');

    Livewire::test(Mechanization::class)
        ->assertSee(route('finance.correct', ['mechanization', '2026-10']), false)
        ->assertSee(route('finance.correct', ['mechanization', '2026-11']), false)
        ->assertSeeHtml('confirmDelete('.$november->id.')')
        ->assertDontSeeHtml('confirmDelete('.$october->id.')');

    $this->actingAs(correctionUser(['finance.accounts.view', 'finance.court-total.view', 'finance.accounts.create', 'finance.units.create']));
    Livewire::test(Mechanization::class)
        ->assertDontSee(route('finance.correct', ['mechanization', '2026-10']), false)
        ->assertDontSeeHtml('confirmDelete(');
});
