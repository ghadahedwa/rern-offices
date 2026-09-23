<?php

use App\Livewire\Contractors\Reports\BranchDashboard;
use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\Profession;
use App\Models\User;
use App\Support\Contractors\AttendanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
 * سبتمبر ٢٠٢٦: ٣٠ يوماً − ٤ جُمَع (٤ · ١١ · ١٨ · ٢٥) = ٢٦ يوم عمل.
 */

function dashUser(array $governorates = [], array $abilities = ['contractors.index']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('dash-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function dashOffice(?Governorate $governorate = null): Office
{
    return Office::factory()->create(['governorate_id' => ($governorate ?? Governorate::factory()->create())->id]);
}

function dashWorker(Office $office, string $from = '2026-09-01', ?string $to = null, ?int $professionId = null): Contractor
{
    $contractor = Contractor::factory()->create(array_filter(['profession_id' => $professionId]));

    ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => $from,
        'ended_on'      => $to,
    ]);

    return $contractor->fresh();
}

function dashScreen()
{
    return Livewire::test(BranchDashboard::class)
        ->set('from', '2026-09-01')
        ->set('to', '2026-09-30');
}

// ── الحراسة والدخول ─────────────────────────────────────────────────────

it('يحجب اللوحة عمّن لا يملك contractors.index', function () {
    $this->actingAs(dashUser([], ['contractors.attendance']));

    Livewire::test(BranchDashboard::class)->assertStatus(403);
});

it('يجعل اللوحة صفحة دخول الفرع لصاحب العرض', function () {
    $this->actingAs(dashUser([]));

    $this->get(route('contractors.dashboard'))->assertOk();
});

// ── تعرض فوراً بلا ضغط زرّ ──────────────────────────────────────────────

it('يعرض الأرقام فور فتح اللوحة بلا ضغط زرّ', function () {
    // ⚠️ الطبقة المشتركة مبنيّة على «حدّد ثم اعرض»، واللوحة تُفتح للنظرة الأولى.
    $gov = Governorate::factory()->create();
    dashWorker(dashOffice($gov));

    $this->actingAs(dashUser([$gov]));

    $screen = Livewire::test(BranchDashboard::class);

    expect($screen->get('hasSearched'))->toBeTrue()
        ->and($screen->viewData('headline')['in_service'])->toBe(1);
});

it('يعيد الحساب فور تغيّر المدى', function () {
    $gov    = Governorate::factory()->create();
    $office = dashOffice($gov);
    dashWorker($office);

    $this->actingAs(dashUser([$gov]));

    $screen = Livewire::test(BranchDashboard::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-15');

    // ١–١٥ سبتمبر: جُمَعه ٤ · ١١ ⇒ ١٣ يوم عمل
    expect($screen->viewData('attendance')['working'])->toBe(13);

    $screen->set('to', '2026-09-30');

    expect($screen->viewData('attendance')['working'])->toBe(26);
});

// ── الأعداد ─────────────────────────────────────────────────────────────

it('يعدّ على رأس العمل بتعريف شاشة العاملين نفسه', function () {
    // ⚠️ تعريفٌ ثانٍ هنا يجعل اللوحة تخالف القائمة برقمٍ بلا أن يُعرف أيُّهما الصحيح
    $gov    = Governorate::factory()->create();
    $office = dashOffice($gov);

    dashWorker($office);                                   // تسكينٌ مفتوح
    dashWorker($office, '2026-01-01', '2026-08-31');        // منتهية خدمته

    $this->actingAs(dashUser([$gov]));

    $headline = dashScreen()->viewData('headline');

    expect($headline['in_service'])->toBe(1)
        ->and($headline['archived'])->toBe(1)
        ->and($headline['offices'])->toBe(1)
        ->and($headline['governorates'])->toBe(1);
});

it('يقصر الأعداد على نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    dashWorker(dashOffice($mine));
    dashWorker(dashOffice($theirs));
    dashWorker(dashOffice($theirs));

    $this->actingAs(dashUser([$mine]));

    expect(dashScreen()->viewData('headline')['in_service'])->toBe(1);
});

it('يُخرج أصفاراً لمستخدمٍ بلا محافظات', function () {
    dashWorker(dashOffice());

    $this->actingAs(dashUser([]));

    $screen = dashScreen();

    expect($screen->viewData('headline')['in_service'])->toBe(0)
        ->and($screen->viewData('byGovernorate'))->toBe([])
        ->and($screen->viewData('attendance')['working'])->toBe(0);
});

// ── التوزيع ─────────────────────────────────────────────────────────────

it('يعرض محافظات النطاق كلها في المخطط حتى الخالية', function () {
    // ⚠️ المحافظة بصفرٍ معلومة: «لا عاملين هنا» ليس «المحافظة غير موجودة»
    $full  = Governorate::factory()->create(['name' => 'بها عاملون', 'order' => 1]);
    $empty = Governorate::factory()->create(['name' => 'بلا عاملين', 'order' => 2]);

    dashWorker(dashOffice($full));

    $this->actingAs(dashUser([$full, $empty]));

    $rows = dashScreen()->viewData('byGovernorate');

    expect($rows)->toBe([
        ['label' => 'بها عاملون', 'value' => 1],
        ['label' => 'بلا عاملين', 'value' => 0],
    ]);
});

it('يوزّع العاملين على الصفات ويُسقط الصفة الخالية', function () {
    $gov    = Governorate::factory()->create();
    $office = dashOffice($gov);

    $entry     = Profession::where('is_system', true)->firstOrFail();
    $translator = Profession::where('id', '!=', $entry->id)->firstOrFail();

    dashWorker($office, professionId: $entry->id);
    dashWorker($office, professionId: $entry->id);
    dashWorker($office, professionId: $translator->id);

    $this->actingAs(dashUser([$gov]));

    $rows = collect(dashScreen()->viewData('byProfession'))->pluck('value', 'label');

    expect($rows[$entry->name])->toBe(2)
        ->and($rows[$translator->name])->toBe(1)
        ->and($rows)->toHaveCount(2);   // الصفات الثلاث الباقية خالية فلا تظهر
});

it('يُهمل محافظةً من الفلتر ليست في نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    dashWorker(dashOffice($mine));
    dashWorker(dashOffice($theirs));

    $this->actingAs(dashUser([$mine]));

    $screen = dashScreen()->set('governorateIds', [$theirs->id]);

    expect($screen->viewData('headline')['in_service'])->toBe(0)
        ->and($screen->viewData('byGovernorate'))->toBe([]);
});

// ── الحضور مفوَّض للمحرّك ────────────────────────────────────────────────

it('يعرض أرقام حضورٍ مطابقة لتقرير الفترة نفسها', function () {
    // ⚠️ لوحةٌ تحسب حضورها بنفسها تخالف تقرير الفترة، فيرى المستخدم رقمين لشيءٍ واحد
    $gov    = Governorate::factory()->create();
    $office = dashOffice($gov);
    $worker = dashWorker($office);

    AttendanceDay::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $worker->id,
        'date'            => '2026-09-02',
        'status_id'       => AttendanceStatus::where('name', 'غائب')->firstOrFail()->id,
    ]);

    $this->actingAs(dashUser([$gov]));

    $attendance = dashScreen()->viewData('attendance');

    expect($attendance['working'])->toBe(26)
        // المعادلة مقفولة على اللوحة كما في التقارير
        ->and(AttendanceReport::attended($attendance) + array_sum($attendance['exceptions']))->toBe(26)
        ->and($attendance['exceptions'][AttendanceStatus::where('name', 'غائب')->first()->id])->toBe(1);
});

it('ينبّه بعدد مَن لم تُرصد أيام حضورهم', function () {
    $gov = Governorate::factory()->create();
    dashWorker(dashOffice($gov));

    $this->actingAs(dashUser([$gov]));

    expect(dashScreen()->viewData('unrecorded'))->toBe(1);
});

// ── الوحدة تشرح نفسها ───────────────────────────────────────────────────

it('لا يعرض مجموع أيام العمل بطاقةً مفردة بل مقاماً يشرح وحدته', function () {
    // ⚠️ «أيام العمل» في التقارير أيامُ عاملٍ واحد (٢٦) وعلى اللوحة مجموع أيام كل
    //    العاملين (آلاف) — الكلمة واحدة والوحدة مختلفة، فالرقم الكبير وحده لبس.
    $gov    = Governorate::factory()->create();
    $office = dashOffice($gov);

    dashWorker($office);
    dashWorker($office);

    $this->actingAs(dashUser([$gov]));

    $screen = dashScreen();

    // ٢٦ يوم عمل × عاملين = ٥٢، ويظهر داخل جملةٍ تذكر العدد والعاملين معاً
    expect($screen->viewData('attendance')['working'])->toBe(52);

    $screen->assertSee(__('home.ct_dash_person_days', [
        'total'   => 52,
        'workers' => 2,
        'present' => 52,
    ]));
});

it('يعرض الصفات بطاقات والمحافظات مخططاً', function () {
    // ⚠️ `assertSee` على الاسم لا يفرّق: المخطط يكتب أسماءه في الـHTML أيضاً
    //    (`Js::from`). الفارق الحقيقي وجودُ `canvas` — فواحدٌ للمحافظات لا اثنان.
    $gov    = Governorate::factory()->create(['name' => 'محافظة الكروت']);
    $office = dashOffice($gov);

    dashWorker($office, professionId: Profession::where('is_system', true)->value('id'));

    $this->actingAs(dashUser([$gov]));

    $html = dashScreen()->html();

    expect(substr_count($html, 'x-ref="bar"'))->toBe(1)
        ->and($html)->toContain(__('home.ct_dash_by_profession'));
});

// ── الترتيب وسطر النطاق ─────────────────────────────────────────────────

it('يضع الأرقام قبل الفلتر لا بعده', function () {
    // ⚠️ اللوحة تُفتح لتُقرأ لا لتُملأ (طلب المستخدمة): فلترٌ فوق يدفع كل معلومة
    //    تحت حدّ الشاشة.
    $gov = Governorate::factory()->create();
    dashWorker(dashOffice($gov));

    $this->actingAs(dashUser([$gov]));

    $html = dashScreen()->html();

    expect(strpos($html, __('home.ct_dash_in_service')))
        ->toBeLessThan(strpos($html, __('home.ct_rep_filters')))
        ->and(strpos($html, __('home.ct_dash_by_governorate')))
        ->toBeLessThan(strpos($html, __('home.ct_rep_filters')));
});

it('يُعلن النطاق المُضيَّق فوق الأرقام ولا يُعلنه بلا تحديد', function () {
    // ⚠️ الفلتر أسفل الصفحة و**المحافظة تحرّك كل رقم فيها** — فقارئٌ لا يراه قد
    //    يقرأ أرقام محافظةٍ واحدة على أنها الجمهورية.
    $first  = Governorate::factory()->create(['name' => 'محافظة معروضة']);
    $second = Governorate::factory()->create(['name' => 'محافظة أخرى']);

    dashWorker(dashOffice($first));
    dashWorker(dashOffice($second));

    $this->actingAs(dashUser([$first, $second]));

    // بلا تحديد: لا سطر
    dashScreen()->assertDontSee(__('home.ct_dash_scoped_to'));

    // وبتحديد: السطر باسم المحافظة، وقبل الأرقام
    $html = dashScreen()->set('governorateIds', [$first->id])->html();

    expect($html)->toContain(__('home.ct_dash_scoped_to'))
        ->and($html)->toContain('محافظة معروضة')
        ->and(strpos($html, __('home.ct_dash_scoped_to')))
        ->toBeLessThan(strpos($html, __('home.ct_dash_in_service')));
});
