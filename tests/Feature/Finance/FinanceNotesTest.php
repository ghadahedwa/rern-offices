<?php

use App\Livewire\Finance\Mechanization;
use App\Livewire\Finance\MinistryOfFinance;
use App\Livewire\Finance\MonthEntry;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceNote;
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
    // تاريخٌ في الماضي — انظر MonthEntryTest. أكتوبر ونوفمبر بدآ، وديسمبر لم يبدأ.
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function noteUser(array $abilities = ['finance.view', 'finance.create']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-note-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

function adminNoteUser(): User
{
    return noteUser(['finance.view', 'finance.create', 'finance.edit', 'finance.delete']);
}

/** يحفظ أكتوبر للميكنة — فيُقفل وملاحظاته معه */
function lockOctober(string $note = ''): void
{
    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '1000')
        ->set('orders.0.number', '77')
        ->set('orders.0.issued_on', '2026-11-02')
        ->set('picked.0', [UploadedFile::fake()->image('o.jpg')])
        ->set('note', $note)
        ->call('save')
        ->assertHasNoErrors();
}

function noteOn(string $month, User $author, string $body = 'ملاحظة'): FinanceNote
{
    return FinanceNote::create([
        'finance_account_id' => FinanceAccount::byKey('mechanization')->id,
        'month'              => $month.'-01',
        'body'               => $body,
        'created_by'         => $author->id,
    ]);
}

it('يضيف ملاحظة على الشهر التالي المفتوح ويعرضها بكاتبها', function () {
    $user = noteUser();
    $user->update(['name' => 'كاتب الملاحظة']);
    $this->actingAs($user);

    Livewire::test(Mechanization::class)
        ->set('noteDrafts.2026-10', 'تأخّر وصول أمر الدفع')
        ->call('addNote', '2026-10')
        ->assertHasNoErrors()
        ->assertSee('تأخّر وصول أمر الدفع')
        ->assertSee('كاتب الملاحظة');

    $note = FinanceNote::sole();
    expect($note->month->toDateString())->toBe('2026-10-01')
        ->and($note->created_by)->toBe($user->id);
});

it('يرفض الملاحظة الفارغة وعلى شهرٍ لم يأتِ دوره', function () {
    $this->actingAs(noteUser());

    Livewire::test(Mechanization::class)->set('noteDrafts.2026-10', '  ')->call('addNote', '2026-10')->assertHasErrors('noteDrafts.2026-10');
    Livewire::test(Mechanization::class)->set('noteDrafts.2026-12', 'مبكراً')->call('addNote', '2026-12')->assertForbidden();
    Livewire::test(Mechanization::class)->call('addNote', 'xx')->assertForbidden();

    expect(FinanceNote::count())->toBe(0);
});

it('يحفظ ملاحظة صفحة الإدخال مع الشهر', function () {
    $this->actingAs(noteUser());
    lockOctober('صدر الأمر متأخراً');

    expect(FinanceNote::sole()->body)->toBe('صدر الأمر متأخراً')
        ->and(FinanceNote::sole()->month->toDateString())->toBe('2026-10-01');
});

it('والشهر مفتوح: يعدّل الكاتب ملاحظته ولا يعدّلها غيره، وبلا سجلّ', function () {
    $author = noteUser();
    $note   = noteOn('2026-10', $author);

    $this->actingAs(noteUser());
    Livewire::test(Mechanization::class)->call('startEditNote', $note->id)->assertForbidden();
    Livewire::test(Mechanization::class)->call('confirmDeleteNote', $note->id)->assertForbidden();

    $this->actingAs($author);
    Livewire::test(Mechanization::class)
        ->call('startEditNote', $note->id)
        ->set('editingNoteBody', 'بعد التعديل')
        ->call('saveNote')
        ->assertHasNoErrors();

    expect($note->fresh()->body)->toBe('بعد التعديل')
        ->and(Activity::where('log_name', FinanceHistory::LOG)->count())->toBe(0);
});

it('بعد قفل الشهر: الكاتب بصلاحية الإدخال وحدها لا يضيف ولا يعدّل ولا يحذف', function () {
    $author = noteUser();
    $this->actingAs($author);
    $note = noteOn('2026-10', $author);
    lockOctober();

    Livewire::test(Mechanization::class)->set('noteDrafts.2026-10', 'متأخرة')->call('addNote', '2026-10')->assertForbidden();
    Livewire::test(Mechanization::class)->call('startEditNote', $note->id)->assertForbidden();
    Livewire::test(Mechanization::class)->call('confirmDeleteNote', $note->id)->assertForbidden();
});

it('يرفض حفظ تعديلٍ بدأ والشهر مفتوح ثم قُفل', function () {
    $author = noteUser();
    $this->actingAs($author);
    $note = noteOn('2026-10', $author);

    $page = Livewire::test(Mechanization::class)->call('startEditNote', $note->id)->set('editingNoteBody', 'تعديل متأخر');
    lockOctober();
    $page->call('saveNote')->assertForbidden();

    expect($note->fresh()->body)->toBe('ملاحظة');
});

it('بعد القفل: التعديل والحذف بالتصحيح ويُسجَّلان في سجلّ الشهر', function () {
    $author = noteUser();
    $this->actingAs($author);
    $note = noteOn('2026-10', $author, 'النص الأول');
    lockOctober();

    $this->actingAs(adminNoteUser());
    Livewire::test(Mechanization::class)
        ->call('startEditNote', $note->id)
        ->set('editingNoteBody', 'النص المصحَّح')
        ->call('saveNote')
        ->assertHasNoErrors();

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->event)->toBe(FinanceHistory::EVENT_NOTE_EDITED)
        ->and($log->properties['note_before'])->toBe('النص الأول')
        ->and($log->properties['note_after'])->toBe('النص المصحَّح');

    Livewire::test(Mechanization::class)
        ->call('confirmDeleteNote', $note->id)
        ->assertSet('showDelete', true)
        ->call('deleteRow')
        ->assertHasNoErrors();

    expect(FinanceNote::count())->toBe(0)
        ->and(FinanceMonth::count())->toBe(1)
        ->and(Activity::where('event', FinanceHistory::EVENT_NOTE_DELETED)->sole()->properties['note_before'])->toBe('النص المصحَّح');

    $this->get(route('finance.mechanization'))
        ->assertSee(__('home.fin_history_badge_notes', ['count' => 2]))
        ->assertSee(__('home.fin_history_event_note_deleted'));
});

it('لا تُفتح ملاحظة حسابٍ آخر من صفحة حساب', function () {
    $this->actingAs(adminNoteUser());
    $note = noteOn('2026-10', auth()->user());

    Livewire::test(MinistryOfFinance::class)->call('startEditNote', $note->id)->assertNotFound();
    Livewire::test(MinistryOfFinance::class)->call('confirmDeleteNote', $note->id)->assertNotFound();
});

it('لا تُفسد أحداث الملاحظات سطر «الأصل» ولا يقسم حذفها السجلّ', function () {
    $this->actingAs(adminNoteUser());
    lockOctober();
    $note = noteOn('2026-10', auth()->user());

    // حدث الملاحظة **قبل** التصحيح: فأقدم أحداث الصفّ ملاحظةٌ لا تحمل أرقاماً، و«الأصل» يُقرأ من التصحيح
    Livewire::test(Mechanization::class)->call('confirmDeleteNote', $note->id)->call('deleteRow');

    Livewire::test(MonthEntry::class, ['account' => 'mechanization', 'period' => '2026-10'])
        ->set('amount', '2000')->set('reason', 'المبلغ الصحيح')->call('save')->assertHasNoErrors();

    $this->get(route('finance.mechanization'))->assertSeeInOrder([
        'المبلغ الصحيح', '2,000',
        __('home.fin_history_event_note_deleted'),
        __('home.fin_history_origin'), '1,000',
    ]);
});
