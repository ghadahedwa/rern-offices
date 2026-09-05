<?php

namespace App\Support\DataEntry;

use App\Models\Governorate;
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
 * قالب Excel لتسكين مدخلي البيانات — يُرسَل للمفتش فيملؤه ويُرفَع هنا.
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
class OperatorsTemplate
{
    /** عدد الصفوف المهيَّأة للإدخال — أكبر من أي دفعة واقعية. */
    public const ROWS = 300;

    public const HEADERS = ['اسم مدخل البيانات', 'رقم التليفون', 'المقر'];

    private const SHEET_DATA    = 'المدخلون';
    private const SHEET_OFFICES = 'المقرات';
    private const OFFICE_RANGE  = 'OfficeList';

    /** @param  Collection<int, \App\Models\Office>  $offices */
    public function __construct(
        private Governorate $governorate,
        private Collection $offices,
    ) {}

    public function filename(): string
    {
        return 'مدخلو البيانات - '.$this->governorate->name.'.xlsx';
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

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8962E']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(60);

        // تجميد الرأس — الشيت قد يطول والمفتش يفقد أسماء الأعمدة
        $sheet->freezePane('A2');

        $sheet->getComment('B1')->getText()->createTextRun(
            'العمود نصّي حتى لا يسقط الصفر الأول من رقم التليفون.'
        );
        $sheet->getComment('C1')->getText()->createTextRun(
            'اختر المقر من القائمة المنسدلة — لا تكتبه بيدك.'
        );
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

        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $last = max(1, $this->offices->count());

        $spreadsheet->addNamedRange(new NamedRange(
            self::OFFICE_RANGE,
            $sheet,
            '$A$1:$A$'.$last
        ));
    }

    private function prepareRows(Worksheet $sheet): void
    {
        $last = self::ROWS + 1;

        // ⚠️ نصّي قبل الكتابة لا بعدها — انظر رأس الكلاس
        $sheet->getStyle('B2:B'.$last)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('A2:C'.$last)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($this->offices->isEmpty()) {
            return;
        }

        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setErrorTitle('مقر غير معروف')
            ->setError('اختر مقراً من القائمة المنسدلة.')
            ->setPromptTitle('المقر')
            ->setPrompt('اختر مقر عمل مدخل البيانات.')
            ->setFormula1('='.self::OFFICE_RANGE);

        $sheet->setDataValidation('C2:C'.$last, clone $validation);
    }
}
