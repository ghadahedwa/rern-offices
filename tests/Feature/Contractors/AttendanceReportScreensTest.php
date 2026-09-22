<?php

use App\Livewire\Contractors\Reports\ContractorReport;
use App\Livewire\Contractors\Reports\GovernorateReport;
use App\Livewire\Contractors\Reports\OfficeReport;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
 * سبتمبر ٢٠٢٦: ٣٠ يوماً − ٤ جُمَع (٤ · ١١ · ١٨ · ٢٥) = ٢٦ يوم عمل.
 */

function repUser(array $governorates = [], array $abilities = ['contractors.index']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('rep-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

/*
 * helpers محلية للملف — الدوال العامة في Pest لا تُحمَّل من ملفٍ آخر حين يُشغَّل
 * ملفٌ واحد وحده، فلكل ملفٍ بادئته.
 */
function scrOffice(?Governorate $governorate = null): Office
{
    return Office::factory()->create(['governorate_id' => ($governorate ?? Governorate::factory()->create())->id]);
}

function scrWorker(Office $office, string $from = '2026-09-01', ?string $to = null, string $name = 'عامل التقرير'): Contractor
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

function scrAssign(Contractor $contractor, Office $office, string $from, ?string $to = null): ContractorAssignment
{
    return ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => $from,
        'ended_on'      => $to,
    ]);
}

function scrMark(Contractor $contractor, string $date, string $status = 'غائب'): App\Models\AttendanceDay
{
    return App\Models\AttendanceDay::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $contractor->id,
        'date'            => $date,
        'status_id'       => App\Models\AttendanceStatus::where('name', $status)->firstOrFail()->id,
    ]);
}

/**
 * ورقة Excel كما كُتبت فعلاً — الطريقة الوحيدة لفحص ما خرج في الملف.
 *
 * ⚠️ تُرجَع **الورقة** لا الصفوف وحدها: عطلُ إزاحة الرأس يقع في **التنسيق** لا في
 *    البيانات، فلا يكشفه فحص القيم. (نفس درس `PrintBarTest` في رأي المواطن.)
 */
function scrSheet(object $export): PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
{
    $binary = Maatwebsite\Excel\Facades\Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    $path   = tempnam(sys_get_temp_dir(), 'xlsx');

    file_put_contents($path, $binary);
    $sheet = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->load($path)->getSheet(0);
    unlink($path);

    return $sheet;
}

function repShow($component, array $filters = [])
{
    return Livewire::test($component, $filters)
        ->set('from', '2026-09-01')
        ->set('to', '2026-09-30')
        ->call('search');
}

// ── الحراسة ─────────────────────────────────────────────────────────────

it('يحجب التقارير الثلاثة عمّن لا يملك contractors.index', function ($component) {
    $this->actingAs(repUser([], ['contractors.attendance']));

    Livewire::test($component)->assertStatus(403);
})->with([GovernorateReport::class, OfficeReport::class, ContractorReport::class]);

it('يفتح التقارير لصاحب contractors.index', function ($component) {
    $this->actingAs(repUser([]));

    Livewire::test($component)->assertOk();
})->with([GovernorateReport::class, OfficeReport::class, ContractorReport::class]);

// ── النطاق ──────────────────────────────────────────────────────────────

it('يعرض محافظات المستخدم وحدها في تقرير المحافظات', function () {
    $mine   = Governorate::factory()->create(['name' => 'محافظتي']);
    $theirs = Governorate::factory()->create(['name' => 'محافظة أخرى']);

    scrWorker(scrOffice($mine), name: 'عاملي');
    scrWorker(scrOffice($theirs), name: 'عامل غيري');

    $this->actingAs(repUser([$mine]));

    $groups = repShow(GovernorateReport::class)->viewData('groups');

    expect(array_keys($groups))->toBe([$mine->id]);
});

it('لا يُظهر في تقريري أيامَ عاملٍ نُقل إلى محافظةٍ ليست لي', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();
    $office = scrOffice($mine);

    $worker = scrWorker($office, '2026-09-01', '2026-09-15');
    scrAssign($worker, scrOffice($theirs), '2026-09-16');

    $this->actingAs(repUser([$mine]));

    $groups = repShow(GovernorateReport::class)->viewData('groups');

    // ١–١٥ سبتمبر = ١٣ يوم عمل؛ والنصف الثاني في محافظةٍ أخرى فلا يظهر.
    expect(array_keys($groups))->toBe([$mine->id])
        ->and($groups[$mine->id]['working'])->toBe(13);
});

it('يُهمل محافظةً من الفورم ليست في نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    scrWorker(scrOffice($mine));
    scrWorker(scrOffice($theirs));

    $this->actingAs(repUser([$mine]));

    $groups = Livewire::test(GovernorateReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateIds', [$theirs->id])
        ->call('search')
        ->viewData('groups');

    expect($groups)->toBe([]);
});

it('يُخرج جدولاً فارغاً لمستخدمٍ بلا محافظات', function () {
    scrWorker(scrOffice());

    $this->actingAs(repUser([]));

    expect(repShow(GovernorateReport::class)->viewData('groups'))->toBe([]);
});

it('يرى السوبر أدمن كل المحافظات بلا تحديد', function () {
    $first  = Governorate::factory()->create();
    $second = Governorate::factory()->create();

    scrWorker(scrOffice($first));
    scrWorker(scrOffice($second));

    Permission::findOrCreate('contractors.index', 'web');
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($admin);

    expect(repShow(GovernorateReport::class)->viewData('groups'))->toHaveCount(2);
});

// ── تقرير المقر ─────────────────────────────────────────────────────────

it('يقصر تقرير المقر على المقر المختار', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $other  = scrOffice($gov);

    scrWorker($office, name: 'عامل المقر');
    scrWorker($other, name: 'عامل مقر آخر');

    $this->actingAs(repUser([$gov]));

    $rows = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)
        ->set('officeId', $office->id)
        ->call('search')
        ->viewData('rows');

    expect(collect($rows)->pluck('contractor_name')->all())->toBe(['عامل المقر']);
});

it('لا يُخرج صفوفاً لمقرٍّ مدسوس من خارج نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();
    $office = scrOffice($theirs);

    scrWorker(scrOffice($mine));
    scrWorker($office, name: 'عامل محافظة أخرى');

    $this->actingAs(repUser([$mine]));

    $rows = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('officeId', $office->id)
        ->call('search')
        ->viewData('rows');

    expect($rows)->toBe([]);
});

it('لا يُخرج أيام مقرٍّ خارج النطاق ولو كان عاملُه مرئياً لي', function () {
    // ⚠️ الحالة التي لا يمسكها فلتر العاملين: العامل **مرئيٌّ لي** بتسكينه القديم
    //    عندي، فلو مُرِّر مقرُّه الجديد كما وصل لَظهرت أيامُ محافظةٍ ليست لي.
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();
    $abroad = scrOffice($theirs);

    $worker = scrWorker(scrOffice($mine), '2026-09-01', '2026-09-15');
    scrAssign($worker, $abroad, '2026-09-16');

    $this->actingAs(repUser([$mine]));

    $rows = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('officeId', $abroad->id)
        ->call('search')
        ->viewData('rows');

    expect($rows)->toBe([]);
});

it('لا يُخرج في تقرير العامل أيامه في محافظةٍ ليست لي', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $worker = scrWorker(scrOffice($mine), '2026-09-01', '2026-09-15');
    scrAssign($worker, scrOffice($theirs), '2026-09-16');

    $this->actingAs(repUser([$mine]));

    $rows = Livewire::test(ContractorReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('contractorId', $worker->id)
        ->call('search')
        ->viewData('rows');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['working'])->toBe(13);
});

it('يرفض تقرير المقر بلا اختيار محافظة ولا يعرض شيئاً', function () {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov]));

    // ⚠️ بلا محافظة كان يجمع عاملي كل مقرات النطاق — تقرير جمهورية لا تقرير مقر.
    repShow(OfficeReport::class)
        ->assertSet('hasSearched', false)
        ->assertSee(__('home.ct_rep_office_prompt'));
});

it('يُصفّر نتيجةً معروضة حين تُمسح المحافظة ويُعاد العرض', function () {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov]));

    $screen = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)
        ->call('search');

    expect($screen->viewData('rows'))->toHaveCount(1);

    // نتيجةٌ قديمة تحت محدداتٍ جديدة تُقرأ على أنها نتيجتها
    $screen->set('governorateId', null)->call('search')
        ->assertSet('hasSearched', false);

    expect($screen->viewData('rows'))->toBe([]);
});

it('يُصفّر المقر عند تغيير المحافظة', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);

    $this->actingAs(repUser([$gov]));

    Livewire::test(OfficeReport::class)
        ->set('officeId', $office->id)
        ->set('governorateId', $gov->id)
        ->assertSet('officeId', null);
});

// ── البحث داخل المنسدلات ────────────────────────────────────────────────

it('يبحث في منسدلة المقرات بتطبيع الألف والتاء المربوطة', function () {
    $gov = Governorate::factory()->create();

    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'مكتب توثيق الإسماعيلية']);
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'مكتب توثيق بورسعيد']);

    $this->actingAs(repUser([$gov]));

    // ⚠️ بلا `ArabicText` لا يجد «الاسماعيليه» ما كُتب «الإسماعيلية»
    $options = Livewire::test(OfficeReport::class)
        ->set('governorateId', $gov->id)
        ->set('officeSearch', 'الاسماعيليه')
        ->viewData('offices');

    expect(collect($options)->pluck('title')->all())->toBe(['مكتب توثيق الإسماعيلية']);
});

it('يُبقي المقر المختار في القائمة ولو لم يطابق البحث', function () {
    $gov      = Governorate::factory()->create();
    $selected = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'مكتب المختار']);
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'مكتب آخر']);

    $this->actingAs(repUser([$gov]));

    // ⚠️ بدونه يختفي اختياره من المنسدلة فيبدو أنه ضاع أو انتقل إلى غيره صامتاً
    $options = Livewire::test(OfficeReport::class)
        ->set('governorateId', $gov->id)
        ->set('officeId', $selected->id)
        ->set('officeSearch', 'لا يطابق شيئاً')
        ->viewData('offices');

    expect(collect($options)->pluck('id')->all())->toBe([$selected->id]);
});

it('يبحث في منسدلة العاملين ولا يتجاوز النطاق', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    scrWorker(scrOffice($mine), name: 'سعيد أحمد');
    scrWorker(scrOffice($mine), name: 'محمود علي');
    scrWorker(scrOffice($theirs), name: 'سعيد الغريب');

    $this->actingAs(repUser([$mine]));

    $options = Livewire::test(ContractorReport::class)
        ->set('contractorSearch', 'سعيد')
        ->viewData('candidates');

    expect(collect($options)->pluck('label')->all())->toBe(['سعيد أحمد']);
});

it('يمسح بحث المنسدلة عند تغيير المحافظة', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(repUser([$gov]));

    // بحثٌ من محافظةٍ سابقة يُخرج قائمةً فارغة في الجديدة بلا سببٍ ظاهر
    Livewire::test(OfficeReport::class)
        ->set('officeSearch', 'بحث قديم')
        ->set('governorateId', $gov->id)
        ->assertSet('officeSearch', '');
});

// ── تقرير العامل ────────────────────────────────────────────────────────

it('لا يُخرج بيانات عاملٍ بمعرّفٍ مدسوس من خارج النطاق', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    scrWorker(scrOffice($mine));
    $stranger = scrWorker(scrOffice($theirs), name: 'غريب');

    $this->actingAs(repUser([$mine]));

    $screen = Livewire::test(ContractorReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('contractorId', $stranger->id)
        ->call('search');

    expect($screen->viewData('subject'))->toBeNull()
        ->and($screen->viewData('rows'))->toBe([]);
});

it('يفرّق بين «لم تختر عاملاً» و«لا بيانات» في تقرير العامل', function () {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov]));

    // بلا اختيار: يطلب الاختيار ولا يقول «لا عاملين»
    repShow(ContractorReport::class)
        ->assertSee(__('home.ct_rep_need_contractor'))
        ->assertDontSee(__('home.ct_rep_empty'));
});

it('يفصّل تواريخ الغياب والإجازة في تقرير العامل', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    scrMark($worker, '2026-09-02');
    scrMark($worker, '2026-09-07', 'إجازة');

    $this->actingAs(repUser([$gov]));

    $exceptions = Livewire::test(ContractorReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('contractorId', $worker->id)
        ->call('search')
        ->viewData('exceptions');

    expect(array_keys($exceptions))->toBe(['2026-09-02', '2026-09-07'])
        ->and($exceptions['2026-09-02']['status'])->toBe('غائب')
        ->and($exceptions['2026-09-07']['status'])->toBe('إجازة');
});

it('لا يعرض في التفصيل يوماً خرج من الحساب — العطلة المتأخرة', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    scrMark($worker, '2026-09-02');
    App\Models\OfficialHoliday::create(['name' => 'عطلة متأخرة', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-02']);

    $this->actingAs(repUser([$gov]));

    $exceptions = Livewire::test(ContractorReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('contractorId', $worker->id)
        ->call('search')
        ->viewData('exceptions');

    expect($exceptions)->toBe([]);
});

// ── المدى ───────────────────────────────────────────────────────────────

it('يتجاهل تاريخاً تالفاً ولا يعرض شيئاً', function () {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov]));

    Livewire::test(GovernorateReport::class)
        ->set('from', 'مش تاريخ')
        ->call('search')
        ->assertSet('hasSearched', false);
});

it('يطبّق اختصار الشهر الماضي على الحقلين', function () {
    $this->actingAs(repUser([]));

    $screen = Livewire::test(GovernorateReport::class)->call('applyPeriod', 'last_month');

    $expected = App\Support\WorkingDays::today()->subMonth();

    expect($screen->get('from'))->toBe($expected->startOfMonth()->toDateString())
        ->and($screen->get('to'))->toBe($expected->endOfMonth()->toDateString());
});

// ── التصدير ─────────────────────────────────────────────────────────────

it('يمنع التصدير من التقارير الثلاثة بلا contractors.export', function ($component) {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov]));

    repShow($component)->call('exportExcel')->assertStatus(403);
})->with([GovernorateReport::class, OfficeReport::class, ContractorReport::class]);

it('يسمح بالتصدير لصاحب contractors.export', function () {
    $gov = Governorate::factory()->create();
    scrWorker(scrOffice($gov));

    $this->actingAs(repUser([$gov], ['contractors.index', 'contractors.export']));

    repShow(GovernorateReport::class)
        ->call('exportExcel')
        ->assertFileDownloaded('contractors-governorates-2026-09-01-2026-09-30.xlsx');
});

it('يُنزّل ملفاً فعلياً من التقارير الثلاثة', function ($component, $base) {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    scrMark($worker, '2026-09-02');

    $this->actingAs(repUser([$gov], ['contractors.index', 'contractors.export']));

    $screen = Livewire::test($component)->set('from', '2026-09-01')->set('to', '2026-09-30');

    if ($component === ContractorReport::class) {
        $screen->set('contractorId', $worker->id);
    }

    if ($component === OfficeReport::class) {
        $screen->set('governorateId', $gov->id);   // إلزامية في هذا التقرير وحده
    }

    $screen->call('search')
        ->call('exportExcel')
        ->assertFileDownloaded($base.'-2026-09-01-2026-09-30.xlsx');
})->with([
    [GovernorateReport::class, 'contractors-governorates'],
    [OfficeReport::class, 'contractors-office'],
    [ContractorReport::class, 'contractor-attendance'],
]);

it('يكتب في الملف أرقامَ الشاشة نفسها ويغلق المعادلة', function () {
    // ⚠️ يُقرأ محتوى الـxlsx فعلاً — نجاحُ الاستدعاء وحده لا يقول إن الأرقام صحيحة.
    $gov    = Governorate::factory()->create(['name' => 'محافظة الملف']);
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    scrMark($worker, '2026-09-02');
    scrMark($worker, '2026-09-03', 'إجازة');

    $this->actingAs(repUser([$gov], ['contractors.index', 'contractors.export']));

    $rows = Livewire::test(GovernorateReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->call('search')
        ->viewData('groups');

    $export = new App\Exports\ContractorsAttendanceExport(
        rows: $rows,
        statuses: App\Support\Contractors\AttendanceReport::statusColumns(array_merge(...array_column($rows, 'rows'))),
        subjectLabel: 'المحافظة',
        subjectKey: 'governorate_name',
        withContractorCount: true,
        title: 'تقرير المحافظات',
        period: 'من ٢٠٢٦-٠٩-٠١ إلى ٢٠٢٦-٠٩-٣٠',
        breakdown: ['total' => 30, 'weekend' => 4, 'holidays' => 0, 'working' => 26]
    );

    $sheet = scrSheet($export);
    $cells = $sheet->toArray();

    // الصفوف: عنوان · فترة · تفكيك · رؤوس · صفّ المحافظة · الإجمالي
    // ⚠️ لا صفَّ فاصلاً فارغاً — الكاتب يُسقطه فيزيح الرؤوس عمّا يحسبه التنسيق.
    $head = $cells[3];
    $line = $cells[4];

    expect($head[0])->toBe('المحافظة')
        ->and($head[1])->toBe('عدد العاملين')
        ->and($head[2])->toBe('أيام العمل')
        ->and($head[3])->toBe('حضر')
        // ⚠️ لا عمود «غير مراجَع» — أيامه داخل «حضر» (طلب المستخدمة)
        ->and($head)->not->toContain('غير مراجَع')
        ->and($line[0])->toBe('محافظة الملف')
        ->and((int) $line[1])->toBe(1)
        ->and((int) $line[2])->toBe(26)
        // أيام العمل = حضر + الحالات — المعادلة مغلقة في الملف أيضاً
        ->and((int) $line[3] + (int) $line[4] + (int) $line[5])->toBe(26)
        ->and($cells[5][0])->toBe('الإجمالي')
        // ⚠️ الصفر يُكتب صفراً لا خانةً فارغة — الفارغة تُقرأ «لا بيانات»
        ->and($line[3])->not->toBeNull()
        // ⚠️ ولون صفّ الرؤوس يُقاس: الإزاحة تصبغ أول صفّ بيانات بلون الرأس
        //    وتترك الرأس بلا لون، والقيم كلها سليمة فلا يكشفها فحص البيانات.
        ->and($sheet->getStyle('A4')->getFill()->getStartColor()->getRGB())->toBe('C9A847')
        ->and($sheet->getStyle('A5')->getFill()->getStartColor()->getRGB())->not->toBe('C9A847');
});

it('لا يُنزّل ملفاً قبل الضغط على «عرض التقرير»', function () {
    $this->actingAs(repUser([], ['contractors.index', 'contractors.export']));

    Livewire::test(GovernorateReport::class)->call('exportExcel')->assertNoFileDownloaded();
});

// ── «لم تُرصد أيام حضوره» ────────────────────────────────────────────────

it('يحسب أيام مَن لم يصل كشفه حضوراً فتقفل المعادلة', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    scrMark($worker, '2026-09-02');   // غياب مسجَّل، وبلا «وصل الكشف» للمقر

    $this->actingAs(repUser([$gov]));

    $rows = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)
        ->call('search')
        ->viewData('rows');

    $row = $rows[0];

    // ⚠️ «حضر» المعروض يبتلع غير المرصود، فالأعمدة تجمع على أيام العمل بالضبط
    expect(App\Support\Contractors\AttendanceReport::attended($row) + array_sum($row['exceptions']))
        ->toBe($row['working'])
        ->and(App\Support\Contractors\AttendanceReport::attended($row))->toBe(25);
});

it('ينبّه بعدد مَن لم تُرصد أيام حضورهم، ولا ينبّه حين رُصدوا', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    $this->actingAs(repUser([$gov]));

    $screen = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)
        ->call('search');

    expect($screen->viewData('unrecorded'))->toBe(1);
    $screen->assertSee(__('home.ct_rep_unrecorded_one'));

    // ووصولُ الكشف يُسكت التنبيه
    App\Models\AttendanceReview::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $worker->id,
        'office_id'       => $office->id,
        'month'           => '2026-09-01',
    ]);

    $after = Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)
        ->call('search');

    expect($after->viewData('unrecorded'))->toBe(0);
    $after->assertDontSee(__('home.ct_rep_unrecorded_one'));
});

it('لا يعرض «غير مراجَع» عموداً في أي من التقارير الثلاثة', function () {
    $gov    = Governorate::factory()->create();
    $office = scrOffice($gov);
    $worker = scrWorker($office);

    $this->actingAs(repUser([$gov]));

    repShow(GovernorateReport::class)->assertDontSee('غير مراجَع');

    Livewire::test(OfficeReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('governorateId', $gov->id)->call('search')
        ->assertDontSee('غير مراجَع');

    Livewire::test(ContractorReport::class)
        ->set('from', '2026-09-01')->set('to', '2026-09-30')
        ->set('contractorId', $worker->id)->call('search')
        ->assertDontSee('غير مراجَع');
});
