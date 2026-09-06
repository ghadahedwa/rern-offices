<?php

use App\Livewire\DataEntry\Operators\Create;
use App\Livewire\DataEntry\Operators\Index;
use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Support\WorkingDays;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/** مستخدم بصلاحيات ومحافظات محددة — نطاق المفتش. */
function opUser(array $abilities, array $governorates = []): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('de-ops-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function opAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function opOffice(?Governorate $governorate = null): Office
{
    return Office::factory()->create([
        'governorate_id' => ($governorate ?? Governorate::factory()->create())->id,
    ]);
}

function makeOperator(Office $office, string $startedOn = '2026-09-01', string $name = 'أحمد محمد'): DataEntryOperator
{
    $operator = DataEntryOperator::factory()->create(['name' => $name, 'phone' => '01012345678']);

    DataEntryAssignment::create([
        'operator_id' => $operator->id,
        'office_id'   => $office->id,
        'started_on'  => $startedOn,
    ]);

    return $operator->fresh();
}

// ── النطاق ───────────────────────────────────────────────

it('يرى المفتش مدخلي محافظاته وحدها', function () {
    $mine   = Governorate::factory()->create();
    $others = Governorate::factory()->create();

    makeOperator(opOffice($mine), name: 'مدخل محافظتي');
    makeOperator(opOffice($others), name: 'مدخل محافظة أخرى');

    $this->actingAs(opUser(['data-entry.index'], [$mine]));

    Livewire::test(Index::class)
        ->assertSee('مدخل محافظتي')
        ->assertDontSee('مدخل محافظة أخرى');
});

it('لا يرى صاحب الصلاحية بلا محافظة شيئاً', function () {
    makeOperator(opOffice(), name: 'مدخل ما');

    $this->actingAs(opUser(['data-entry.index']));

    Livewire::test(Index::class)->assertDontSee('مدخل ما');
});

it('يرى السوبر أدمن مدخلي الجمهورية كلها', function () {
    makeOperator(opOffice(), name: 'مدخل الأولى');
    makeOperator(opOffice(), name: 'مدخل الثانية');

    $this->actingAs(opAdmin());

    Livewire::test(Index::class)
        ->assertSee('مدخل الأولى')
        ->assertSee('مدخل الثانية');
});

it('لا تسرّب منسدلة المقرات مقرات خارج النطاق', function () {
    $mine = Governorate::factory()->create();
    opOffice($mine);
    $outside = Office::factory()->create(['name' => 'مقر خارج النطاق']);

    $this->actingAs(opUser(['data-entry.index'], [$mine]));

    Livewire::test(Index::class)->assertDontSee($outside->name);
});

it('لا يُخرج فلتر محافظةٍ ليست للمستخدم شيئاً', function () {
    $mine   = Governorate::factory()->create();
    $others = Governorate::factory()->create();

    makeOperator(opOffice($mine), name: 'مدخل محافظتي');
    makeOperator(opOffice($others), name: 'مدخل محافظة أخرى');

    $this->actingAs(opUser(['data-entry.index'], [$mine]));

    Livewire::test(Index::class)
        ->set('governorate', (string) $others->id)
        ->assertDontSee('مدخل محافظة أخرى')
        ->assertDontSee('مدخل محافظتي');
});

it('لا يمسّ معرّفٌ مدسوس من محافظة أخرى', function () {
    $mine    = Governorate::factory()->create();
    $outside = makeOperator(opOffice(), name: 'مدخل بعيد');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit', 'data-entry.delete'], [$mine]));

    expect(fn () => Livewire::test(Index::class)->call('askTransfer', $outside->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::test(Index::class)->call('askDelete', $outside->id))
        ->toThrow(ModelNotFoundException::class);
});

// ── الصلاحيات ────────────────────────────────────────────

it('تفتح data-entry.index القائمة ولا تعطي نقلاً ولا حذفاً', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov));

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    $this->get(route('data-entry.index'))->assertOk();

    Livewire::test(Index::class)->call('askTransfer', $operator->id)->assertForbidden();
    Livewire::test(Index::class)->call('askEnd', $operator->id)->assertForbidden();
    Livewire::test(Index::class)->call('askDelete', $operator->id)->assertForbidden();
});

it('تحرس شاشةُ الإضافة صلاحيةَ الإنشاء وشاشةُ التعديل صلاحيةَ التعديل', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov));

    $this->actingAs(opUser(['data-entry.index'], [$gov]));
    $this->get(route('data-entry.operators.create'))->assertForbidden();
    $this->get(route('data-entry.operators.edit', $operator))->assertForbidden();

    $this->actingAs(opUser(['data-entry.index', 'data-entry.create'], [$gov]));
    $this->get(route('data-entry.operators.create'))->assertOk();
    $this->get(route('data-entry.operators.edit', $operator))->assertForbidden();

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));
    $this->get(route('data-entry.operators.edit', $operator))->assertOk();
});

it('يمنع تعديل مدخلٍ خارج النطاق ولو ملك الصلاحية', function () {
    $mine    = Governorate::factory()->create();
    $outside = makeOperator(opOffice());

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$mine]));

    $this->get(route('data-entry.operators.edit', $outside))->assertForbidden();
});

// ── الإضافة ──────────────────────────────────────────────

it('تُنشئ الإضافةُ المدخلَ مع تسكينه الأول', function () {
    $gov    = Governorate::factory()->create();
    $office = opOffice($gov);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.create'], [$gov]));

    Livewire::test(Create::class)
        ->set('name', 'محمود سعيد')
        ->set('phone', '01099999999')
        ->set('governorate', (string) $gov->id)
        ->set('office', (string) $office->id)
        ->set('started_on', '2026-09-15')
        ->call('save')
        ->assertHasNoErrors();

    $operator = DataEntryOperator::firstWhere('name', 'محمود سعيد');

    expect($operator)->not->toBeNull()
        ->and($operator->currentAssignment->office_id)->toBe($office->id)
        ->and($operator->currentAssignment->started_on->toDateString())->toBe('2026-09-15');
});

it('ترفض الإضافةُ مقراً خارج النطاق', function () {
    $mine    = Governorate::factory()->create();
    $outside = opOffice();

    $this->actingAs(opUser(['data-entry.index', 'data-entry.create'], [$mine]));

    Livewire::test(Create::class)
        ->set('name', 'محاولة')
        ->set('office', (string) $outside->id)
        ->set('started_on', '2026-09-01')
        ->call('save')
        ->assertHasErrors('office');

    expect(DataEntryOperator::count())->toBe(0);
});

it('لا يمسّ تعديلُ البيانات التسكينَ', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Create::class, ['operator' => $operator])
        ->set('name', 'اسم بعد التعديل')
        ->call('save')
        ->assertHasNoErrors();

    expect($operator->fresh()->name)->toBe('اسم بعد التعديل')
        ->and($operator->assignments()->count())->toBe(1)
        ->and($operator->fresh()->currentAssignment->started_on->toDateString())->toBe('2026-09-01');
});

// ── النقل ────────────────────────────────────────────────

it('يُغلق النقلُ التسكينَ السابق في اليوم السابق ويفتح جديداً', function () {
    $gov      = Governorate::factory()->create();
    $from     = opOffice($gov);
    $to       = opOffice($gov);
    $operator = makeOperator($from, '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askTransfer', $operator->id)
        ->set('transferOffice', (string) $to->id)
        ->set('transferDate', '2026-09-16')
        ->call('transfer')
        ->assertHasNoErrors();

    $assignments = $operator->assignments()->orderBy('started_on')->get();

    expect($assignments)->toHaveCount(2)
        ->and($assignments[0]->ended_on->toDateString())->toBe('2026-09-15')
        ->and($assignments[0]->end_reason)->toBe(DataEntryAssignment::REASON_TRANSFER)
        ->and($assignments[1]->office_id)->toBe($to->id)
        ->and($assignments[1]->ended_on)->toBeNull();
});

it('لا يُضاعف النقلُ أيامَ عمل المدخل', function () {
    // ⚠️ يومٌ واحد بمقرّين يُعدّ مرتين في تقرير المحافظة — والإغلاق قبل الفتح يمنعه
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askTransfer', $operator->id)
        ->set('transferOffice', (string) opOffice($gov)->id)
        ->set('transferDate', '2026-09-16')
        ->call('transfer');

    $days = WorkingDays::operatorCalendar($operator->fresh(), '2026-09-01', '2026-09-30');

    // لا يومَ يحمله تسكينان: الفحص على التداخل نفسه لا على العدّ وحده — عدُّ أيام
    // المدخل يوحّد اليوم المكرّر تلقائياً، وتقريرُ المقر هو الذي يعدّه مرتين.
    $overlapping = $operator->fresh()->assignments->filter->overlapsExisting();

    expect($days)->toHaveCount(26)
        ->and($overlapping)->toHaveCount(0);
});

it('يرفض تاريخ نقلٍ لا يتجاوز بداية التسكين الحالي', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-10');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askTransfer', $operator->id)
        ->set('transferOffice', (string) opOffice($gov)->id)
        ->set('transferDate', '2026-09-05')
        ->call('transfer')
        ->assertHasErrors('transferDate');

    expect($operator->assignments()->count())->toBe(1);
});

it('يرفض النقل إلى مقر خارج النطاق', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askTransfer', $operator->id)
        ->set('transferOffice', (string) opOffice()->id)
        ->set('transferDate', '2026-09-16')
        ->call('transfer')
        ->assertHasErrors('transferOffice');

    expect($operator->assignments()->count())->toBe(1);
});

it('يتجاهل النقل إن لم يُفتح المودال', function () {
    // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->set('transferOperatorId', $operator->id)
        ->set('transferOffice', (string) opOffice($gov)->id)
        ->set('transferDate', '2026-09-16')
        ->call('transfer');

    expect($operator->assignments()->count())->toBe(1);
});

// ── إنهاء الخدمة ─────────────────────────────────────────

it('يُغلق إنهاءُ الخدمة التسكينَ ويُخرج المدخل من الخدمة', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askEnd', $operator->id)
        ->set('endDate', '2026-09-20')
        ->call('endService')
        ->assertHasNoErrors();

    $assignment = $operator->assignments()->first();

    expect($assignment->ended_on->toDateString())->toBe('2026-09-20')
        ->and($assignment->end_reason)->toBe(DataEntryAssignment::REASON_LEFT)
        ->and($operator->fresh()->isInService())->toBeFalse()
        // ⚠️ لا يُحذف: أيام خدمته السابقة تبقى في التقرير
        ->and(WorkingDays::operatorCalendar($operator->fresh(), '2026-09-01', '2026-09-30'))->toHaveCount(17);
});

it('يرفض تاريخ إنهاءٍ يسبق بداية التسكين', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-10');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askEnd', $operator->id)
        ->set('endDate', '2026-09-05')
        ->call('endService')
        ->assertHasErrors('endDate');

    expect($operator->assignments()->first()->ended_on)->toBeNull();
});

// ── الحذف ────────────────────────────────────────────────

it('يحذف المدخلَ بلا سجل حضور', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov));

    $this->actingAs(opUser(['data-entry.index', 'data-entry.delete'], [$gov]));

    Livewire::test(Index::class)->call('askDelete', $operator->id)->call('deleteRow');

    expect(DataEntryOperator::count())->toBe(0)
        ->and(DataEntryAssignment::count())->toBe(0);
});

it('يمنع حذف مدخلٍ له سجل حضور', function () {
    // ⚠️ الحذف لتصحيح إدخالٍ خاطئ لا لطيّ تاريخ موظف
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov));

    AttendanceDay::create([
        'attendable_type' => DataEntryOperator::class,
        'attendable_id'   => $operator->id,
        'date'            => '2026-09-07',
        'status_id'       => AttendanceStatus::where('name', 'غائب')->value('id'),
    ]);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.delete'], [$gov]));

    Livewire::test(Index::class)->call('askDelete', $operator->id)->call('deleteRow');

    expect(DataEntryOperator::count())->toBe(1)
        ->and(AttendanceDay::count())->toBe(1);
});

// ── الفلاتر ──────────────────────────────────────────────

it('يبحث بالعربية المطبَّعة وبالهاتف', function () {
    $gov = Governorate::factory()->create();
    makeOperator(opOffice($gov), name: 'إسماعيل أحمد');

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    Livewire::test(Index::class)->set('search', 'اسماعيل')->assertSee('إسماعيل أحمد');
    Livewire::test(Index::class)->set('search', '0101234')->assertSee('إسماعيل أحمد');

    // ⚠️ قيمة محافظة تالفة تصل من الرابط: تُهمَل ولا تُخرج شاشة فارغة
    Livewire::test(Index::class)->set('governorate', 'أي كلام')->assertSee('إسماعيل أحمد');
});

it('يفصل فلترُ الحالة المؤرشَف عمّن على رأس العمل', function () {
    $gov     = Governorate::factory()->create();
    // ⚠️ أسماء لا تشبه نصوص المنسدلة نفسها، وإلا صار assertDontSee يفحص الفلتر لا الصفوف
    $working = makeOperator(opOffice($gov), name: 'سعيد الباقي');
    $left    = makeOperator(opOffice($gov), name: 'كامل المغادر');
    $left->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    Livewire::test(Index::class)->set('status', 'in_service')
        ->assertSee($working->name)->assertDontSee($left->name);

    Livewire::test(Index::class)->set('status', 'ended')
        ->assertSee($left->name)->assertDontSee($working->name);
});

it('يقصر فلترُ النوع الصفوفَ على مقرات نوعه', function () {
    $gov      = Governorate::factory()->create();
    $shahr    = OfficeType::factory()->create(['name' => 'مكتب شهر عقاري']);
    $tawtheeq = OfficeType::factory()->create(['name' => 'مكتب توثيق']);

    $inShahr = makeOperator(
        Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $shahr->id]),
        name: 'سعيد الشاهر'
    );
    $inTawtheeq = makeOperator(
        Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $tawtheeq->id]),
        name: 'كامل الموثّق'
    );

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    Livewire::test(Index::class)->set('officeType', (string) $shahr->id)
        ->assertSee($inShahr->name)->assertDontSee($inTawtheeq->name);

    // ⚠️ قيمة نوع تالفة تصل من الرابط: تُهمَل ولا تُخرج شاشة فارغة
    Livewire::test(Index::class)->set('officeType', 'أي كلام')
        ->assertSee($inShahr->name)->assertSee($inTawtheeq->name);
});

it('يضيّق النوعُ منسدلةَ المقرات ويُصفّر المقر المختار', function () {
    // ⚠️ خيارٌ بلا صفوف خلفه يُوهم المستخدم أن الشاشة معطّلة لا أن مقراتها من نوعٍ آخر
    $gov      = Governorate::factory()->create();
    $shahr    = OfficeType::factory()->create();
    $tawtheeq = OfficeType::factory()->create();

    $shahrOffice    = Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $shahr->id]);
    $tawtheeqOffice = Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $tawtheeq->id]);

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    $component = Livewire::test(Index::class)->set('office', (string) $tawtheeqOffice->id);

    $component->set('officeType', (string) $shahr->id)->assertSet('office', '');

    expect($component->viewData('offices')->pluck('id')->all())->toBe([$shahrOffice->id]);
});
// ── الترتيب وعدد الصفوف ──────────────────────────────────

it('يرتّب بالاسم ويعكسه ثم يعود للافتراضي', function () {
    $gov = Governorate::factory()->create();
    makeOperator(opOffice($gov), name: 'ياسر');
    makeOperator(opOffice($gov), name: 'أحمد');

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    $component = Livewire::test(Index::class);

    $names = fn () => $component->viewData('operators')->pluck('name')->all();

    expect($names())->toBe(['أحمد', 'ياسر']);          // الافتراضي أبجدي

    $component->call('sort', 'name');
    expect($names())->toBe(['أحمد', 'ياسر']);

    $component->call('sort', 'name');                  // تنازلي
    expect($names())->toBe(['ياسر', 'أحمد']);

    $component->call('sort', 'name');                  // ← الافتراضي
    expect($component->get('sortBy'))->toBe('')
        ->and($names())->toBe(['أحمد', 'ياسر']);
});

it('يسقط عمود ترتيبٍ خارج القائمة البيضاء إلى الافتراضي', function () {
    // ⚠️ اسم العمود يصل من الرابط — تمريره لـorderBy بلا قائمة بيضاء حقنُ SQL
    $gov = Governorate::factory()->create();
    makeOperator(opOffice($gov), name: 'ياسر');
    makeOperator(opOffice($gov), name: 'أحمد');

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    $component = Livewire::test(Index::class)->set('sortBy', 'phone) --');

    expect($component->viewData('operators')->pluck('name')->all())->toBe(['أحمد', 'ياسر']);
});

it('يمسح زرُّ المسح الفلاتر والترتيب معاً', function () {
    $gov = Governorate::factory()->create();
    makeOperator(opOffice($gov), name: 'أحمد');

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    Livewire::test(Index::class)
        ->set('search', 'أحمد')
        ->set('officeType', '7')
        ->set('status', 'all')
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('governorate', '')
        ->assertSet('office', '')
        ->assertSet('officeType', '')
        ->assertSet('status', 'in_service')
        ->assertSet('sortBy', '');
});

it('يحصر عددَ الصفوف الآتي من الرابط في القائمة المسموحة', function () {
    $gov = Governorate::factory()->create();
    makeOperator(opOffice($gov), name: 'أحمد');

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    $component = Livewire::test(Index::class)->set('perPage', '9999');

    expect($component->viewData('operators')->perPage())->toBe(15);
});

// ── عرض أسماء المقرات الطويلة ────────────────────────────

it('يقصّ اسم المقر الطويل عند حدّ كلمة ويُبقي القصير كما هو', function () {
    // ⚠️ أسماء المقرات تبلغ ١٣٦ حرفاً، والمنسدلة تتّسع لأطول خيار فيها
    $long  = 'حفظ مستغل ( الدفترخانة ومخازن المكتب ومحفوظات توثيق أول طنطا ) مكتب طنطا محافظة الغربية';
    $short = 'توثيق زفتى';

    $cut = App\Support\ArabicText::shorten($long);

    expect(App\Support\ArabicText::shorten($short))->toBe($short)
        ->and(mb_strlen($cut))->toBeLessThan(mb_strlen($long))
        ->and($cut)->toEndWith('…')
        // القصّ عند حدّ كلمة: ما قبل علامة القطع كلمة كاملة من الأصل
        ->and(str_contains($long, trim(str_replace('…', '', $cut))))->toBeTrue();
});

it('يعرض المنسدلةُ الاسمَ مقصوصاً والكاملَ في title', function () {
    $gov  = Governorate::factory()->create();
    $long = 'مأمورية شهر المجتمعات العمرانية الجديدة بمدينة رشيد الجديدة منشأة حديثاً لم يتم التشغيل';
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => $long]);

    $this->actingAs(opUser(['data-entry.index'], [$gov]));

    Livewire::test(Index::class)
        ->assertSee(App\Support\ArabicText::shorten($long))
        ->assertSee('title="'.$long.'"', escape: false);
});

// ── إعادة التسكين ────────────────────────────────────────

it('تُعيد إعادةُ التسكين المؤرشَفَ إلى رأس العمل بتسكين جديد', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');
    $operator->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $back = opOffice($gov);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askReassign', $operator->id)
        ->set('reassignOffice', (string) $back->id)
        ->set('reassignDate', '2026-09-21')
        ->call('reassign')
        ->assertHasNoErrors();

    $operator = $operator->fresh();

    expect($operator->isInService())->toBeTrue()
        ->and($operator->assignments()->count())->toBe(2)
        ->and($operator->currentAssignment->office_id)->toBe($back->id)
        // ⚠️ مدة الانقطاع تبقى خارج الحساب: ٩ أيام عمل قبلها (١–١٠ بلا الجمعة ٤)
        //    و٩ بعدها (٢١–٣٠ بلا الجمعة ٢٥) = ١٨ لا ٢٦ يوماً
        ->and(WorkingDays::operatorCalendar($operator, '2026-09-01', '2026-09-30'))->toHaveCount(18)
        ->and($operator->assignments->filter->overlapsExisting())->toHaveCount(0);
});

it('يرفض تاريخ عودةٍ لا يتجاوز نهاية آخر تسكين', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');
    $operator->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askReassign', $operator->id)
        ->set('reassignOffice', (string) opOffice($gov)->id)
        ->set('reassignDate', '2026-09-10')
        ->call('reassign')
        ->assertHasErrors('reassignDate');

    expect($operator->assignments()->count())->toBe(1);
});

it('يرفض إعادة تسكين مقرٍّ خارج النطاق، ومَن هو على رأس العمل', function () {
    $gov      = Governorate::factory()->create();
    $archived = makeOperator(opOffice($gov), '2026-09-01', name: 'مؤرشف');
    $archived->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);
    $working  = makeOperator(opOffice($gov), '2026-09-01', name: 'قائم');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->call('askReassign', $archived->id)
        ->set('reassignOffice', (string) opOffice()->id)   // محافظة أخرى
        ->set('reassignDate', '2026-09-21')
        ->call('reassign')
        ->assertHasErrors('reassignOffice');

    Livewire::test(Index::class)
        ->call('askReassign', $working->id)
        ->set('reassignOffice', (string) opOffice($gov)->id)
        ->set('reassignDate', '2026-09-21')
        ->call('reassign');

    expect($archived->assignments()->count())->toBe(1)
        ->and($working->assignments()->count())->toBe(1);
});

it('يتجاهل إعادة التسكين إن لم يُفتح المودال', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');
    $operator->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    Livewire::test(Index::class)
        ->set('reassignOperatorId', $operator->id)
        ->set('reassignOffice', (string) opOffice($gov)->id)
        ->set('reassignDate', '2026-09-21')
        ->call('reassign');

    expect($operator->assignments()->count())->toBe(1);
});

it('يحرس إعادةَ التسكين بصلاحية التعديل وبالنطاق', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');
    $operator->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $this->actingAs(opUser(['data-entry.index'], [$gov]));
    Livewire::test(Index::class)->call('askReassign', $operator->id)->assertForbidden();

    $outside = makeOperator(opOffice(), '2026-09-01');
    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));
    expect(fn () => Livewire::test(Index::class)->call('askReassign', $outside->id))
        ->toThrow(ModelNotFoundException::class);
});

// ── محافظة المودالين ─────────────────────────────────────

it('يفتح مودالُ النقل على محافظة المقر الحالي ويحصر قائمته بها', function () {
    $here  = Governorate::factory()->create();
    $there = Governorate::factory()->create();

    $operator = makeOperator(opOffice($here), '2026-09-01');
    Office::factory()->create(['governorate_id' => $there->id, 'name' => 'مقر المحافظة الأخرى']);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$here, $there]));

    $component = Livewire::test(Index::class)->call('askTransfer', $operator->id);

    expect($component->get('transferGovernorate'))->toBe((string) $here->id)
        ->and($component->viewData('transferOffices')->pluck('governorate_id')->unique()->all())
        ->toBe([$here->id]);

    // وتغيير المحافظة يفتح مقرات الأخرى
    $component->set('transferGovernorate', (string) $there->id);

    expect($component->viewData('transferOffices')->pluck('name')->all())
        ->toBe(['مقر المحافظة الأخرى']);
});

it('يُصفّر تغييرُ محافظة المودال المقرَّ المختار', function () {
    // ⚠️ وإلا حُفظ تسكينٌ في مقرٍّ لا ينتمي للمحافظة المعروضة
    $here  = Governorate::factory()->create();
    $there = Governorate::factory()->create();
    $operator = makeOperator(opOffice($here), '2026-09-01');

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$here, $there]));

    Livewire::test(Index::class)
        ->call('askTransfer', $operator->id)
        ->set('transferOffice', (string) opOffice($here)->id)
        ->set('transferGovernorate', (string) $there->id)
        ->assertSet('transferOffice', '');
});

it('يفتح مودالُ إعادة التسكين على محافظة آخر تسكين', function () {
    $gov      = Governorate::factory()->create();
    $operator = makeOperator(opOffice($gov), '2026-09-01');
    $operator->assignments()->first()->update(['ended_on' => '2026-09-10', 'end_reason' => 'left']);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$gov]));

    $component = Livewire::test(Index::class)->call('askReassign', $operator->id);

    expect($component->get('reassignGovernorate'))->toBe((string) $gov->id)
        ->and($component->viewData('reassignOffices')->pluck('governorate_id')->unique()->all())
        ->toBe([$gov->id]);

    $component->set('reassignOffice', (string) opOffice($gov)->id)
        ->set('reassignGovernorate', '')
        ->assertSet('reassignOffice', '');
});

it('لا تسرّب قائمةُ مقرات المودال ما هو خارج النطاق', function () {
    $mine    = Governorate::factory()->create();
    $operator = makeOperator(opOffice($mine), '2026-09-01');
    Office::factory()->create(['name' => 'مقر خارج النطاق']);

    $this->actingAs(opUser(['data-entry.index', 'data-entry.edit'], [$mine]));

    $component = Livewire::test(Index::class)->call('askTransfer', $operator->id)
        ->set('transferGovernorate', '');

    expect($component->viewData('transferOffices')->pluck('name')->all())
        ->not->toContain('مقر خارج النطاق');
});
