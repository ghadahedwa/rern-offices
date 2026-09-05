<?php

use App\Livewire\OfficialHolidays\Create;
use App\Livewire\OfficialHolidays\Index;
use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Models\User;
use App\Support\WorkingDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function holidayAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function holidayUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('holidays-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

function holidayRow(string $from, ?string $to = null, string $name = 'عطلة'): OfficialHoliday
{
    return OfficialHoliday::create(['name' => $name, 'starts_on' => $from, 'ends_on' => $to ?? $from]);
}

/** مدخل على رأس العمل من أول سبتمبر — لاختبار أثر العطلة على الحساب. */
function holidayOperator(): DataEntryOperator
{
    $operator = DataEntryOperator::factory()->create();

    DataEntryAssignment::create([
        'operator_id' => $operator->id,
        'office_id'   => Office::factory()->create()->id,
        'started_on'  => '2026-09-01',
    ]);

    return $operator->fresh();
}

function absenceOn(DataEntryOperator $operator, string $date): AttendanceDay
{
    return AttendanceDay::create([
        'attendable_type' => DataEntryOperator::class,
        'attendable_id'   => $operator->id,
        'date'            => $date,
        'status_id'       => AttendanceStatus::where('name', 'غائب')->value('id'),
    ]);
}

// ── الحراسة: سوبر أدمن وحده ──────────────────────────────

it('يفتح شاشتي العطلات للسوبر أدمن', function () {
    $this->actingAs(holidayAdmin());

    $this->get(route('official-holidays.index'))->assertOk();
    $this->get(route('official-holidays.create'))->assertOk();
});

it('يمنع مديرَ القوائم المرجعية من شاشة العطلات', function () {
    // ⚠️ أضيق من data-entry.settings عمداً: القائمة قومية لا محلية
    $this->actingAs(holidayUser(['data-entry.settings']));

    $this->get(route('official-holidays.index'))->assertForbidden();
    $this->get(route('official-holidays.create'))->assertForbidden();
});

it('يمنع صاحب صلاحيات الفرع التشغيلية', function () {
    $this->actingAs(holidayUser(['data-entry.index', 'data-entry.attendance', 'data-entry.export']));

    $this->get(route('official-holidays.index'))->assertForbidden();
});

it('يمنع الإجراءات عمّن سُحب دوره والشاشة مفتوحة', function () {
    // ⚠️ الإجراء يصل في طلب مستقل عن فتح الشاشة، فالحارس فيه لا في mount وحدها
    $user = holidayAdmin();
    $this->actingAs($user);

    // كلٌّ في نسخة مستقلة: الاستجابة المرفوضة تُفسد الـsnapshot فلا تُستأنف عليها
    $seeding  = Livewire::test(Index::class);
    $deleting = Livewire::test(Index::class);
    $saving   = Livewire::test(Create::class)
        ->set('name', 'عطلة')->set('starts_on', '2026-09-17')->set('ends_on', '2026-09-17');
    $id = holidayRow('2026-09-17')->id;

    $user->removeRole('super-admin');

    $seeding->call('seedFixed')->assertForbidden();
    $deleting->call('askDelete', $id)->assertForbidden();
    $saving->call('save')->assertForbidden();
});

// ── أثر العطلة على الحساب ────────────────────────────────

it('تُنقص العطلةُ المضافة أيامَ العمل فوراً', function () {
    $this->actingAs(holidayAdmin());

    expect(WorkingDays::count('2026-09-01', '2026-09-30'))->toBe(26);

    Livewire::test(Create::class)
        ->set('name', 'المولد النبوي')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('save')
        ->assertHasNoErrors();

    expect(WorkingDays::count('2026-09-01', '2026-09-30'))->toBe(25);
});

it('يُعيد الحذفُ اليومَ يومَ عمل', function () {
    $this->actingAs(holidayAdmin());

    $holiday = holidayRow('2026-09-17', name: 'المولد النبوي');
    expect(WorkingDays::count('2026-09-01', '2026-09-30'))->toBe(25);

    Livewire::test(Index::class)
        ->call('askDelete', $holiday->id)
        ->call('deleteRow');

    expect(WorkingDays::count('2026-09-01', '2026-09-30'))->toBe(26);
});

it('يصحّح ترحيلُ العطلة الحسابَ بتعديل تاريخها', function () {
    // المولد كان الثلاثاء ١٥ ثم رُحِّل إلى الخميس ١٧ — تعديل تاريخ لا صفّ جديد
    $this->actingAs(holidayAdmin());

    $holiday = holidayRow('2026-09-15', name: 'المولد النبوي');

    Livewire::test(Create::class, ['officialHoliday' => $holiday])
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('save')
        ->assertHasNoErrors();

    expect(OfficialHoliday::count())->toBe(1)
        ->and(WorkingDays::holidayMap('2026-09-01', '2026-09-30'))->toBe(['2026-09-17' => 'المولد النبوي']);
});

it('لا يحذف حذفُ العطلة سجلات الحضور من جدولها', function () {
    // الحذف يعيد اليوم يوم عمل، والسجلات التي حُذفت عند الاعتماد لا تعود — سلوك مقصود
    $this->actingAs(holidayAdmin());

    $operator = holidayOperator();
    absenceOn($operator, '2026-09-16');

    $holiday = holidayRow('2026-09-17');

    Livewire::test(Index::class)->call('askDelete', $holiday->id)->call('deleteRow');

    expect(AttendanceDay::count())->toBe(1);
});

// ── حارس الإضافة بأثر رجعي ───────────────────────────────

it('لا يحفظ العطلةَ فوراً إذا وقع داخلها غياب مسجَّل', function () {
    $this->actingAs(holidayAdmin());

    $operator = holidayOperator();
    absenceOn($operator, '2026-09-17');

    Livewire::test(Create::class)
        ->set('name', 'المولد النبوي')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('save')
        ->assertSet('showConflict', true)
        ->assertSet('conflictCount', 1);

    // لم تُحفظ ولم يُحذف شيء قبل الموافقة
    expect(OfficialHoliday::count())->toBe(0)
        ->and(AttendanceDay::count())->toBe(1);
});

it('يحذف السجلات المتعارضة ويعتمد العطلة بعد الموافقة', function () {
    $this->actingAs(holidayAdmin());

    $operator = holidayOperator();
    absenceOn($operator, '2026-09-17');
    absenceOn($operator, '2026-09-16');   // خارج مدى العطلة — لا يُمسّ

    Livewire::test(Create::class)
        ->set('name', 'المولد النبوي')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('save')
        ->call('confirmSave')
        ->assertHasNoErrors();

    expect(OfficialHoliday::count())->toBe(1)
        ->and(AttendanceDay::pluck('date')->map->toDateString()->all())->toBe(['2026-09-16']);
});

it('يتجاهل تأكيد الحفظ إذا لم يُعرَض المودال', function () {
    // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب تأكيده
    $this->actingAs(holidayAdmin());

    $operator = holidayOperator();
    absenceOn($operator, '2026-09-17');

    Livewire::test(Create::class)
        ->set('name', 'المولد النبوي')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('confirmSave');

    expect(OfficialHoliday::count())->toBe(0)
        ->and(AttendanceDay::count())->toBe(1);
});

it('يحفظ بلا مودال حين لا تعارض', function () {
    $this->actingAs(holidayAdmin());

    $operator = holidayOperator();
    absenceOn($operator, '2026-09-10');

    Livewire::test(Create::class)
        ->set('name', 'المولد النبوي')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-17')
        ->call('save')
        ->assertSet('showConflict', false);

    expect(OfficialHoliday::count())->toBe(1)
        ->and(AttendanceDay::count())->toBe(1);
});

// ── الزرع ────────────────────────────────────────────────

it('يزرع ثوابت السنة مرة واحدة ولا يكررها', function () {
    $this->actingAs(holidayAdmin());

    $component = Livewire::test(Index::class)->set('year', '2026')->call('seedFixed');

    expect(OfficialHoliday::count())->toBe(count(Index::FIXED));

    $component->call('seedFixed');

    expect(OfficialHoliday::count())->toBe(count(Index::FIXED));
});

it('يزرع في السنة المختارة لا في السنة الحالية', function () {
    $this->actingAs(holidayAdmin());

    Livewire::test(Index::class)->set('year', '2027')->call('seedFixed');

    expect(OfficialHoliday::pluck('starts_on')->every(fn ($d) => $d->year === 2027))->toBeTrue();
});

// ── الفلترة والبحث ───────────────────────────────────────

it('يفلتر بالسنة ويهمل قيمة تالفة من الرابط', function () {
    $this->actingAs(holidayAdmin());

    holidayRow('2026-09-17', name: 'المولد النبوي');
    holidayRow('2027-01-07', name: 'عيد الميلاد');

    Livewire::test(Index::class)->set('year', '2026')
        ->assertSee('المولد النبوي')->assertDontSee('عيد الميلاد');

    // ⚠️ القيمة تصل من الرابط — التالفة تُهمَل ولا تُخرج شاشة فارغة
    Livewire::test(Index::class)->set('year', 'أي كلام')
        ->assertSee('المولد النبوي')->assertSee('عيد الميلاد');
});

it('يبحث بالعربية المطبَّعة', function () {
    $this->actingAs(holidayAdmin());

    holidayRow('2026-09-17', name: 'عيد الأضحى');

    Livewire::test(Index::class)->set('year', '')->set('search', 'الاضحي')
        ->assertSee('عيد الأضحى');
});

it('يرفض نهايةً قبل البداية', function () {
    $this->actingAs(holidayAdmin());

    Livewire::test(Create::class)
        ->set('name', 'عطلة')
        ->set('starts_on', '2026-09-17')
        ->set('ends_on', '2026-09-15')
        ->call('save')
        ->assertHasErrors(['ends_on']);
});
