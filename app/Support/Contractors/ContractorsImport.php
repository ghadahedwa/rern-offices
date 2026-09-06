<?php

namespace App\Support\Contractors;

use App\Models\Contractor;
use App\Models\Governorate;
use App\Models\Profession;
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
 * ⚠️ **الأعمدة تُقرأ بأسماء رؤوسها لا بمواضعها**: إضافة عمود «الصفة» أزاحت المقر
 *    من C إلى D، وقالبٌ قديم مملوء عند المفتش كان سيصير كلّ صفوفه «المقر مطلوب»
 *    بلا سبب ظاهر له. والقراءة بالرأس تقبل القالبين معاً.
 * ⚠️ **الصفة الفارغة تُقرأ «مدخل بيانات»** — صفة كل ما دخل قبل هذا الموديول —
 *    والمعاينة تعرض عمود الصفة لكل صفّ، فالافتراضي مرئيّ لا صامت.
 *
 * ⚠️ **اسم المقر يُطابَق بالتطبيع** (`ArabicText`) لا حرفياً: المفتش قد ينسخ
 *    الاسم بهمزة مختلفة أو مسافة زائدة، ومقارنة حرفية ترفض صفاً صحيحاً.
 * ⚠️ **والمقر يُبحث في مقرات المحافظة المختارة وحدها** — لا في المقرات كلها،
 *    وإلا سُكِّن مدخلٌ في محافظة أخرى بملفٍّ خاطئ.
 */
class ContractorsImport
{
    /** رأس كل عمود ← مفتاحه في الصفّ المقروء. الرأس هو العقد مع الملف، لا موضعه. */
    public const HEADERS = [
        'name'       => ['اسم العامل', 'اسم مدخل البيانات', 'الاسم'],
        'phone'      => ['رقم التليفون', 'رقم الهاتف', 'التليفون'],
        'profession' => ['الصفة', 'المهنة'],
        'office'     => ['المقر'],
    ];

    public const STATUS_OK        = 'ok';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_ERROR     = 'error';

    /** @param  Collection<int, \App\Models\Office>  $offices */
    public function __construct(
        private Governorate $governorate,
        private Collection $offices,
    ) {}

    /**
     * @return array<int, array{line:int, name:string, phone:string, profession:string, profession_id:?int, office:string, office_id:?int, status:string, message:string}>
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);

        $officesByName     = $this->offices->keyBy(fn ($office) => ArabicText::normalize($office->name));
        $professionsByName = Profession::selectable()->ordered()->get()
            ->keyBy(fn (Profession $profession) => ArabicText::normalize($profession->name));

        $columns = $this->mapColumns($sheet);
        $rows    = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $line = $row->getRowIndex();

            $values = [
                'name'       => $this->column($sheet, $columns, 'name', $line),
                'phone'      => ArabicDigits::toLatin($this->column($sheet, $columns, 'phone', $line)),
                'profession' => $this->column($sheet, $columns, 'profession', $line),
                'office'     => $this->column($sheet, $columns, 'office', $line),
            ];

            // ⚠️ الصفّ الفارغ يُقاس بالاسم والهاتف والمقر **لا بالصفة**: القالب يأتي
            //    بالصفة الافتراضية معبّأةً في صفوفه الثلاثمئة، فلو حُسبت لصار كل صفّ
            //    غير مستعمَل خطأً «الاسم مطلوب» — ثلاثمئة خطأ في ملفٍ سليم.
            if ($values['name'] === '' && $values['phone'] === '' && $values['office'] === '') {
                continue;
            }

            $rows[] = $this->evaluate($line, $values, $officesByName, $professionsByName);
        }

        // ⚠️ تحرير المصنّف صراحةً — مراجعه الدائرية تُبقيه في الذاكرة بلا ذلك
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * رأس كل عمود ← حرفه في الورقة المرفوعة.
     *
     * ⚠️ القراءة بالرأس لا بالموضع: القالب القديم بثلاثة أعمدة والجديد بأربعة،
     *    وكلاهما يُرفع. والرأس المفقود يعني عموداً غائباً لا صفوفاً خاطئة.
     *
     * @return array<string, string>
     */
    private function mapColumns($sheet): array
    {
        $found      = [];
        $lastColumn = $sheet->getHighestDataColumn(1);

        foreach (range('A', $lastColumn) as $letter) {
            $header = ArabicText::normalize($this->cell($sheet, $letter.'1'));

            if ($header === '') {
                continue;
            }

            foreach (self::HEADERS as $key => $titles) {
                if (isset($found[$key])) {
                    continue;
                }

                foreach ($titles as $title) {
                    if ($header === ArabicText::normalize($title)) {
                        $found[$key] = $letter;
                        break 2;
                    }
                }
            }
        }

        return $found;
    }

    /**
     * الصفة الافتراضية للصفّ الذي لا صفة فيه.
     *
     * ⚠️ تُعرَف بـ`is_system` لا بأول الترتيب: ترتيب العرض حقلٌ يعدّله المدير من
     *    شاشة الصفات، فلو صار «مترجم» أولَ الترتيب لصارت ملفات المفتشين القديمة
     *    تُستورد مترجمين بلا أن يظهر شيء. والصفة الأساسية واحدة لا تُحذف ولا تُعطَّل.
     */
    private function defaultProfession(Collection $professions): ?Profession
    {
        return $professions->firstWhere('is_system', true) ?? $professions->first();
    }

    /** @param  array<string, string>  $columns */
    private function column($sheet, array $columns, string $key, int $line): string
    {
        return isset($columns[$key]) ? $this->cell($sheet, $columns[$key].$line) : '';
    }

    /**
     * @param  array<string, string>  $values
     */
    private function evaluate(int $line, array $values, Collection $officesByName, Collection $professionsByName): array
    {
        [$name, $phone, $profession, $office] = [
            $values['name'], $values['phone'], $values['profession'], $values['office'],
        ];

        $row = [
            'line'          => $line,
            'name'          => $name,
            'phone'         => $phone,
            'profession'    => $profession,
            'profession_id' => null,
            'office'        => $office,
            'office_id'     => null,
            'status'        => self::STATUS_OK,
            'message'       => '',
        ];

        if ($name === '') {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_no_name')] + $row;
        }

        // ⚠️ الصفة الفارغة تُقرأ الافتراضية (أولى الصفات ترتيباً = مدخل بيانات)،
        //    والمجهولة خطأ لا تجاهلٌ صامت: المفتش كتب شيئاً ويستحق أن يعرف مصيره.
        if ($profession === '') {
            $default = $this->defaultProfession($professionsByName);

            if (! $default) {
                return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_unknown_profession')] + $row;
            }

            $row['profession']    = $default->name;
            $row['profession_id'] = $default->id;
        } else {
            $match = $professionsByName->get(ArabicText::normalize($profession));

            if (! $match) {
                return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_unknown_profession')] + $row;
            }

            $row['profession_id'] = $match->id;
        }

        if ($office === '') {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_no_office')] + $row;
        }

        $match = $officesByName->get(ArabicText::normalize($office));

        if (! $match) {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_unknown_office')] + $row;
        }

        $row['office_id'] = $match->id;

        // ⚠️ الهاتف يُنقّى من كل ما ليس رقماً: Excel قد يعيده بمسافات أو شرطات
        $row['phone'] = preg_replace('/\D+/', '', $phone) ?? '';

        if ($row['phone'] !== '' && ! preg_match('/^01\d{9}$/', $row['phone'])) {
            return ['status' => self::STATUS_ERROR, 'message' => __('home.ct_import_row_bad_phone')] + $row;
        }

        if ($this->alreadyExists($row['name'], $row['phone'], $match->id)) {
            return ['status' => self::STATUS_DUPLICATE, 'message' => __('home.ct_import_row_duplicate')] + $row;
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
        return Contractor::query()
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
