# ملخص مشروع rern-offices

## نظرة عامة
نظام لإدارة مقرات التوثيق والشهر العقاري في مصر. يتيح تسجيل بيانات المقرات (البيانات الأساسية، الخدمات، التقييم، الوسائط، الإحصائيات) وعرضها وتعديلها.

## Stack التقني
- **Laravel 12** + **Livewire 3** (wire:navigate للـ SPA navigation)
- **Flux UI** (`Flux::toast` للإشعارات)
- **Tailwind CSS** (ألوان: `#c9a847` ذهبي, `#b8962e` ذهبي داكن)
- **Alpine.js** للـ modals والـ viewer
- **Spatie Permissions** للصلاحيات (`spatie/laravel-permission`)
- **Storage disk: `public`** للوسائط

---

## نظام الصلاحيات
```
offices.view    — عرض تفاصيل مقر
offices.create  — إنشاء مقر
offices.edit    — تعديل مقر
offices.delete  — حذف مقر
offices.export  — تصدير (غير منفذ بعد)
```
- **super-admin**: يتجاوز جميع الصلاحيات
- **المستخدم العادي**: مربوط بمحافظات معينة (`governorate_user` pivot table)، يرى مقرات محافظاته فقط

---

## Models والعلاقات

### Office (الجدول الرئيسي)
```
office_id → governorate_id, parent_office_id (self-ref)
type_id → OfficeType
location_description_id → LocationDescription (بها: shows_windows_count boolean)
work_system_id → WorkSystem
connection_type_id → ConnectionType
working_hours_id → WorkingHour
contractual_status_id → ContractualStatus
structural_condition_id → StructuralCondition
microfilm_option_id → MicrofilmOption
disabilities_access_id → DisabilitieAccess
fire_safety_id → FireSafety
document_photocopying_service_id → DocumentPhotocopyingService
buffet_service_id → BuffetService
cleanliness_contract_id → CleanlinessContract
```

**حقول enum مهمة:**
```php
// كلها nullable string columns
Braille_sign_device: 'available' | 'not_available'
queue_management_system: 'working' | 'not_working' | 'not_available'
surveillance_cameras: 'available' | 'not_available' | 'broken'
electricity_meter_type / water_meter_type: 'prepaid' | 'invoice' | 'entity_meter'
electricity_meter_debt / water_meter_debt: 'yes' | 'no'
working_days: 'full_week' | 'one_day' | 'two_days' | 'three_days' | 'four_days' | 'five_days'

// Constants في الموديل
Office::CLEANLINESS_RATINGS = ['good'=>'جيدة', 'average'=>'متوسطة', 'bad'=>'سيئة']
Office::ARCHIVE_RATINGS = ['excellent','good','average','bad','none']
Office::COMMITMENT_RATINGS = ['excellent','good','average','bad']
```

**HasMany:**
```
media() → OfficeMedia (type: 'photo'|'video'|'document', path, original_name)
brokenDevices() → OfficeBrokenDevice (device_type_id, count) → DeviceType
statistics() → OfficeStat (جدول: office_statistics, stat_type_id, year, month, value)
branches() → Office (self HasMany via parent_office_id)
```

**booted():** عند حذف مقر → يحذف ملفاته من `storage/public` تلقائياً.

### Governorate
```
fillable: name, order, supervising_counselor, latitude, longitude
BelongsToMany: users (via governorate_user pivot)
HasMany: offices
```

### OfficeStat (جدول: office_statistics)
```
stat_type_id: 1=معاملات التوثيق(سنوية), 2=نماذج توثيق(شهرية), 3=حوافظ توثيق(شهرية)
```
**ملاحظة:** تابز الإحصائيات تشمل أيضاً: طلبات الشهر، نماذج/حوافظ شهر، طلبات السجل، نماذج/حوافظ سجل — هذه تستعمل جدول مستقل (StatTab components).

---

## Routes (web.php)
```php
offices                              → Offices\Index       (offices.index)
offices/create                       → Offices\Create      (offices.create)
offices/{office}/edit                → Offices\Create      (offices.edit)  ← نفس component
offices/{office}/statistics          → Offices\Statistics  (offices.statistics)
// مطلوب إضافته:
offices/{office}                     → Offices\Show        (offices.show)
```

---

## Livewire Components

### Offices\Index
- جدول مقرات مع بحث + فلتر (محافظة، نوع، وصف موقع)
- أزرار: تعديل، حذف
- يجب إضافة: زر "عرض" → offices.show

### Offices\Create
- **Wizard 4 steps** (step في URL):
  - **Step 1**: البيانات الأساسية + أوقات العمل + بيانات الموقع + بيانات التشغيل
  - **Step 2**: الخدمات والتجهيزات + الأجهزة والمعدات + العدادات + الأجهزة المعطلة
  - **Step 3**: بيانات الزيارة والتقييم + النصوص الحرة
  - **Step 4**: الوسائط (صور max10، فيديو max1، PDF max1) + الإحصائيات القديمة (منقولة لـ Statistics)
- نفس الـ component لـ create وedit (يفرق بـ `$isEditing`)
- التحقق: step1 إلزامي، step3 كان إلزامياً لكن مُعلَّق حالياً

### Offices\Statistics
- صفحة إحصائيات مستقلة `/offices/{id}/statistics`
- 6 تابز: معاملات التوثيق، نماذج/حوافظ توثيق، طلبات الشهر، نماذج/حوافظ شهر، طلبات السجل، نماذج/حوافظ سجل
- كل تاب = Livewire sub-component في `Offices\StatTab\`

### Offices\Show ← **مطلوب إنشاؤه**
- `/offices/{id}` — view-only
- صلاحية: `offices.view` أو `offices.edit` أو `super-admin`
- التصميم المتفق عليه: Cards (read-only) + نفس تابز الإحصائيات في الأسفل

---

## قاعدة اللغة (إلزامية)
**كل نص عربي يظهر في أي view لازم يكون مفتاح في `lang/ar/home.php` ويُستدعى بـ `__('home.key')`.**
- لا يُكتب نص عربي hardcoded مباشرة في أي blade file
- عند إضافة view جديد، أضف مفاتيحه في `lang/ar/home.php` أولاً قبل كتابة الـ blade
- ملف اللغة: `lang/ar/home.php`

---

## UI Conventions (مهم — اتبعها دائماً)

### ستايل الصفحات
```
max-w-4xl mx-auto p-6 space-y-6
```

### Header pattern (في كل صفحة)
```html
<div class="flex items-center justify-between gap-4">
  <div class="flex items-center gap-4">
    <!-- back button: w-8 h-8 rounded-lg border border-zinc-300 -->
    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">...</h1>
  </div>
  <!-- action button: border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 -->
</div>
```

### Section header (داخل كل قسم)
```html
<div class="flex items-center gap-3 mb-5">
    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">...</h3>
</div>
```

### Tabs pattern
```html
<div class="border-b border-zinc-200 dark:border-zinc-700">
    <nav class="flex gap-1">
        <button class="px-4 py-2.5 text-sm font-medium border-b-2 transition
            {{ active ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 ...' }}">
```

### Card read-only pattern (للـ Show page)
```html
<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
  <!-- section header -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">التسمية</p>
      <p class="text-sm text-zinc-800 dark:text-zinc-100">القيمة أو —</p>
    </div>
  </div>
</div>
```

### Input classes المستخدمة في Create
```php
$inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 ...'
$lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1'
```

---

## هيكل الملفات المهم
```
app/
  Livewire/Offices/
    Index.php
    Create.php       ← create + edit
    Statistics.php
    Show.php         ← مطلوب
    StatTab/         ← 6 sub-components للإحصائيات

resources/views/livewire/offices/
    index.blade.php
    create.blade.php
    statistics.blade.php
    show.blade.php          ← مطلوب
    includes/
        create-step1.blade.php   ← بيانات أساسية + عمل + موقع + تشغيل
        create-step2.blade.php   ← خدمات + أجهزة + عدادات + أجهزة معطلة
        create-step3.blade.php   ← تقييم + نصوص حرة
        create-step4.blade.php   ← وسائط + إحصائيات (قديمة)
        create-step5.blade.php   ← إحصائيات منفصلة (محتوى مشابه لـ create-step4)
    stat-tab/
        transactions-sales.blade.php
        forms-and-folders.blade.php
        requests.blade.php
        registry-requests.blade.php
        registry-forms-and-folders.blade.php
        monthly-forms-and-folders.blade.php
        claims.blade.php
```

---

## المتبقي من Features

### 1. صفحة Show `/offices/{id}` — **نعمل عليها الآن**
- Cards read-only لكل sections
- الإحصائيات كتابز في الأسفل (مدمجة)
- زر تعديل للمستخدمين ذوي صلاحية edit

### 2. Export
- صلاحية `offices.export` موجودة لكن بدون تنفيذ
- المطلوب: Excel أو PDF من صفحة Index

### 3. تشكيل المكتب (موديول الموظفين)
- صفحة مستقلة `/offices/{id}/formation`
- لا تزال في مرحلة التخطيط

### 4. Dashboard
- لا توجد إحصائيات عامة حتى الآن

---

## أوامر السيرفر المعلقة
بعد كل `git push` يجب تذكير المستخدم بتشغيل على السيرفر:
```bash
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ملاحظات مهمة
- `debt_amount` موجود في `$fillable` لكن غير مستخدم في الواجهة بعد
- `avg_daily_transactions` نُقل من step1 إلى صفحة الإحصائيات (معلّق في step1)
- `parent_office_id` موجود في الموديل لكن مخفي من الـ wizard حالياً
- `claims.blade.php` موجود لكن غير مفعّل في تابز الإحصائيات
- تحقق step3 مُعلَّق حالياً في `nextStep()`
