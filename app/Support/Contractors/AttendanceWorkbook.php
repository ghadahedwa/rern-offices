<?php

namespace App\Support\Contractors;

use App\Models\AttendanceStatus;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * ما يشترك فيه ملفّا كشف الشهر — **كشف الأيام** (`AttendanceMonthFile`: خانةٌ لكل يوم) و**الكشف
 * المختصر** (`AttendanceCompactFile`: خانةٌ لكل حالة بأرقام أيامها). طلب العميلة ٢٠٢٦-٠٩-٢٤ أن
 * يبقى الاثنان جنباً إلى جنب.
 *
 * ⚠️ **الملفان يختلفان في الشكل وحده**: المقرات والبصمة والمعاينة والحفظ «كلٌّ أو لا شيء» ورفض
 *    الشهر الآخر هنا مرةً واحدة — والمنطق نفسه في `AttendanceSheet` تحتهما.
 * ⚠️ **نوع الملف يُعرف من بصمته** (`kindOf`) لا من اختيار المفتش: صفحة الرفع واحدة للملفين.
 */
abstract class AttendanceWorkbook
{
    protected const SHEET_DATA = 'الكشف';
    protected const SHEET_META = 'بيانات';

    /** إصدار الملف — السطر الأول في ورقة البيانات المخفية. */
    public const VERSION = '';

    private ?array $sheets = null;

    public readonly CarbonImmutable $month;

    public function __construct(public readonly Governorate $governorate, CarbonImmutable $month)
    {
        $this->month = $month->startOfMonth()->startOfDay();
    }

    abstract public function filename(): string;

    abstract public function build(): Spreadsheet;

    /** يقرأ صفوف ورقة الكشف في `$result` — بعد التحقق من البصمة والشهر. */
    abstract protected function readRows(Spreadsheet $spreadsheet, array &$result): void;

    /**
     * الملف المرفوع: أيّ الكشفين هو؟ يُقرأ من ورقة البيانات المخفية وحدها.
     *
     * @return class-string<self>|null
     */
    public static function kindOf(string $path): ?string
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setLoadSheetsOnly([self::SHEET_META]);
            $book    = $reader->load($path);
            $version = (string) ($book->getSheetByName(self::SHEET_META)?->getCell('A1')->getValue() ?? '');
            $book->disconnectWorksheets();
        } catch (\Throwable) {
            return null;
        }

        foreach ([AttendanceMonthFile::class, AttendanceCompactFile::class] as $class) {
            if ($version === $class::VERSION) {
                return $class;
            }
        }

        return null;
    }

    public function monthLabel(): string
    {
        return $this->month->locale('ar')->translatedFormat('F Y');
    }

    /**
     * كشوف مقرات المحافظة التي خدم فيها عاملٌ خلال الشهر، مرتّبةً بالاسم.
     *
     * @return array<int, AttendanceSheet> [officeId => sheet]
     */
    public function sheets(): array
    {
        if ($this->sheets !== null) {
            return $this->sheets;
        }

        $officeIds = ContractorAssignment::query()
            ->overlapping($this->month, $this->month->endOfMonth())
            ->whereHas('office', fn ($q) => $q->where('governorate_id', $this->governorate->id))
            ->distinct()
            ->pluck('office_id');

        $this->sheets = [];

        foreach (Office::whereKey($officeIds)->orderBy('name')->get() as $office) {
            $this->sheets[$office->id] = new AttendanceSheet($office, $this->month);
        }

        return $this->sheets;
    }

    /** بصمة كل مقر — تُحفظ عند المعاينة وتُقارن عند الحفظ. @return array<int,string> */
    public function fingerprints(array $officeIds): array
    {
        $sheets = $this->sheets();
        $prints = [];

        foreach ($officeIds as $id) {
            if (isset($sheets[$id])) {
                $prints[$id] = $sheets[$id]->fingerprint();
            }
        }

        return $prints;
    }

    /** @return array<int, array{date:string, day:int, weekday:int, kind:string, holiday:?string}> */
    protected function columns(): array
    {
        $sheets = $this->sheets();
        $first  = reset($sheets);

        return $first
            ? $first->columns()
            : (new AttendanceSheet(new Office(), $this->month))->columns();
    }

    // ── البناء ───────────────────────────────────────────────

    public function saveTo(string $path): string
    {
        $spreadsheet = $this->build();

        (new Xlsx($spreadsheet))->save($path);

        // ⚠️ مراجع PhpSpreadsheet الدائرية — انظر ContractorsTemplate
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    /** بصمة الملف: الإصدار والمحافظة والشهر (+ ما يضيفه الكشف) — تُقرأ عند الرفع. */
    protected function writeMeta(Spreadsheet $spreadsheet, array $extra = []): void
    {
        $meta = $spreadsheet->createSheet();
        $meta->setTitle(self::SHEET_META);
        $meta->setCellValue('A1', static::VERSION);
        $meta->setCellValue('A2', (string) $this->governorate->id);
        $meta->setCellValue('A3', $this->month->format('Y-m'));

        foreach (array_values($extra) as $index => $value) {
            $meta->setCellValue('A'.(4 + $index), $value);
        }

        $meta->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    // ── القراءة ──────────────────────────────────────────────

    /**
     * يقرأ الملف المملوء — **قراءة وفحص بلا حفظ**.
     *
     * @return array{error:?string, offices:array<int, array{marks:array, reviewed:array}>, errors:array, ignored:int, ignored_days:array}
     */
    public function parse(string $path): array
    {
        $result = ['error' => null, 'offices' => [], 'errors' => [], 'ignored' => 0, 'ignored_days' => []];

        $spreadsheet = IOFactory::load($path);

        try {
            $meta = $spreadsheet->getSheetByName(self::SHEET_META);

            if (! $meta || (string) $meta->getCell('A1')->getValue() !== static::VERSION) {
                $result['error'] = 'not_template';

                return $result;
            }

            if ((string) $meta->getCell('A2')->getValue() !== (string) $this->governorate->id
                || (string) $meta->getCell('A3')->getValue() !== $this->month->format('Y-m')) {
                $result['error'] = 'wrong_month';

                return $result;
            }

            $this->readRows($spreadsheet, $result);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        ksort($result['ignored_days']);
        $result['ignored_days'] = array_keys($result['ignored_days']);

        return $result;
    }

    protected function dataSheet(Spreadsheet $spreadsheet): Worksheet
    {
        return $spreadsheet->getSheetByName(static::SHEET_DATA) ?? $spreadsheet->getSheet(0);
    }

    /** صفوف الكشوف المخزَّنة: [officeId => [workerId => row]]. */
    protected function storedRows(): array
    {
        $rows = [];

        foreach ($this->sheets() as $officeId => $officeSheet) {
            foreach ($officeSheet->rows() as $row) {
                $rows[$officeId][$row['id']] = $row;
            }
        }

        return $rows;
    }

    /**
     * معرّفا العامل والمقر من العمودين المخفيين، ومطابقتهما بصفٍّ مخزَّن — أو خطأٌ في `$result`.
     *
     * ⚠️ صفٌّ بلا معرّف خطأٌ صريح لا مطابقةٌ بالاسم احتياطاً — والملف لا يُنشئ عاملاً.
     * ⚠️ `ctype_digit` لا تحويلٌ مجرّد: «12x» يُقرأ 12 فيُكتب غيابٌ على عاملٍ لم يقصده أحد.
     *
     * @return array{0:int,1:int,2:array}|null [officeId, workerId, row]
     */
    protected function matchRow(Worksheet $sheet, int $line, string $name, array $rows, array &$seen, array &$result): ?array
    {
        $workerId = trim((string) $sheet->getCell([1, $line])->getValue());
        $officeId = trim((string) $sheet->getCell([2, $line])->getValue());

        if (! ctype_digit($workerId) || ! ctype_digit($officeId)) {
            $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'no_id'];

            return null;
        }

        $row = $rows[(int) $officeId][(int) $workerId] ?? null;

        if (! $row) {
            $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'not_in_office', 'office_id' => (int) $officeId];

            return null;
        }

        $key = $officeId.'-'.$workerId;

        if (isset($seen[$key])) {
            $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'duplicate', 'office_id' => (int) $officeId];

            return null;
        }

        $seen[$key] = true;

        return [(int) $officeId, (int) $workerId, $row];
    }

    // ── المعاينة والحفظ ──────────────────────────────────────

    /** ما سيقع عند الحفظ: @return array{workers:int, offices:int, created:int, updated:int, deleted:int} */
    public function summarize(array $parsed): array
    {
        $summary = ['workers' => 0, 'offices' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0];
        $sheets  = $this->sheets();

        foreach ($parsed['offices'] as $officeId => $data) {
            if (! isset($sheets[$officeId])) {
                continue;
            }

            $summary['offices']++;
            $stored = collect($sheets[$officeId]->rows())->keyBy('id');

            foreach ($data['reviewed'] as $workerId => $_) {
                $summary['workers']++;
                $before = $stored[$workerId]['marks'] ?? [];
                $after  = $data['marks'][$workerId] ?? [];

                $summary['deleted'] += count(array_diff_key($before, $after));

                foreach ($after as $date => $status) {
                    if (! isset($before[$date])) {
                        $summary['created']++;
                    } elseif ($before[$date] !== $status) {
                        $summary['updated']++;
                    }
                }
            }
        }

        return $summary;
    }

    /**
     * تفاصيل المعاينة **مقرّاً مقرّاً** (طلب المستخدمة 2026-09-27) — الكشف الورقي يصل من كل مقر وحده،
     * فالمراجعة مقرٌّ مقرّ لا مئة وأربعون اسماً متتالية.
     *
     * لكل عاملٍ في الملف أيامُه في كل حالة بحالتها: `new` (ستُضاف أو تغيّرت حالتها) · `kept` (مسجَّلة
     * وباقية) · `removed` (ستُحذف). ⚠️ **الباقية تُعرض أيضاً** — المفتش يقارن غياب الشهر كله بالورقة،
     * لا ما تغيّر وحده. ⚠️ اليوم الذي تغيّرت حالته يظهر مرتين: `removed` في القديمة و`new` في الجديدة.
     *
     * @return array{offices: array<int, array>, statuses: array<int, array{id:int,name:string,color:string}>, loose: array}
     */
    public function preview(array $parsed): array
    {
        $sheets    = $this->sheets();
        $offices   = [];
        $statusIds = [];

        $office = function (int $officeId) use (&$offices, $sheets) {
            return $offices[$officeId] ??= [
                'id'        => $officeId,
                'name'      => $sheets[$officeId]->office->name,
                'workers'   => [],
                'errors'    => [],
                'created'   => 0,
                'updated'   => 0,
                'deleted'   => 0,
                'days'      => [],
                'untouched' => 0,
            ];
        };

        foreach ($parsed['offices'] as $officeId => $data) {
            if (! isset($sheets[$officeId])) {
                continue;
            }

            $office($officeId);
            $stored = collect($sheets[$officeId]->rows())->keyBy('id');

            foreach ($data['reviewed'] as $workerId => $_) {
                $before = $stored[$workerId]['marks'] ?? [];
                $after  = $data['marks'][$workerId] ?? [];
                $cells  = [];

                foreach ($after as $date => $status) {
                    $cells[$status][(int) substr($date, 8, 2)] = ($before[$date] ?? null) === $status ? 'kept' : 'new';
                    $offices[$officeId]['days'][$status] = ($offices[$officeId]['days'][$status] ?? 0) + 1;
                    $statusIds[$status] = true;

                    if (! isset($before[$date])) {
                        $offices[$officeId]['created']++;
                    } elseif ($before[$date] !== $status) {
                        $offices[$officeId]['updated']++;
                    }
                }

                foreach ($before as $date => $status) {
                    if (($after[$date] ?? null) !== $status) {
                        $cells[$status][(int) substr($date, 8, 2)] = 'removed';
                        $statusIds[$status] = true;

                        if (! isset($after[$date])) {
                            $offices[$officeId]['deleted']++;
                        }
                    }
                }

                foreach ($cells as &$days) {
                    ksort($days);
                }
                unset($days);

                $offices[$officeId]['workers'][] = [
                    'id'    => $workerId,
                    'name'  => $stored[$workerId]['name'] ?? '—',
                    'cells' => $cells,
                ];
            }

        }

        $loose = [];

        foreach ($parsed['errors'] as $error) {
            $officeId = $error['office_id'] ?? null;

            if ($officeId !== null && isset($sheets[$officeId])) {
                $office($officeId);
                $offices[$officeId]['errors'][] = $error;
            } else {
                $loose[] = $error;   // صفٌّ لا يُعرف مقره (بلا معرّف) — يُعرض فوق المقرات
            }
        }

        foreach ($offices as &$entry) {
            // مَن في المقر ولم يرد في الملف — لا يُمسّ، ويُذكر عدده فلا يُظنّ أنه سقط سهواً.
            // ⚠️ **صفُّه المرفوض ليس غياباً عن الملف**: هو في الملف ومعروضٌ بالأحمر فوق، فلا يُعدّ هنا.
            $present = array_merge(array_column($entry['workers'], 'id'), array_filter(array_column($entry['errors'], 'worker_id')));
            // ⚠️ من كشف المقر نفسه لا من المقرات المقروءة وحدها — مقرٌّ رُفضت صفوفه كلها لا يُقرأ هناك
            $stored = array_column($sheets[$entry['id']]->rows(), 'id');
            $entry['untouched'] = count(array_diff($stored, $present));

            usort($entry['workers'], fn ($a, $b) => strcmp($a['name'], $b['name']));
        }
        unset($entry);

        // ⚠️ **ما يستوقف المفتش أولاً**: مقرٌّ فيه حذفٌ أو صفٌّ مرفوض فوق البقية (شمال القاهرة ٦٢ مقراً —
        //    مطويّةً تبقى قائمةً طويلة)، ثم بالاسم.
        usort($offices, fn ($a, $b) => [! ($a['deleted'] || $a['errors']), $a['name']] <=> [! ($b['deleted'] || $b['errors']), $b['name']]);

        return [
            'offices'  => $offices,
            'statuses' => AttendanceStatus::query()->whereKey(array_keys($statusIds))->ordered()
                ->get(['id', 'name', 'color'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'color' => $s->color])
                ->all(),
            'loose'    => $loose,
        ];
    }

    /**
     * يحفظ المقرات كلها **أو لا شيء**: مقرٌّ تغيّرت بصمته بعد المعاينة يُلغي الحفظ كله.
     *
     * @return string|array{created:int, updated:int, deleted:int}
     */
    public function apply(array $parsed, array $fingerprints, User $user): string|array
    {
        $sheets = $this->sheets();
        $totals = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        try {
            DB::transaction(function () use ($parsed, $fingerprints, $user, $sheets, &$totals) {
                foreach ($parsed['offices'] as $officeId => $data) {
                    if (! isset($sheets[$officeId], $fingerprints[$officeId])) {
                        continue;
                    }

                    $result = $sheets[$officeId]->save($data['marks'], $data['reviewed'], $fingerprints[$officeId], $user);

                    if ($result === AttendanceSheet::STALE) {
                        throw new RuntimeException(AttendanceSheet::STALE);
                    }

                    foreach ($totals as $key => $value) {
                        $totals[$key] = $value + $result[$key];
                    }
                }
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === AttendanceSheet::STALE) {
                return AttendanceSheet::STALE;
            }

            throw $e;
        }

        return $totals;
    }
}
