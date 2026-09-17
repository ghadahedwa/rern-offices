<?php

use App\Livewire\Contractors\Attendance;
use App\Livewire\Contractors\Index;
use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Models\User;
use App\Support\WorkingDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
 * سبتمبر ٢٠٢٦: يبدأ الثلاثاء، وجُمَعه ٤ · ١١ · ١٨ · ٢٥ — ٣٠ يوماً − ٤ جُمَع = ٢٦ يوم عمل.
 */

function attUser(array $governorates = [], array $abilities = ['contractors.attendance']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('att-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function attOffice(?Governorate $governorate = null): Office
{
    return Office::factory()->create(['governorate_id' => ($governorate ?? Governorate::factory()->create())->id]);
}

function attWorker(Office $office, string $from = '2026-09-01', ?string $to = null, string $name = 'عامل الكشف'): Contractor
{
    $contractor = Contractor::factory()->create(['name' => $name]);

    ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => $from,
        'ended_on'      => $to,
    ]);

    return $contractor->fresh();
}

function attStatus(string $name): AttendanceStatus
{
    return AttendanceStatus::where('name', $name)->firstOrFail();
}

function attAbsence(Contractor $contractor, string $date, ?AttendanceStatus $status = null): AttendanceDay
{
    return AttendanceDay::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $contractor->id,
        'date'            => $date,
        'status_id'       => ($status ?? attStatus('غائب'))->id,
    ]);
}

function attSheet(Office $office, string $month = '2026-09')
{
    return Livewire::withQueryParams(['office' => $office->id, 'month' => $month])->test(Attendance::class);
}

// ── ما تعرضه الشبكة ──────────────────────────────────────

it('يعرض مَن خدم في المقر خلال الشهر وحدهم', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);

    attWorker($office, name: 'عامل الشهر');
    attWorker($office, '2026-09-15', name: 'ملتحق في المنتصف');
    attWorker($office, '2026-01-01', '2026-08-31', name: 'انتهت خدمته قبل الشهر');
    attWorker(attOffice($gov), name: 'عامل مقر آخر');

    $this->actingAs(attUser([$gov]));

    expect(collect(attSheet($office)->viewData('rows'))->pluck('name')->all())
        ->toBe(['عامل الشهر', 'ملتحق في المنتصف']);
});

it('يقفل الجمعة والعطلة والأيام قبل التحاقه', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office, '2026-09-15');

    OfficialHoliday::create(['name' => 'عطلة تجريبية', 'starts_on' => '2026-09-16', 'ends_on' => '2026-09-16']);

    $this->actingAs(attUser([$gov]));

    $open = attSheet($office)->viewData('rows')[0]['open'];

    expect($open)->not->toContain('2026-09-14')   // قبل التحاقه
        ->not->toContain('2026-09-16')            // عطلة
        ->not->toContain('2026-09-18')            // جمعة
        ->toContain('2026-09-15')
        // ١٥–٣٠ = ١٦ يوماً − جمعتان (١٨ · ٢٥) − عطلة = ١٣
        ->toHaveCount(13);
});

it('يقصر الأيام المفتوحة على تسكينه في هذا المقر وحده', function () {
    $gov    = Governorate::factory()->create();
    $first  = attOffice($gov);
    $second = attOffice($gov);
    $worker = attWorker($first, '2026-09-01', '2026-09-14');

    ContractorAssignment::create(['contractor_id' => $worker->id, 'office_id' => $second->id, 'started_on' => '2026-09-15']);

    expect(WorkingDays::contractorCalendar($worker->fresh(), '2026-09-01', '2026-09-30', officeId: $first->id))
        ->toContain('2026-09-14')->not->toContain('2026-09-15')
        ->and(WorkingDays::contractorCalendar($worker->fresh(), '2026-09-01', '2026-09-30', officeId: $second->id))
        ->toContain('2026-09-15')->not->toContain('2026-09-14');
});

// ── الحفظ ────────────────────────────────────────────────

it('يحفظ الغياب ولا يُخزّن الحضور', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    $user   = attUser([$gov]);

    $this->actingAs($user);

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => ['2026-09-02' => attStatus('غائب')->id]], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(1)
        ->and(AttendanceDay::first()->recorded_by)->toBe($user->id)
        ->and(WorkingDays::summaryFor($worker, '2026-09-01', '2026-09-30')['present'])->toBe(25);
});

it('يحذف غياباً مسجَّلاً رجعت خليته «حاضر»', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    attAbsence($worker, '2026-09-02');

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => []], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(0);
});

it('يغيّر حالة الخلية المسجَّلة بدل أن يُكرّرها', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    attAbsence($worker, '2026-09-02');

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => ['2026-09-02' => attStatus('إجازة')->id]], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(1)
        ->and(AttendanceDay::first()->status_id)->toBe(attStatus('إجازة')->id);
});

it('لا يكتب علامةً في يومٍ مقفول ولو وصلت من العميل', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office, '2026-09-10');
    OfficialHoliday::create(['name' => 'عطلة', 'starts_on' => '2026-09-16', 'ends_on' => '2026-09-16']);

    $this->actingAs(attUser([$gov]));

    $absent = attStatus('غائب')->id;
    $sheet  = attSheet($office);
    $sheet->call('save', [$worker->id => [
        '2026-09-09' => $absent,   // قبل التحاقه
        '2026-09-11' => $absent,   // جمعة
        '2026-09-16' => $absent,   // عطلة
        '2026-10-01' => $absent,   // شهرٌ آخر
    ]], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(0);
});

it('لا يحذف غياب العامل في مقرّه السابق عند حفظ كشف مقرّه الجديد', function () {
    // ⚠️ «الشبكة مصدر الحقيقة» محصورة بما تعرضه: المنقول في منتصف الشهر نصفه في شبكة مقرٍّ آخر
    $gov    = Governorate::factory()->create();
    $first  = attOffice($gov);
    $second = attOffice($gov);
    $worker = attWorker($first, '2026-09-01', '2026-09-14');
    ContractorAssignment::create(['contractor_id' => $worker->id, 'office_id' => $second->id, 'started_on' => '2026-09-15']);
    attAbsence($worker, '2026-09-10');

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($second);
    $sheet->call('save', [$worker->id => []], [$worker->id => true], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(1);
});

it('يرفض الحالة الافتراضية وحالةً غير موجودة', function () {
    // «حاضر» مخزَّنةً تُحسب استثناءً فتُنقص الحضور يوماً — عكس معناها
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => [
        '2026-09-02' => attStatus('حاضر')->id,
        '2026-09-03' => 99999,
        '2026-09-05' => 'غائب',
    ]], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(0);
});

it('يُبقي حالةً معطَّلة على خليتها ولا يكتبها في خليةٍ جديدة', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    $duty   = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#7c3aed', 'order' => 5]);
    attAbsence($worker, '2026-09-02', $duty);
    $duty->update(['is_active' => false]);

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => ['2026-09-02' => $duty->id, '2026-09-03' => $duty->id]], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::pluck('date')->map->toDateString()->all())->toBe(['2026-09-02']);
});

it('يتجاهل عاملاً مدسوساً ليس في الشبكة', function () {
    $gov      = Governorate::factory()->create();
    $office   = attOffice($gov);
    $worker   = attWorker($office);
    $stranger = attWorker(attOffice($gov), name: 'عامل مقر آخر');

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call(
        'save',
        [$stranger->id => ['2026-09-02' => attStatus('غائب')->id]],
        [$worker->id => false, $stranger->id => true],
        $sheet->viewData('fingerprint')
    );

    expect(AttendanceDay::count())->toBe(0)
        ->and(AttendanceReview::count())->toBe(0);
});

it('لا يمسّ صفّاً لم يُرسله العميل', function () {
    // غياب الصفّ من الطلب لا يعني «حاضر الشهر كله»
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    attAbsence($worker, '2026-09-02');

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($office);
    $sheet->call('save', [], [], $sheet->viewData('fingerprint'));

    expect(AttendanceDay::count())->toBe(1);
});

it('يرفض الحفظ إن عدّل غيره الكشف بعد فتحه', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);

    $this->actingAs(attUser([$gov]));

    $sheet       = attSheet($office);
    $fingerprint = $sheet->viewData('fingerprint');

    attAbsence($worker, '2026-09-07');   // زميلٌ سجّل بعد الفتح

    $sheet->call('save', [$worker->id => ['2026-09-02' => attStatus('إجازة')->id]], [$worker->id => true], $fingerprint);

    expect(AttendanceDay::pluck('date')->map->toDateString()->all())->toBe(['2026-09-07'])
        ->and(AttendanceReview::count())->toBe(0);
});

// ── وصل الكشف ────────────────────────────────────────────

it('يحفظ «وصل الكشف» على المقر والشهر ويلغيه', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office);
    $user   = attUser([$gov]);

    $this->actingAs($user);

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => []], [$worker->id => true], $sheet->viewData('fingerprint'));

    $review = AttendanceReview::first();

    expect($review->office_id)->toBe($office->id)
        ->and($review->month->toDateString())->toBe('2026-09-01')
        ->and($review->reviewed_by)->toBe($user->id)
        ->and(attSheet($office)->viewData('rows')[0]['reviewed'])->toBeTrue()
        ->and(attSheet($office, '2026-10')->viewData('rows')[0]['reviewed'])->toBeFalse();

    $sheet = attSheet($office);
    $sheet->call('save', [$worker->id => []], [$worker->id => false], $sheet->viewData('fingerprint'));

    expect(AttendanceReview::count())->toBe(0);
});

it('لا يُعدّ وصول كشف مقرٍّ وصولاً لكشف المقر الآخر للمنقول', function () {
    $gov    = Governorate::factory()->create();
    $first  = attOffice($gov);
    $second = attOffice($gov);
    $worker = attWorker($first, '2026-09-01', '2026-09-14');
    ContractorAssignment::create(['contractor_id' => $worker->id, 'office_id' => $second->id, 'started_on' => '2026-09-15']);

    $this->actingAs(attUser([$gov]));

    $sheet = attSheet($first);
    $sheet->call('save', [$worker->id => []], [$worker->id => true], $sheet->viewData('fingerprint'));

    expect(attSheet($second)->viewData('rows')[0]['reviewed'])->toBeFalse();
});

it('لا يُحذف عاملٌ وصل كشفه ولو بلا غياب', function () {
    // حضر الشهر كله فلا صفّ له في attendance_days — والمراجعة وحدها أثر ذلك الشهر
    Role::findOrCreate('super-admin', 'web');
    $admin  = tap(User::factory()->create())->assignRole('super-admin');
    $office = attOffice();
    $worker = attWorker($office);

    AttendanceReview::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $worker->id,
        'office_id'       => $office->id,
        'month'           => '2026-09-01',
    ]);

    $this->actingAs($admin);

    Livewire::test(Index::class)->call('askDelete', $worker->id)->call('deleteRow');

    expect(Contractor::whereKey($worker->id)->exists())->toBeTrue();
});

// ── النطاق والرابط ───────────────────────────────────────

it('لا يفتح مقراً خارج النطاق من الرابط ولا يحفظ عليه', function () {
    $mine    = Governorate::factory()->create();
    $outside = attOffice();
    $worker  = attWorker($outside);

    $this->actingAs(attUser([$mine]));

    $sheet = attSheet($outside);

    expect($sheet->viewData('sheet'))->toBeNull()
        ->and($sheet->viewData('rows'))->toBe([]);

    $sheet->call('save', [$worker->id => ['2026-09-02' => attStatus('غائب')->id]], [$worker->id => true], '')
        ->assertForbidden();

    expect(AttendanceDay::count())->toBe(0);
});

it('يعرض في منسدلة المقرات مقارّ المحافظة التي بها عاملون في الشهر وحدها', function () {
    $gov   = Governorate::factory()->create();
    $busy  = attOffice($gov);
    $empty = attOffice($gov);
    $old   = attOffice($gov);
    attWorker($busy);
    attWorker($old, '2026-01-01', '2026-06-30');

    $this->actingAs(attUser([$gov]));

    $offices = Livewire::withQueryParams(['gov' => $gov->id, 'month' => '2026-09'])
        ->test(Attendance::class)
        ->viewData('offices')
        ->pluck('id')
        ->all();

    expect($offices)->toBe([$busy->id]);
});

it('يُهمل الشهر التالف من الرابط ويفتح الشهر الحالي', function () {
    $this->travelTo('2026-09-20 10:00:00');
    $this->actingAs(attUser([Governorate::factory()->create()]));

    Livewire::withQueryParams(['month' => '2026-13'])
        ->test(Attendance::class)
        ->assertSet('month', '2026-09');
});

it('يفتح البحثُ كشفَ مقرّ العامل وشهرَ خدمته', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office, '2026-01-01', '2026-06-30', name: 'عامل قديم');

    $this->actingAs(attUser([$gov]));

    Livewire::withQueryParams(['month' => '2026-09'])
        ->test(Attendance::class)
        ->set('search', 'عامل قديم')
        ->call('openWorker', $worker->id)
        ->assertSet('governorate', (string) $gov->id)
        ->assertSet('office', (string) $office->id)
        ->assertSet('worker', (string) $worker->id)
        ->assertSet('month', '2026-06');
});

it('لا يفتح البحثُ عاملاً خارج النطاق', function () {
    $mine   = Governorate::factory()->create();
    $worker = attWorker(attOffice());

    $this->actingAs(attUser([$mine]));

    Livewire::test(Attendance::class)
        ->call('openWorker', $worker->id)
        ->assertSet('office', '');
});

it('لا يفتح البحثُ مقرّاً خارج النطاق لعاملٍ نُقل من محافظتي', function () {
    // ⚠️ العامل نفسه مرئيٌّ لي (له تسكينٌ سابق عندي)، لكن تسكينه في الشهر المعروض خارج نطاقي
    $mine   = Governorate::factory()->create();
    $worker = attWorker(attOffice($mine), '2026-01-01', '2026-06-30');
    ContractorAssignment::create(['contractor_id' => $worker->id, 'office_id' => attOffice()->id, 'started_on' => '2026-07-01']);

    $this->actingAs(attUser([$mine]));

    Livewire::withQueryParams(['month' => '2026-09'])
        ->test(Attendance::class)
        ->call('openWorker', $worker->id)
        ->assertSet('office', '')
        ->assertSet('governorate', '');
});

it('يعلّم «حاضر» الحالة الافتراضية ويستبعدها من أدوات التعليم', function () {
    expect(AttendanceStatus::where('is_default', true)->pluck('name')->all())->toBe(['حاضر'])
        ->and(AttendanceStatus::markable()->pluck('name')->all())->not->toContain('حاضر');
});

it('يعرض الشبكة بعلاماتها المخزَّنة', function () {
    $gov    = Governorate::factory()->create();
    $office = attOffice($gov);
    $worker = attWorker($office, name: 'عامل معروض');
    attAbsence($worker, '2026-09-02');

    $this->actingAs(attUser([$gov]));

    attSheet($office)
        ->assertOk()
        ->assertSee('عامل معروض')
        ->assertSee(__('home.ct_att_working_days', ['count' => 26]));

    expect(attSheet($office)->viewData('rows')[0]['marks'])->toBe(['2026-09-02' => attStatus('غائب')->id]);
});
