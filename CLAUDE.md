# ملخص مشروع rern-offices

## نظرة عامة
نظام لإدارة مقرات التوثيق والشهر العقاري في مصر. يتيح تسجيل بيانات المقرات (البيانات الأساسية، الخدمات، التقييم، الوسائط، الإحصائيات) وعرضها وتعديلها.

## Stack التقني
- **Laravel 13** + **Livewire 4** (wire:navigate للـ SPA navigation)
  - ⚠️ Livewire **4** مش 3 — الـ JS API مختلف (مثال: اعتراض 419 بـ `Livewire.interceptRequest({onError})` + `preventDefault()`، **مش** `Livewire.onPageExpired` الخاص بـ v3)
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
- ⚠️ **تنبيه**: صلاحيات الـ Seeder القديمة (`view`, `create`...) ميتة — الكود يفحص `offices.*`. النطاق الفعلي للمستخدم العادي يعتمد على **ربط المحافظة** (تصميم مقصود: المحافظة شبه ثابتة = حدود المنطقة).

### نظام المستويات الهرمي (الرؤية)
عمود `level` على جدول `roles` (1=مفتش، 2=مستشار، 3=رئيس، افتراضي 1). مستوى المستخدم = أعلى مستوى بين أدواره. **المستوى منفصل تماماً عن الصلاحيات** (المستوى = نشاط مَن أرى، الصلاحية = ماذا أفعل).
- يُضبط من شاشة الأدوار (Roles\Create/Edit) عبر partial `level-select`
- القاعدة: `صاحب النشاط.level ≤ مستواي` (الأدنى لا يرى الأعلى، الأقران يرون بعض)
- نشاط المقرات يُفلتر حسب **محافظة المقر** (لا محافظة الفاعل) — المشرف يرى نشاط مفتشه في المحافظات المشتركة فقط
- الدخول/الخروج يُفلتر حسب مشاركة المحافظة
- super-admin مستبعد دائماً من رؤية أي مشرف (فوق الجميع)
- مستخدم في `Dashboard.php` فقط (سجل النشاط + المتصلون الآن)

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
البيانات مربوطة بـ stat_type_id من جدول stat_types
stat_types.group_key:
  transactions          → معاملات التوثيق (سنوية)
  forms_folders         → نماذج وحوافظ توثيق (شهرية)
  shaher_requests       → طلبات الشهر
  monthly_forms_folders → نماذج وحوافظ شهر (شهرية)
  registry_requests     → طلبات السجل
  registry_forms_folders→ نماذج وحوافظ سجل (شهرية)
  law9_registrations    → مشهرات قانون ٩ (سنوية، min year: 2022)
  law27_forms_folders   → نماذج وحوافظ قانون ٢٧ (شهرية: بيع النماذج، بيع الحوافظ)
```
**StatType model:** `fillable: name, period(yearly|monthly), value_type(count|amount), group_key, order`

---

## Routes (web.php)
```php
/                                    → redirect إلى /feedback (name: home)  ← الجذر يفتح البوابة العامة
offices                              → Offices\Index       (offices.index)
offices/create                       → Offices\Create      (offices.create)
offices/{office}                     → Offices\Show        (offices.show)
offices/{office}/edit                → Offices\Create      (offices.edit)  ← نفس component
offices/{office}/statistics          → Offices\Statistics  (offices.statistics)

// بوابة رأي المواطن — عامة (بدون auth):
feedback                             → view feedback.landing (feedback)
feedback/rating                      → Feedback\Rating      (feedback.rating)
feedback/suggestion                  → Feedback\Suggestion  (feedback.suggestion)

// نتائج رأي المواطن — إدارية (super-admin فقط):
feedback-results                     → FeedbackResults\Dashboard         (feedback-results.dashboard)
feedback-results/ratings             → FeedbackResults\Ratings           (feedback-results.ratings)
feedback-results/suggestions         → FeedbackResults\Suggestions       (feedback-results.suggestions)
feedback-results/rejected            → FeedbackResults\RejectedAttempts  (feedback-results.rejected)
```

---

## Livewire Components

### Offices\Index
- جدول مقرات مع بحث + فلتر (محافظة، نوع، وصف موقع)
- أزرار: عرض، تعديل، حذف

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
- **8 تابز** (مرتبة):
  1. `transactions` → معاملات التوثيق (سنوية)
  2. `forms_folders` → نماذج وحوافظ التوثيق (شهرية)
  3. `shaher_requests` → طلبات الشهر
  4. `law9_registrations` → مشهرات قانون ٩ (سنوية، من 2022)
  5. `monthly_forms_folders` → نماذج وحوافظ قانون ٩ (شهرية)
  6. `law27_forms_folders` → نماذج وحوافظ قانون ٢٧ (شهرية) ← **مضافة**
  7. `registry_requests` → طلبات السجل
  8. `registry_forms_folders` → نماذج وحوافظ سجل
- كل تاب = Livewire sub-component في `Offices\StatTab\`
- نظام التابز: أزرار دائرية مرقمة متصلة بخط أفقي (وليس border-b tabs)

### Offices\Show ✅
- `/offices/{id}` — view-only
- صلاحية: `offices.view` أو `offices.edit` أو `super-admin`
- التصميم: Card واحدة بـ 4 tabs (البيانات الأساسية / الخدمات والأجهزة / التقييم / الوسائط)
- زر "إحصائيات" في الـ header يذهب إلى `/offices/{id}/statistics` (مثل صفحة التعديل)
- زر "تعديل" يظهر فقط لمن لديه صلاحية `offices.edit`

---

## قاعدة اللغة (إلزامية)
**كل نص عربي يظهر في أي view لازم يكون مفتاح في `lang/ar/home.php` ويُستدعى بـ `__('home.key')`.**
- لا يُكتب نص عربي hardcoded مباشرة في أي blade file
- عند إضافة view جديد، أضف مفاتيحه في `lang/ar/home.php` أولاً قبل كتابة الـ blade
- ملف اللغة: `lang/ar/home.php`
- **رسائل التحقق:** أي حقل جديد له `required`/قواعد validation لازم اسمه العربي يتضاف في
  `lang/ar/validation.php` تحت `attributes` (وإلا الرسالة تطلع باسم الحقل الإنجليزي).

### الاستثناء الوحيد: بوابة رأي المواطن
**نصوص `resources/views/feedback/` و`resources/views/livewire/feedback/` تُكتب inline مباشرة — بقرار مقصود، ليست مخالفة.**
السبب: القاعدة قيمتها في شاشات الإدارة حيث تتكرر نفس اللافتة عبر عشرات الشاشات فيخدمها مفتاح واحد. نصوص البوابة **سردية فريدة** لا تتكرر، ولا توجد لغة ثانية مخطط لها، فالمفتاح يضيف طبقة بلا مقابل.
ينطبق الاستثناء أيضاً على النصوص العربية في `Rating::WAIT_TIMES` / `Rating::CRITERIA` / `Suggestion::DOMAINS`.
⚠️ الاستثناء **للبوابة العامة فقط** — أي شاشة إدارية جديدة (بما فيها موديول عرض النتائج القادم) تلتزم بالقاعدة كاملة.

---

## قاعدة التوقيت (إلزامية)
**التخزين بـ UTC، والعرض بتوقيت مصر — لا تخلط بينهما.**
`config('app.timezone') = UTC` (لا تغيّرها: الصفوف المخزَّنة ستُقرأ كأنها توقيت محلي فتصبح غلطاً بـ٢–٣ ساعات)، و`config('app.display_timezone') = Africa/Cairo` للعرض فقط عبر **`App\Support\LocalTime`**:
```php
LocalTime::date($dt)   // 2026-08-03
LocalTime::time($dt)   // 9:00 ص   (نظام ١٢ ساعة بالعربي)
LocalTime::stamp($dt)  // 2026-08-03 9:00 ص
```
- **حوِّل**: أي **لحظة زمنية** — `created_at`/`updated_at`، `now()` في تذييل الطباعة، والقيمة الافتراضية لـ«اليوم» في فورمات الإدخال (`now()` بـ UTC يعطي تاريخ الأمس بين ١٢ و٣ فجراً).
- **لا تحوِّل**: الحقول المصبوبة `'date'` (`visited_at`, `established_at`, `Meeting::date`, `received_at`…) — يوم كتبه المستخدم بلا دلالة توقيت، وتحويله قد ينقله يوماً كاملاً.
- **لا تلمس**: `diffForHumans()` و`sessions.last_activity` — فروق بين لحظتين، سليمة أصلاً.
- ⚠️ التوقيت الصيفي في مصر يجعل الفرق `+2` أو `+3` حسب الشهر — لذلك لا يصلح أي إزاحة ثابتة، و`LocalTime` يتكفّل بها.
- ⚠️ **لا تستبدل مسارات الـ namespace بـ `sed`/`perl` عبر الشِل** — `\L` و`\S` تُفسَّر كتحويل حالة أحرف فتفسد `\App\Support\LocalTime`. استخدم أداة التحرير أو سكربت PHP.

## قاعدة البحث العربي (إلزامية)
**أي بحث نصي عربي لازم يستخدم `App\Support\ArabicText` — مايتكتبش `where('col','like',...)` مباشرة.**
- `ArabicText::normalize($term)` لكلمة البحث (PHP)، `ArabicText::sqlNormalize($col)` للعمود (SQL).
- يوحّد: الألف (`أ إ آ ٱ→ا`)، الياء (`ى→ي`)، التاء المربوطة (`ة→ه`)، ويزيل المسافات.
- الـ pagination يفضل في الداتابيز (مفيش سحب كل الصفوف). النمط:
  ```php
  ->when($this->search, fn($q) => $q->whereRaw(
      \App\Support\ArabicText::sqlNormalize('name').' LIKE ?',
      ['%'.\App\Support\ArabicText::normalize($this->search).'%']
  ))
  ```
- مطبَّق حالياً في: المقرات، السيارات، الاجتماعات، المستخدمين.

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

### Keepalive (إلزامي في كل صفحات الإدخال)
**كل صفحة يقضي فيها المستخدم وقتاً في كتابة بيانات** (create, edit, statistics, وأي صفحة إدخال جديدة) يجب أن تحتوي على هذا السطر قبل الـ `</div>` الأخير:
```html
{{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
<div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
```
**السبب:** يمنع مشكلة "This page has expired" (419) التي تظهر بعد وقت طويل من الإدخال بدون إرسال request للسيرفر.

---

## هيكل الملفات المهم
```
app/
  Livewire/Offices/
    Index.php
    Create.php          ← create + edit (نفس component)
    Statistics.php
    Show.php            ✅
    StatTab/            ← 7 sub-components للإحصائيات
      TransactionsSales.php
      FormsAndFolders.php
      Requests.php            ← group_key: shaher_requests
      MonthlyFormsAndFolders.php
      RegistryRequests.php
      RegistryFormsAndFolders.php
      Law9Registrations.php       ← group_key: law9_registrations ✅
      Law27FormsAndFolders.php    ← group_key: law27_forms_folders ✅ مضاف

resources/views/livewire/offices/
    index.blade.php
    create.blade.php
    statistics.blade.php
    show.blade.php       ✅
    includes/
        create-step1.blade.php      ← بيانات أساسية + عمل + موقع + تشغيل
        create-step2.blade.php      ← خدمات + أجهزة + عدادات + أجهزة معطلة
        create-step3.blade.php      ← تقييم + نصوص حرة
        create-step4.blade.php      ← وسائط
        create-step5.blade.php      ← إحصائيات قديمة (معلّقة)
        show-tab-basic.blade.php    ← تاب البيانات الأساسية
        show-tab-services.blade.php ← تاب الخدمات والأجهزة
        show-tab-assessment.blade.php ← تاب التقييم
        show-tab-media.blade.php    ← تاب الوسائط
    stat-tab/
        transactions-sales.blade.php
        forms-and-folders.blade.php
        requests.blade.php
        registry-requests.blade.php
        registry-forms-and-folders.blade.php
        monthly-forms-and-folders.blade.php
        law9-registrations.blade.php        ✅
        law27-forms-and-folders.blade.php   ✅ مضاف
        claims.blade.php                    ← موجود لكن غير مفعّل
```

---

## المتبقي من Features

### 1. صفحة Show `/offices/{id}` ✅ **منجزة**
- 4 tabs: البيانات الأساسية / الخدمات والأجهزة / التقييم / الوسائط
- زر إحصائيات → `/offices/{id}/statistics`
- زر تعديل للمستخدمين ذوي صلاحية edit

### 2. تقرير PDF للمقر ← **قيد التطبيق**
- PDF شامل لبيانات مقر واحد (بيانات أساسية + خدمات + تقييم + ملخص إحصائيات)
- الحزمة المقررة: `barryvdh/laravel-dompdf`
- route مقترح: `GET /offices/{office}/pdf` → controller عادي (ليس Livewire)
- التحدي: دعم العربية RTL في dompdf (يحتاج unicode_enabled + DejaVu font)

### 3. Export (قائمة)
- صلاحية `offices.export` موجودة لكن بدون تنفيذ
- المطلوب: Excel من صفحة Index (بعد PDF)

### 4. لوحة التحكم — Dashboard ✅ **منجزة**
الحزمة: `spatie/laravel-activitylog` (v4.12.3 — PHP لا يدعم v5). جدول `activity_log`.
المكوّن: `App\Livewire\Dashboard` + `resources/views/livewire/dashboard.blade.php`. Chart.js من CDN في `partials/head`.

**المكوّنات المنفّذة:**
- **KPI Cards**: إجمالي المقرات، المحافظات، المستخدمين، مقرات جديدة هذا الشهر
- **تنبيه**: مقرات لم تُزر منذ +6 أشهر (`visited_at`) + زر لقائمة المقرات بفلتر `needs_visit`
- **3 مخططات**: المحافظات (عمودي، كل المحافظات، tooltip بإحصائيات كل محافظة) / النوع (دائري) / الحالة الإنشائية (أفقي)
- **6 بطاقات إحصائية** بترتيب صفحة الإحصائيات: transactions, shaher_requests, law9_registrations, law27_registrations, registry_requests, forms_folders. كل بطاقة: آخر سنتين + نسبة تغيير. `forms_folders` بطاقة مفصّلة (بيع النماذج/الحوافظ منفصلين، من 2026 — حد أدنى `$statGroupMinYear`)
- **المتصلون الآن**: من جدول `sessions` (آخر 10 دقائق)
- **سجل النشاط**: إنشاء/تعديل/حذف/عرض مقر + دخول/خروج. `wire:poll.300s` (تحديث كل 5 دقائق). فلتر + بحث. pagination بـ `WithoutUrlPagination` + إزالة `scrollIntoViewJsSnippet` من pagination view. أداة حذف السجلات القديمة (super-admin، 3 أيام→شهر)

**النطاق حسب المستوى** (انظر "نظام المستويات الهرمي" أعلاه):
- super-admin: كل شيء | مشرف (level≥2): فريق محافظاته | مفتش: نشاطه + نفسه في المتصلين
- تسجيل العرض: `Show::mount()` يسجّل `event=viewed`؛ الدخول/الخروج: `AppServiceProvider` يضبط `event=login/logout`

### 5. تشكيل المكتب (موديول الموظفين)
- صفحة مستقلة `/offices/{id}/formation`
- جدول منفصل (مثل `office_employees`) — بيانات تتبع المقر، **ليسوا مستخدمين** ولا أدوار/مستويات لهم
- لا تزال في مرحلة التخطيط

---

## بوابة رأي المواطن (Citizen Feedback) ✅ **منجزة (جهة المواطن)**
بوابة عامة **بدون تسجيل دخول** تحت `/feedback`. الجذر `/` يوجّه لها. الموظفون يدخلون عبر `/login` مباشرة (لا رابط دخول في البوابة).

### الشاشات
- `feedback.landing` (blade) — كرتان: **تقييم الخدمة** / **تقديم اقتراح** + **شريط إرشادي للشكاوى الرسمية** (منظومة الشكاوى الموحدة بمجلس الوزراء: 16528 · واتساب · shakwa.eg — لا منظومة شكاوى داخلية).
- `Feedback\Rating` (`/feedback/rating`) و `Feedback\Suggestion` (`/feedback/suggestion`) — مكوّنات Livewire عامة، **كشف تدريجي على صفحة واحدة**: قسم الهوية والمقر أولاً، وبمجرد اختيار المقر تظهر البنود (شرط: التحقق قبل استيفاء البنود). موبايل-أولاً (خط الإدخال 16px لمنع zoom iOS).
- **التقييم:** مدة الانتظار (4) + 6 محاور نجوم (السادس اختياري) + تقييم عام + ملاحظات. **المقترحات:** 5 مجالات × عناوين multi-select (chips) + "اقتراح آخر" حر (كتالوج في `Suggestion::DOMAINS`).

### البنية (layout + partials مشتركة)
- Layout عام موحّد: `resources/views/components/layouts/feedback.blade.php` — يخدم الـ landing (`<x-layouts.feedback>`) والمكوّنات (`#[Layout('components.layouts.feedback')]`). يحوي design tokens + topbar + footer + theme toggle + `digitsOnly()` (تنقية الأرقام: عربي→إنجليزي + إزالة الحروف). `body{overflow-x:hidden}`.
- Partials تحت `resources/views/livewire/feedback/includes/`: `form-styles` (ستايل الفورم بمتغيّر `--accent`؛ كل شاشة تحدد لونها)، `identity-fields` (الهوية والمقر — **الرقم القومي/الهاتف `wire:model.live` + `oninput="digitsOnly"`**؛ ⚠️ لا تضع `x-data` على input فيه `wire:model` — يكسر التفاعلية)، `star-row`.

### الجداول والموديلات
`feedback_ratings` · `feedback_suggestions` · `suggestion_domains` · `suggestion_topics` · `feedback_suggestion_topic` (pivot) · `feedback_rejected_attempts`. FK للمقر/المحافظة `nullOnDelete` (حفظ التاريخ). `SuggestionCatalogSeeder` يزرع 5 مجالات/20 عنوان من `Suggestion::DOMAINS` (idempotent، في DatabaseSeeder).

### بوابة الحماية (Anti-abuse)
- `config/feedback.php`: `window_days=7` · `ip_max_per_minute=10` · `rejected_retention_days=30`.
- `App\Services\FeedbackGate`: `duplicateRetryDate` (يفحص **الرقم القومي أو الهاتف** + المقر خلال المدة — الهاتف معرّف شخصي، لكل نوع منفصل) · `ipThrottled`/`hitIp` (RateLimiter نافذة 60ث، صمّام ضد البوت فقط) · `logRejection`.
- Trait `App\Livewire\Feedback\Concerns\InteractsWithFeedbackGate`: honeypot (حقل `website` مخفي visually-hidden) · `evaluateGate()` فحص تفاعلي (يحجب قبل البنود؛ **يسجّل duplicate_window مرة واحدة عند دخول الحجب فقط** لا مع كل re-check) · `submit()` (honeypot→validate→IP→تكرار→حفظ في transaction) · `formatArabicDate` · الانتقال بين الفورمين (تحت).
- المكوّن يوفّر `feedbackType()`/`persist()`؛ Suggestion يربط العناوين بـ `topics()->sync`. `showRating`/`showTopics` = `office_id && !gateBlocked`.
- ⚠️ المشروع يستخدم **CarbonImmutable** — أي دالة ترجع تاريخ استخدم `CarbonInterface`.

### سلامة المدخلات (لا تُضعِفها)
الفورمان **عامان بلا auth**، فأي قيمة جاية من العميل غير موثوقة حتى لو الواجهة مابتسمحش بيها:
- `office_id` يُتحقق منه بـ **`App\Rules\PublicFeedbackOffice`** (وليس `exists:offices,id`) — يمنع تعليق رأي مواطن على مقر من نوع غير عام عبر طلب متلاعَب فيه. `scopePublicFeedback` وحده يخصّ **العرض** فقط.
- `governorate_id` **يُشتق من المقر** عبر `officeGovernorateId()` في الـ trait — لا يُحفظ كما أرسله العميل (وإلا فسد تجميع النتائج حسب المحافظة لاحقاً). **المقر هو مصدر الحقيقة.**
- فهرس `['phone','office_id']` على جدولي التقييم/المقترحات: `duplicateRetryDate` يبحث `national_id OR phone` والاستعلام يتنفّذ أثناء الكتابة (`wire:model.live`).

### الإظهار حسب النوع
فلاج **`is_public` على `office_types`** (مش على المقر) — قابل للتعديل من شاشة إدارة الأنواع (checkbox + شارة "عام" + فلتر). الفورمان يعرضان مقرات الأنواع العامة فقط عبر `Office::scopePublicFeedback` (whereHas officeType is_public)، والمحافظات بلا مقرات عامة تُخفى. فلتر "الظهور للمواطن": toggle في شاشة المقرات (`public_only`)، select في شاشة الأنواع.

### الانتقال بين الفورمين بنفس البيانات
بعد الإرسال الناجح أو شاشة الرفض، زر «قيّم/قدّم اقتراح أيضاً» ينقل الهوية عبر الـ **session** ورابط `?resume=1`. `mount()→resumeFromCarry()` يقرأ الـ session **فقط عند resume=1** (لا تعبئة تلقائية عند الفتح اليدوي = آمن للأجهزة المشتركة)، يمسحها بعد قراءة واحدة، ويشغّل `evaluateGate()`.

### التنظيف التلقائي
أمر `feedback:prune-rejected` (مجدول يومياً 03:30 في `routes/console.php`) يحذف `feedback_rejected_attempts` الأقدم من `rejected_retention_days`.
⚠️ الجدولة تحتاج `schedule:run` في cron السيرفر — **أُضيف بالفعل** (2026-07-30):
`* * * * * cd /var/www/rern-offices && /usr/bin/php artisan schedule:run >> /dev/null 2>&1`
نسخة احتياطية من الـ crontab السابق في `/root/crontab-backup-2026-07-30.txt`. أي `Schedule::command` جديد يشتغل تلقائياً من الآن.

### الاختبارات (Pest)
`php artisan test` — **١٠٩ اختبار، كلها ناجحة**. تشغيل اختبارات البوابة وحدها: `php artisan test tests/Feature/Feedback`.
- `tests/Feature/Feedback/FeedbackGateTest.php` — منع التكرار (بالرقم القومي/الهاتف، انتهاء المدة، لكل مقر ولكل نوع)، تسجيل الرفض مرة واحدة، honeypot، حد الـ IP، `is_public`، نقل الهوية بـ `resume=1`.
- `tests/Feature/Feedback/FeedbackValidationTest.php` — الرقم القومي بحالات رفضه الستة، صيغة الهاتف، سلامة المقر/المحافظة، أمر التنظيف.
- factories في `database/factories/` لـ Governorate / OfficeType / Office / FeedbackRating / FeedbackSuggestion (state `->public()` لنوع ظاهر للمواطن). الموديلات المستخدَمة في الاختبارات تحتاج `HasFactory`.
- **الاختبارات تعمل على sqlite في الذاكرة** (`phpunit.xml`) — لا تلمس قاعدة البيانات الحقيقية أبداً. لا تشغّلها على السيرفر.
- ⚠️ **عند تعديل منطق البوابة شغّل الاختبارات قبل الـ push** — هي الحارس الوحيد لمنطق منع التكرار.

### المتبقّي
- **قناة وصول المواطن للبوابة** — غير موجودة. المقترح: `?office={id}` يعبّي المحافظة والمقر تلقائياً + QR لكل مقر يُطبع ويُعلَّق على الشباك. بدونها البوابة منشورة لكن لا أحد يعرف كيف يصلها.
- شاشة المقترحات تقرأ المجالات من الـ const (المفاتيح مطابقة للكتالوج المزروع) — تُحوَّل لـ DB مع شاشة إدارة الكتالوج.

**⚠️ النشر:** موديول البوابة يحتاج `git pull` + `migrate --force` (جداول + is_public) + `config:cache` (مفاتيح feedback) + `route:cache` (توجيه الجذر) + `view:cache`.

---

## موديول نتائج رأي المواطن (الإدارة) ✅ **منجز**
فرع مستقل في السايدبار اسمه **«رأي المواطن»** (`config/branches.php` → مفتاح `feedback`, `super_admin_only => true`). الشاشات تحت `App\Livewire\FeedbackResults\` وقوالبها في `resources/views/livewire/feedback-results/`.

```php
feedback-results.dashboard     → /feedback-results              (Dashboard)
feedback-results.ratings       → /feedback-results/ratings      (Ratings)
feedback-results.suggestions   → /feedback-results/suggestions  (Suggestions)
feedback-results.rejected      → /feedback-results/rejected     (RejectedAttempts)
```
كلها خلف `middleware('role:super-admin')` + فحص ثانٍ في `mount()`.
⚠️ **لا تخلط** `feedback.*` (البوابة العامة بلا auth) مع `feedback-results.*` (الشاشات الإدارية) — أنماط الفرع تطابق الثانية فقط.

### الفلاتر المشتركة
`Concerns\WithFeedbackFilters` — محافظة/مقر/فترة مربوطة بالـ URL (`gov`, `office`, `from`, `to`)، والقالب المشترك `includes/filters.blade.php` (ومعه اختصارات فترة جاهزة: هذا الشهر / آخر ٣ شهور / هذه السنة).
- `applyFilters()` هو المدخل الوحيد لأي استعلام في الموديول.
- `filterByGovernorate()` قابل للتجاوز — جدول `feedback_rejected_attempts` **ليس فيه `governorate_id`**، فالمكوّن يفلتر عبر علاقة المقر.
- `applyScope()` فارغ حالياً (super-admin) — **هو النقطة الوحيدة** التي تُضاف فيها فلترة محافظات المستخدم عند فتح الموديول لأدوار أخرى (مع إضافة صلاحية `feedback.view` وحذف `super_admin_only`).
- ⚠️ **`applyDateRange()` لا تستخدم `whereDate`** — `DATE(created_at) >= ?` يطبّق دالة على العمود فيمنع MySQL من استخدام الفهرس (تأكّد بـ EXPLAIN: `type=ALL` قبل، `type=range` بعد). النطاق مفتوح على الأعلى (`< اليوم التالي`) ليدخل يوم النهاية كاملاً. الداشبورد تستدعي نفس الدالة لجدول المرفوضات حتى لا يتفرّع المنطق. فهرس `created_at` على الجداول الثلاثة في هجرة `2026_08_02_120000`.
- التاريخ قادم من الـ URL فيُمرَّر على `parsedDate()` — قيمة تالفة تُهمَل ولا تُسقط الصفحة.

### الترتيب
`Concerns\WithFeedbackSorting` + قالب `includes/sortable-th.blade.php`. المكوّن يوفّر `sortableColumns()`.
⚠️ اسم العمود يأتي من الـ URL — **لا يُمرَّر لـ `orderBy` إلا بعد التحقق من القائمة البيضاء**، وإلا صار حقن SQL عبر الرابط. مغطّى باختبار.

### البحث
البحث في التقييمات يشمل الاسم و**نص الملاحظة**، وفي المقترحات الاسم و**الاقتراح الحر وعناوين الكتالوج** — كلها عبر `ArabicText` (تطبيع الألف/الياء/التاء المربوطة). الرقم القومي والهاتف بحث نصي مباشر.

### قواعد حسابية لا تُكسر
- **المحور السادس (ذوو الإعاقة) `nullable`** — المتوسط يُحسب على المجيبين فقط. في SQL نعتمد أن `AVG`/`COUNT` يتجاهلان NULL؛ وعلى مستوى الصف `FeedbackRating::criteriaAverage()`. احتسابه صفراً يبوّظ الرقم.
- **حد أدنى للعينة قبل ترتيب المقرات**: `config('feedback.min_ratings_for_ranking')` (افتراضي ٥). المقرات الأقل تُعرض في مجموعة «عينة غير كافية» **خارج الترتيب** — مقر بتقييم واحد بخمس نجوم ليس أفضل مقر.
- **التقييم العام ومتوسط المحاور رقمان مختلفان** — يُعرضان معاً، لا يُستبدل أحدهما بالآخر.
- **أولويات المقترحات** تُحسب من جدول الربط `feedback_suggestion_topic` مقيَّداً بـ subquery على المقترحات المفلترة (لا تحميل كامل للصفوف).
- **الاتجاه الشهري** (`monthlyTrend`) يجيب على «هل يتحسّن؟» وهو ما لا تقوله لقطة الفترة الواحدة. بلا فلتر فترة يقتصر على آخر ١٢ شهراً. ⚠️ تجميع الشهر يختلف بين المحرّكين — `monthExpression()` يختار `DATE_FORMAT` لـ MySQL و`strftime` لـ sqlite (الاختبارات)، فلا تكتب أياً منهما مباشرة.
- **ترتيب المحافظات** يستخدم نفس حدّ العينة، لكنه يعرض الجميع ويُعلِّم ناقصي العينة بدل استبعادهم (عدد المحافظات محدود فالإخفاء يضرّ).

### الحذف الجماعي وسلة المحذوفات
`Concerns\WithBulkDelete` + القوالب `includes/bulk-bar` · `bulk-th` · `bulk-td` · `trash-toggle`. نمط بريد إلكتروني: checkbox لكل صف، checkbox في الرأس يحدد الصفحة (بحالة وسيطة)، وشريط إجراءات يظهر عند أول تحديد.
- المكوّن يوفّر ثلاث دوال: `bulkModel()` · `bulkQuery()` · `bulkSubject()`. **`bulkQuery()` هو نفسه استعلام `render()`** — مصدر واحد لما يُعرض ولما يُحذف.
- ⚠️ **الحذف يمرّ دائماً عبر `bulkQuery()`** حتى مع التحديد اليدوي (`whereIn` فوقه) — معرّف يُدسّ من العميل خارج نطاق الفلتر/الصلاحية لا يُمسّ. مغطّى باختبار.
- ⚠️ **`FeedbackRating` و`FeedbackSuggestion` بـ`SoftDeletes`** (أول استخدام له في المشروع). `FeedbackGate::duplicateRetryDate` يقرأ بـ**`withTrashed()`** — بدونها يصير حذف رأي عبثي إذناً لصاحبه بإعادة إرساله فوراً. مغطّى باختبار، **لا تُزلها**.
- الصفوف المحذوفة خارج المتوسطات والإحصائيات تلقائياً (الـ global scope).
- **`feedback_rejected_attempts` بلا سلة** — حذفه نهائي مباشر (يُنظَّف تلقائياً أصلاً ولا علاقة له بمنع التكرار). `restore`/`forceDelete` عليه يرجع 403.
- **«حدّد كل الـN المطابقة للفلتر»** يظهر عند تحديد الصفحة كاملة ووجود صفوف خلفها — استعلام واحد لا تحميل معرّفات.
- ⚠️ **التحديد يُمسح عند أي تغيّر في الصفحة** عبر `updatedPaginators()`، والفلاتر/البحث كلها تمرّ بـ`resetPage()` فتغطّيها النقطة نفسها. بدونه يُحذف صف خرج من الشاشة.
- **التأكيد بالمودال المشترك `livewire.partials.delete-modal`** (نفس مودال حذف المقرات) لا بـ`wire:confirm`. المكوّن يوفّر `showDelete`/`deletingLabel`/`deletingWarning`/`deletingPrompt` + `deleteRow()`. النص يتغيّر بقابلية التراجع: الحذف للسلة بلا تنبيه أحمر، والنهائي (وحذف المرفوضات) بتنبيه «لا يمكن التراجع». **الاسترجاع بلا تأكيد** — فعل غير مدمّر.
- ⚠️ `deleteRow()` يتجاهل النداء لو `showDelete === false` — حتى لا يُنفَّذ إجراء مؤجَّل بطلب مستقل.
- القالب المشترك صار يقبل `$deletingPrompt` اختيارياً (يسقط للنص الافتراضي لو غائب/فارغ) — لا يؤثر على مستدعييه القدامى.
- سجل مساءلة: `activity('feedback')` بحدث `bulk_delete`/`bulk_restore`/`bulk_force_delete` وخصائص فيها العدد والفلتر. **بلا subject** حتى لا يظهر في سجلّي داشبورد المقرات وإدارة النظام (كلاهما يفلتر على `subject_type`).

### قاعدة عرض الجداول (اتبعها في أي جدول جديد)
جدول بأعمدة كثيرة ونص عربي حقيقي يتجاوز عرض الشاشة بسهولة. الأعمدة الثابتة بالبكسل (`w-44`…) لا تحلّ المشكلة — تنقلها لشاشة أضيق. المطبَّق هنا:
- **`table-fixed` + نِسَب مئوية مجموعها ١٠٠٪** على الـ `<th>` — الجدول = عرض الحاوية بالضبط مهما طال المحتوى.
- **`truncate` + `title`** على كل خلية نصية (اسم مقر/مواطن/ملاحظة) — النص الكامل في الـ tooltip وفي صف التفاصيل.
- **إخفاء تدريجي حسب الأهمية**: `hidden xl:table-cell` للأعمدة الثانوية، `hidden 2xl:table-cell` لعمود الترقيم. الأساسي الظاهر دائماً ٥ أعمدة.
- **`min-w-140`** (٥٦٠px) — على الموبايل يتحوّل لتمرير أفقي داخل البطاقة بدل سحق الأعمدة.
- ⚠️ **أي عمود يُخفى لازم محتواه يكون موجوداً في صف التفاصيل** — مدة الانتظار أُضيفت للتفاصيل لهذا السبب بالذات.
- ⚠️ الفئات دي محتاجة `npm run build` بعد أي تعديل.

### مصدر الحقيقة للمسمّيات
`FeedbackRating::WAIT_TIMES` و`FeedbackRating::CRITERIA` **على الموديل** (بيانات لا واجهة)، و`Livewire\Feedback\Rating` يشير إليهما (`const X = FeedbackRating::X`). عناوين المقترحات ومجالاتها من جدولي `suggestion_topics`/`suggestion_domains`. لا تُكرَّر هذه النصوص في `lang/ar/home.php`.
باقي نصوص الموديول **كلها مفاتيح `fr_*` في `lang/ar/home.php`** — استثناء اللغة يخصّ البوابة العامة فقط.

### بيانات تجريبية (محلي فقط)
```bash
php artisan db:seed --class=FeedbackDemoSeeder
```
`FeedbackDemoSeeder` يرفض العمل على production ولا يُستدعى من `DatabaseSeeder`. **يمسح بيانات البوابة الموجودة** قبل الزرع، ويولّد انحيازاً بين المقرات ومقرّين بعينة صغيرة عمداً + محوراً اختيارياً فارغاً في ~40% من الصفوف — أي أنه يختبر القاعدتين أعلاه بصرياً.

### الاختبارات
`php artisan test tests/Feature/FeedbackResults` — **٥٤ اختباراً** (`BulkDeleteTest.php` منها ٢٠ للحذف الجماعي): السلة والاسترجاع والحذف النهائي مع صفوف الربط · بقاء منع التكرار بعد الحذف · خروج المحذوف من متوسطات الداشبورد · احترام الفلتر في «حدّد كل المطابق» · تجاهل معرّف مدسوس · تصفير التحديد · حذف المرفوضات نهائياً · تسجيل النشاط.
والباقي: منع غير السوبر أدمن من الشاشات الأربع · متوسط المحور الاختياري · حد العينة (للمقرات وللمحافظات) · فلاتر الفترة/المحافظة · **شمول يومَي طرفَي الفترة** · **تجاهل تاريخ تالف من الرابط** · تصفير المقر عند تغيير المحافظة · عدّ عناوين المقترحات مع الفلتر · البحث العربي المطبَّع **وداخل النصوص الحرة وعناوين الكتالوج** · **الترتيب وعكسه ورفض عمود خارج القائمة البيضاء** · **اختصارات الفترة** · **التجميع الشهري** · فلترة المرفوضات عبر علاقة المقر · المقر المحذوف · خروج الملاحظات من الجدول لصف التفاصيل.

**⚠️ نشر هذه الدفعة:** `migrate --force` (عمود `deleted_at` على `feedback_ratings` و`feedback_suggestions`) + `view:cache`. الأصول مبنية ومرفوعة (`npm run build` اتنفّذ محلياً).

### المتبقّي في هذا الموديول
- **ربط النتائج بصفحة المقر** (`/offices/{id}`) — تاب أو بطاقة بمتوسط تقييم المقر وآخر الآراء. **مؤجَّل بانتظار رأي العميل**؛ هو أعلى الإضافات قيمةً لأنه يضع المعلومة في سياق عمل المفتش بدل موديول منفصل.
- **التصدير (PDF/Excel)** — مؤجَّل عمداً للإصدار الثاني.
- توسيع الوصول لأدوار غير super-admin (صلاحية `feedback.view` + `applyScope`).

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
