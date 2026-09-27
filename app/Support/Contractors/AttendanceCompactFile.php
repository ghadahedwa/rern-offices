<?php

namespace App\Support\Contractors;

use App\Models\AttendanceStatus;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * **كشف الشهر المختصر** (طلب العميلة ٢٠٢٦-٠٩-٢٤) — بجوار كشف الأيام لا بدلاً منه: صفٌّ لكل
 * (عامل × مقر)، وخانةٌ لكل حالة («إجازة» · «غائب») يُكتب فيها **أرقام أيامها بينها فاصلة**،
 * والحضور = أيام العمل − ما كُتب.
 *
 * ⚠️ **أيامٌ مفردة فقط** (قرار العميلة): «١٠ إلى ١٤» و«١٠-١٤» تُرفض برسالة — الشَرطة بالذات لو
 *    قُرئت فاصلاً لصار «١٠-١٤» يومين بدل خمسة **بلا أثرٍ ظاهر**.
 * ⚠️ **القاعدة: يُقبل ما له معنى واحد ويُرفض ما له معنيان.** الأرقام العربية والإنجليزية، والفاصل
 *    «،» أو «,» أو «؛» أو «و» أو المسافة، والفواصل الزائدة — كلها معنى واحد.
 * ⚠️ **اليوم المقفول يرفض الصفّ** (لا يُهمَل كما في كشف الأيام): هناك الخانة الرمادية تقول «-»،
 *    وهنا الرقم مكتوبٌ صراحةً في خانة الغياب فإهماله صامتاً يُسقط غياباً ظنّه المفتش مسجَّلاً.
 * ⚠️ **Excel يُنبّه ولا يمنع**: عمود «ملاحظات» بمعادلة — التحقق المانع يحتاج معادلةً فوق حدّ
 *    الـ٢٥٥ حرفاً ليقبل الأرقام العربية، ويختلف سلوكه على الموبايل فقد يرفض إدخالاً صحيحاً.
 *    **الحارس الحقيقي `readRows`** والمعادلات لا تُقرأ عند الرفع.
 * ⚠️ **خانات الإدخال نصٌّ قبل الكتابة** — وإلا «3,7» تصير 37 و«5/8» تاريخاً.
 */
final class AttendanceCompactFile extends AttendanceWorkbook
{
    public const VERSION = 'attendance-month-compact-v1';

    private const COL_WORKER      = 1;   // A — مخفي
    private const COL_OFFICE      = 2;   // B — مخفي
    private const COL_NAME        = 3;
    private const COL_OFFICE_NAME = 4;
    private const COL_WORKING     = 5;
    private const COL_CLOSED      = 6;
    private const COL_FIRST_STATUS = 7;  // G

    private const ROW_IDS   = 5;   // مخفي: معرّف الحالة فوق عمودها
    private const ROW_HEAD  = 6;
    private const ROW_FIRST = 7;

    private const NAME_CHARS_PER_LINE   = 24;
    private const OFFICE_CHARS_PER_LINE = 22;

    private const ARABIC_DIGITS  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /** ما يُقرأ فاصلاً بين الأيام — كلها معنى واحد. */
    private const SEPARATORS = ['،', ',', '؛', ';', 'و', "\u{00A0}"];

    /** ما يُفهم منه فترة — يُرفض برسالة لا يُقرأ فاصلاً. */
    private const RANGE_MARKS = ['-', '–', '—', '/', '\\', 'الى', 'إلى', 'لحد', 'حتى'];

    private ?Collection $statuses = null;

    public function filename(): string
    {
        return 'كشف الحضور المختصر - '.$this->governorate->name.' - '.$this->month->format('Y-m').'.xlsx';
    }

    /**
     * أعمدة الحالات: المفعَّلة عدا «حاضر» بترتيب جدول الحالات، **ومعها معطَّلةٌ لها أيامٌ مسجَّلة
     * في الملف** — بلا عمودها يمحو رفعُ الملف أيامَها لأنها لم تظهر فيه.
     */
    public function statuses(): Collection
    {
        if ($this->statuses !== null) {
            return $this->statuses;
        }

        $used = [];
        foreach ($this->sheets() as $sheet) {
            foreach ($sheet->rows() as $row) {
                foreach ($row['marks'] as $status) {
                    $used[$status] = true;
                }
            }
        }

        return $this->statuses = AttendanceStatus::query()
            ->where('is_default', false)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', array_keys($used)))
            ->ordered()
            ->get(['id', 'name', 'color', 'is_active']);
    }

    public static function arabicDigits(int|string $value): string
    {
        return strtr((string) $value, array_combine(range(0, 9), self::ARABIC_DIGITS));
    }

    // ── البناء ───────────────────────────────────────────────

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_DATA);
        $sheet->setRightToLeft(true);

        $statuses   = $this->statuses();
        $colPresent = self::COL_FIRST_STATUS + $statuses->count();
        $colNotes   = $colPresent + 1;
        $lastL      = Coordinate::stringFromColumnIndex($colNotes);

        $this->writeHeader($sheet, $statuses, $colPresent, $colNotes, $lastL);

        $line = self::ROW_FIRST;

        foreach ($this->sheets() as $officeSheet) {
            foreach ($officeSheet->rows() as $row) {
                $this->writeRow($sheet, $line, $officeSheet->office->id, $officeSheet->office->name, $row, $statuses, $colPresent, $colNotes);
                $line++;
            }
        }

        $this->styleBody($sheet, $statuses->count(), $colPresent, $colNotes, max(self::ROW_FIRST, $line - 1));
        $this->writeMeta($spreadsheet, [$statuses->pluck('id')->implode(',')]);

        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell(Coordinate::stringFromColumnIndex(self::COL_FIRST_STATUS).self::ROW_FIRST);

        return $spreadsheet;
    }

    private function writeHeader(Worksheet $sheet, Collection $statuses, int $colPresent, int $colNotes, string $lastL): void
    {
        $columns  = $this->columns();
        $fridays  = collect($columns)->where('kind', 'weekend')->pluck('day');
        $holidays = collect($columns)->where('kind', 'holiday')
            ->map(fn ($c) => self::arabicDigits($c['day']).' ('.$c['holiday'].')');
        $first    = $statuses->first()?->name ?? 'غائب';

        $sheet->setCellValue('C1', 'كشف حضور '.self::arabicDigits($this->monthLabel()).' — '.$this->governorate->name.' (مختصر)');
        $sheet->setCellValue('C2', 'الجُمَع: '.self::arabicDigits($fridays->implode('، '))
            .'   ·   العطلات: '.($holidays->isEmpty() ? 'لا توجد' : $holidays->implode('، ')));
        $sheet->setCellValue('C3', 'اكتب أرقام الأيام بينها فاصلة، كل يوم لوحده:   '.self::arabicDigits('3، 7، 15'));
        $sheet->setCellValue('C4', 'الخانة الفارغة = حضر كل أيام الشهر.   لا تكتب الأيام المذكورة في عمود «أيام لا تُكتب»، وراجع عمود «ملاحظات» قبل الرفع.');

        foreach ([1, 2, 3, 4] as $row) {
            $sheet->mergeCells('C'.$row.':'.$lastL.$row);
        }

        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        // الجُمَع والعطلات بارزةٌ بالأحمر (طلب العميلة) — هي ما لا يُكتب لأي عامل
        $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('DC2626');
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle('C3')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('B8962E');
        $sheet->getStyle('C2:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $heads = [
            self::COL_WORKER      => 'id',
            self::COL_OFFICE      => 'office',
            self::COL_NAME        => 'اسم العامل',
            self::COL_OFFICE_NAME => 'المقر',
            self::COL_WORKING     => 'أيام العمل',
            self::COL_CLOSED      => 'أيام لا تُكتب',
            $colPresent           => AttendanceStatus::where('is_default', true)->value('name') ?? 'حاضر',
            $colNotes             => 'ملاحظات',
        ];

        foreach ($statuses->values() as $index => $status) {
            $heads[self::COL_FIRST_STATUS + $index] = 'أيام '.$status->name;
            // معرّف الحالة فوق عمودها — القراءة تطابق به لا بالموضع ولا بالاسم (يُعدَّل من شاشة الحالات)
            $sheet->setCellValue([self::COL_FIRST_STATUS + $index, self::ROW_IDS], $status->id);
        }

        foreach ($heads as $col => $title) {
            $sheet->setCellValue([$col, self::ROW_HEAD], $title);
        }

        $sheet->getStyle('C'.self::ROW_HEAD.':'.$lastL.self::ROW_HEAD)->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4EFDF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D4D4D4']]],
        ]);

        foreach ($statuses->values() as $index => $status) {
            $sheet->getStyle([self::COL_FIRST_STATUS + $index, self::ROW_HEAD])->getFont()->getColor()->setRGB(ltrim($status->color, '#'));
        }

        $sheet->getRowDimension(self::ROW_HEAD)->setRowHeight(24);
        $sheet->getRowDimension(self::ROW_IDS)->setVisible(false);
        $sheet->getColumnDimension('A')->setVisible(false);
        $sheet->getColumnDimension('B')->setVisible(false);

        $widths = [self::COL_NAME => 22, self::COL_OFFICE_NAME => 22, self::COL_WORKING => 9, self::COL_CLOSED => 26, $colPresent => 9, $colNotes => 30];
        foreach ($statuses->keys() as $index) {
            $widths[self::COL_FIRST_STATUS + $index] = 20;
        }
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth($width);
        }

        $sheet->freezePane(Coordinate::stringFromColumnIndex(self::COL_WORKING).self::ROW_FIRST);
    }

    private function writeRow(Worksheet $sheet, int $line, int $officeId, string $officeName, array $row, Collection $statuses, int $colPresent, int $colNotes): void
    {
        $columns = $this->columns();
        $open    = array_flip($row['open']);
        $closed  = collect($columns)->reject(fn ($c) => isset($open[$c['date']]))->pluck('day')->all();

        $sheet->setCellValue([self::COL_WORKER, $line], $row['id']);
        $sheet->setCellValue([self::COL_OFFICE, $line], $officeId);
        $sheet->setCellValue([self::COL_NAME, $line], $row['name']);
        $sheet->setCellValue([self::COL_OFFICE_NAME, $line], $officeName);
        $sheet->setCellValue([self::COL_WORKING, $line], count($row['open']));
        $sheet->setCellValue([self::COL_CLOSED, $line], $this->closedText($columns, $open));

        // المسجَّل ينزل في خانته — فرفع الملف بلا تغيير لا يمحو شيئاً
        $days = [];
        foreach ($row['marks'] as $date => $status) {
            $days[$status][] = (int) substr($date, 8, 2);
        }

        foreach ($statuses->values() as $index => $status) {
            $col = self::COL_FIRST_STATUS + $index;
            $sheet->getStyle([$col, $line])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

            if (! empty($days[$status->id])) {
                sort($days[$status->id]);
                $sheet->setCellValueExplicit([$col, $line], self::arabicDigits(implode('، ', $days[$status->id])), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $this->writeFormulas($sheet, $line, $statuses->count(), $closed, $colPresent, $colNotes);

        $lines = max(
            (int) ceil(mb_strlen($officeName) / self::OFFICE_CHARS_PER_LINE),
            (int) ceil(mb_strlen($row['name']) / self::NAME_CHARS_PER_LINE),
            2
        );
        $sheet->getRowDimension($line)->setRowHeight($lines * 15 + 4);
    }

    /**
     * «أيام لا تُكتب» بكلماتٍ لا بشَرطة: الجُمَع والعطلات داخل مدة التسكين، وما خارجها مدىً واحد
     * («قبل ١٥» · «من ٢٠ لآخر الشهر») — سردُ أيامه واحداً واحداً سطرٌ لا يُقرأ.
     */
    private function closedText(array $columns, array $open): string
    {
        $last = count($columns);
        $kind = [];

        foreach ($columns as $c) {
            $kind[$c['day']] = isset($open[$c['date']]) ? 'open' : ($c['kind'] === 'work' ? 'out' : 'closed');
        }

        // مدد «خارج المقر»: أيام عملٍ ليست له، تتصل عبر جمعةٍ أو عطلة بينها، وتمتدّ لطرف الشهر
        $runs = [];
        foreach ($kind as $day => $type) {
            if ($type !== 'out') {
                continue;
            }

            $lastRun = count($runs) - 1;
            if ($lastRun >= 0 && collect(range($runs[$lastRun][1] + 1, $day))->every(fn ($d) => $kind[$d] !== 'open')) {
                $runs[$lastRun][1] = $day;
            } else {
                $runs[] = [$day, $day];
            }
        }

        foreach ($runs as &$run) {
            while ($run[0] > 1 && $kind[$run[0] - 1] === 'closed' && collect(range(1, $run[0] - 1))->every(fn ($d) => $kind[$d] !== 'open')) {
                $run[0]--;
            }
            while ($run[1] < $last && collect(range($run[1] + 1, $last))->every(fn ($d) => $kind[$d] !== 'open')) {
                $run[1] = $last;
            }
        }
        unset($run);

        $inRun = fn (int $day) => collect($runs)->contains(fn ($r) => $day >= $r[0] && $day <= $r[1]);

        $parts = [];
        $closedDays = collect($kind)->filter(fn ($t, $d) => $t === 'closed' && ! $inRun($d))->keys();
        if ($closedDays->isNotEmpty()) {
            $parts[] = $closedDays->implode('، ');
        }

        foreach ($runs as [$from, $to]) {
            $parts[] = match (true) {
                $from === 1 && $to === $last => 'الشهر كله',
                $from === 1                  => 'قبل '.($to + 1),
                $to === $last                => 'من '.$from.' لآخر الشهر',
                default                      => 'من '.$from.' حتى '.$to,
            }.' (خارج هذا المقر)';
        }

        return self::arabicDigits(implode('، ', $parts));
    }

    /**
     * الحضور والملاحظة بمعادلات في الخانة نفسها — تتغيّر لحظة الكتابة، **ولا تُقرأ عند الرفع**.
     *
     * الأعمدة المساعدة المخفية بعد «ملاحظات»: لكل حالة نصُّها مطبَّعاً (أرقام إنجليزية · مسافة بين
     * الأيام) · ثم قائمة الأيام المقفولة · ثم الحروف غير المقبولة · عدد المقفول · عدد المكرّر بين
     * خانتين · الأيام المقفولة المكتوبة · الأيام المكرّرة · رقمٌ ليس من أيام الشهر.
     *
     * ⚠️ العدّ بـ`SUMPRODUCT` لا `MAX` داخلها: Excel لا يُقيّم `MAX` على المصفوفة هناك فيُرجع صفراً
     *    (مُثبَت على Excel 16). والأيام نفسها **كلها** بسلسلة `REPT` (انظر `$list`).
     */
    private function writeFormulas(Worksheet $sheet, int $line, int $count, array $closed, int $colPresent, int $colNotes): void
    {
        if ($count === 0) {
            $sheet->setCellValue([$colPresent, $line], '='.Coordinate::stringFromColumnIndex(self::COL_WORKING).$line);

            return;
        }

        $days  = count($this->columns());
        $R     = 'ROW($A$1:$A$'.$days.')';
        $col   = fn (int $index) => Coordinate::stringFromColumnIndex($index);
        $help  = $colNotes + 1;
        $norms = [];

        for ($i = 0; $i < $count; $i++) {
            $norms[$i] = $col($help).$line;
            $sheet->setCellValue([$help++, $line], '='.$this->normFormula($col(self::COL_FIRST_STATUS + $i).$line));
        }

        $blocked = $col($help).$line;
        $sheet->setCellValue([$help++, $line], ','.implode(',', $closed).',');

        $in    = fn (string $ref) => 'ISNUMBER(SEARCH(" "&'.$R.'&" "," "&'.$ref.'&" "))';
        $isB   = 'ISNUMBER(SEARCH(","&'.$R.'&",",'.$blocked.'))';
        $sum   = '('.collect($norms)->map(fn ($ref) => '('.$in($ref).')')->implode('+').')';
        $badB  = '((('.$sum.')>0)*('.$isB.'))';
        $badP  = '(('.$sum.')>1)';

        $leftovers = collect($norms)->map(fn ($ref) => $this->leftoversFormula($ref))->implode('&');
        $refN = $col($help).$line; $sheet->setCellValue([$help++, $line], '='.$leftovers);
        $refO = $col($help).$line; $sheet->setCellValue([$help++, $line], '=SUMPRODUCT('.$badB.')');
        $refP = $col($help).$line; $sheet->setCellValue([$help++, $line], '=SUMPRODUCT(--'.$badP.')');
        // الأيام نفسها كلها لا أولها (طلب العميلة): «٩، ١٦، » — سلسلة REPT بعدد أيامٍ ثابت، دالةٌ عادية
        // تعمل في كل برنامج. ⚠️ لا TEXTJOIN: غير موجودة في Excel 2016، وتحتاج تقييم مصفوفة يختلف بين البرامج.
        $list = function (array $days, int $min) use ($norms) {
            return collect($days)->map(function (int $day) use ($norms, $min) {
                $hits = collect($norms)->map(fn ($ref) => 'ISNUMBER(SEARCH(" '.$day.' "," "&'.$ref.'&" "))')->implode('+');

                return 'REPT("'.self::arabicDigits($day).'، ",--(('.$hits.')>='.$min.'))';
            })->implode('&');
        };

        $refR = $col($help).$line; $sheet->setCellValue([$help++, $line], $closed === [] ? '' : '='.$list($closed, 1));
        $refS = $col($help).$line; $sheet->setCellValue([$help++, $line], '='.$list(range(1, $days), 2));

        // رقمٌ ليس من أيام الشهر: عدد الأيام المكتوبة ≠ عدد ما طابق منها يوماً من ١ إلى آخر الشهر.
        // ⚠️ المطابقة بالتكرار لا بالوجود — «٣، ٣» معنى واحد يُقبل، ولا يصير «رقماً خاطئاً».
        $bad = collect($norms)->map(function ($ref) use ($R) {
            $spaced = '(" "&SUBSTITUTE('.$ref.'," ","  ")&" ")';
            $tokens = 'IF('.$ref.'="",0,LEN('.$ref.')-LEN(SUBSTITUTE('.$ref.'," ",""))+1)';
            $valid  = 'SUMPRODUCT((LEN('.$spaced.')-LEN(SUBSTITUTE('.$spaced.'," "&'.$R.'&" ","")))/LEN(" "&'.$R.'&" "))';

            return $tokens.'<>'.$valid;
        })->implode(',');
        $refQ = $col($help).$line; $sheet->setCellValue([$help++, $line], '=OR('.$bad.')');

        $sheet->setCellValue([$colPresent, $line], '='.$col(self::COL_WORKING).$line.'-SUMPRODUCT((('.$sum.')>0)*(1-('.$isB.')))');

        // القائمة تنتهي بـ«، » — تُقصّ قبل العرض
        $trim = fn (string $ref) => 'LEFT('.$ref.',MAX(0,LEN('.$ref.')-2))';

        $sheet->setCellValue([$colNotes, $line],
            '=IF('.$refN.'<>"",IF(OR(ISNUMBER(SEARCH("-",'.$refN.')),ISNUMBER(SEARCH("/",'.$refN.'))),'
            .'"اكتب كل يوم لوحده بينها فاصلة: ١٠، ١١، ١٢","اكتب أرقام الأيام فقط"),'
            .'IF('.$refO.'>1,"أيام "&'.$trim($refR).'&" لا تُكتب لهذا العامل",'
            .'IF('.$refO.'>0,"يوم "&'.$trim($refR).'&" لا يُكتب لهذا العامل",'
            .'IF('.$refP.'>1,"أيام "&'.$trim($refS).'&" مكتوبة في خانتين معاً",'
            .'IF('.$refP.'>0,"يوم "&'.$trim($refS).'&" في خانتين معاً",'
            .'IF('.$refQ.',"فيه رقم ليس من أيام الشهر",""))))))'
        );
    }

    /** أرقام عربية/فارسية ← إنجليزية، وكل فاصل ← مسافة، ثم TRIM. */
    private function normFormula(string $ref): string
    {
        $f = $ref.'&""';

        foreach ([self::ARABIC_DIGITS, self::PERSIAN_DIGITS] as $digits) {
            foreach ($digits as $i => $digit) {
                $f = 'SUBSTITUTE('.$f.',"'.$digit.'","'.$i.'")';
            }
        }

        foreach (self::SEPARATORS as $separator) {
            $f = 'SUBSTITUTE('.$f.',"'.$separator.'"," ")';
        }

        return 'TRIM('.$f.')';
    }

    /** ما يبقى بعد حذف الأرقام والمسافات = حروفٌ غير مقبولة. */
    private function leftoversFormula(string $ref): string
    {
        $f = $ref;

        foreach (range(0, 9) as $digit) {
            $f = 'SUBSTITUTE('.$f.',"'.$digit.'","")';
        }

        return 'SUBSTITUTE('.$f.'," ","")';
    }

    private function styleBody(Worksheet $sheet, int $count, int $colPresent, int $colNotes, int $lastRow): void
    {
        $col   = fn (int $index) => Coordinate::stringFromColumnIndex($index);
        $first = self::ROW_FIRST;

        $sheet->getStyle('C'.$first.':'.$col($colNotes).$lastRow)->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D4D4D4']]],
        ]);

        foreach ([self::COL_WORKING, $colPresent] as $center) {
            $sheet->getStyle($col($center).$first.':'.$col($center).$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $present = ltrim((string) (AttendanceStatus::where('is_default', true)->value('color') ?? '#16a34a'), '#');
        $sheet->getStyle($col($colPresent).$first.':'.$col($colPresent).$lastRow)->getFont()->setBold(true)->getColor()->setRGB($present);
        $sheet->getStyle($col(self::COL_CLOSED).$first.':'.$col(self::COL_CLOSED).$lastRow)->getFont()->getColor()->setRGB('71717A');
        $sheet->getStyle($col(self::COL_CLOSED).$first.':'.$col(self::COL_CLOSED).$lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4F4F5');
        $sheet->getStyle($col($colNotes).$first.':'.$col($colNotes).$lastRow)->getFont()->setBold(true)->getColor()->setRGB('DC2626');

        for ($index = $colNotes + 1; $index <= $colNotes + 20; $index++) {
            $sheet->getColumnDimension($col($index))->setVisible(false);
        }

        // ⚠️ الورقة مقفولة إلا خانات الإدخال (طلب العميلة): الكتابة فوق «ملاحظات» أو «حاضر» تمحو
        //    معادلتهما فيختفي التنبيه، وإظهار الأعمدة المخفية يكشف المعرّفات. بلا كلمة سرّ — الغرض
        //    منع الكتابة بالخطأ لا منع صاحب الملف، وتغيير عرض الأعمدة وارتفاع الصفوف مسموح.
        $sheet->getProtection()->setSheet(true)->setFormatColumns(false)->setFormatRows(false);

        if ($count === 0) {
            return;
        }

        $inputs = $col(self::COL_FIRST_STATUS).$first.':'.$col(self::COL_FIRST_STATUS + $count - 1).$lastRow;
        $sheet->getStyle($inputs)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle($inputs)->getFont()->setBold(true)->setSize(12);

        // إرشادٌ عند الوقوف على الخانة — **لا منع** (انظر رأس الكلاس)
        $hint = new DataValidation();
        $hint->setType(DataValidation::TYPE_NONE)
            ->setShowInputMessage(true)
            ->setPromptTitle('أرقام الأيام')
            ->setPrompt('مثال: '.self::arabicDigits('3، 7، 15').' — كل يوم لوحده، والفارغ = لا شيء');
        $sheet->setDataValidation($inputs, $hint);

        // الصفّ الذي فيه ملاحظة: خانات الإدخال بخلفية حمراء باهتة
        $rule = new Conditional();
        $rule->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition('$'.$col($colNotes).$first.'<>""');
        $rule->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $rule->getStyle()->getFill()->getStartColor()->setRGB('FEE2E2');
        $rule->getStyle()->getFill()->getEndColor()->setRGB('FEE2E2');
        $sheet->getStyle($inputs)->setConditionalStyles([$rule]);
    }

    // ── القراءة ──────────────────────────────────────────────

    protected function readRows(Spreadsheet $spreadsheet, array &$result): void
    {
        $sheet   = $this->dataSheet($spreadsheet);
        $meta    = $spreadsheet->getSheetByName(self::SHEET_META);
        $ids     = array_filter(explode(',', (string) $meta->getCell('A4')->getValue()), 'strlen');
        $known   = AttendanceStatus::where('is_default', false)->pluck('id')->all();
        $columns = [];

        // ⚠️ عمود كل حالة بمعرّفها المخفي فوقه — عمودٌ أُضيف أو حُذف يُزيح الحالات فيُسجَّل الغياب إجازة
        foreach (array_values($ids) as $index => $id) {
            $col = self::COL_FIRST_STATUS + $index;

            if (! ctype_digit($id) || ! in_array((int) $id, $known, true)
                || (string) $sheet->getCell([$col, self::ROW_IDS])->getValue() !== $id) {
                $result['error'] = 'template_changed';

                return;
            }

            $columns[$col] = (int) $id;
        }

        $calendar = collect($this->columns())->keyBy('day');
        $last     = $calendar->count();
        $rows     = $this->storedRows();
        $seen     = [];

        for ($line = self::ROW_FIRST; $line <= $sheet->getHighestDataRow(); $line++) {
            $name = trim((string) $sheet->getCell([self::COL_NAME, $line])->getValue());

            if ($name === ''
                && trim((string) $sheet->getCell([self::COL_WORKER, $line])->getValue()) === ''
                && trim((string) $sheet->getCell([self::COL_OFFICE, $line])->getValue()) === '') {
                continue;
            }

            $match = $this->matchRow($sheet, $line, $name, $rows, $seen, $result);

            if (! $match) {
                continue;
            }

            [$officeId, $workerId, $row] = $match;

            $problem = $this->readMarks($sheet, $line, $columns, $calendar, $last, $row);

            // ⚠️ صفٌّ فيه خطأ لا يُحفظ جزئياً — نصف شهرٍ صحيح ونصفه ساقط أسوأ من صفٍّ مرفوض ظاهر
            if (isset($problem['message'])) {
                $result['errors'][] = ['line' => $line, 'name' => $name, 'office_id' => $officeId, 'worker_id' => $workerId] + $problem;

                continue;
            }

            $result['offices'][$officeId]['marks'][$workerId]    = $problem;
            $result['offices'][$officeId]['reviewed'][$workerId] = true;
        }
    }

    /**
     * علامات صفٍّ واحد: ['Y-m-d' => statusId] — أو خطأ ['message' => …, 'cells' => …].
     *
     * ترتيب الفحص: كتابةٌ غير مفهومة (فترة · حروف) ← رقمٌ ليس من الشهر ← يومٌ في خانتين ← يومٌ مقفول.
     */
    private function readMarks(Worksheet $sheet, int $line, array $columns, Collection $calendar, int $last, array $row): array
    {
        $parsed = [];

        foreach ($columns as $col => $statusId) {
            $raw = $this->cellText($sheet, $col, $line);
            $tokens = $this->tokens($raw);

            if (is_string($tokens)) {
                return ['message' => $tokens, 'cells' => '«'.$raw.'»'];
            }

            $parsed[$statusId] = $tokens;
        }

        $outside = collect($parsed)->flatten()->filter(fn ($day) => $day < 1 || $day > $last)->unique()->values();
        if ($outside->isNotEmpty()) {
            return ['message' => 'out_of_month', 'cells' => $outside->implode('، ')];
        }

        $owner = [];
        $twice = [];
        foreach ($parsed as $statusId => $days) {
            foreach (array_unique($days) as $day) {
                if (isset($owner[$day])) {
                    $twice[$day] = true;
                }
                $owner[$day] = $statusId;
            }
        }
        if ($twice !== []) {
            ksort($twice);

            return ['message' => 'overlap', 'cells' => implode('، ', array_keys($twice))];
        }

        $open   = array_flip($row['open']);
        $closed = [];
        $marks  = [];
        ksort($owner);

        foreach ($owner as $day => $statusId) {
            $column = $calendar[$day];

            if (! isset($open[$column['date']])) {
                $closed[] = $day.' ('.match ($column['kind']) {
                    'weekend' => 'جمعة',
                    'holiday' => $column['holiday'] ?: 'عطلة',
                    default   => 'خارج هذا المقر',
                }.')';

                continue;
            }

            $marks[$column['date']] = $statusId;
        }

        if ($closed !== []) {
            return ['message' => 'closed_day', 'cells' => implode('، ', $closed)];
        }

        return $marks;
    }

    /**
     * نصّ الخانة كما كُتب. ⚠️ خانةٌ نُسخت من برنامجٍ آخر قد تصل رقماً (٣ ← 3.0) — تُقرأ عدداً صحيحاً.
     */
    private function cellText(Worksheet $sheet, int $col, int $line): string
    {
        $value = $sheet->getCell([$col, $line])->getValue();

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        return trim((string) $value);
    }

    /**
     * أرقام الأيام من نصّ الخانة — أو سبب الرفض ('range' · 'not_numbers').
     *
     * @return array<int,int>|string
     */
    public function tokens(string $raw): array|string
    {
        $text = strtr($raw, array_combine(self::ARABIC_DIGITS, range(0, 9)) + array_combine(self::PERSIAN_DIGITS, range(0, 9)));

        foreach (self::RANGE_MARKS as $mark) {
            if (str_contains($text, $mark)) {
                return 'range';
            }
        }

        $text = trim(preg_replace('/\s+/u', ' ', str_replace(self::SEPARATORS, ' ', $text)) ?? '');

        if ($text === '') {
            return [];
        }

        if (! preg_match('/^\d+( \d+)*$/', $text)) {
            return 'not_numbers';
        }

        return array_map('intval', explode(' ', $text));
    }
}
