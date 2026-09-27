<?php

use App\Livewire\Contractors\AttendanceFile;
use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Models\User;
use App\Support\Contractors\AttendanceCompactFile;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
 * سبتمبر ٢٠٢٦: ٣٠ يوماً، الجُمَع ٤ · ١١ · ١٨ · ٢٥ — أيام العمل ٢٦.
 * الصفوف تبدأ من السطر ٧، وعمود كل حالة يُعرف بمعرّفها في السطر ٥ المخفي.
 */

function cfUser(array $governorates): User
{
    Permission::findOrCreate('contractors.attendance', 'web');
    $role = Role::findOrCreate('cf-'.uniqid(), 'web');
    $role->givePermissionTo('contractors.attendance');

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function cfWorker(Office $office, string $name, string $from = '2026-09-01', ?string $to = null): Contractor
{
    $contractor = Contractor::factory()->create(['name' => $name]);
    ContractorAssignment::create(['contractor_id' => $contractor->id, 'office_id' => $office->id, 'started_on' => $from, 'ended_on' => $to]);

    return $contractor;
}

function cfStatus(string $name): int
{
    return AttendanceStatus::where('name', $name)->value('id');
}

function cfMark(Contractor $worker, string $date, string $status): void
{
    AttendanceDay::create(['attendable_type' => Contractor::class, 'attendable_id' => $worker->id, 'date' => $date, 'status_id' => cfStatus($status)]);
}

function cfPath(Governorate $gov, string $month = '2026-09'): string
{
    return (new AttendanceCompactFile($gov, CarbonImmutable::parse($month.'-01')))->saveTo(tempnam(sys_get_temp_dir(), 'cf_').'.xlsx');
}

/** عمود الحالة في الكشف — بمعرّفها في السطر المخفي لا بموضعٍ مفترض. */
function cfColumn($sheet, string $status): int
{
    for ($col = 7; $col < 20; $col++) {
        if ((string) $sheet->getCell([$col, 5])->getValue() === (string) cfStatus($status)) {
            return $col;
        }
    }

    throw new RuntimeException('no column for '.$status);
}

/** ينزّل الكشف ويكتب فيه كما يفعل المفتش: [سطر => [حالة => نصّ]] · وحذف سطر بـ null. */
function cfFilled(Governorate $gov, array $edits, string $month = '2026-09', ?callable $tamper = null): UploadedFile
{
    $path  = cfPath($gov, $month);
    $book  = IOFactory::load($path);
    $sheet = $book->getSheetByName('الكشف');

    foreach ($edits as $line => $cells) {
        if ($cells === null) {
            $sheet->removeRow($line);

            continue;
        }

        foreach ($cells as $status => $value) {
            $sheet->setCellValue([cfColumn($sheet, $status), $line], $value);
        }
    }

    if ($tamper) {
        $tamper($sheet);
    }

    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();

    return UploadedFile::fake()->createWithContent('kashf.xlsx', file_get_contents($path));
}

function cfScreen(Governorate $gov, string $month = '2026-09')
{
    return Livewire::withQueryParams(['gov' => $gov->id, 'month' => $month])->test(AttendanceFile::class);
}

function cfErrors($screen): array
{
    return collect($screen->get('filePreview')['errors'])->map(fn ($e) => [$e['message'], $e['cells'] ?? null])->all();
}

// ── القالب ───────────────────────────────────────────────

it('ينزل الكشف المختصر بأيام العمل والأيام المقفولة والمسجَّل بأرقامٍ عربية', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $full   = cfWorker($office, 'أ من أول الشهر');
    cfWorker($office, 'ب ملتحق يوم ١٥', '2026-09-15');
    cfMark($full, '2026-09-02', 'غائب');
    cfMark($full, '2026-09-03', 'غائب');

    $sheet = IOFactory::load(cfPath($gov))->getSheetByName('الكشف');

    expect($sheet->getCell('A7')->getValue())->toBe($full->id)
        ->and($sheet->getCell('C7')->getValue())->toBe('أ من أول الشهر')
        ->and($sheet->getCell('E7')->getValue())->toBe(26)                     // ٣٠ − ٤ جُمَع
        ->and($sheet->getCell('F7')->getValue())->toBe('٤، ١١، ١٨، ٢٥')
        ->and($sheet->getCell([cfColumn($sheet, 'غائب'), 7])->getValue())->toBe('٢، ٣')   // المسجَّل ينزل كما هو
        ->and($sheet->getCell([cfColumn($sheet, 'إجازة'), 7])->getValue())->toBeNull()
        ->and($sheet->getCell([cfColumn($sheet, 'غائب'), 6])->getValue())->toBe('أيام غائب')
        // الملتحق: ما قبل التحاقه مدىً واحد بالكلمات لا سرد أيام
        ->and($sheet->getCell('E8')->getValue())->toBe(14)
        ->and($sheet->getCell('F8')->getValue())->toBe('١٨، ٢٥، قبل ١٥ (خارج هذا المقر)')
        // ⚠️ خانات الإدخال نصٌّ قبل الكتابة — وإلا «3,7» تصير 37
        ->and($sheet->getStyle([cfColumn($sheet, 'غائب'), 7])->getNumberFormat()->getFormatCode())->toBe('@')
        ->and($sheet->getRowDimension(5)->getVisible())->toBeFalse();
});

it('يسمّي العطلة في رأس الكشف ويصف النقل في منتصف الشهر', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    cfWorker($office, 'منقول', '2026-09-01', '2026-09-20');
    OfficialHoliday::create(['name' => 'عطلة تجربة', 'starts_on' => '2026-09-16', 'ends_on' => '2026-09-16']);

    $sheet = IOFactory::load(cfPath($gov))->getSheetByName('الكشف');

    expect($sheet->getCell('C2')->getValue())->toContain('العطلات: ١٦ (عطلة تجربة)')
        ->and($sheet->getCell('F7')->getValue())->toBe('٤، ١١، ١٦، ١٨، من ٢١ لآخر الشهر (خارج هذا المقر)')
        ->and($sheet->getCell('E7')->getValue())->toBe(16);   // ١–٢٠ − ٣ جُمَع − عطلة
});

it('يقفل الورقة إلا خانات الأيام ويُبرز الجُمَع والعطلات بالأحمر', function () {
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');

    $sheet  = IOFactory::load(cfPath($gov))->getSheetByName('الكشف');
    $locked = fn (string|array $cell) => $sheet->getStyle($cell)->getProtection()->getLocked() !== \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED;
    $notes  = collect(range(7, 15))->first(fn ($col) => $sheet->getCell([$col, 6])->getValue() === 'ملاحظات');

    expect($sheet->getProtection()->getSheet())->toBeTrue()
        ->and($locked([$notes, 7]))->toBeTrue()
        ->and($locked([$notes - 1, 7]))->toBeTrue()                           // حاضر
        ->and($locked('A7'))->toBeTrue()                                        // المعرّف المخفي
        ->and($locked([cfColumn($sheet, 'غائب'), 7]))->toBeFalse()
        ->and($locked([cfColumn($sheet, 'إجازة'), 7]))->toBeFalse()
        ->and($sheet->getStyle('C2')->getFont()->getColor()->getRGB())->toBe('DC2626')
        ->and($sheet->getStyle('C2')->getFont()->getSize())->toBeGreaterThan(12);
});

it('تذكر الملاحظة كل الأيام المقفولة والمكرّرة لا أولها', function () {
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');

    $book  = IOFactory::load(cfPath($gov));
    $sheet = $book->getSheetByName('الكشف');
    $notes = collect(range(7, 15))->first(fn ($col) => $sheet->getCell([$col, 6])->getValue() === 'ملاحظات');
    $note  = function (string $leave, string $absent) use ($sheet, $notes) {
        $sheet->setCellValue([cfColumn($sheet, 'إجازة'), 7], $leave);
        $sheet->setCellValue([cfColumn($sheet, 'غائب'), 7], $absent);
        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($sheet->getParent())->clearCalculationCache();

        return $sheet->getCell([$notes, 7])->getCalculatedValue();
    };

    expect($note('', '٤'))->toBe('يوم ٤ لا يُكتب لهذا العامل')
        ->and($note('4', '11، 25'))->toBe('أيام ٤، ١١، ٢٥ لا تُكتب لهذا العامل')
        ->and($note('3، 5', '3، 5، 8'))->toBe('أيام ٣، ٥ مكتوبة في خانتين معاً')
        ->and($note('', '3، 7'))->toBe('');

    $book->disconnectWorksheets();
});

// ── الحفظ ────────────────────────────────────────────────

it('يحفظ أيام الغياب والإجازات ويعلّم وصل الكشف لكل مَن في الملف', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $first  = cfWorker($office, 'أ عامل');
    $second = cfWorker($office, 'ب عامل');
    $user   = cfUser([$gov]);
    $this->actingAs($user);

    cfScreen($gov)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => '٣، ٧', 'إجازة' => '10,14']]))
        ->assertSet('filePreview.error', null)
        ->assertSet('filePreview.summary.created', 4)
        ->assertSet('filePreview.summary.workers', 2)
        ->call('importMonthFile');

    $days = AttendanceDay::where('attendable_id', $first->id)->get()
        ->mapWithKeys(fn ($d) => [$d->date->day => $d->status_id])->sortKeys()->all();

    expect($days)->toBe([3 => cfStatus('غائب'), 7 => cfStatus('غائب'), 10 => cfStatus('إجازة'), 14 => cfStatus('إجازة')])
        ->and(AttendanceDay::where('attendable_id', $second->id)->count())->toBe(0)
        ->and(AttendanceReview::pluck('attendable_id')->sort()->values()->all())->toBe([$first->id, $second->id]);
});

it('يقبل كل كتابةٍ لها معنى واحد', function (mixed $written) {
    $gov    = Governorate::factory()->create();
    $worker = cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => $written]]))
        ->assertSet('filePreview.errors', [])
        ->call('importMonthFile');

    expect(AttendanceDay::where('attendable_id', $worker->id)->get()->map(fn ($d) => $d->date->day)->sort()->values()->all())
        ->toBe(is_int($written) ? [$written] : [3, 7]);
})->with([
    'فاصلة عربية'      => '3، 7',
    'أرقام عربية'      => '٣، ٧',
    'أرقام فارسية'     => '۳، ۷',
    'فاصلة إنجليزية'   => '3,7',
    'مسافة'            => '3 7',
    'واو'              => '3 و7',
    'فواصل زائدة'      => '،3،،7،',
    'تكرار في الخانة'  => '3، 7، 3',
    'صفر في أول الرقم' => '03، 07',
    'رقم لا نصّ'        => 3,
]);

it('يرفض الفترة والحروف والرقم خارج الشهر ويوماً في خانتين — ويحفظ الصفوف السليمة', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    foreach (['أ', 'ب', 'ج', 'د', 'هـ', 'و', 'ز'] as $letter) {
        cfWorker($office, $letter.' عامل');
    }
    $this->actingAs(cfUser([$gov]));

    $screen = cfScreen($gov)->set('monthFile', cfFilled($gov, [
        7  => ['غائب' => '10-14'],
        8  => ['إجازة' => '10 إلى 14'],
        9  => ['غائب' => 'غ'],
        10 => ['غائب' => '3، 31'],                     // سبتمبر ٣٠ يوماً
        11 => ['غائب' => '0'],
        12 => ['غائب' => '3، 8', 'إجازة' => '8'],
        13 => ['غائب' => '5'],                         // السليم
    ]));

    expect(cfErrors($screen))->toBe([
        ['range', '«10-14»'],
        ['range', '«10 إلى 14»'],
        ['not_numbers', '«غ»'],
        ['out_of_month', '31'],
        ['out_of_month', '0'],
        ['overlap', '8'],
    ]);

    $screen->call('importMonthFile');

    // الصفّ المرفوض لا يُحفظ جزئياً ولا يُعلَّم وصل كشفه
    expect(AttendanceDay::count())->toBe(1)
        ->and(AttendanceDay::first()->date->day)->toBe(5)
        ->and(AttendanceReview::count())->toBe(1);
});

it('يرفض يوماً مقفولاً ويسمّي سببه: جمعة · عطلة · خارج المقر', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    cfWorker($office, 'أ من أول الشهر');
    cfWorker($office, 'ب ملتحق يوم ١٥', '2026-09-15');
    OfficialHoliday::create(['name' => 'عطلة تجربة', 'starts_on' => '2026-09-16', 'ends_on' => '2026-09-16']);
    $this->actingAs(cfUser([$gov]));

    $screen = cfScreen($gov)->set('monthFile', cfFilled($gov, [
        7 => ['غائب' => '4، 16، 17'],
        8 => ['إجازة' => '10، 17'],
    ]));

    expect(cfErrors($screen))->toBe([
        ['closed_day', '4 (جمعة)، 16 (عطلة تجربة)'],
        ['closed_day', '10 (خارج هذا المقر)'],
    ]);

    $screen->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('رفع الكشف كما نزل لا يغيّر شيئاً، والخانة الراجعة فارغة تحذف', function () {
    $gov    = Governorate::factory()->create();
    $worker = cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    cfMark($worker, '2026-09-02', 'غائب');
    cfMark($worker, '2026-09-08', 'إجازة');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov)
        ->set('monthFile', cfFilled($gov, []))
        ->assertSet('filePreview.summary.created', 0)
        ->assertSet('filePreview.summary.deleted', 0)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => null]]))
        ->assertSet('filePreview.summary.deleted', 1)
        ->call('importMonthFile');

    expect(AttendanceDay::pluck('status_id')->all())->toBe([cfStatus('إجازة')]);
});

it('لا يمسّ عاملاً حُذف صفّه من الملف', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $kept   = cfWorker($office, 'أ باقٍ');
    $gone   = cfWorker($office, 'ب محذوف من الملف');
    cfMark($gone, '2026-09-02', 'غائب');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov)->set('monthFile', cfFilled($gov, [8 => null]))->call('importMonthFile');

    expect(AttendanceDay::where('attendable_id', $gone->id)->count())->toBe(1)
        ->and(AttendanceReview::pluck('attendable_id')->all())->toBe([$kept->id]);
});

it('يعطي الحالة المعطَّلة عموداً إن كانت لها أيام فلا يمحوها الرفع', function () {
    $gov    = Governorate::factory()->create();
    $worker = cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    $old    = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#7c3aed', 'order' => 9, 'is_active' => true]);
    cfMark($worker, '2026-09-02', 'مأمورية');
    $old->update(['is_active' => false]);
    $this->actingAs(cfUser([$gov]));

    $sheet = IOFactory::load(cfPath($gov))->getSheetByName('الكشف');
    expect($sheet->getCell([cfColumn($sheet, 'مأمورية'), 7])->getValue())->toBe('٢');

    cfScreen($gov)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => '3']]))
        ->assertSet('filePreview.summary.deleted', 0)
        ->call('importMonthFile');

    expect(AttendanceDay::where('status_id', $old->id)->count())->toBe(1);
});

it('لا يعطي حالةً معطَّلة بلا أيام عموداً', function () {
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#7c3aed', 'order' => 9, 'is_active' => false]);

    $sheet = IOFactory::load(cfPath($gov))->getSheetByName('الكشف');

    expect(fn () => cfColumn($sheet, 'مأمورية'))->toThrow(RuntimeException::class);
});

// ── المعاينة مقرّاً مقرّاً ──────────────────────────────────

it('تعرض المعاينة كل مقر بعماله وأيامهم: الجديد والباقي وما سيُحذف', function () {
    $gov    = Governorate::factory()->create();
    $first  = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'أ مقر']);
    $second = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'ب مقر']);
    $worker = cfWorker($first, 'عامل المقر الأول');
    cfWorker($first, 'عامل بلا تغيير');
    cfWorker($second, 'عامل المقر الثاني');
    cfMark($worker, '2026-09-02', 'غائب');    // سيُحذف
    cfMark($worker, '2026-09-08', 'إجازة');   // باقٍ
    $this->actingAs(cfUser([$gov]));

    // السطر ٧ عامل المقر الأول · ٨ عامل بلا تغيير · ٩ عامل الثاني بيومٍ مقفول (الجمعة)
    $details = cfScreen($gov)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => '3'], 9 => ['غائب' => '4']]))
        ->get('filePreview')['details'];

    $a = collect($details['offices'])->firstWhere('id', $first->id);
    $b = collect($details['offices'])->firstWhere('id', $second->id);

    expect(collect($details['offices'])->pluck('name')->all())->toBe(['أ مقر', 'ب مقر'])
        ->and($a['workers'][0]['cells'])->toBe([
            cfStatus('غائب')  => [2 => 'removed', 3 => 'new'],
            cfStatus('إجازة') => [8 => 'kept'],
        ])
        ->and($a['deleted'])->toBe(1)
        ->and($a['created'])->toBe(1)
        ->and($a['untouched'])->toBe(0)
        // مَن في الملف بلا أيامٍ يظهر أيضاً — المفتش يقارن المقرّ كله بورقته
        ->and(collect($a['workers'])->pluck('name')->all())->toContain('عامل بلا تغيير')
        // الصفّ المرفوض في مقرّه لا فوق المقرات
        ->and($b['errors'][0]['message'])->toBe('closed_day')
        // ⚠️ صاحب الصفّ المرفوض في الملف — لا يُعدّ «ليس في الملف»
        ->and($b['untouched'])->toBe(0)
        ->and($details['loose'])->toBe([]);
});

it('تذكر المعاينة عدد عاملي المقر الغائبين عن الملف وتفتح المقرّ الذي فيه حذف', function () {
    $gov    = Governorate::factory()->create();
    $calm   = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'أ مقر هادئ']);
    $risky  = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'ب مقر فيه حذف']);
    cfWorker($calm, 'عامل هادئ');
    cfWorker($calm, 'عامل حُذف صفّه');
    $marked = cfWorker($risky, 'عامل سيُحذف غيابه');
    cfMark($marked, '2026-09-02', 'غائب');
    $this->actingAs(cfUser([$gov]));

    // الأسطر: ٧ عامل حُذف صفّه · ٨ عامل هادئ · ٩ عامل سيُحذف غيابه (بترتيب الاسم داخل المقر)
    $screen = cfScreen($gov)->set('monthFile', cfFilled($gov, [7 => null, 8 => ['غائب' => '3'], 9 => ['غائب' => null]]));

    $offices = collect($screen->get('filePreview')['details']['offices'])->keyBy('name');

    // ما فيه حذفٌ فوق البقية ولو سبقه غيره في الترتيب الأبجدي
    expect($offices->keys()->all())->toBe(['ب مقر فيه حذف', 'أ مقر هادئ'])
        ->and($offices['أ مقر هادئ']['untouched'])->toBe(1)
        ->and($offices['ب مقر فيه حذف']['deleted'])->toBe(1);

    $html = $screen->html();

    // المقرّ الذي فيه حذف مفتوحٌ من البداية، والهادئ مطويّ
    expect($html)->toMatch('/wire:key="pv-'.$risky->id.'-[a-f0-9]+"\s+x-data="\{ open: true \}"/')
        ->and($html)->toMatch('/wire:key="pv-'.$calm->id.'-[a-f0-9]+"\s+x-data="\{ open: false \}"/')
        ->and($html)->toContain(__('home.ct_att_pv_untouched', ['count' => 1]));
});

it('يبحث في المعاينة بالاسم بالتطبيع العربي ويفتح مقرّ مَن وُجد', function () {
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'أ مقر']), 'أحمد فكري');
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'ب مقر']), 'سامية حسن');
    $this->actingAs(cfUser([$gov]));

    $screen = cfScreen($gov)->set('monthFile', cfFilled($gov, []))->set('previewSearch', 'احمد');

    $offices = $screen->viewData('previewOffices');

    expect(collect($offices)->pluck('name')->all())->toBe(['أ مقر'])
        ->and(collect($offices[0]['workers'])->pluck('name')->all())->toBe(['أحمد فكري'])
        ->and($offices[0]['match'])->toBeTrue();
});

// ── الرفض على مستوى الملف ────────────────────────────────

it('يرفض كشفاً حُذف منه عمود حالة', function () {
    // عمودٌ محذوف يُزيح ما بعده: الغياب يُقرأ إجازة
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov)
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => '3']], tamper: fn ($sheet) => $sheet->removeColumnByIndex(7)))
        ->assertSet('filePreview.error', 'template_changed')
        ->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('يرفض كشفاً مختصراً لشهرٍ آخر', function () {
    $gov = Governorate::factory()->create();
    cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل', '2026-08-01');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov, '2026-09')
        ->set('monthFile', cfFilled($gov, [7 => ['غائب' => '3']], '2026-08'))
        ->assertSet('filePreview.error', 'wrong_month')
        ->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('يرفض معرّفاً تالفاً أو صفّاً مضافاً باليد', function () {
    $gov    = Governorate::factory()->create();
    $worker = cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    $this->actingAs(cfUser([$gov]));

    $screen = cfScreen($gov)->set('monthFile', cfFilled($gov, [7 => ['غائب' => '3']], tamper: function ($sheet) use ($worker) {
        $sheet->setCellValueExplicit('A7', $worker->id.'x', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C8', 'عامل جديد باليد');
    }));

    expect(collect($screen->get('filePreview')['errors'])->pluck('message')->all())->toBe(['no_id', 'no_id']);

    $screen->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

// ── الصفحة ───────────────────────────────────────────────

it('ينزّل الكشفين من الصفحة ويعرف نوع المرفوع وحده', function () {
    $gov    = Governorate::factory()->create();
    $worker = cfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    $this->actingAs(cfUser([$gov]));

    cfScreen($gov)
        ->assertSee(__('home.ct_att_file_download_compact', ['label' => 'سبتمبر 2026 — '.$gov->name]))
        ->call('downloadMonthFile', 'compact')
        ->assertFileDownloaded('كشف الحضور المختصر - '.$gov->name.' - 2026-09.xlsx')
        ->call('downloadMonthFile', 'days')
        ->assertFileDownloaded('كشف الحضور - '.$gov->name.' - 2026-09.xlsx')
        // نوعٌ مجهول من العميل يسقط لكشف الأيام لا لخطأ
        ->call('downloadMonthFile', 'x')
        ->assertFileDownloaded('كشف الحضور - '.$gov->name.' - 2026-09.xlsx');
});
