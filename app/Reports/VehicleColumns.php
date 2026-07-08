<?php

namespace App\Reports;

use App\Models\Vehicle;

/**
 * كتالوج أعمدة السيارات المتنقلة — مصدر وحيد لتعريف الأعمدة القابلة للتصدير.
 * نفس فكرة OfficeColumns بالحرف — يستهلكه التقرير الشامل والمخصّص معاً (Excel + PDF).
 *
 * كل عمود: label · group · filter (مفتاح/مفاتيح الفلتر المقابل) · fixed · excelOnly · value (closure)
 */
class VehicleColumns
{
    private const DASH = '—';

    private const AVAILABILITY = ['available' => 'متاح', 'not_available' => 'غير متاح'];
    private const GENERATOR    = ['available' => 'يعمل', 'not_available' => 'غير متوفر', 'broken' => 'معطل'];
    private const CAMERA       = ['available' => 'تعمل', 'not_available' => 'غير متوفرة', 'broken' => 'معطلة'];

    /**
     * @return array<string, array{label:string, group:string, filter:null|string|array, fixed:bool, excelOnly:bool, value:\Closure}>
     */
    public static function all(): array
    {
        $dash = self::DASH;

        return [
            // ── ثابتة ──
            'governorate' => [
                'label' => 'المحافظة', 'group' => 'البيانات الأساسية',
                'filter' => 'governorateIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->governorate->name ?? $dash,
            ],
            'name' => [
                'label' => 'اسم السيارة', 'group' => 'البيانات الأساسية',
                'filter' => 'vehicleIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->name,
            ],
            'type' => [
                'label' => 'نوع السيارة', 'group' => 'البيانات الأساسية',
                'filter' => 'typeIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->type->name ?? $dash,
            ],

            // ── البيانات الأساسية ──
            'work_system' => [
                'label' => 'نظام العمل', 'group' => 'البيانات الأساسية',
                'filter' => 'workSystemIds', 'fixed' => true, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->workSystem->name ?? $dash,
            ],
            'working_hours' => [
                'label' => 'أوقات العمل', 'group' => 'البيانات الأساسية',
                'filter' => 'workingHoursIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->workingHour->name ?? $dash,
            ],
            'brand' => [
                'label' => 'الماركة', 'group' => 'البيانات الأساسية',
                'filter' => 'brandIds', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->brand->name ?? $dash,
            ],
            'license_plate' => [
                'label' => 'رقم اللوحة', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->license_plate ?: $dash,
            ],
            'chassis_number' => [
                'label' => 'رقم الشاسية', 'group' => 'البيانات الأساسية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->chassis_number ?: $dash,
            ],
            'manufacture_year' => [
                'label' => 'سنة الصنع', 'group' => 'البيانات الأساسية',
                'filter' => ['manufactureYearFrom', 'manufactureYearTo'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->manufacture_year ?? $dash,
            ],
            'operated_at' => [
                'label' => 'تاريخ التشغيل', 'group' => 'البيانات الأساسية',
                'filter' => ['operatedAtFrom', 'operatedAtTo'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->operated_at?->format('Y-m-d') ?? $dash,
            ],
            'license_expiry_date' => [
                'label' => 'تاريخ انتهاء الترخيص', 'group' => 'البيانات الأساسية',
                'filter' => ['licenseExpiryFrom', 'licenseExpiryTo'], 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->license_expiry_date?->format('Y-m-d') ?? $dash,
            ],
            'status' => [
                'label' => 'حالة السيارة', 'group' => 'البيانات الأساسية',
                'filter' => 'statuses', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => Vehicle::STATUSES[$v->status] ?? $dash,
            ],

            // ── أيام التمركز ──
            'locations' => [
                'label' => 'أيام التمركز', 'group' => 'أيام التمركز',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $byAddress = $v->locations->groupBy('address');

                    if ($byAddress->isEmpty()) {
                        return $dash;
                    }

                    $days = \App\Models\VehicleLocation::DAYS;

                    return $byAddress->map(function ($rows, $address) use ($days) {
                        $dayNames = $rows->pluck('day')->map(fn ($d) => $days[$d] ?? $d)->implode('، ');

                        return $address . ' (' . $dayNames . ')';
                    })->implode(' | ');
                },
            ],

            // ── بيانات إضافية ──
            'overnight_address' => [
                'label' => 'عنوان المبيت', 'group' => 'بيانات إضافية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->overnight_address ?: $dash,
            ],
            'storage_room_location' => [
                'label' => 'مكان غرفة الحفظ', 'group' => 'بيانات إضافية',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->storage_room_location ?: $dash,
            ],

            // ── العاملون ──
            'driver' => [
                'label' => 'السائق (الاسم والهاتف)', 'group' => 'العاملون',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $parts = array_filter([$v->driver_name, $v->driver_phone]);

                    return $parts ? implode(' — ', $parts) : $dash;
                },
            ],
            'notary' => [
                'label' => 'الموثق (الاسم والهاتف)', 'group' => 'العاملون',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $parts = array_filter([$v->notary_name, $v->notary_phone]);

                    return $parts ? implode(' — ', $parts) : $dash;
                },
            ],
            'reviewer' => [
                'label' => 'المراجع (الاسم والهاتف)', 'group' => 'العاملون',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $parts = array_filter([$v->reviewer_name, $v->reviewer_phone]);

                    return $parts ? implode(' — ', $parts) : $dash;
                },
            ],

            // ── التجهيزات ──
            'mobility_bag' => [
                'label' => 'شنطة التنقلات', 'group' => 'التجهيزات',
                'filter' => 'mobilityBag', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::AVAILABILITY[$v->mobility_bag] ?? $dash,
            ],
            'generator_status' => [
                'label' => 'المولد الكهربائي', 'group' => 'التجهيزات',
                'filter' => 'generatorStatus', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::GENERATOR[$v->generator_status] ?? $dash,
            ],
            'surveillance_cameras' => [
                'label' => 'كاميرات المراقبة', 'group' => 'التجهيزات',
                'filter' => 'cameras', 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::CAMERA[$v->surveillance_cameras] ?? $dash,
            ],
            // عمود التجهيزات الوحيد — كل الأعداد مجمّعة في خلية واحدة (بلا أعمدة فردية منفصلة، لا في الشامل ولا في المخصّص)
            'working_equipment' => [
                'label' => 'الأجهزة التي تعمل', 'group' => 'التجهيزات',
                // pickerGroup: يظهر تحت "بيانات إضافية" في منتقي التقرير المخصّص فقط — الـ group الأصلي يبقى للعناوين في الشامل (Excel/PDF)
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $devices = [
                        'لابتوب'         => $v->laptops_count,
                        'بصمة'           => $v->fingerprints_count,
                        'طابعات'         => $v->printers_count,
                        'ماكينات تحصيل'  => $v->collection_machines_count,
                        'MiFi'           => $v->mifi_count,
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

            // ── الأجهزة المعطلة ──
            'broken_devices' => [
                'label' => 'الأجهزة المعطلة', 'group' => 'الأجهزة المعطلة',
                'filter' => null, 'fixed' => false, 'excelOnly' => false, 'pickerGroup' => true,
                'value' => function (Vehicle $v) use ($dash) {
                    $broken = $v->brokenDevices
                        ->map(fn ($bd) => ($bd->deviceType->name ?? $dash) . ': ' . $bd->count)
                        ->implode('، ');

                    return $broken !== '' ? $broken : 'لا يوجد';
                },
            ],

            // ── الإحصائيات ──
            'avg_daily_transactions' => [
                'label' => 'متوسط المعاملات اليومية', 'group' => 'الإحصائيات',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => $v->avg_daily_transactions ?? $dash,
            ],
            'stat_transactions' => [
                'label' => 'معاملات التوثيق (آخر سنة)', 'group' => 'الإحصائيات',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::lastYearStat($v, 1) ?? $dash,
            ],
            'stat_form_sales' => [
                'label' => 'بيع النماذج (آخر سنة)', 'group' => 'الإحصائيات',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::lastYearStat($v, 2) ?? $dash,
            ],
            'stat_folder_sales' => [
                'label' => 'بيع الحوافظ (آخر سنة)', 'group' => 'الإحصائيات',
                'filter' => null, 'fixed' => false, 'excelOnly' => false,
                'value' => fn (Vehicle $v) => self::lastYearStat($v, 3) ?? $dash,
            ],

            // ── ملاحظات ──
            'notes' => [
                'label' => 'ملاحظات', 'group' => 'ملاحظات',
                'filter' => null, 'fixed' => false, 'excelOnly' => true, 'pickerGroup' => true,
                'value' => fn (Vehicle $v) => $v->notes ?: $dash,
            ],
        ];
    }

    /** قيمة آخر سنة مسجّلة لنوع إحصائية معيّن — "قيمة (سنة)"، أو null إن لم توجد بيانات (يتطلب eager-load لـ statistics) */
    private static function lastYearStat(Vehicle $v, int $statTypeId): ?string
    {
        $rows = $v->statistics->where('stat_type_id', $statTypeId);

        if ($rows->isEmpty()) {
            return null;
        }

        $lastYear = $rows->max('year');
        $total    = $rows->where('year', $lastYear)->sum('value');

        return number_format((float) $total, 0) . ' (' . $lastYear . ')';
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
     * أعمدة التقرير المخصّص الافتراضية (المعلّمة تلقائياً) = الثابتة + المقابلة لفلاتر مُطبَّقة فعلاً.
     *
     * @param  array  $applied       snapshot الفلاتر ($this->applied)
     * @param  array  $optionTotals  إجمالي خيارات كل فلتر متعدد (لاستبعاد "تحديد الكل" — بلا تقييد فعلي)
     * @return array<string>
     */
    public static function defaultKeysForFilters(array $applied, array $optionTotals = []): array
    {
        $keys = [];

        foreach (self::all() as $key => $def) {
            if ($def['fixed'] || self::filterActive($def['filter'], $applied, $optionTotals)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * كل أعمدة الكتالوج تظهر دائماً في منتقي التقرير المخصّص، كل عمود تحت قسمه الطبيعي —
     * عدد أعمدة السيارات صغير (~32) فلا داعي لإخفاء غير المفلتَر منها كما في المقرات.
     * المعلّم منها تلقائياً فقط (انظر defaultKeysForFilters) هو الثابت أو المرتبط بفلتر مُطبَّق فعلاً.
     *
     * @return array<string>
     */
    public static function customPickerKeys(): array
    {
        return array_keys(self::all());
    }

    /**
     * هل الفلتر المقابل للعمود يقيّد النتائج فعلاً؟
     * لفلاتر متعددة القيم: "تحديد الكل" (العدد المختار = إجمالي الخيارات) يُعتبر بلا تقييد —
     * نفس معيار MultiVehicle::multiActive() المستخدَم في بناء الاستعلام، لضمان اتساق عمود المنتقي مع الاستعلام الفعلي.
     */
    private static function filterActive(null|string|array $filter, array $applied, array $optionTotals = []): bool
    {
        if ($filter === null) {
            return false;
        }

        foreach ((array) $filter as $k) {
            $v = $applied[$k] ?? null;

            if (is_array($v)) {
                if (empty($v)) {
                    continue;
                }
                $total = $optionTotals[$k] ?? null;
                if ($total !== null && count($v) >= $total) {
                    continue;
                }

                return true;
            }

            if ($v !== null && $v !== '' && $v !== false) {
                return true;
            }
        }

        return false;
    }
}
