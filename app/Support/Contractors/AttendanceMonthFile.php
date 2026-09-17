<?php

namespace App\Support\Contractors;

use App\Models\AttendanceStatus;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Support\ArabicText;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * كشف حضور الشهر لمحافظة في ملف Excel — **صورة شبكة التسجيل** بنفس قواعدها (قرار العميلة
 * ٢٠٢٦-٠٩-١٧: «أسهل طريقة بلا تعقيد»).
 *
 * صفٌّ لكل (عامل × مقر) خدم فيه خلال الشهر، وعمودٌ لكل يوم. يُكتب حرف الحالة في يوم الاستثناء
 * («غ» · «إ»)، والفارغ حاضر، والمقفول «-».
 *
 * ⚠️ **المنطق كله من `AttendanceSheet`** (الأيام المفتوحة · الحفظ · البصمة) — الملف نافذةٌ ثانية
 *    على الحالة نفسها لا نسخةٌ ثانية من القواعد.
 * ⚠️ **رفع الملف = وصل كشف كل صفٍّ صالحٍ فيه** (قرار العميلة). والعامل الغائب عن الملف لا يُمسّ
 *    ويبقى «غير مراجَع» — فالملتحق بعد تنزيل القالب لا يُحسب حاضراً الشهر كله بصمت.
 * ⚠️ **الملف ينزل معبّأً بالمسجَّل**، فرفعه لا يمحو شهراً بالخطأ — والخلية الراجعة فارغة **تحذف**
 *    الاستثناء كالشبكة، والمعاينة تعرض «سيُحذف» صريحاً.
 * ⚠️ **ملفُّ شهرٍ أو محافظةٍ غير المعروضين يُرفض** لا يُنبَّه عليه: أعمدة الأيام تخصّ شهرها.
 * ⚠️ **المطابقة بمعرّفَي العامل والمقر في عمودين مخفيين** لا بالاسم — الأسماء تتشابه في المئات.
 */
final class AttendanceMonthFile
{
    public const VERSION = 'attendance-month-v1';

    private const SHEET_DATA = 'الكشف';
    private const SHEET_META = 'بيانات';

    private const COL_WORKER     = 1;   // A — مخفي
    private const COL_OFFICE     = 2;   // B — مخفي
    private const COL_NAME       = 3;
    private const COL_PROFESSION = 4;
    private const COL_OFFICE_NAME = 5;
    private const COL_FIRST_DAY  = 6;   // F

    private const ROW_DAYS     = 3;
    private const ROW_WEEKDAYS = 4;
    private const ROW_FIRST    = 5;

    private const WEEKDAYS = ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'];

    private ?array $sheets = null;
    private ?array $codes = null;

    public readonly CarbonImmutable $month;

    public function __construct(public readonly Governorate $governorate, CarbonImmutable $month)
    {
        $this->month = $month->startOfMonth()->startOfDay();
    }

    public function filename(): string
    {
        return 'كشف الحضور - '.$this->governorate->name.' - '.$this->month->format('Y-m').'.xlsx';
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

    /**
     * رمز كل حالة: أول حرف من اسمها («غ» · «إ»)، وحرفان إن تكرّر الأول.
     *
     * ⚠️ المعطَّلة لها رمزٌ أيضاً: الملف ينزل بعلامةٍ قديمة بها، ورفعه بلا تغيير يُبقيها
     *    (والحفظ يرفض كتابتها في خليةٍ جديدة — قاعدة `AttendanceSheet`).
     *
     * @return array<int, string> [statusId => code]
     */
    public function codes(): array
    {
        if ($this->codes !== null) {
            return $this->codes;
        }

        $statuses = AttendanceStatus::query()
            ->where('is_default', false)
            ->orderByDesc('is_active')
            ->ordered()
            ->get(['id', 'name']);

        $codes = [];
        $taken = [];

        foreach ($statuses as $status) {
            foreach ([1, 2, null] as $length) {
                $code = $length ? mb_substr($status->name, 0, $length) : $status->name;
                $key  = ArabicText::normalize($code);

                if (! isset($taken[$key])) {
                    $codes[$status->id] = $code;
                    $taken[$key]        = true;
                    break;
                }
            }
        }

        return $this->codes = $codes;
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

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_DATA);
        $sheet->setRightToLeft(true);

        $columns = $this->columns();
        $lastCol = self::COL_FIRST_DAY + count($columns) - 1;
        $lastL   = Coordinate::stringFromColumnIndex($lastCol);

        $this->writeHeader($sheet, $columns, $lastL);

        $line = self::ROW_FIRST;

        foreach ($this->sheets() as $officeId => $officeSheet) {
            foreach ($officeSheet->rows() as $row) {
                $this->writeRow($sheet, $line, $officeSheet->office, $row, $columns);
                $line++;
            }
        }

        $lastRow = max(self::ROW_FIRST, $line - 1);
        $this->styleBody($sheet, $lastL, $lastRow);
        $this->writeMeta($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell(Coordinate::stringFromColumnIndex(self::COL_FIRST_DAY).self::ROW_FIRST);

        return $spreadsheet;
    }

    /** @return array<int, array{date:string, day:int, weekday:int, kind:string, holiday:?string}> */
    private function columns(): array
    {
        $sheets = $this->sheets();
        $first  = reset($sheets);

        return $first
            ? $first->columns()
            : (new AttendanceSheet(new Office(), $this->month))->columns();
    }

    private function writeHeader(Worksheet $sheet, array $columns, string $lastL): void
    {
        $codes = collect($this->codes())->take(3)
            ->map(fn ($code, $id) => '«'.$code.'» '.AttendanceStatus::find($id)?->name)
            ->implode(' · ');

        $sheet->setCellValue('C1', 'كشف حضور '.$this->monthLabel().' — '.$this->governorate->name);
        $sheet->setCellValue('C2', 'اكتب في يوم الاستثناء: '.$codes.' — واترك الحاضر فارغاً. الخلايا الرمادية («-») جمعة أو عطلة أو خارج تسكين العامل ولا تُقرأ.');
        $sheet->mergeCells('C1:'.$lastL.'1');
        $sheet->mergeCells('C2:'.$lastL.'2');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('C2')->getFont()->setSize(10)->getColor()->setRGB('52525B');
        $sheet->getRowDimension(1)->setRowHeight(24);

        $sheet->setCellValue([self::COL_WORKER, self::ROW_DAYS], 'id');
        $sheet->setCellValue([self::COL_OFFICE, self::ROW_DAYS], 'office');
        $sheet->setCellValue([self::COL_NAME, self::ROW_DAYS], 'اسم العامل');
        $sheet->setCellValue([self::COL_PROFESSION, self::ROW_DAYS], 'الصفة');
        $sheet->setCellValue([self::COL_OFFICE_NAME, self::ROW_DAYS], 'المقر');

        foreach ([self::COL_NAME, self::COL_PROFESSION, self::COL_OFFICE_NAME] as $col) {
            $sheet->mergeCells([$col, self::ROW_DAYS, $col, self::ROW_WEEKDAYS]);
        }

        foreach ($columns as $index => $column) {
            $col = self::COL_FIRST_DAY + $index;

            $sheet->setCellValue([$col, self::ROW_DAYS], $column['day']);
            $sheet->setCellValue([$col, self::ROW_WEEKDAYS], self::WEEKDAYS[$column['weekday']]);
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(4.5);

            if ($column['kind'] !== 'work') {
                $sheet->getStyle([$col, self::ROW_DAYS, $col, self::ROW_WEEKDAYS])->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($column['kind'] === 'holiday' ? 'E9DBAE' : 'D4D4D4');
            }

            if ($column['holiday']) {
                $sheet->getComment([$col, self::ROW_DAYS])->getText()->createTextRun($column['holiday']);
            }
        }

        $sheet->getStyle('C'.self::ROW_DAYS.':'.$lastL.self::ROW_WEEKDAYS)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D4D4D4']]],
        ]);

        $sheet->getColumnDimension('A')->setVisible(false);
        $sheet->getColumnDimension('B')->setVisible(false);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(32);

        // الاسم والمقر ثابتان عند التمرير لليوم العشرين، ورؤوس الأيام ثابتة عند التمرير للصفّ المئة
        $sheet->freezePane(Coordinate::stringFromColumnIndex(self::COL_FIRST_DAY).self::ROW_FIRST);
    }

    private function writeRow(Worksheet $sheet, int $line, Office $office, array $row, array $columns): void
    {
        $codes = $this->codes();
        $open  = array_flip($row['open']);

        $sheet->setCellValue([self::COL_WORKER, $line], $row['id']);
        $sheet->setCellValue([self::COL_OFFICE, $line], $office->id);
        $sheet->setCellValue([self::COL_NAME, $line], $row['name']);
        $sheet->setCellValue([self::COL_PROFESSION, $line], $row['profession'] ?? '');
        $sheet->setCellValue([self::COL_OFFICE_NAME, $line], $office->name);

        foreach ($columns as $index => $column) {
            $col = self::COL_FIRST_DAY + $index;

            if (! isset($open[$column['date']])) {
                $sheet->setCellValue([$col, $line], '-');
                $sheet->getStyle([$col, $line])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E5E5');

                continue;
            }

            $status = $row['marks'][$column['date']] ?? null;

            if ($status !== null && isset($codes[$status])) {
                $sheet->setCellValue([$col, $line], $codes[$status]);
            }
        }
    }

    private function styleBody(Worksheet $sheet, string $lastL, int $lastRow): void
    {
        $days = Coordinate::stringFromColumnIndex(self::COL_FIRST_DAY).self::ROW_FIRST.':'.$lastL.$lastRow;

        $sheet->getStyle('C'.self::ROW_FIRST.':'.$lastL.$lastRow)->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E5E5']]],
        ]);
        $sheet->getStyle($days)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($days)->getFont()->setBold(true);

        // ⚠️ تلوين الحرف المكتوب بلون حالته — بلا تمييزٍ لوني تغرق العلامات في الشبكة
        $colors = AttendanceStatus::query()->whereKey(array_keys($this->codes()))->pluck('color', 'id');
        $rules  = [];

        foreach ($this->codes() as $id => $code) {
            $rule = new Conditional();
            $rule->setConditionType(Conditional::CONDITION_CELLIS)
                ->setOperatorType(Conditional::OPERATOR_EQUAL)
                ->addCondition('"'.$code.'"');
            $rule->getStyle()->getFont()->getColor()->setRGB('FFFFFF');
            // ⚠️ تعبئة التنسيق الشرطي تُقرأ من endColor في Excel لا من startColor — فالاثنان
            $rgb = ltrim((string) ($colors[$id] ?? '#71717a'), '#');
            $rule->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $rule->getStyle()->getFill()->getStartColor()->setRGB($rgb);
            $rule->getStyle()->getFill()->getEndColor()->setRGB($rgb);
            $rules[] = $rule;
        }

        $sheet->getStyle($days)->setConditionalStyles($rules);
    }

    /** بصمة الملف: المحافظة والشهر والإصدار — تُقرأ عند الرفع. */
    private function writeMeta(Spreadsheet $spreadsheet): void
    {
        $meta = $spreadsheet->createSheet();
        $meta->setTitle(self::SHEET_META);
        $meta->setCellValue('A1', self::VERSION);
        $meta->setCellValue('A2', (string) $this->governorate->id);
        $meta->setCellValue('A3', $this->month->format('Y-m'));
        $meta->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    // ── القراءة ──────────────────────────────────────────────

    /**
     * يقرأ الملف المملوء — **قراءة وفحص بلا حفظ**.
     *
     * @return array{error:?string, offices:array<int, array{marks:array, reviewed:array}>, errors:array<int, array{line:int, name:string, message:string}>, ignored:int}
     */
    public function parse(string $path): array
    {
        $result = ['error' => null, 'offices' => [], 'errors' => [], 'ignored' => 0];

        $spreadsheet = IOFactory::load($path);

        try {
            $meta = $spreadsheet->getSheetByName(self::SHEET_META);

            if (! $meta || (string) $meta->getCell('A1')->getValue() !== self::VERSION) {
                $result['error'] = 'not_template';

                return $result;
            }

            if ((string) $meta->getCell('A2')->getValue() !== (string) $this->governorate->id
                || (string) $meta->getCell('A3')->getValue() !== $this->month->format('Y-m')) {
                $result['error'] = 'wrong_month';

                return $result;
            }

            $this->readRows($spreadsheet->getSheetByName(self::SHEET_DATA) ?? $spreadsheet->getSheet(0), $result);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return $result;
    }

    private function readRows(Worksheet $sheet, array &$result): void
    {
        $columns = $this->columns();

        // ⚠️ أعمدة الأيام تُطابَق بأرقامها في الرأس — عمودٌ أُدرج أو حُذف يُزيح الشهر كله
        foreach ($columns as $index => $column) {
            if ((int) $sheet->getCell([self::COL_FIRST_DAY + $index, self::ROW_DAYS])->getValue() !== $column['day']) {
                $result['error'] = 'template_changed';

                return;
            }
        }

        $lookup = $this->codeLookup();
        $rows   = [];

        foreach ($this->sheets() as $officeId => $officeSheet) {
            foreach ($officeSheet->rows() as $row) {
                $rows[$officeId][$row['id']] = $row;
            }
        }

        $seen = [];
        $last = $sheet->getHighestDataRow();

        for ($line = self::ROW_FIRST; $line <= $last; $line++) {
            $workerId = trim((string) $sheet->getCell([self::COL_WORKER, $line])->getValue());
            $officeId = trim((string) $sheet->getCell([self::COL_OFFICE, $line])->getValue());
            $name     = trim((string) $sheet->getCell([self::COL_NAME, $line])->getValue());

            if ($workerId === '' && $officeId === '' && $name === '') {
                continue;
            }

            // ⚠️ صفٌّ بلا معرّف خطأٌ صريح لا مطابقةٌ بالاسم احتياطاً — والملف لا يُنشئ عاملاً
            if (! ctype_digit($workerId) || ! ctype_digit($officeId)) {
                $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'no_id'];

                continue;
            }

            $row = $rows[(int) $officeId][(int) $workerId] ?? null;

            if (! $row) {
                $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'not_in_office'];

                continue;
            }

            $key = $officeId.'-'.$workerId;

            if (isset($seen[$key])) {
                $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'duplicate'];

                continue;
            }

            $seen[$key] = true;
            $open       = array_flip($row['open']);
            $marks      = [];
            $bad        = [];

            foreach ($columns as $index => $column) {
                $raw   = trim((string) $sheet->getCell([self::COL_FIRST_DAY + $index, $line])->getCalculatedValue());
                $empty = $raw === '' || $raw === '-';

                if (! isset($open[$column['date']])) {
                    // يومٌ مقفول: ما كُتب فيه يُهمَل (جمعة · عطلة · خارج التسكين)
                    $result['ignored'] += $empty ? 0 : 1;

                    continue;
                }

                if ($empty) {
                    continue;
                }

                $status = $lookup[ArabicText::normalize($raw)] ?? null;

                if ($status === null) {
                    $bad[] = $column['day'].': «'.$raw.'»';

                    continue;
                }

                $marks[$column['date']] = $status;
            }

            // ⚠️ صفٌّ فيه خليةٌ مجهولة لا يُحفظ جزئياً — نصف شهرٍ صحيح ونصفه ساقط أسوأ من صفٍّ مرفوض ظاهر
            if ($bad !== []) {
                $result['errors'][] = ['line' => $line, 'name' => $name, 'message' => 'bad_cells', 'cells' => implode('، ', $bad)];

                continue;
            }

            $result['offices'][(int) $officeId]['marks'][(int) $workerId]    = $marks;
            $result['offices'][(int) $officeId]['reviewed'][(int) $workerId] = true;
        }
    }

    /** الرمز أو الاسم الكامل مطبَّعاً ← الحالة: «ا» و«أ» تُقرآن «إ». @return array<string,int> */
    private function codeLookup(): array
    {
        $lookup = [];
        $names  = AttendanceStatus::query()->whereKey(array_keys($this->codes()))->pluck('name', 'id');

        foreach ($this->codes() as $id => $code) {
            $lookup[ArabicText::normalize($code)]            = $id;
            $lookup[ArabicText::normalize($names[$id] ?? '')] ??= $id;
        }

        unset($lookup['']);

        return $lookup;
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
