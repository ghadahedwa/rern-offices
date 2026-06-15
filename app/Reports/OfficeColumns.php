<?php

namespace App\Reports;

use App\Models\Office;

/**
 * كتالوج أعمدة المقرات — مصدر وحيد لتعريف الأعمدة القابلة للتصدير.
 *
 * يستهلكه:
 *   - التقرير الشامل (Excel)  → كل الأعمدة بالترتيب
 *   - التقرير المخصّص (Excel/PDF) → مجموعة فرعية مختارة
 *
 * كل عمود: label · group · filter (مفتاح/مفاتيح الفلتر المقابل) · fixed · excelOnly · value (closure)
 * إضافة حقل جديد = إدخال واحد هنا يشتغل في التقريرين معاً.
 */
class OfficeColumns
{
    private const DASH = '—';

    private const WORKING_DAYS = [
        'full_week' => 'أسبوع كامل', 'one_day' => 'يوم واحد', 'two_days' => 'يومان',
        'three_days' => 'ثلاثة أيام', 'four_days' => 'أربعة أيام', 'five_days' => 'خمسة أيام',
    ];
    private const BRAILLE    = ['available' => 'متوفر', 'not_available' => 'غير متوفر'];
    private const QUEUE      = ['working' => 'يعمل', 'not_working' => 'لا يعمل', 'not_available' => 'غير متوفر'];
    private const CAMERA     = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];
    private const METER_TYPE = ['prepaid' => 'مسبق الدفع', 'invoice' => 'فاتورة', 'entity_meter' => 'عداد خاص بالجهة التابع لها المقر'];
    private const METER_DEBT = ['yes' => 'يوجد', 'no' => 'لا يوجد'];

    /**
     * تعريف كل الأعمدة بالترتيب.
     *
     * @return array<string, array{label:string, group:string, filter:null|string|array, fixed:bool, excelOnly:bool, value:\Closure}>
     */
    public static function all(): array
    {
        $dash = self::DASH;

        return [
            // ── البيانات الأساسية ──
            'name' => [
                'label' => 'اسم المقر', 'group' => 'البيانات الأساسية',
                'filter' => 'officeIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->name,
            ],
            'governorate' => [
                'label' => 'المحافظة', 'group' => 'البيانات الأساسية',
                'filter' => 'governorateIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->governorate->name ?? $dash,
            ],
            'head_mobile' => [
                'label' => 'رقم موبيل رئيس المقر', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->head_mobile ?: $dash,
            ],
            'supervising_counselor' => [
                'label' => 'المستشار المشرف', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->governorate->supervising_counselor ?? $dash,
            ],
            'type' => [
                'label' => 'نوع المقر', 'group' => 'البيانات الأساسية',
                'filter' => 'typeIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->officeType->name ?? $dash,
            ],
            'location' => [
                'label' => 'وصف الموقع', 'group' => 'البيانات الأساسية',
                'filter' => 'locationIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->locationDescription->name ?? $dash,
            ],
            'established_at' => [
                'label' => 'تاريخ الإنشاء', 'group' => 'البيانات الأساسية',
                'filter' => ['establishedFrom', 'establishedTo'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->established_at?->format('Y-m-d') ?? $dash,
            ],
            'district_court' => [
                'label' => 'المحكمة الابتدائية', 'group' => 'البيانات الأساسية',
                'filter' => 'districtCourt', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->district_court ?: $dash,
            ],
            'address' => [
                'label' => 'العنوان', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->address ?: $dash,
            ],
            'office_area' => [
                'label' => 'المساحة (م²)', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->office_area ?? $dash,
            ],
            'floors_description' => [
                'label' => 'وصف الطوابق', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->floors_description ?: $dash,
            ],
            'contractual_status' => [
                'label' => 'الوضع التعاقدي', 'group' => 'البيانات الأساسية',
                'filter' => 'contractualStatusIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->contractualStatus->name ?? $dash,
            ],
            'structural_condition' => [
                'label' => 'الحالة الإنشائية', 'group' => 'البيانات الأساسية',
                'filter' => 'structuralConditionIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->structuralCondition->name ?? $dash,
            ],
            'google_maps_link' => [
                // إكسل فقط — الرابط لا فائدة منه في PDF مطبوع، وURL الطويل يكسر عرض الجدول
                'label' => 'رابط الخريطة', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => true,
                'value' => fn (Office $o) => $o->google_maps_link ?: $dash,
            ],
            'visited_at' => [
                'label' => 'آخر زيارة', 'group' => 'البيانات الأساسية',
                'filter' => ['neverVisited', 'notVisitedMonths'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->visited_at?->format('Y-m-d') ?? 'لم تُزر',
            ],

            // ── أوقات وأنظمة العمل ──
            'work_system' => [
                'label' => 'نظام العمل', 'group' => 'أوقات وأنظمة العمل',
                'filter' => 'workSystemIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->workSystem->name ?? $dash,
            ],
            'working_hours' => [
                'label' => 'ساعات العمل', 'group' => 'أوقات وأنظمة العمل',
                'filter' => 'workingHoursIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->workingHour->name ?? $dash,
            ],
            'working_days' => [
                'label' => 'أيام العمل', 'group' => 'أوقات وأنظمة العمل',
                'filter' => 'workingDays', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::WORKING_DAYS[$o->working_days] ?? $dash,
            ],
            'connection_type' => [
                'label' => 'نوع الاتصال', 'group' => 'أوقات وأنظمة العمل',
                'filter' => 'connectionTypeIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->connectionType->name ?? $dash,
            ],
            'mechanization_at' => [
                'label' => 'تاريخ الميكنة', 'group' => 'أوقات وأنظمة العمل',
                'filter' => ['mechanizationFrom', 'mechanizationTo'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->mechanization_at?->format('Y-m-d') ?? $dash,
            ],
            'windows_count' => [
                'label' => 'عدد الشبابيك', 'group' => 'أوقات وأنظمة العمل',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->windows_count ?? $dash,
            ],

            // ── الخدمات والتجهيزات ──
            'microfilm' => [
                'label' => 'الميكروفيلم', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'microfilmIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->MicrofilmOption->name ?? $dash,
            ],
            'disabilities_access' => [
                'label' => 'تجهيزات ذوي الهمم', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'disabilitiesAccessIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->DisabilitieAccess->name ?? $dash,
            ],
            'fire_safety' => [
                'label' => 'الحماية المدنية', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'fireSafetyIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->FireSafety->name ?? $dash,
            ],
            'photocopying' => [
                'label' => 'تصوير المستندات', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'photocopyingIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->DocumentPhotocopyingService->name ?? $dash,
            ],
            'buffet' => [
                'label' => 'البوفيه', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'buffetIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->BuffetService->name ?? $dash,
            ],
            'cleanliness_contract' => [
                'label' => 'عقد النظافة', 'group' => 'الخدمات والتجهيزات',
                'filter' => 'cleanlinessContractIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->CleanlinessContract->name ?? $dash,
            ],

            // ── الأنظمة التقنية ──
            'braille' => [
                'label' => 'لوحة برايل', 'group' => 'الأنظمة التقنية',
                'filter' => 'braille', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::BRAILLE[$o->Braille_sign_device] ?? $dash,
            ],
            'queue_management' => [
                'label' => 'إدارة الطوابير', 'group' => 'الأنظمة التقنية',
                'filter' => 'queueSystem', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::QUEUE[$o->queue_management_system] ?? $dash,
            ],
            'cameras' => [
                'label' => 'كاميرات المراقبة', 'group' => 'الأنظمة التقنية',
                'filter' => 'cameras', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::CAMERA[$o->surveillance_cameras] ?? $dash,
            ],

            // ── عدد الأجهزة ──
            'computers_count' => [
                'label' => 'كمبيوتر', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->computers_count ?? 0,
            ],
            'monitors_count' => [
                'label' => 'شاشات العرض', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->monitors_count ?? 0,
            ],
            'printers_count' => [
                'label' => 'طابعات', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->printers_count ?? 0,
            ],
            'scanners_count' => [
                'label' => 'ماسحات', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->scanners_count ?? 0,
            ],
            'fingerprints_count' => [
                'label' => 'بصمة', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->fingerprints_count ?? 0,
            ],
            'payment_machine_count' => [
                'label' => 'ماكينات دفع', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->payment_machine_count ?? 0,
            ],
            'air_conditioners_count' => [
                'label' => 'مكيفات', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->air_conditioners_count ?? 0,
            ],
            'ups_count' => [
                'label' => 'UPS', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => $o->ups_count ?? 0,
            ],
            // عمود مجمّع للتقرير المخصّص فقط (customOnly) — كل الأجهزة الشغالة في خلية واحدة
            'working_devices' => [
                'label' => 'الأجهزة التي تعمل', 'group' => 'عدد الأجهزة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'customOnly' => true,
                'value' => function (Office $o) use ($dash) {
                    $devices = [
                        'كمبيوتر'      => $o->computers_count,
                        'شاشات العرض'  => $o->monitors_count,
                        'طابعات'       => $o->printers_count,
                        'ماسحات'       => $o->scanners_count,
                        'بصمة'         => $o->fingerprints_count,
                        'ماكينات دفع'  => $o->payment_machine_count,
                        'مكيفات'       => $o->air_conditioners_count,
                        'UPS'          => $o->ups_count,
                    ];
                    $parts = [];
                    foreach ($devices as $label => $cnt) {
                        if ((int) $cnt > 0) {
                            $parts[] = $label . ': ' . (int) $cnt;
                        }
                    }

                    return $parts ? implode('، ', $parts) : $dash;
                },
            ],

            // ── العدادات ──
            'electricity_meter_type' => [
                'label' => 'عداد الكهرباء', 'group' => 'العدادات',
                'filter' => 'electricityMeterType', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::METER_TYPE[$o->electricity_meter_type] ?? $dash,
            ],
            'electricity_meter_debt' => [
                'label' => 'مديونية الكهرباء', 'group' => 'العدادات',
                'filter' => 'electricityMeterDebt', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::METER_DEBT[$o->electricity_meter_debt] ?? $dash,
            ],
            'water_meter_type' => [
                'label' => 'عداد المياه', 'group' => 'العدادات',
                'filter' => 'waterMeterType', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::METER_TYPE[$o->water_meter_type] ?? $dash,
            ],
            'water_meter_debt' => [
                'label' => 'مديونية المياه', 'group' => 'العدادات',
                'filter' => 'waterMeterDebt', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => self::METER_DEBT[$o->water_meter_debt] ?? $dash,
            ],

            // ── التقييم والجودة ──
            'cleanliness_rating' => [
                'label' => 'تقييم النظافة', 'group' => 'التقييم والجودة',
                'filter' => 'cleanlinessRating', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => Office::CLEANLINESS_RATINGS[$o->cleanliness_rating] ?? $dash,
            ],
            'archive_rating' => [
                'label' => 'تقييم الأرشيف', 'group' => 'التقييم والجودة',
                'filter' => 'archiveRating', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => Office::ARCHIVE_RATINGS[$o->archive_rating] ?? $dash,
            ],
            'work_schedule_commitment' => [
                'label' => 'الالتزام بالمواعيد', 'group' => 'التقييم والجودة',
                'filter' => 'scheduleCommitment', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => Office::COMMITMENT_RATINGS[$o->work_schedule_commitment] ?? $dash,
            ],
            'citizen_treatment_commitment' => [
                'label' => 'معاملة المواطنين', 'group' => 'التقييم والجودة',
                'filter' => 'citizenCommitment', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Office $o) => Office::COMMITMENT_RATINGS[$o->citizen_treatment_commitment] ?? $dash,
            ],

            // ── الأجهزة المعطلة ──
            'broken_devices' => [
                'label' => 'الأجهزة المعطلة', 'group' => 'الأجهزة المعطلة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => function (Office $o) use ($dash) {
                    $broken = $o->brokenDevices
                        ->map(fn ($bd) => ($bd->deviceType->name ?? $dash) . ': ' . $bd->count)
                        ->implode('، ');

                    return $broken !== '' ? $broken : 'لا يوجد';
                },
            ],

            // ── ملاحظات المقر (نصوص طويلة → Excel فقط) ──
            'office_needs' => [
                'label' => 'احتياجات المقر', 'group' => 'ملاحظات المقر',
                'filter' => null, 'fixed' => false, 'excelOnly' => true,
                'value' => fn (Office $o) => $o->office_needs ?: $dash,
            ],
            'negatives_and_solutions' => [
                'label' => 'السلبيات والحلول', 'group' => 'ملاحظات المقر',
                'filter' => null, 'fixed' => false, 'excelOnly' => true,
                'value' => fn (Office $o) => $o->negatives_and_solutions ?: $dash,
            ],
            'development_proposals' => [
                'label' => 'مقترحات التطوير', 'group' => 'ملاحظات المقر',
                'filter' => null, 'fixed' => false, 'excelOnly' => true,
                'value' => fn (Office $o) => $o->development_proposals ?: $dash,
            ],
        ];
    }

    /** مفاتيح الأعمدة الثابتة (لا يمكن إلغاؤها في التقرير المخصّص) */
    public static function fixedKeys(): array
    {
        return array_keys(array_filter(self::all(), fn ($c) => $c['fixed']));
    }

    /**
     * مجموعة فرعية مرتّبة من الأعمدة حسب ترتيب الكتالوج.
     * $keys = null → كل الأعمدة (التقرير الشامل).
     *
     * @param  array<string>|null  $keys
     * @return array<string, array>
     */
    public static function select(?array $keys): array
    {
        $all = self::all();

        // التقرير الشامل (null) لا يشمل الأعمدة المخصّصة فقط (مثل العمود المجمّع)
        if ($keys === null) {
            return array_filter($all, fn ($def) => empty($def['customOnly']));
        }

        $wanted = array_flip($keys);

        return array_filter(
            $all,
            fn ($key) => isset($wanted[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * أعمدة التقرير المخصّص الافتراضية (المعلّمة تلقائياً) = الثابتة + المقابلة لفلاتر مُطبَّقة.
     * مرتّبة حسب ترتيب الكتالوج.
     *
     * @param  array  $applied  snapshot الفلاتر ($this->applied)
     * @return array<string>
     */
    public static function defaultKeysForFilters(array $applied): array
    {
        $keys = [];

        foreach (self::all() as $key => $def) {
            if ($def['fixed'] || self::filterActive($def['filter'], $applied)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** أعمدة اختيارية تظهر في منتقي التقرير المخصّص بلا فلتر (غير معلّمة افتراضياً) */
    public static function optionalCustomKeys(): array
    {
        return [
            'head_mobile', 'supervising_counselor', 'address', 'office_area',
            'floors_description', 'windows_count',
            'working_devices', 'broken_devices',
        ];
    }

    /**
     * كل الأعمدة المتاحة في منتقي التقرير المخصّص = الثابتة + أعمدة الفلاتر المستخدَمة + الاختيارية.
     * مرتّبة حسب ترتيب الكتالوج. (الملاحظات النصية والأعمدة المنفصلة للأجهزة لا تظهر)
     *
     * @return array<string>
     */
    public static function customPickerKeys(array $applied): array
    {
        $optional = array_flip(self::optionalCustomKeys());
        $keys     = [];

        foreach (self::all() as $key => $def) {
            if ($def['fixed'] || self::filterActive($def['filter'], $applied) || isset($optional[$key])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** هل الفلتر المقابل للعمود مُطبَّق فعلاً؟ */
    private static function filterActive(null|string|array $filter, array $applied): bool
    {
        if ($filter === null) {
            return false;
        }

        foreach ((array) $filter as $k) {
            $v = $applied[$k] ?? null;

            if (is_array($v) ? ! empty($v) : ($v !== null && $v !== '' && $v !== false)) {
                return true;
            }
        }

        return false;
    }
}
