<?php

namespace App\Support\Contractors;

use App\Models\Governorate;
use App\Models\Profession;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * قالب Excel لتسكين العاملين بالتعاقد — يُرسَل للمفتش فيملؤه ويُرفَع هنا.
 *
 * ⚠️ **عمود الهاتف نصّي** (`FORMAT_TEXT`): Excel يقرأ `01012345678` رقماً فيسقط
 *    الصفر الأول ويصير `1012345678`. والتنسيق النصّي يُطبَّق **قبل** أن يكتب
 *    المستخدم، فالصفر لا يضيع أصلاً بدل أن نصلحه بعد فوات الأوان.
 * ⚠️ **قائمة المقرات في ورقة مخفية بنطاق مسمّى** لا قائمةً مكتوبة في التحقق:
 *    أسماء المقرات تبلغ ١٣٦ حرفاً، وقائمة التحقق المضمّنة محدودة بـ٢٥٥ حرفاً
 *    للقائمة كلها — فمحافظة بثلاثة مقرات تتجاوزها.
 * ⚠️ والقالب **يُبنى لحظة الطلب** من مقرات المحافظة: مقرٌّ يُضاف اليوم يظهر في
 *    قالب الغد بلا تدخّل.
 */
class ContractorsTemplate
{
    /** عدد الصفوف المهيَّأة للإدخال — أكبر من أي دفعة واقعية. */
    public const ROWS = 300;

    public const HEADERS = ['اسم العامل', 'رقم التليفون', 'الصفة', 'المقر'];

    private const SHEET_DATA        = 'العاملون';
    private const SHEET_OFFICES     = 'المقرات';
    private const OFFICE_RANGE      = 'OfficeList';
    private const PROFESSION_RANGE  = 'ProfessionList';

    /** @param  Collection<int, \App\Models\Office>  $offices */
    public function __construct(
        private Governorate $governorate,
        private Collection $offices,
    ) {}

    public function filename(): string
    {
        return 'العاملون بالتعاقد - '.$this->governorate->name.'.xlsx';
    }

    /** يكتب الملف في المسار المُعطى (يستعمله التنزيل والاختبار معاً). */
    public function saveTo(string $path): string
    {
        $spreadsheet = $this->build();

        (new Xlsx($spreadsheet))->save($path);

        // ⚠️ PhpSpreadsheet يربط الأوراق بالمصنّف بمراجع دائرية، فلا يحرّرها جامعُ
        //    المهملات وحده. بلا هذا السطر تتراكم مصنّفاتٌ كاملة في ذاكرة العملية —
        //    ظهر بنفاد الذاكرة عند بناء عدة قوالب في طلب/تشغيل واحد.
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_DATA);
        $sheet->setRightToLeft(true);

        $this->writeHeader($sheet);
        $this->writeOfficesSheet($spreadsheet);
        $this->prepareRows($sheet);

        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell('A2');

        return $spreadsheet;
    }

    private function writeHeader(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $index => $title) {
            $sheet->setCellValue([$index + 1, 1], $title);
        }

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8962E']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(60);

        // تجميد الرأس — الشيت قد يطول والمفتش يفقد أسماء الأعمدة
        $sheet->freezePane('A2');

        $sheet->getComment('B1')->getText()->createTextRun(
            'العمود نصّي حتى لا يسقط الصفر الأول من رقم التليفون.'
        );
        $sheet->getComment('C1')->getText()->createTextRun(
            'اختر الصفة من القائمة المنسدلة. الفارغ يُقرأ «مدخل بيانات».'
        );
        $sheet->getComment('D1')->getText()->createTextRun(
            'اختر المقر من القائمة المنسدلة — لا تكتبه بيدك.'
        );
    }

    /**
     * الصفة الأساسية — تُعرَف بـ`is_system` لا بأول الترتيب (نفس قاعدة القارئ).
     */
    private function defaultProfession(): ?string
    {
        return Profession::selectable()->where('is_system', true)->value('name')
            ?? Profession::selectable()->ordered()->value('name');
    }

    /** ورقة مخفية تحمل مقرات المحافظة، ومنها يقرأ التحقق قائمته. */
    private function writeOfficesSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(self::SHEET_OFFICES);
        $sheet->setRightToLeft(true);

        $row = 1;

        foreach ($this->offices as $office) {
            $sheet->setCellValue([1, $row], $office->name);
            $row++;
        }

        // بصمة المحافظة: الاستيراد يتحقق أن الملف المرفوع قالبُ المحافظة المختارة
        $sheet->setCellValue([3, 1], (string) $this->governorate->id);
        $sheet->setCellValue([3, 2], $this->governorate->name);

        // الصفات في العمود B — والعمود C محجوز لبصمة المحافظة
        $professions = Profession::selectable()->ordered()->pluck('name');

        foreach ($professions as $index => $name) {
            $sheet->setCellValue([2, $index + 1], $name);
        }

        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $last = max(1, $this->offices->count());

        $spreadsheet->addNamedRange(new NamedRange(
            self::OFFICE_RANGE,
            $sheet,
            '$A$1:$A$'.$last
        ));

        $spreadsheet->addNamedRange(new NamedRange(
            self::PROFESSION_RANGE,
            $sheet,
            '$B$1:$B$'.max(1, $professions->count())
        ));
    }

    private function prepareRows(Worksheet $sheet): void
    {
        $last = self::ROWS + 1;

        // ⚠️ نصّي قبل الكتابة لا بعدها — انظر رأس الكلاس
        $sheet->getStyle('B2:B'.$last)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('A2:D'.$last)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // ⚠️ الصفة الافتراضية معبّأة في كل صفّ: أغلب المتعاقدين مدخلو بيانات، فالمفتش
        //    يغيّرها في الاستثناء وحده. والقارئ يعتبر الصفّ فارغاً بالاسم والهاتف والمقر
        //    لا بالصفة — وإلا صارت الصفوف غير المستعمَلة ثلاثمئة خطأ «الاسم مطلوب».
        $default = $this->defaultProfession();

        if ($default !== null) {
            for ($row = 2; $row <= $last; $row++) {
                $sheet->setCellValue([3, $row], $default);
            }
        }

        $this->addListValidation(
            $sheet, 'C2:C'.$last, self::PROFESSION_RANGE,
            'صفة غير معروفة', 'اختر صفة من القائمة المنسدلة.',
            'الصفة', 'اختر صفة العامل — والفارغ يُقرأ «مدخل بيانات».'
        );

        if ($this->offices->isEmpty()) {
            return;
        }

        $this->addListValidation(
            $sheet, 'D2:D'.$last, self::OFFICE_RANGE,
            'مقر غير معروف', 'اختر مقراً من القائمة المنسدلة.',
            'المقر', 'اختر مقر عمل العامل المتعاقد.'
        );
    }

    /**
     * قائمة منسدلة من **نطاق مسمّى** لا من قائمة مضمّنة.
     *
     * ⚠️ القائمة المضمّنة محدودة بـ٢٥٥ حرفاً للقائمة كلها، واسم المقر الواحد يبلغ
     *    ١٣٦ — فمحافظة بثلاثة مقرات تتجاوزها. والنطاق المسمّى بلا هذا الحدّ.
     */
    private function addListValidation(
        Worksheet $sheet,
        string $range,
        string $namedRange,
        string $errorTitle,
        string $error,
        string $promptTitle,
        string $prompt,
    ): void {
        $cell       = explode(':', $range)[0];
        $validation = $sheet->getCell($cell)->getDataValidation();

        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setErrorTitle($errorTitle)
            ->setError($error)
            ->setPromptTitle($promptTitle)
            ->setPrompt($prompt)
            ->setFormula1('='.$namedRange);

        $sheet->setDataValidation($range, clone $validation);
    }
}
