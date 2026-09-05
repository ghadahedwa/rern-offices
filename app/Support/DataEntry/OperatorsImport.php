<?php

namespace App\Support\DataEntry;

use App\Models\DataEntryOperator;
use App\Models\Governorate;
use App\Support\ArabicDigits;
use App\Support\ArabicText;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * قراءة قالب التسكين المملوء — **قراءة وفحص فقط، بلا حفظ**.
 *
 * الشاشة تعرض النتيجة أولاً ثم تحفظ بعد موافقة المستخدم: ملفٌ فيه صفٌّ خاطئ
 * واحد لا يُوقف الباقي، والمستخدم يرى ما سيدخل وما سيُتجاوز قبل أن يقع.
 *
 * ⚠️ **اسم المقر يُطابَق بالتطبيع** (`ArabicText`) لا حرفياً: المفتش قد ينسخ
 *    الاسم بهمزة مختلفة أو مسافة زائدة، ومقارنة حرفية ترفض صفاً صحيحاً.
 * ⚠️ **والمقر يُبحث في مقرات المحافظة المختارة وحدها** — لا في المقرات كلها،
 *    وإلا سُكِّن مدخلٌ في محافظة أخرى بملفٍّ خاطئ.
 */
class OperatorsImport
{
    public const STATUS_OK        = 'ok';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_ERROR     = 'error';

    /** @param  Collection<int, \App\Models\Office>  $offices */
    public function __construct(
        private Governorate $governorate,
        private Collection $offices,
    ) {}

    /**
     * @return array<int, array{line:int, name:string, phone:string, office:string, office_id:?int, status:string, message:string}>
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);

        $officesByName = $this->offices->keyBy(fn ($office) => ArabicText::normalize($office->name));

        $rows = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $line   = $row->getRowIndex();
            $name   = $this->cell($sheet, 'A'.$line);
            $phone  = ArabicDigits::toLatin($this->cell($sheet, 'B'.$line));
            $office = $this->cell($sheet, 'C'.$line);

            // الصفّ الفارغ ليس خطأ — القالب يأتي بثلاثمئة صفّ مهيَّأ
            if ($name === '' && $phone === '' && $office === '') {
                continue;
            }

            $rows[] = $this->evaluate($line, $name, $phone, $office, $officesByName);
        }

        // ⚠️ تحرير المصنّف صراحةً — مراجعه الدائرية تُبقيه في الذاكرة بلا ذلك
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    private function evaluate(int $line, string $name, string $phone, string $office, Collection $officesByName): array
    {
        $row = [
            'line'      => $line,
            'name'      => $name,
            'phone'     => $phone,
            'office'    => $office,
            'office_id' => null,
            'status'    => self::STATUS_OK,
            'message'   => '',
        ];

        if ($name === '') {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.de_import_row_no_name')] + $row;
        }

        if ($office === '') {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.de_import_row_no_office')] + $row;
        }

        $match = $officesByName->get(ArabicText::normalize($office));

        if (! $match) {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.de_import_row_unknown_office')] + $row;
        }

        $row['office_id'] = $match->id;

        // ⚠️ الهاتف يُنقّى من كل ما ليس رقماً: Excel قد يعيده بمسافات أو شرطات
        $row['phone'] = preg_replace('/\D+/', '', $phone) ?? '';

        if ($row['phone'] !== '' && ! preg_match('/^01\d{9}$/', $row['phone'])) {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.de_import_row_bad_phone')] + $row;
        }

        if ($this->alreadyExists($row['name'], $row['phone'], $match->id)) {
            return ['status' => self::STATUS_DUPLICATE, 'message' => __('home.de_import_row_duplicate')] + $row;
        }

        return $row;
    }

    /**
     * مُسجَّل بالفعل وعلى رأس العمل في هذا المقر؟
     *
     * ⚠️ الفحص على الهاتف **أو** الاسم داخل المقر نفسه: الملف قد يُرفع مرتين
     *    سهواً، فيصير للمدخل الواحد صفّان وحضورٌ مضاعف في التقرير.
     */
    private function alreadyExists(string $name, string $phone, int $officeId): bool
    {
        return DataEntryOperator::query()
            ->whereHas('currentAssignment', fn ($q) => $q->where('office_id', $officeId))
            ->where(function ($q) use ($name, $phone) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('name').' = ?',
                    [ArabicText::normalize($name)]
                );

                if ($phone !== '') {
                    $q->orWhere('phone', $phone);
                }
            })
            ->exists();
    }

    private function cell($sheet, string $coordinate): string
    {
        return trim((string) $sheet->getCell($coordinate)->getFormattedValue());
    }
}
