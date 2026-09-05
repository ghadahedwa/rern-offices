<?php

use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Models\Office;
use App\Models\OfficialHoliday;
use App\Support\WorkingDays;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| سبتمبر ٢٠٢٦ هو مثال العميلة نفسه: ٣٠ يوماً فيها ٤ جُمَع (٤ · ١١ · ١٨ · ٢٥)
| فأيام العمل ٢٦ — ومنه تُبنى كل الحالات تحت.
*/
const SEP_FROM = '2026-09-01';
const SEP_TO   = '2026-09-30';

function holiday(string $from, ?string $to = null, string $name = 'عطلة'): OfficialHoliday
{
    return OfficialHoliday::create([
        'name'      => $name,
        'starts_on' => $from,
        'ends_on'   => $to ?? $from,
    ]);
}

function operatorServing(string $from, ?string $to = null, ?Office $office = null): DataEntryOperator
{
    $operator = DataEntryOperator::factory()->create();

    DataEntryAssignment::create([
        'operator_id' => $operator->id,
        'office_id'   => ($office ?? Office::factory()->create())->id,
        'started_on'  => $from,
        'ended_on'    => $to,
    ]);

    return $operator->fresh();
}

function statusId(string $name): int
{
    return AttendanceStatus::where('name', $name)->value('id');
}

// ── أيام العمل: الجمعة ───────────────────────────────────

it('يخصم الجُمَع وحدها بلا أي إدخال', function () {
    expect(WorkingDays::count(SEP_FROM, SEP_TO))->toBe(26)
        ->and(WorkingDays::breakdown(SEP_FROM, SEP_TO))
        ->toBe(['total' => 30, 'weekend' => 4, 'holidays' => 0, 'working' => 26]);
});

// ── أيام العمل: العطلات الرسمية ──────────────────────────

it('يخصم العطلة الرسمية الواقعة في يوم عمل', function () {
    holiday('2026-09-17', name: 'المولد النبوي');

    expect(WorkingDays::count(SEP_FROM, SEP_TO))->toBe(25)
        ->and(WorkingDays::breakdown(SEP_FROM, SEP_TO)['holidays'])->toBe(1);
});

it('لا يخصم العطلة المُرحَّلة إلى يوم جمعة مرتين', function () {
    // حالة العميلة بعينها: عطلة تُرحَّل إلى الجمعة — واليوم مخصوم أصلاً.
    holiday('2026-09-18', name: 'المولد النبوي (مُرحَّل)');

    expect(WorkingDays::count(SEP_FROM, SEP_TO))->toBe(26)
        ->and(WorkingDays::breakdown(SEP_FROM, SEP_TO))
        ->toBe(['total' => 30, 'weekend' => 4, 'holidays' => 0, 'working' => 26]);
});

it('يخصم العطلة الممتدة أيامها العاملة فقط', function () {
    // وقفة + عيد: من الخميس ١٧ إلى السبت ١٩، والجمعة ١٨ داخلها مخصومة أصلاً.
    holiday('2026-09-17', '2026-09-19', 'عيد');

    expect(WorkingDays::count(SEP_FROM, SEP_TO))->toBe(24)
        ->and(WorkingDays::breakdown(SEP_FROM, SEP_TO)['holidays'])->toBe(2);
});

it('لا يتأثر المدى بعطلة خارجه', function () {
    holiday('2026-10-06', name: 'نصر أكتوبر');

    expect(WorkingDays::count(SEP_FROM, SEP_TO))->toBe(26);
});

it('يعطي خريطة عطلات المدى بأسمائها لشريط شاشة التسجيل', function () {
    holiday('2026-09-17', name: 'المولد النبوي');
    holiday('2026-10-06', name: 'نصر أكتوبر');

    expect(WorkingDays::holidayMap(SEP_FROM, SEP_TO))->toBe(['2026-09-17' => 'المولد النبوي']);
});

it('يميّز يوم العمل من الجمعة ومن العطلة', function () {
    holiday('2026-09-17');

    expect(WorkingDays::isWorkingDay('2026-09-16'))->toBeTrue()
        ->and(WorkingDays::isWorkingDay('2026-09-18'))->toBeFalse()  // جمعة
        ->and(WorkingDays::isWorkingDay('2026-09-17'))->toBeFalse(); // عطلة
});

it('يعطي صفراً لمدى مقلوب', function () {
    expect(WorkingDays::count(SEP_TO, SEP_FROM))->toBe(0)
        ->and(WorkingDays::breakdown(SEP_TO, SEP_FROM)['working'])->toBe(0);
});

// ── مدة الخدمة ───────────────────────────────────────────

it('يبدأ حساب المدخل من تاريخ التحاقه لا من أول المدى', function () {
    // التحق الثلاثاء ١٥: أيام العمل من ١٥ إلى ٣٠ (بلا الجُمَع ١٨ و٢٥) = ١٤
    $operator = operatorServing('2026-09-15');

    expect(WorkingDays::operatorCalendar($operator, SEP_FROM, SEP_TO))->toHaveCount(14);
});

it('يقف حساب المدخل عند انتهاء خدمته', function () {
    $operator = operatorServing('2026-09-01', '2026-09-10');

    // ١ إلى ١٠ بلا الجمعتين ٤ و١١ (١١ خارج المدة أصلاً) = ٩
    expect(WorkingDays::operatorCalendar($operator, SEP_FROM, SEP_TO))->toHaveCount(9);
});

it('لا يحسب يوماً لمدخل بلا تسكين', function () {
    $operator = DataEntryOperator::factory()->create();

    expect(WorkingDays::operatorCalendar($operator, SEP_FROM, SEP_TO))->toBe([]);
});

it('يحسب المنقول بين مقرّين مرة واحدة لا مرتين', function () {
    $operator = operatorServing('2026-09-01', '2026-09-15');

    DataEntryAssignment::create([
        'operator_id' => $operator->id,
        'office_id'   => Office::factory()->create()->id,
        'started_on'  => '2026-09-16',
        'ended_on'    => null,
    ]);

    $days = WorkingDays::operatorCalendar($operator->fresh(), SEP_FROM, SEP_TO);

    expect($days)->toHaveCount(26)
        ->and(array_unique($days))->toHaveCount(26);
});

it('يكشف تداخل التسكينات ولا يعترض على تتابعها', function () {
    $operator = operatorServing('2026-09-01', '2026-09-15');
    $office   = Office::factory()->create();

    $following = new DataEntryAssignment([
        'operator_id' => $operator->id,
        'office_id'   => $office->id,
        'started_on'  => '2026-09-16',
    ]);

    $overlapping = new DataEntryAssignment([
        'operator_id' => $operator->id,
        'office_id'   => $office->id,
        'started_on'  => '2026-09-10',
    ]);

    expect($following->overlapsExisting())->toBeFalse()
        ->and($overlapping->overlapsExisting())->toBeTrue();
});

// ── الحضور المشتقّ ───────────────────────────────────────

it('يشتق الحضور من أيام العمل ناقص الغياب والإجازات', function () {
    $operator = operatorServing('2026-09-01');

    foreach (['2026-09-07', '2026-09-08'] as $day) {
        AttendanceDay::create([
            'attendable_type' => DataEntryOperator::class,
            'attendable_id'   => $operator->id,
            'date'            => $day,
            'status_id'       => statusId('غائب'),
        ]);
    }

    AttendanceDay::create([
        'attendable_type' => DataEntryOperator::class,
        'attendable_id'   => $operator->id,
        'date'            => '2026-09-09',
        'status_id'       => statusId('إجازة'),
    ]);

    $summary = WorkingDays::summaryFor($operator, SEP_FROM, SEP_TO);

    expect($summary['working'])->toBe(26)
        ->and($summary['present'])->toBe(23)
        ->and($summary['exceptions'][statusId('غائب')])->toBe(2)
        ->and($summary['exceptions'][statusId('إجازة')])->toBe(1);
});

it('لا يخصم غياباً وقع في يومٍ صار عطلةً بعد تسجيله', function () {
    // الشاشة مفتوحة والقرار يصل متأخراً: العطلة تُضاف بعد أن سُجِّل اليوم غياباً.
    $operator = operatorServing('2026-09-01');

    AttendanceDay::create([
        'attendable_type' => DataEntryOperator::class,
        'attendable_id'   => $operator->id,
        'date'            => '2026-09-17',
        'status_id'       => statusId('غائب'),
    ]);

    holiday('2026-09-17', name: 'المولد النبوي');

    $summary = WorkingDays::summaryFor($operator, SEP_FROM, SEP_TO);

    // اليوم خرج من أيام العمل، فلا يُخصم غياباً مرة ثانية.
    expect($summary['working'])->toBe(25)
        ->and($summary['present'])->toBe(25)
        ->and($summary['exceptions'])->toBe([]);
});

it('لا يحتسب استثناءً في جمعة ولا خارج مدة الخدمة', function () {
    $operator = operatorServing('2026-09-15');

    foreach ([['2026-09-18', 'غائب'], ['2026-09-02', 'غائب']] as [$day, $status]) {
        AttendanceDay::create([
            'attendable_type' => DataEntryOperator::class,
            'attendable_id'   => $operator->id,
            'date'            => $day,
            'status_id'       => statusId($status),
        ]);
    }

    $summary = WorkingDays::summaryFor($operator, SEP_FROM, SEP_TO);

    expect($summary['working'])->toBe(14)
        ->and($summary['present'])->toBe(14)
        ->and($summary['exceptions'])->toBe([]);
});

it('يمنع المحرّك تسجيل حالتين ليومٍ واحد', function () {
    $operator = operatorServing('2026-09-01');

    $row = [
        'attendable_type' => DataEntryOperator::class,
        'attendable_id'   => $operator->id,
        'date'            => '2026-09-07',
    ];

    AttendanceDay::create($row + ['status_id' => statusId('غائب')]);

    expect(fn () => AttendanceDay::create($row + ['status_id' => statusId('إجازة')]))
        ->toThrow(QueryException::class);
});

it('يكشف تداخلاً مع تسكينٍ مفتوح مهما بعُد تاريخ الجديد', function () {
    // التسكين المفتوح يمتدّ بلا نهاية: أي تسكين يبدأ بعده متداخل معه.
    $operator = operatorServing('2026-09-01');

    $later = new DataEntryAssignment([
        'operator_id' => $operator->id,
        'office_id'   => Office::factory()->create()->id,
        'started_on'  => '2027-03-01',
    ]);

    expect($later->overlapsExisting())->toBeTrue();
});

it('يكشف تسكيناً مفتوحاً يبتلع تسكيناً لاحقاً', function () {
    $operator = DataEntryOperator::factory()->create();
    $office   = Office::factory()->create();

    DataEntryAssignment::create([
        'operator_id' => $operator->id,
        'office_id'   => $office->id,
        'started_on'  => '2026-09-20',
        'ended_on'    => '2026-09-25',
    ]);

    $open = new DataEntryAssignment([
        'operator_id' => $operator->id,
        'office_id'   => $office->id,
        'started_on'  => '2026-09-01',
    ]);

    expect($open->overlapsExisting())->toBeTrue();
});

it('لا يعدّ التسكين المحفوظ متداخلاً مع نفسه', function () {
    $operator   = operatorServing('2026-09-01');
    $assignment = $operator->assignments()->first();

    expect($assignment->overlapsExisting())->toBeFalse();
});
