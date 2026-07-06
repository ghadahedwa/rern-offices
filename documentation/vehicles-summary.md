# ملخص موديول السيارات المتنقلة

## الفكرة العامة
نظام لإدارة السيارات المتنقلة وسيارات مركز خدمات مصر المتنقلة. مرتبطة بالمحافظات بنفس منطق المقرات (المستخدم يرى سيارات محافظاته فقط).

---

## ما تم تنفيذه ✅

### قاعدة البيانات
| الجدول | الوصف |
|--------|-------|
| `vehicle_types` | أنواع السيارات — مع seed: سيارة متنقلة / مركز خدمات مصر المتنقلة |
| `vehicle_brands` | الماركات — مع seed: تويوتا، هيونداي، كيا، ميتسوبيشي، نيسان، فورد، شيفروليه، فولكس فاجن |
| `vehicle_work_systems` | نظام العمل — مع seed: تنقلات فقط / دعم فقط / تنقلات ودعم |
| `vehicle_working_hours` | أوقات العمل — مع seed: صباحي / صباحي ومسائي أول / صباحي ومسائي أول وثانٍ / مسائي فقط |
| `vehicle_device_types` | أجهزة السيارات المعطلة — مع seed: لاب توب، جهاز بصمة، طابعة، ماكينة تحصيل، MiFi، مولد، كاميرا |
| `vehicles` | الجدول الرئيسي |
| `vehicle_locations` | أيام التمركز (day + address لكل سيارة) |
| `vehicle_broken_devices` | الأجهزة المعطلة (device_type_id + count لكل سيارة) |
| `vehicle_media` | الوسائط (type: photo/video/document/license_photo + path) |

**جدول `vehicles` — الحقول:**
```
governorate_id (FK)     type_id (FK)           work_system_id (FK)
working_hours_id (FK)   brand_id (FK)          name
license_plate           manufacture_year        chassis_number
license_expiry_date     status (enum)           overnight_address
storage_room_location   notes
driver_name / driver_phone   notary_name / notary_phone   reviewer_name / reviewer_phone
mobility_bag (enum)  laptops_count  fingerprints_count  printers_count
collection_machines_count  mifi_count  generator_status (enum)  surveillance_cameras (enum)
```

**جدول `vehicle_locations`:**
```
vehicle_id (FK)    day (enum: saturday→friday)    address
```

### Models
- `Vehicle` — مع `STATUSES` constant + علاقات BelongsTo/HasMany
- `VehicleLocation` — مع `DAYS` constant
- `VehicleType`, `VehicleBrand`, `VehicleWorkSystem`, `VehicleWorkingHour`, `VehicleDeviceType`
- `VehicleBrokenDevice` — نفس نمط `OfficeBrokenDevice`
- `VehicleMedia` — نفس نمط `OfficeMedia`

### Permissions
```
vehicles.index    vehicles.view
vehicles.create   vehicles.edit
vehicles.delete   vehicles.export
```
- تظهر في شاشة الأدوار تحت مجموعة "السيارات المتنقلة" بين المقرات والمطالبات
- تُنشأ تلقائياً عند فتح صفحة إنشاء/تعديل دور (firstOrCreate)

### Routes
```php
vehicles           → Vehicles\Index    (vehicles.index)
vehicles/create    → Vehicles\Create  (vehicles.create)
vehicles/{vehicle}/edit → Vehicles\Create (vehicles.edit)

// Lookup tables (داخل مجموعة role:super-admin) — كل جدول له 3 routes: index / create / {id}/edit
vehicle-types           → VehicleTypes\Index|Create
vehicle-brands          → VehicleBrands\Index|Create
vehicle-work-systems    → VehicleWorkSystems\Index|Create
vehicle-working-hours   → VehicleWorkingHours\Index|Create
vehicle-device-types    → VehicleDeviceTypes\Index|Create
```

### Livewire Components
**`Vehicles\Index`**
- جدول مع pagination (15 لكل صفحة)
- فلاتر (صف واحد 4 أعمدة): بحث، محافظة، نوع، نظام عمل
- بحث متقدم (toggle): حالة السيارة مع badge ملون (أخضر/أصفر/أحمر)
- أزرار: تعديل + حذف (مع confirm modal)
- صلاحيات: vehicles.index / edit / delete

**`Vehicles\Create`** (إنشاء + تعديل — نفس component)
- **نظام تابات مع حفظ فعلي لكل تاب** (`$activeTab` #[Url] + `$vehicle_id` #[Url] + `$totalTabs = 5` — العدد النهائي المستهدف). نفس نهج wizard المقرات بالضبط (زي `office_id` في `offices/create?step=2&office_id=949`):
  - الرابط بيبقى شكله `vehicles/create?activeTab=2&vehicle_id=X` بعد أول حفظ — لو المستخدم عمل refresh أو رجع بالمتصفح، الـ `mount()` بيلاقي `vehicle_id` في الرابط ويحمّل بيانات السيارة تاني (`elseif ($this->vehicle_id)` — نفس منطق "استئناف جلسة الإنشاء" الموجود في `Offices\Create::mount()`)
  - تاب 1 فقط: زرار "حفظ وخروج" (يمين)
  - أي تاب بعد الأول: "حفظ والسابق" (يسار) + "حفظ والتالي" (يمين) لو مش آخر تاب، أو "حفظ وخروج" بدل التالي لو وصلنا لآخر تاب
  - كل تاب له validate + persist خاص به (`validateCurrentTab`/`persistCurrentTab` عبر match على `activeTab`) — التابات غير المُنفَّذة (default) بترجع null بدون أي حفظ
  - تابات الـ nav العلوية تتفعّل (goToTab) بمجرد وجود `vehicle_id`؛ التاب الأول فقط متاح قبل الحفظ الأول
  - **شكل التابات**: دوائر مرقّمة متصلة بخط أفقي (نفس شكل wizard المقرات بالحرف — مش `border-b` tabs)، لأن التابات فعلياً خطوات متتالية معتمدة على بعض مش أقسام مستقلة
- Tab 1 مُنجزة: **البيانات الأساسية**
  - المحافظة (حقل مستقل بعرض كامل — اسم المستشار المشرف اتشال من شاشة السيارات، عكس المقرات اللي لسه بتعرضه)
  - كل حقول البيانات الأساسية (نوع السيارة ونظام العمل أصبحا required)
  - قسم **أيام التمركز**: صفوف ديناميكية، كل صف = عنوان واحد + **checkboxes لاختيار أكتر من يوم لنفس العنوان** (العنوان اتغيّر من "أيام التمركز الأساسي" — فكرة "أيام التمركز الإضافي" المنفصلة اتلغت). أي يوم مُختار في صف تاني بيتعطّل (opacity + disabled) في باقي الصفوف عشان يوم واحد ميتكررش على أكتر من عنوان
    - **بدون migration**: جدول `vehicle_locations` فضل زي ما هو (يوم + عنوان لكل صف) — الصف الواحد في الواجهة (عنوان + كذا يوم) بينفصل تلقائياً لسجل مستقل لكل يوم عند الحفظ (`persistBasicTab`)، وعند فتح صفحة التعديل بيتجمّعوا تاني في صف واحد حسب العنوان المشترك (`loadVehicle` — `groupBy('address')`)
  - قسم البيانات الإضافية: عنوان المبيت + غرفة الحفظ + ملاحظات
  - عند الحفظ لأول مرة: يُنشأ السجل ويصير `isEditing = true`، وتتفعّل باقي التابات في الـ nav
- Tab 2 مُنجزة: **العاملون** — السائق (اسم + هاتف) / الموثق (اسم + هاتف) / المراجع (اسم + هاتف). كل الحقول nullable، عمود منفصل لكل حقل في جدول `vehicles`. أرقام الهاتف: نفس قيود "هاتف رئيس المقر" (`tel` + `inputmode=numeric` + تصفية غير الأرقام بـ Alpine + `regex:/^01[0-9]{9}$/`)
- Tab 3 مُنجزة: **التجهيزات** — **قرار معماري متعمَّد**: الحقول الثابتة (8 حقول) بدل تصميم ديناميكي مبني على `vehicle_device_types` (اتناقشنا في الموضوع واتفقنا إن العدد ثابت ومعروف فمفيش داعي للتعقيد الإضافي؛ الديناميكية اتسابت بس لقسم الأجهزة المعطلة اللي فعلاً محتاجها):
  - `mobility_bag` (enum: available/not_available) — شنطة التنقلات
  - `laptops_count`, `fingerprints_count`, `printers_count`, `collection_machines_count`, `mifi_count` (أعداد nullable)
  - `generator_status`, `surveillance_cameras` (enum: available/not_available/broken — نفس enum كاميرا المراقبة في المقرات)
  - **الأجهزة المعطلة**: جدول جديد `vehicle_broken_devices` (`vehicle_id`, `device_type_id` → `vehicle_device_types`, `count`) + موديل `VehicleBrokenDevice` — نفس نمط `OfficeBrokenDevice` بالحرف (صفوف ديناميكية إضافة/حذف، منع تكرار نفس نوع الجهاز في أكتر من صف)
- Tab 4 مُنجزة: **الوسائط** — **نفس أسلوب المقرات بالحرف** (رفع فوري لكل ملف، منفصل تماماً عن حفظ/تنقل التابات — مفيش validate/persist لتاب 4 في الـ dispatch، كل ملف بيتحفظ لحظة اختياره عبر method مخصص):
  - جدول جديد `vehicle_media` (`vehicle_id`, `type`, `path`, `original_name`) + موديل `VehicleMedia` — نفس نمط `OfficeMedia` بالحرف
  - `Vehicle::booted()` بيحذف ملفات الوسائط من `storage/public` تلقائياً عند حذف السيارة (نفس hook الموجود في `Office`)
  - 4 أقسام: **صور السيارة** (حد أقصى 5 — بدل 10 في المقرات) / **فيديو السيارة** (1) / **قرار الإنشاء** (PDF، 1) / **صورة الرخصة** (صورة، 1 — قسم جديد مش موجود في المقرات)
  - نفس Alpine.js pattern (modal رفع + modal معاينة + modal تأكيد حذف) من `create-step4.blade.php` بالمقرات، مُوسَّع لدعم نوع رابع (`license_photo`)
  - `uploadPhoto/uploadVideo/uploadDocument/uploadLicensePhoto/deleteMedia` — نفس تسمية methods المقرات + واحدة إضافية لصورة الرخصة
- Tab 5 مُنجزة: **الإحصائيات** — مقصورة على "التوثيق" فقط (مش كل تابات إحصائيات المقرات الثمانية)، وجايه كتاب واحد يجمع القسمين (بعكس المقرات اللي بتفصلهم في تابين منفصلين):
  - **جدول جديد** `vehicle_statistics` (`vehicle_id`, `stat_type_id`, `year`, `month`, `value`) — نفس شكل `office_statistics` بالحرف (بما فيها `decimal(12,2)` لعمود `value`) + موديل `VehicleStat`
  - **بيعاد استخدام جدول `stat_types` وصفوفه الحالية** (id=1 معاملات، id=2 بيع نماذج، id=3 بيع حوافظ) كمرجع مشترك بين المقرات والسيارات — الجدول لم يتغيّر ولم تُعدَّل أسماء صفوفه
  - **التسميات المعروضة مختلفة عن المقرات**: بدل عرض `$type->name` مباشرة، فيه mapping بالـ id لمفاتيح لغة جديدة (`vehicle_stat_transactions`, `vehicle_stat_form_sales`, `vehicle_stat_folder_sales`) في `Vehicles\StatTab\Documentation` — بيسمح للاسم يختلف حسب مكان العرض من غير ما يتغيّر شيء مشترك مع المقرات
  - **حقل جديد** `avg_daily_transactions` على جدول `vehicles` (نفس نمط المقرات) يتحرر من نفس التاب
  - **Component**: `App\Livewire\Vehicles\StatTab\Documentation` — دمج منطق `Offices\StatTab\TransactionsSales` + `Offices\StatTab\FormsAndFolders` في مكوّن واحد (نفس نظام الإضافة/تعديل/حذف بـ modals + فلاتر سنة/شهر + pagination لكل نوع)
  - **بدون `canEdit`**: الوصول لـ wizard السيارة أصلاً محجوب بصلاحية `vehicles.create`/`vehicles.edit` في `Create::mount()`، فأزرار الإضافة/التعديل/الحذف تظهر دايماً
  - الحفظ فوري (زي تاب الوسائط) — مش جزء من تدفق next/previous بالـ wizard؛ `validateCurrentTab`/`persistCurrentTab` بترجع `null` لتاب 5 زي تاب 4
  - View: `resources/views/livewire/vehicles/stat-tab/documentation.blade.php` (مضمّن عبر `<livewire:vehicles.stat-tab.documentation>` في `create-tab-statistics.blade.php`)
  - `create-tab-placeholder.blade.php` اتشال بعد ما بقى غير مُستخدم
- Keepalive مضاف

### الـ Sidebar
- ظهور بين المقرات ودليل الهاتف
- مقيّد بصلاحية `vehicles.index`

### سجل النشاط (Activity Log) ✅
`Vehicle` model بقى فيه `Spatie\Activitylog\Traits\LogsActivity` (نفس نمط `Office` بالحرف): `logFillable()` + `logOnlyDirty()` + `dontSubmitEmptyLogs()` + أوصاف عربية (إضافة/تعديل/حذف سيارة). التسجيل تلقائي بالكامل عبر Eloquent events — مفيش أي كود يدوي إضافي في `Vehicles\Create`/`Index`.
- **نطاق المشرف بالداشبورد**: `app/Livewire/Dashboard.php` بقى فيه `$myVehicleIds` (سيارات محافظات المستخدم) + شرط `orWhere` إضافي في استعلام النشاط (`subject_type = Vehicle::class`) — بنفس منطق المقرات بالظبط (نشاط لمستوى ≤ مستوى المشرف، مش نشاط اللي فوقه)
- **عمود "الموضوع" بجدول النشاط**: `activity-log.blade.php` بقى فيه فرع لـ `Vehicle::class` — لينك لـ `vehicles.edit` لو عند المستخدم صلاحية `vehicles.edit` (مفيش `vehicles.show` لسه)، وإلا اسم السيارة كنص عادي. مبني على متغيّر جديد `canEditVehicles` مُمرَّر من `Dashboard::render()`
- super-admin والمفتش العادي (نشاطه الشخصي فقط) ما احتاجوش أي تعديل — الفلترة عندهم مش مبنية على subject_type أصلاً

### ملخص السيارات بالداشبورد ✅
قسم مستقل جديد `resources/views/livewire/dashboard/vehicles-summary.blade.php` (نفس نمط `claims-summary.blade.php` — كارت واحد بعنوان + عمود لكل رقم، مش الشبكة الديناميكية بتاعة `kpi-cards.blade.php` عشان منلمسش عدّ أعمدتها):
- **4 أعمدة**: (إجمالي السيارات + توزيع الحالات في نفس العمود/الصف: تعمل أخضر، صيانة أصفر، متوقفة أحمر — كل حالة باسمها جنب رقمها) / معاملات التوثيق (آخر سنة فقط) / بيع النماذج (آخر سنة فقط) / بيع الحوافظ (آخر سنة فقط) — النماذج والحوافظ منفصلين مش مجموعين، وبدون نسبة تغيير
- ألوان الحالات نفس badge الحالة في `Vehicles\Index`
- **إحصائيات التوثيق**: `$vehicleStatsSummary` في `Dashboard::render()` — استعلام مستقل لكل `stat_type_id` (1=معاملات، 2=بيع نماذج، 3=بيع حوافظ، نفس تسميات `Vehicles\StatTab\Documentation`) بياخد آخر سنة بس (مفيش سنة سابقة/نسبة تغيير)
- محجوب بصلاحية `vehicles.index` (`canViewVehicleStats`)، ومفلتر بمحافظات المستخدم لغير super-admin (السيارات عبر `governorate_id` مباشرة، الإحصائيات عبر `whereHas('vehicle', ...)`)
- يظهر قبل قسم ملخص المطالبات وتنبيه "مقرات تحتاج زيارة"

### رسم توزيع المقرات على المحافظات — بقى فيه السيارات كمان ✅
`chart-governorates.blade.php` بقى فيه **dataset تاني** (لون أزرق، جنب الذهبي بتاع المقرات) لعدد السيارات في كل محافظة، بمحاذاة نفس ترتيب/محاور المحافظات الموجودة بالفعل (نفس القائمة اللي فيها مقرات — `$officesByGovRaw`):
- `$vehiclesByGov` في `Dashboard::render()` — مصفوفة أعداد سيارات محاذية 1:1 لترتيب `$officesByGov`، محجوبة بـ `canViewVehicleStats` (لو مفيش صلاحية، الـ dataset مش بيتضاف خالص)
- الـ legend بقى ظاهر بس لو dataset السيارات موجود (عشان يميّز بين اللونين)؛ تلميح الجدول (tooltip) بقى بياخد اسم الـ dataset ديناميكياً بدل ما كان مكتوب "عدد المقرات" ثابت
- **العنوان اتغيّر** من "توزيع المقرات على المحافظات" لـ "التوزيع الجغرافي على المحافظات" (مفتاح `home.chart_offices_by_gov` نفسه، النص بس اتغيّر) — عشان يفضل صحيح حتى لو dataset السيارات مخفي لمستخدم بدون صلاحية `vehicles.index`

### Lookup Tables — شاشات الإعدادات ✅
CRUD كامل (Index + Create/Edit) لكل الجداول الخمسة، بنفس نمط `WorkSystems` (بحث، pagination، حذف بـ modal تأكيد، صلاحية `super-admin` فقط)، مع روابط في قسم "إعدادات البرنامج" بالـ sidebar ومفاتيح لغة `vehicle_*`:
- `vehicle_types` → `app/Livewire/VehicleTypes/`
- `vehicle_brands` → `app/Livewire/VehicleBrands/`
- `vehicle_work_systems` → `app/Livewire/VehicleWorkSystems/`
- `vehicle_working_hours` → `app/Livewire/VehicleWorkingHours/`
- `vehicle_device_types` → `app/Livewire/VehicleDeviceTypes/` (تم أيضاً إنشاء موديل `VehicleDeviceType` الذي كان ناقصاً)

---

## ما لم يُنفَّذ بعد ⏳

### بنود مؤجلة (تنتظر تأكيد العميل)
- **سجل تحركات الدعم**: عنوان + تاريخ — على الأرجح جدول منفصل (`vehicle_support_movements`) — بانتظار تأكيد العميل

> ✅ **حُسم**: فكرة "أيام التمركز الإضافي" المنفصلة اتلغت — فضل قسم واحد بس "أيام التمركز" (نفس منطق الأساسي، من غير تقسيم أساسي/إضافي).

### صفحة Show (عرض)
- مثل `offices/{id}` — صفحة قراءة فقط بـ tabs
- صلاحية: `vehicles.view`

### باقي الصلاحيات
- `vehicles.index` وصلاحية `vehicles.export` موجودتان لكن بدون تنفيذ export بعد
- إضافة السيارات للتقارير مستقبلاً

---

## enum values المستخدمة
```php
// Vehicle::STATUSES
'working'     => 'تعمل'
'maintenance' => 'متوقفة للصيانة'
'stopped'     => 'متوقفة'

// VehicleLocation::DAYS
'saturday' => 'السبت'    'sunday'    => 'الأحد'
'monday'   => 'الاثنين'  'tuesday'   => 'الثلاثاء'
'wednesday'=> 'الأربعاء' 'thursday'  => 'الخميس'
'friday'   => 'الجمعة'
```

---

## هيكل الملفات
```
app/
  Livewire/Vehicles/
    Index.php
    Create.php
  Livewire/Vehicles/StatTab/     ✅ تاب 5
    Documentation.php
  Livewire/VehicleTypes/         ✅ CRUD lookup
    Index.php
    Create.php
  Livewire/VehicleBrands/        ✅ CRUD lookup
    Index.php
    Create.php
  Livewire/VehicleWorkSystems/   ✅ CRUD lookup
    Index.php
    Create.php
  Livewire/VehicleWorkingHours/  ✅ CRUD lookup
    Index.php
    Create.php
  Livewire/VehicleDeviceTypes/   ✅ CRUD lookup
    Index.php
    Create.php

app/Models/
  Vehicle.php
  VehicleLocation.php
  VehicleType.php
  VehicleBrand.php
  VehicleWorkSystem.php
  VehicleWorkingHour.php
  VehicleDeviceType.php        ✅ تم إنشاؤه (كان ناقصاً) — مستخدم الآن في CRUD
  VehicleBrokenDevice.php      ✅ (نفس نمط OfficeBrokenDevice)
  VehicleMedia.php             ✅ (نفس نمط OfficeMedia)
  VehicleStat.php              ✅ (نفس نمط OfficeStat) — جدول vehicle_statistics

resources/views/livewire/vehicles/
  index.blade.php
  create.blade.php
  includes/
    create-tab-basic.blade.php       ✅
    create-tab-workers.blade.php     ✅
    create-tab-equipment.blade.php   ✅
    create-tab-media.blade.php       ✅
    create-tab-statistics.blade.php  ✅ (يضمّن livewire:vehicles.stat-tab.documentation)
  stat-tab/
    documentation.blade.php          ✅

resources/views/livewire/vehicle-types/         ✅ index.blade.php + create.blade.php
resources/views/livewire/vehicle-brands/        ✅ index.blade.php + create.blade.php
resources/views/livewire/vehicle-work-systems/  ✅ index.blade.php + create.blade.php
resources/views/livewire/vehicle-working-hours/ ✅ index.blade.php + create.blade.php
resources/views/livewire/vehicle-device-types/  ✅ index.blade.php + create.blade.php

database/migrations/
  2026_07_01_000001  → vehicle_types
  2026_07_01_000002  → vehicle_brands
  2026_07_01_000003  → vehicle_work_systems
  2026_07_01_000004  → vehicle_working_hours
  2026_07_01_000005  → vehicle_device_types
  2026_07_01_000006  → vehicle permissions
  2026_07_01_000007  → vehicles
  2026_07_01_000008  → vehicle_locations
  2026_07_05_070735  → إضافة حقول العاملين (driver/notary/reviewer) لجدول vehicles
  2026_07_05_074356  → إضافة حقول التجهيزات الثابتة لجدول vehicles
  2026_07_05_074414  → vehicle_broken_devices
  2026_07_05_080527  → vehicle_media
  2026_07_06_000001  → إضافة avg_daily_transactions لجدول vehicles
  2026_07_06_000002  → vehicle_statistics (FK على stat_types المشترك مع المقرات)
```
