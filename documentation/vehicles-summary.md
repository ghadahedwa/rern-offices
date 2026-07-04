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
| `vehicle_locations` | أيام التمركز الأساسي (day + address لكل سيارة) |

**جدول `vehicles` — الحقول:**
```
governorate_id (FK)     type_id (FK)           work_system_id (FK)
working_hours_id (FK)   brand_id (FK)          name
license_plate           manufacture_year        chassis_number
license_expiry_date     status (enum)           overnight_address
storage_room_location   notes
```

**جدول `vehicle_locations`:**
```
vehicle_id (FK)    day (enum: saturday→friday)    address
```

### Models
- `Vehicle` — مع `STATUSES` constant + علاقات BelongsTo/HasMany
- `VehicleLocation` — مع `DAYS` constant
- `VehicleType`, `VehicleBrand`, `VehicleWorkSystem`, `VehicleWorkingHour`, `VehicleDeviceType`

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
- Tab 1 مُنجزة: **البيانات الأساسية**
  - المحافظة → المستشار يظهر تلقائياً
  - كل حقول البيانات الأساسية
  - قسم **أيام التمركز الأساسي**: صفوف ديناميكية (يوم + عنوان)، الأيام المختارة تُشال من القوائم الأخرى تلقائياً
  - قسم البيانات الإضافية: عنوان المبيت + غرفة الحفظ + ملاحظات
- Tabs 2-5 ظاهرة في الـ UI لكن disabled (للتطوير لاحقاً)
- Keepalive مضاف

### الـ Sidebar
- ظهور بين المقرات ودليل الهاتف
- مقيّد بصلاحية `vehicles.index`

### Lookup Tables — شاشات الإعدادات ✅
CRUD كامل (Index + Create/Edit) لكل الجداول الخمسة، بنفس نمط `WorkSystems` (بحث، pagination، حذف بـ modal تأكيد، صلاحية `super-admin` فقط)، مع روابط في قسم "إعدادات البرنامج" بالـ sidebar ومفاتيح لغة `vehicle_*`:
- `vehicle_types` → `app/Livewire/VehicleTypes/`
- `vehicle_brands` → `app/Livewire/VehicleBrands/`
- `vehicle_work_systems` → `app/Livewire/VehicleWorkSystems/`
- `vehicle_working_hours` → `app/Livewire/VehicleWorkingHours/`
- `vehicle_device_types` → `app/Livewire/VehicleDeviceTypes/` (تم أيضاً إنشاء موديل `VehicleDeviceType` الذي كان ناقصاً)

---

## ما لم يُنفَّذ بعد ⏳

### Tabs المتبقية (Tab 2→5) في صفحة التعديل
| التاب | المحتوى |
|-------|---------|
| **العاملون** | السائق (اسم + هاتف) / الموثق (اسم + هاتف) / المراجع (اسم + هاتف) |
| **التجهيزات** | شنطة التنقلات / لاب توب / بصمة / طابعة / ماكينة تحصيل / MiFi / مولد / كاميرا + الأجهزة المعطلة |
| **الوسائط** | فيديو واحد / 5 صور / قرار الإنشاء / صورة الرخصة |
| **الإحصائيات** | متوسط معاملات يومي / عدد حوافظ شهري / عدد نماذج شهري |

### بنود مؤجلة (تنتظر تأكيد العميل)
- **أيام التمركز الإضافي**: المنطق المطلوب كان معقداً (أيام متبقية تفتح حقل إضافي ديناميكياً) — بانتظار توضيح العميل
- **سجل تحركات الدعم**: عنوان + تاريخ — على الأرجح جدول منفصل (`vehicle_support_movements`) — بانتظار تأكيد العميل

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

resources/views/livewire/vehicles/
  index.blade.php
  create.blade.php
  includes/
    create-tab-basic.blade.php  ✅

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
```
