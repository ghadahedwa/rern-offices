<?php

use App\Livewire\Contractors\Attendance;
use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Models\User;
use App\Support\Contractors\AttendanceMonthFile;
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
 * سبتمبر ٢٠٢٦: يبدأ الثلاثاء — العمود F يوم ١، فيوم N في العمود رقم (5 + N).
 * الجُمَع ٤ · ١١ · ١٨ · ٢٥. والصفوف تبدأ من السطر ٥ مرتّبةً بالمقر ثم الاسم.
 */

function mfUser(array $governorates, array $abilities = ['contractors.attendance']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('mf-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function mfWorker(Office $office, string $name, string $from = '2026-09-01', ?string $to = null): Contractor
{
    $contractor = Contractor::factory()->create(['name' => $name]);

    ContractorAssignment::create(['contractor_id' => $contractor->id, 'office_id' => $office->id, 'started_on' => $from, 'ended_on' => $to]);

    return $contractor;
}

function mfStatus(string $name): int
{
    return AttendanceStatus::where('name', $name)->value('id');
}

function mfFile(Governorate $gov, string $month = '2026-09'): AttendanceMonthFile
{
    return new AttendanceMonthFile($gov, CarbonImmutable::parse($month.'-01'));
}

/** ينزّل الكشف ويعدّله كما يفعل المفتش: [سطر => [يوم => قيمة]] · وحذف سطر بـ null. */
function mfFilled(Governorate $gov, array $edits, string $month = '2026-09'): UploadedFile
{
    $path = mfFile($gov, $month)->saveTo(tempnam(sys_get_temp_dir(), 'mf_').'.xlsx');
    $book = IOFactory::load($path);
    $sheet = $book->getSheetByName('الكشف');

    foreach ($edits as $line => $days) {
        if ($days === null) {
            $sheet->removeRow($line);

            continue;
        }

        foreach ($days as $day => $value) {
            $sheet->setCellValue([5 + $day, $line], $value);
        }
    }

    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();

    return UploadedFile::fake()->createWithContent('kashf.xlsx', file_get_contents($path));
}

function mfScreen(Governorate $gov, string $month = '2026-09')
{
    return Livewire::withQueryParams(['gov' => $gov->id, 'month' => $month])->test(Attendance::class);
}

// ── القالب ───────────────────────────────────────────────

it('ينزل الكشف بعاملي المحافظة ومعرّفاتهم والأيام المقفولة والمسجَّل فعلاً', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'مقر أ']);
    $worker = mfWorker($office, 'عامل أول');
    mfWorker(Office::factory()->create(), 'عامل محافظة أخرى');

    AttendanceDay::create(['attendable_type' => Contractor::class, 'attendable_id' => $worker->id, 'date' => '2026-09-02', 'status_id' => mfStatus('غائب')]);

    $path  = mfFile($gov)->saveTo(tempnam(sys_get_temp_dir(), 'mf_').'.xlsx');
    $sheet = IOFactory::load($path)->getSheetByName('الكشف');

    expect($sheet->getCell('A5')->getValue())->toBe($worker->id)
        ->and($sheet->getCell('C5')->getValue())->toBe('عامل أول')
        ->and($sheet->getCell([5 + 2, 5])->getValue())->toBe('غ')     // المسجَّل ينزل كما هو
        ->and($sheet->getCell([5 + 4, 5])->getValue())->toBe('-')     // جمعة
        ->and($sheet->getCell([5 + 3, 5])->getValue())->toBeNull()     // حاضر = فارغ
        ->and($sheet->getCell('C6')->getValue())->toBeNull();           // لا صفّ لعامل محافظة أخرى
});

// ── الرفع والحفظ ─────────────────────────────────────────

it('يحفظ الغياب والإجازة ويعلّم وصل الكشف لكل مَن في الملف', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $first  = mfWorker($office, 'أ عامل');
    $second = mfWorker($office, 'ب عامل');
    $user   = mfUser([$gov]);

    $this->actingAs($user);

    mfScreen($gov)
        ->set('monthFile', mfFilled($gov, [5 => [2 => 'غ', 3 => 'إ']]))
        ->assertSet('filePreview.summary.created', 2)
        ->assertSet('filePreview.summary.workers', 2)
        ->call('importMonthFile');

    expect(AttendanceDay::where('attendable_id', $first->id)->pluck('status_id')->sort()->values()->all())
        ->toBe(collect([mfStatus('غائب'), mfStatus('إجازة')])->sort()->values()->all())
        ->and(AttendanceDay::where('attendable_id', $second->id)->count())->toBe(0)
        ->and(AttendanceReview::pluck('attendable_id')->sort()->values()->all())->toBe([$first->id, $second->id])
        ->and(AttendanceReview::first()->reviewed_by)->toBe($user->id);
});

it('يقرأ الهمزة بلا تمييز والاسم الكامل', function () {
    $gov    = Governorate::factory()->create();
    $worker = mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov)->set('monthFile', mfFilled($gov, [5 => [2 => 'ا', 3 => 'أ', 7 => 'غائب']]))->call('importMonthFile');

    expect(AttendanceDay::where('attendable_id', $worker->id)->count())->toBe(3);
});

it('يحذف استثناءً رجعت خليته فارغة', function () {
    $gov    = Governorate::factory()->create();
    $worker = mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    AttendanceDay::create(['attendable_type' => Contractor::class, 'attendable_id' => $worker->id, 'date' => '2026-09-02', 'status_id' => mfStatus('غائب')]);

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov)
        ->set('monthFile', mfFilled($gov, [5 => [2 => null]]))
        ->assertSet('filePreview.summary.deleted', 1)
        ->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('لا يمسّ عاملاً ليس في الملف ولا يعلّم وصل كشفه', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $kept   = mfWorker($office, 'أ باقٍ في الملف');
    $gone   = mfWorker($office, 'ب محذوف من الملف');
    AttendanceDay::create(['attendable_type' => Contractor::class, 'attendable_id' => $gone->id, 'date' => '2026-09-02', 'status_id' => mfStatus('غائب')]);

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov)->set('monthFile', mfFilled($gov, [6 => null]))->call('importMonthFile');

    expect(AttendanceDay::where('attendable_id', $gone->id)->count())->toBe(1)
        ->and(AttendanceReview::pluck('attendable_id')->all())->toBe([$kept->id]);
});

it('يرفض صفّاً فيه حرفٌ مجهول كاملاً ويحفظ الباقي', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id]);
    $bad    = mfWorker($office, 'أ صفّ خاطئ');
    $good   = mfWorker($office, 'ب صفّ سليم');

    $this->actingAs(mfUser([$gov]));

    $screen = mfScreen($gov)->set('monthFile', mfFilled($gov, [5 => [2 => 'غ', 3 => 'x'], 6 => [2 => 'غ']]));

    expect($screen->get('filePreview')['errors'])->toHaveCount(1)
        ->and($screen->get('filePreview')['errors'][0]['message'])->toBe('bad_cells');

    $screen->call('importMonthFile');

    expect(AttendanceDay::pluck('attendable_id')->all())->toBe([$good->id])
        ->and(AttendanceReview::pluck('attendable_id')->all())->toBe([$good->id]);
});

it('يُهمل ما كُتب في يومٍ مقفول', function () {
    $gov = Governorate::factory()->create();
    mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');
    OfficialHoliday::create(['name' => 'عطلة', 'starts_on' => '2026-09-16', 'ends_on' => '2026-09-16']);

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov)
        ->set('monthFile', mfFilled($gov, [5 => [4 => 'غ', 16 => 'غ']]))   // جمعة وعطلة
        ->assertSet('filePreview.ignored', 2)
        ->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('يرفض ملفَّ شهرٍ آخر', function () {
    $gov = Governorate::factory()->create();
    mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل', '2026-08-01');

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov, '2026-09')
        ->set('monthFile', mfFilled($gov, [5 => [3 => 'غ']], '2026-08'))
        ->assertSet('filePreview.error', 'wrong_month')
        ->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('يرفض ملفاً ليس كشفاً من النظام', function () {
    $gov = Governorate::factory()->create();
    $this->actingAs(mfUser([$gov]));

    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $book->getActiveSheet()->setCellValue('A1', 'أي شيء');
    $path = tempnam(sys_get_temp_dir(), 'mf_').'.xlsx';
    (new Xlsx($book))->save($path);

    mfScreen($gov)
        ->set('monthFile', UploadedFile::fake()->createWithContent('x.xlsx', file_get_contents($path)))
        ->assertSet('filePreview.error', 'not_template');
});

it('يرفض صفّاً أُضيف باليد بلا معرّف وصفّاً لعاملٍ ليس في مقره', function () {
    $gov      = Governorate::factory()->create();
    $office   = Office::factory()->create(['governorate_id' => $gov->id]);
    mfWorker($office, 'عامل');
    $stranger = mfWorker(Office::factory()->create(), 'عامل محافظة أخرى');

    $this->actingAs(mfUser([$gov]));

    $path  = mfFile($gov)->saveTo(tempnam(sys_get_temp_dir(), 'mf_').'.xlsx');
    $book  = IOFactory::load($path);
    $sheet = $book->getSheetByName('الكشف');
    $sheet->setCellValue('C6', 'عامل جديد باليد');
    $sheet->setCellValue([7, 6], 'غ');
    $sheet->setCellValue('A7', $stranger->id);
    $sheet->setCellValue('B7', $office->id);
    $sheet->setCellValue('C7', 'مدسوس');
    $sheet->setCellValue([7, 7], 'غ');
    (new Xlsx($book))->save($path);

    $screen = mfScreen($gov)->set('monthFile', UploadedFile::fake()->createWithContent('k.xlsx', file_get_contents($path)));

    expect(collect($screen->get('filePreview')['errors'])->pluck('message')->all())->toBe(['no_id', 'not_in_office']);

    $screen->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('لا يحفظ شيئاً في أي مقر إن تغيّر كشف مقرٍّ واحد بعد المعاينة', function () {
    // مقرّان: الثاني تغيّر، فلا يُحفظ الأول أيضاً — كلٌّ أو لا شيء
    $gov    = Governorate::factory()->create();
    $first  = mfWorker(Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'أ مقر']), 'عامل المقر الأول');
    $second = mfWorker(Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'ب مقر']), 'عامل المقر الثاني');

    $this->actingAs(mfUser([$gov]));

    $screen = mfScreen($gov)->set('monthFile', mfFilled($gov, [5 => [2 => 'غ'], 6 => [2 => 'غ']]));

    // زميلٌ سجّل من الشبكة في المقر الثاني بين المعاينة والحفظ
    AttendanceDay::create(['attendable_type' => Contractor::class, 'attendable_id' => $second->id, 'date' => '2026-09-09', 'status_id' => mfStatus('إجازة')]);

    $screen->call('importMonthFile');

    expect(AttendanceDay::pluck('attendable_id')->all())->toBe([$second->id])
        ->and(AttendanceDay::where('attendable_id', $first->id)->count())->toBe(0)
        ->and(AttendanceReview::count())->toBe(0);
});

it('لا يطابق معرّفاً تالفاً بعاملٍ حقيقي', function () {
    // «12x» يُقرأ 12 بالتحويل المجرّد — فيُكتب غيابٌ على عاملٍ لم يقصده أحد
    $gov    = Governorate::factory()->create();
    $worker = mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');

    $this->actingAs(mfUser([$gov]));

    $path  = mfFile($gov)->saveTo(tempnam(sys_get_temp_dir(), 'mf_').'.xlsx');
    $book  = IOFactory::load($path);
    $sheet = $book->getSheetByName('الكشف');
    $sheet->setCellValueExplicit('A5', $worker->id.'x', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue([7, 5], 'غ');
    (new Xlsx($book))->save($path);

    $screen = mfScreen($gov)->set('monthFile', UploadedFile::fake()->createWithContent('k.xlsx', file_get_contents($path)));

    expect(collect($screen->get('filePreview')['errors'])->pluck('message')->all())->toBe(['no_id']);

    $screen->call('importMonthFile');

    expect(AttendanceDay::count())->toBe(0);
});

it('لا ينزّل كشف محافظةٍ خارج النطاق ولا يقرأ ملفها', function () {
    $mine    = Governorate::factory()->create();
    $outside = Governorate::factory()->create();
    mfWorker(Office::factory()->create(['governorate_id' => $outside->id]), 'عامل خارج النطاق');

    $this->actingAs(mfUser([$mine]));

    $screen = mfScreen($outside)->call('downloadMonthFile');

    expect($screen->effects['download'] ?? null)->toBeNull();

    mfScreen($outside)->set('monthFile', mfFilled($outside, [5 => [2 => 'غ']]))->assertForbidden();

    expect(AttendanceDay::count())->toBe(0);
});

it('ينزّل الكشف لصاحب صلاحية التسجيل', function () {
    $gov = Governorate::factory()->create();
    mfWorker(Office::factory()->create(['governorate_id' => $gov->id]), 'عامل');

    $this->actingAs(mfUser([$gov]));

    mfScreen($gov)->call('downloadMonthFile')->assertFileDownloaded(mfFile($gov)->filename());
});
