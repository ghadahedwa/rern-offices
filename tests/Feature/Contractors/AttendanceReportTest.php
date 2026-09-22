<?php

use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Support\Contractors\AttendanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * سبتمبر ٢٠٢٦: يبدأ الثلاثاء، وجُمَعه ٤ · ١١ · ١٨ · ٢٥ — ٣٠ يوماً − ٤ جُمَع = ٢٦ يوم عمل.
 */

function repOffice(?Governorate $governorate = null): Office
{
    return Office::factory()->create(['governorate_id' => ($governorate ?? Governorate::factory()->create())->id]);
}

function repWorker(Office $office, string $from = '2026-09-01', ?string $to = null, string $name = 'عامل التقرير'): Contractor
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

function repAssign(Contractor $contractor, Office $office, string $from, ?string $to = null): ContractorAssignment
{
    return ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => $from,
        'ended_on'      => $to,
    ]);
}

function repStatus(string $name): AttendanceStatus
{
    return AttendanceStatus::where('name', $name)->firstOrFail();
}

function repMark(Contractor $contractor, string $date, string $status = 'غائب'): AttendanceDay
{
    return AttendanceDay::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $contractor->id,
        'date'            => $date,
        'status_id'       => repStatus($status)->id,
    ]);
}

function repReview(Contractor $contractor, Office $office, string $month = '2026-09-01'): AttendanceReview
{
    return AttendanceReview::create([
        'attendable_type' => Contractor::class,
        'attendable_id'   => $contractor->id,
        'office_id'       => $office->id,
        'month'           => $month,
    ]);
}

function repRows(array $contractors, string $from = '2026-09-01', string $to = '2026-09-30', ?array $officeIds = null): array
{
    $report = new AttendanceReport(CarbonImmutable::parse($from), CarbonImmutable::parse($to));

    return $report->rows(Contractor::with('profession')->whereIn('id', collect($contractors)->pluck('id'))->orderBy('id')->get(), $officeIds);
}

// ── المعادلة: أيام العمل = حضر + غير مراجَع + الاستثناءات ────────────────

it('يغلق المعادلة: أيام العمل = حضر + غير مراجَع + الاستثناءات', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repReview($worker, $office);
    repMark($worker, '2026-09-02');
    repMark($worker, '2026-09-03', 'إجازة');

    $row = repRows([$worker])[0];

    expect($row['working'])->toBe(26)
        ->and($row['present'] + $row['unreviewed'] + array_sum($row['exceptions']))->toBe(26)
        ->and($row['present'])->toBe(24)
        ->and($row['unreviewed'])->toBe(0)
        ->and($row['exceptions'][repStatus('غائب')->id])->toBe(1)
        ->and($row['exceptions'][repStatus('إجازة')->id])->toBe(1);
});

// ── «غير مراجَع» ────────────────────────────────────────────────────────

it('يُخرج أيام الحضور «غير مراجَع» ما لم يصل كشف المقر عن الشهر', function () {
    $office = repOffice();
    $worker = repWorker($office);

    $row = repRows([$worker])[0];

    expect($row['present'])->toBe(0)
        ->and($row['unreviewed'])->toBe(26);
});

it('لا يحوّل الغياب المسجَّل إلى «غير مراجَع» — واقعةٌ مكتوبة لا اشتقاق', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repMark($worker, '2026-09-02');

    $row = repRows([$worker])[0];

    expect($row['exceptions'][repStatus('غائب')->id])->toBe(1)
        ->and($row['unreviewed'])->toBe(25)
        ->and($row['present'])->toBe(0);
});

it('لا يُراجِع كشفُ مقرٍّ آخر أيامَ هذا المقر', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repReview($worker, repOffice());

    expect(repRows([$worker])[0]['unreviewed'])->toBe(26);
});

it('لا يُراجِع كشفُ شهرٍ آخر أيامَ هذا الشهر', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repReview($worker, $office, '2026-08-01');

    expect(repRows([$worker])[0]['unreviewed'])->toBe(26);
});

it('يراجع كل شهر بكشفه في مدىً يمتدّ على شهرين', function () {
    $office = repOffice();
    $worker = repWorker($office, '2026-08-01');

    repReview($worker, $office, '2026-09-01');

    $row = repRows([$worker], '2026-08-01', '2026-09-30')[0];

    // أغسطس ٢٠٢٦: ٣١ يوماً − ٤ جُمَع (٧ · ١٤ · ٢١ · ٢٨) = ٢٧ يوم عمل.
    expect($row['working'])->toBe(27 + 26)
        ->and($row['present'])->toBe(26)
        ->and($row['unreviewed'])->toBe(27);
});

// ── نسبة اليوم لمقرّه ────────────────────────────────────────────────────

it('ينسب اليوم لمقرّه وقتها لا لمقر العامل الحالي', function () {
    $from   = repOffice();
    $to     = repOffice();
    $worker = repWorker($from, '2026-09-01', '2026-09-15');

    repAssign($worker, $to, '2026-09-16');

    $rows = collect(repRows([$worker]))->keyBy('office_id');

    // ١–١٥ سبتمبر: جُمَعه ٤ · ١١ ⇒ ١٣ يوم عمل. و١٦–٣٠: جُمَعه ١٨ · ٢٥ ⇒ ١٣.
    expect($rows[$from->id]['working'])->toBe(13)
        ->and($rows[$to->id]['working'])->toBe(13)
        ->and($rows[$from->id]['working'] + $rows[$to->id]['working'])->toBe(26);
});

it('لا يعدّ اليوم مرتين لو تداخل تسكينان في الداتابيز', function () {
    $first  = repOffice();
    $second = repOffice();
    $worker = repWorker($first);

    // تداخلٌ يمنعه `overlapsExisting()` عند الحفظ — الحارس هنا للصفّ القديم في الداتابيز.
    repAssign($worker, $second, '2026-09-01');

    $rows = repRows([$worker]);

    expect(collect($rows)->sum('working'))->toBe(26)
        ->and(collect($rows)->firstWhere('office_id', $first->id)['working'])->toBe(26)
        ->and(collect($rows)->firstWhere('office_id', $second->id)['working'])->toBe(0);
});

it('يقصر الحساب على مدة الخدمة — الملتحق في المنتصف لا يُحسب من أول الشهر', function () {
    $office = repOffice();
    $worker = repWorker($office, '2026-09-16');

    expect(repRows([$worker])[0]['working'])->toBe(13);
});

// ── اليوم غير العامل ────────────────────────────────────────────────────

it('لا يحتسب استثناءً وقع في عطلةٍ أُضيفت بعد تسجيله', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repMark($worker, '2026-09-02');
    OfficialHoliday::create(['name' => 'عطلة متأخرة', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-02']);

    $row = repRows([$worker])[0];

    expect($row['working'])->toBe(25)
        ->and($row['exceptions'])->toBe([])
        ->and($row['unreviewed'])->toBe(25);
});

it('لا يحتسب استثناءً سُجِّل في جمعة', function () {
    $office = repOffice();
    $worker = repWorker($office);

    repMark($worker, '2026-09-04');

    expect(repRows([$worker])[0]['exceptions'])->toBe([]);
});

// ── فلتر المقر ──────────────────────────────────────────────────────────

it('يقصر الصفوف على المقر المطلوب بلا أن يغيّر توزيع الأيام', function () {
    $first  = repOffice();
    $second = repOffice();
    $worker = repWorker($first, '2026-09-01', '2026-09-15');

    repAssign($worker, $second, '2026-09-16');

    $rows = repRows([$worker], officeIds: [$second->id]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['office_id'])->toBe($second->id)
        ->and($rows[0]['working'])->toBe(13);
});

it('يوزّع الأيام قبل فلتر المقر لا بعده', function () {
    $first  = repOffice();
    $second = repOffice();
    $worker = repWorker($first);

    // تسكينٌ متداخل: الأسبق يملك الأيام، فالمقر الثاني يخرج بصفرٍ ولو كان هو المطلوب.
    repAssign($worker, $second, '2026-09-01');

    $rows = repRows([$worker], officeIds: [$second->id]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['working'])->toBe(0);
});

// ── الجمع والتجميع ──────────────────────────────────────────────────────

it('يعدّ العاملين المتمايزين لا الصفوف عند التجميع', function () {
    $office = repOffice();
    $other  = repOffice();
    $worker = repWorker($office, '2026-09-01', '2026-09-10', name: 'عائد');

    // نقلٌ ثم عودة إلى المقر نفسه: صفّان في مقرٍّ واحد لعاملٍ واحد.
    repAssign($worker, $other, '2026-09-11', '2026-09-20');
    repAssign($worker, $office, '2026-09-21');

    $groups = AttendanceReport::groupBy(repRows([$worker]), 'office_id');

    expect($groups[$office->id]['contractors'])->toBe(1)
        ->and($groups[$office->id]['rows'])->toHaveCount(2)
        ->and($groups[$office->id]['working'] + $groups[$other->id]['working'])->toBe(26);
});

it('يجمع الاستثناءات بمفتاح الحالة عبر الصفوف', function () {
    $office = repOffice();
    $one    = repWorker($office, name: 'أول');
    $two    = repWorker($office, name: 'ثانٍ');

    repMark($one, '2026-09-02');
    repMark($two, '2026-09-03');
    repMark($two, '2026-09-07', 'إجازة');

    $total = AttendanceReport::sum(repRows([$one, $two]));

    expect($total['working'])->toBe(52)
        ->and($total['exceptions'][repStatus('غائب')->id])->toBe(2)
        ->and($total['exceptions'][repStatus('إجازة')->id])->toBe(1);
});

// ── أعمدة الحالات ───────────────────────────────────────────────────────

it('يُدرج الحالة المعطَّلة في الأعمدة ما دامت لها أرقامٌ في المدى', function () {
    $office = repOffice();
    $worker = repWorker($office);
    $status = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#888888', 'order' => 9, 'is_active' => true]);

    repMark($worker, '2026-09-02', 'مأمورية');
    $status->update(['is_active' => false]);

    $columns = AttendanceReport::statusColumns(repRows([$worker]));

    expect($columns->pluck('name')->all())->toContain('مأمورية');
});

it('لا يُدرج الحالة الافتراضية «حاضر» عموداً — الحضور مشتقٌّ لا استثناء', function () {
    $office = repOffice();
    $worker = repWorker($office);

    expect(AttendanceReport::statusColumns(repRows([$worker]))->pluck('name')->all())
        ->not->toContain('حاضر');
});

it('لا يُدرج المعطَّلة الخالية من الأرقام', function () {
    $office = repOffice();
    $worker = repWorker($office);

    AttendanceStatus::create(['name' => 'حالة مهجورة', 'color' => '#888888', 'order' => 9, 'is_active' => false]);

    expect(AttendanceReport::statusColumns(repRows([$worker]))->pluck('name')->all())
        ->not->toContain('حالة مهجورة');
});

// ── المدى من الرابط ─────────────────────────────────────────────────────

it('يقوّم المدى المقلوب بدل أن يُخرج صفراً', function () {
    $report = new AttendanceReport(CarbonImmutable::parse('2026-09-30'), CarbonImmutable::parse('2026-09-01'));

    expect($report->start->toDateString())->toBe('2026-09-01')
        ->and($report->end->toDateString())->toBe('2026-09-30')
        ->and($report->breakdown()['working'])->toBe(26);
});
