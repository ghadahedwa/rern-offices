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

## جداول النظام: الفلاتر والترتيب وعدد الصفوف (بنية مشتركة) ✅

بنية واحدة تخدم أي شاشة جدول جديدة — لا تُكتب فلاتر ولا ترتيب من الصفر.

```
app/Support/TableSort.php                    ← تطبيق الترتيب بقائمة بيضاء (يقرأه FeedbackSort أيضاً)
app/Livewire/Concerns/WithTableSorting.php   ← $sortBy/$sortDir في الرابط + دورة الضغط
app/Livewire/Concerns/WithPerPage.php        ← عدد الصفوف في الرابط (١٥/٢٥/٥٠/١٠٠)
app/Livewire/Concerns/WithDateRange.php      ← فلتر فترة + اختصارات جاهزة + دالتا التطبيق
resources/views/components/filter-bar.blade.php       ← إطار الفلاتر + زر المسح + منتقي الصفوف
resources/views/components/filter-select|filter-input ← عناصر الفلتر بستايل موحّد
resources/views/components/period-shortcuts.blade.php ← أزرار الفترة
resources/views/livewire/partials/sortable-th.blade.php ← رأس عمود قابل للترتيب (مشترك)
```

**الاستعمال:** المكوّن يوفّر `sortableColumns()` (خريطة مفتاح الرابط ← عمود SQL) و`defaultOrder()`
و`resetFilters()` و`hasActiveFilters()`، والقالب:
```blade
<x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()">…</x-filter-bar>
@include('livewire.partials.sortable-th', ['column' => 'name', 'label' => __('home.…')])
```

### ⚠️ قواعد لا تُكسر
- **اسم عمود الترتيب يأتي من الرابط** — لا يُمرَّر لـ`orderBy` إلا بعد خريطة `sortableColumns()`. المفتاح المجهول يسقط إلى **الترتيب الافتراضي للشاشة** لا إلى عمود عشوائي.
- **الترتيب الافتراضي ذو معنى تنظيمي** (المخازن: المستوى ثم المحافظة · الأصناف: القسم ثم ترتيب الصنف)، ولذلك **دورة الضغط ثلاثية**: تصاعدي ← تنازلي ← الافتراضي. بلا الحالة الثالثة يفقده المستخدم بضغطة ولا يستعيده إلا بمسح الرابط. مصدر ترتيب المخازن الواحد: `Warehouse::scopeApplyDefaultOrdering` (و`ordered()` = `withOrderingJoins()` + هي).
- **كل قيمة فلتر تصل من الرابط تُفحَص**: `ctype_digit` للمعرّفات، وقائمة بيضاء لأنواع الحركة، وعدد الصفوف يُحصر في `PER_PAGE_OPTIONS`. القيمة التالفة **تُهمَل ولا تُمرَّر** — تمريرها يُخرج شاشة فارغة بلا سبب ظاهر للمستخدم.
- **لعمود التاريخ دالتان لا واحدة، والخلط بينهما يخفي صفوفاً:**
  - `applyTimestampRange` للحظة مخزَّنة UTC (`warehouse_movements.created_at`) — طرفا اليوم يُحوَّلان من توقيت القاهرة عبر `LocalTime::dayStart/dayAfter`. **حركة الواحدة فجراً بالقاهرة محفوظة في اليوم السابق بـUTC**، وكانت تغيب عن فلتر يومها.
  - `applyDayRange` ليوم كتبه المستخدم (`received_at` · `transferred_at` المصبوبان `'date'`) — بلا تحويل توقيت.
  - وكلاهما **نطاق مفتوح على الأعلى** (`< اليوم التالي`) لا `whereDate` ولا `<=`: الدالة على العمود تمنع الفهرس، و`<=` تُسقط يوم النهاية حين يُخزَّن اليوم كـ`'... 00:00:00'` (يقع على sqlite في الاختبارات).
- **فئات Tailwind لا تُركَّب بالنص** (`lg:grid-cols-{{ $n }}`) — البناء لا يراها. الفئات صريحة في `match` داخل `filter-bar`. وأي تعديل يحتاج `npm run build`.

### ترتيب الأصناف: مصدر واحد
**`Item::statementOrder()`** (وscope `inStatementOrder()` لاستعلامٍ على الأصناف) = **ترتيب الدفتر الورقي**:
القسم (`order` ثم الاسم) ← `items.order` داخل القسم ← الاسم، والأصناف بلا قسم في الآخر.
⚠️ **هو الافتراضي في كل عرضٍ للأصناف** — شاشة الأصناف · الأرصدة (داخل كل مخزن) · تاب أرصدة البروفايل ·
منسدلات الفلترة والإدخال — لأن **البيان المطبوع يجب أن يطابق الدفتر**، والأبجدي وحده يزيح سطوره مع كل صنف
جديد. ومَن أراد الأبجدي يضغط رأس عمود «الصنف».
⚠️ الاستعلام يجب أن يضمّ `item_categories`، و`is_active` **يُؤهَّل بـ`items.`** بعد الضمّ (العمود موجود على
جدول الأقسام أيضاً، فيصير ملتبساً).

### الشاشات المطبَّق عليها (المخازن)
إدارة المخازن · الأصناف · الأرصدة · سجل الحركات · الوارد · النقل — كلها فلاترها في الرابط
(`?q=&wh=&category=&type=&from=&to=&sort=&dir=&per=`) فرابط «الرئيسي + المستديم» صار قابلاً للمشاركة.
الوارد والنقل يعدّان الأصناف بـ`withCount('items')` (لا تحميل البنود لعدّها) والعدد عمود قابل للترتيب.

**شاشة الأرصدة** فيها فوقها: القسم · الوحدة · «أكبر من صفر / صفر» · «تحت الحد الأدنى فقط» · وبحثٌ يشمل **رقم الصنف** (بـ`ArabicDigits` كشاشة الأصناف).
⚠️ **فلتر الحد الأدنى وشارته مشروطان بنوع مخزن الصف نفسه (`level=1`)** لا بالصنف مطلقاً: الحد الأدنى قيمة واحدة للصنف تُقاس على الرئيسي، فصفٌّ في فرعٍ تحت الحد ليس تنبيهاً. نفس القاعدة في الداشبورد وبروفايل المخزن — ثلاثة مواضع تقرأها فلا تُخالف بينها.

**وبروفايل المخزن** (`Manage\Show`) بتاباته الأربعة على النمط نفسه، وفيه ثلاث خصوصيات:
- **التاب نفسه في الرابط** (`?tab=`) ويُفحص في `mount()` لا في `setTab()` وحدها — الرابط لا يتجاوز قاعدة «الوارد للرئيسي فقط».
- **فلاتر موحّدة بين التابات** (`search`/`itemFilter`/`typeFilter`/`dateFrom`/`dateTo`) لا فلتر لكل تاب: تابٌ واحد معروض في كل لحظة، و`setTab()` يمسح الفلاتر والترتيب — سطر البحث في الأرصدة اسم صنف وفي النقل اسم مخزن، وإبقاؤه بين التابين يُخرج شاشة فارغة بلا سبب ظاهر. (اختصر ١١ خاصية إلى خمس.)
- ⚠️ **الصفحة مُرقِّم مسمّى لكل تاب** (`stockPage`…) والـtraits تنادي `resetPage()` بلا اسم — فالمكوّن يعيد تعريف `resetPage()` (بـalias `basePaginationReset`) ليوجّهها لمُرقِّم التاب المعروض. بدونه يُصفَّر مُرقِّم `page` الذي لا يستعمله أحد ويبقى المستخدم على صفحة ٣ من نتيجةٍ صارت صفحة واحدة.
- و`sortableColumns()`/`defaultOrder()` تتفرّعان بالتاب، فعمود ترتيبٍ من تابٍ آخر يصل من الرابط يسقط إلى الافتراضي.
- **فلاتر تاب الأرصدة**: القسم · الوحدة · «أكبر من صفر / صفر» · خانة «تحت الحد الأدنى فقط». ⚠️ الأخيرة **للمخزن الرئيسي وحده** (نفس قاعدة الشارة)، والفحص في الاستعلام لا في القالب فقط — الخانة تصل من الرابط (`?low=1`). ومنسدلتا القسم والوحدة **مقصورتان على أصناف هذا المخزن**: خيارٌ بلا صفوف خلفه يُوهم المستخدم أن الشاشة معطّلة لا أن الصنف غائب.

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
فرع مستقل في السايدبار اسمه **«رأي المواطن»** (`config/branches.php` → مفتاح `feedback`). الشاشات تحت `App\Livewire\FeedbackResults\` وقوالبها في `resources/views/livewire/feedback-results/`.

```php
feedback-results.dashboard     → /feedback-results              (Dashboard)
feedback-results.ratings       → /feedback-results/ratings      (Ratings)
feedback-results.suggestions   → /feedback-results/suggestions  (Suggestions)
feedback-results.rejected      → /feedback-results/rejected     (RejectedAttempts)
```
كلها خلف `middleware('role_or_permission:super-admin|feedback.view')` + فحص ثانٍ في `mount()`.
⚠️ **لا تخلط** `feedback.*` (البوابة العامة بلا auth) مع `feedback-results.*` (الشاشات الإدارية) — أنماط الفرع تطابق الثانية فقط.

### الصلاحيات والنطاق ✅
أربع صلاحيات (`App\Support\FeedbackResults\FeedbackAccess` — المصدر الواحد، وهجرتا `2026_08_20_100000` و`100001`):

| الصلاحية | تفتح | الحارس |
|---|---|---|
| `feedback.view` | اللوحة + التقييمات + المقترحات | `mount()` + middleware الراوت |
| `feedback.export` | Excel + تقارير PDF الثلاثة | `guardExport()` + `guardPdf()` |
| `feedback.delete` | الحذف الجماعي والسلة والاسترجاع | `applyBulk()` |
| `feedback.rejected` | شاشة المحاولات المرفوضة + بطاقتها في اللوحة | `mount()` + `DashboardReport::rejectedSummary()` |

- ⚠️ **`feedback.rejected` مستقلة عن `feedback.view`** (قرار المستخدمة): الشاشة **أمنية لا تقريرية** (سبب الرفض · الـIP · بصمة المتصفح)، فمَن يتابع رضا المواطنين لا يلزمه سجل محاولات البوابة.
- **حارس بطاقة المرفوضات في `DashboardReport` لا في القالب** — اللوحة تُطبع وتُصدَّر من نفس الدالة، فإخفاء البطاقة من الشاشة وحدها كان يُخرج الأرقام في الملف.
- middleware الراوتات مصفاة خشنة (`super-admin|feedback.view|feedback.rejected`) والحارس الفعلي في `mount()` لكل شاشة — لأن المرفوضات تُفتح بصلاحيتها وحدها. ومعها `entries` ثانٍ في الفرع يهبط بصاحبها على شاشتها مباشرة بدل ٤٠٣ على اللوحة.
- كل إجراء داخل شاشة المرفوضات يشترط `feedback.rejected` **فوق** صلاحيته (`canDelete()`/`guardExport()` مُعاد تعريفهما في المكوّن).

- **النطاق محافظة** كالمقرات تماماً (نفس pivot `governorate_user`): صاحب `feedback.view` يرى بيانات محافظاته وحدها، وsuper-admin يرى الكل. التنفيذ في **`FeedbackScope::apply()` وحده** — يمرّ منه كل استعلام (شاشة · تصدير · حذف جماعي)، فلا تُكرَّر الفلترة في مكان آخر.
- ⚠️ **`FeedbackAccess::governorateIds()` ترجع `null` لبلا حدّ و`[]` لبلا رؤية** — الخلط بين المعنيين يفتح كل البيانات لمن لا محافظة له.
- ⚠️ **`feedback_rejected_attempts` بلا عمود محافظة** — النطاق يمرّ عبر علاقة المقر، وصفوفه **بلا مقر** (honeypot/rate_limit قبل اختيار مقر) لا تنتمي لمحافظة فيراها super-admin وحده.
- **قوائم الفلتر مقيَّدة بالنطاق أيضاً** (`governorateOptions`/`officeOptions`) — الأرقام كانت ستُفلتر على أي حال، لكن القائمة نفسها كانت ستسرّب أسماء مقرات محافظة أخرى لمن يعدّل `?gov=` في الرابط.
- ⚠️ **السلة لمن يملك الحذف**: `showTrashed` مربوطة بالـURL، فالقراءة تمرّ بـ`viewingTrash()` لا بالخاصية مباشرة (وكذلك `?trashed=1` في كنترولرات الـPDF).
- **بيانات المواطن تُعرض كاملة** لمن له `feedback.view` (قرار المستخدمة 2026-08-20) — والقفل الافتراضي يبقى على **الملف** المصدَّر لا الشاشة.
- `PermissionGroups::GOVERNORATE_BRANCHES` صارت **قائمة** (المقرات + رأي المواطن) — فدور بـ`feedback.view` وحدها يُظهر منتقي المحافظات في فورم المستخدم. بدونه يُحفظ المستخدم بلا محافظة فيجد الشاشة فارغة أبداً.
- ⚠️ **الأدوار تُنشأ من شاشة الأدوار لا من هجرة** — الهجرة تزرع الصلاحيات الثلاث وتمنحها لـ`super-admin`/`admin` فقط.

### طبقة الاستعلام المشتركة (`app/Support/FeedbackResults/`)
الشاشة مكوّن Livewire والتصدير كنترولر خارجه، ولو بنى كلٌّ منهما استعلامه لخرج الملف بأرقام تخالف الشاشة. فالاستعلام يُبنى مرة واحدة هنا ويقرأه الاثنان:

| الكلاس | الدور |
|---|---|
| `FeedbackFilterSet` | الفلاتر (محافظة/مقر/فترة) ككائن + `apply()` + `describe()` + `fromRequest()` |
| `FeedbackScope` | نطاق رؤية المستخدم — **نقطة التوسيع الوحيدة** للأدوار غير super-admin |
| `RatingsQuery` / `SuggestionsQuery` / `RejectedAttemptsQuery` | استعلام كل شاشة (فلتر + بحث + سلة) + ثابت `SORTABLE` |
| `FeedbackSort` | ترتيب بقائمة بيضاء إلزامية |
| `DashboardReport` | حسابات اللوحة كلها — الشاشة والتقرير يقرآن منه معاً |

- `WithFeedbackFilters` صار يفوّض لـ `FeedbackFilterSet` عبر `filterSet()`؛ الواجهة القديمة (`applyFilters`/`applyDateRange`/`parsedDate`) باقية كما هي.
- `bulkQuery()` في كل شاشة = استدعاء لكلاس الاستعلام — **مصدر واحد لما يُعرض ويُحذف ويُصدَّر**.
- ⚠️ فلترة المحافظات كلها في `FeedbackScope::apply()` وحده (يستدعيه كل كلاس استعلام) — أي توسيع مستقبلي يُكتب **هناك**، وإلا صار الملف المصدَّر يتجاوز نطاق الرؤية.
- جدول `feedback_rejected_attempts` **ليس فيه `governorate_id`** — `RejectedAttemptsQuery` يمرّر لـ `FeedbackFilterSet::apply()` closure يفلتر عبر علاقة المقر.
- القالب المشترك `includes/filters.blade.php` (ومعه اختصارات فترة: هذا الشهر / آخر ٣ شهور / هذه السنة).
- ⚠️ **`applyDateRange()` لا تستخدم `whereDate`** — `DATE(created_at) >= ?` يطبّق دالة على العمود فيمنع MySQL من استخدام الفهرس (تأكّد بـ EXPLAIN: `type=ALL` قبل، `type=range` بعد). النطاق مفتوح على الأعلى (`< اليوم التالي`) ليدخل يوم النهاية كاملاً. الداشبورد تستدعي نفس الدالة لجدول المرفوضات حتى لا يتفرّع المنطق. فهرس `created_at` على الجداول الثلاثة في هجرة `2026_08_02_120000`.
- التاريخ قادم من الـ URL فيُمرَّر على `parsedDate()` — قيمة تالفة تُهمَل ولا تُسقط الصفحة.

### الترتيب
`Concerns\WithFeedbackSorting` + القالب المشترك `livewire.partials.sortable-th` (انتقل من `includes/` ليخدم جداول المخازن معه). المكوّن يوفّر `sortableColumns()` من ثابت `SORTABLE` على كلاس الاستعلام، والتنفيذ في `FeedbackSort::apply()` (وهو نفسه يفوّض لـ`App\Support\TableSort`؛ يبقى فيه ما يخصّ الموديول: السقوط إلى `created_at` والاتجاه الافتراضي تنازلي).
⚠️ اسم العمود يأتي من الـ URL (الشاشة **ورابط الـ PDF** على السواء) — **لا يُمرَّر لـ `orderBy` إلا بعد التحقق من القائمة البيضاء**، وإلا صار حقن SQL عبر الرابط. مغطّى باختبارين.

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

### التصدير (PDF / Excel) ✅
`Concerns\WithFeedbackExport` + القالب `includes/export-bar.blade.php` (زر Excel + زر PDF + خانة «تضمين بيانات المواطن»). المكوّن يوفّر `exportBaseName()` · `excelExport()` · `pdfRouteName()`.

**القاعدة الحاكمة: الملف يخرج من نفس استعلام الشاشة** (`bulkQuery()` / `DashboardReport`) — بنفس الفلتر والبحث والترتيب وحالة السلة. ملف بأرقام تخالف الشاشة أسوأ من غياب التصدير.

- **Excel** يُبنى ويُنزَّل من المكوّن مباشرة (`Excel::download`). **PDF** يُفتح في تبويب جديد عبر كنترولر، والفلاتر تنتقل في الـ **query string** لا في الـ session (بخلاف تقارير المقرات/السيارات) — لأن فلاتر الموديول مربوطة بالـ URL أصلاً، فرابط التقرير صار قابلاً للمشاركة والحفظ.
- ⚠️ **بيانات المواطن (الاسم/الرقم القومي/الهاتف) مقفولة افتراضياً** (`exportPersonal = false`). قرار عرضها كاملة على الشاشة كان عن الشاشة داخل النظام؛ الملف يخرج منه. اللوحة تجميعية بالكامل فلا خانة فيها (`exportHasPersonalData() = false`).
- ⚠️ **حارس `feedback.export` داخل إجراء التصدير وداخل كنترولر الـ PDF** — التصدير يصل في طلب مستقل عن `mount`، والرابط قد يُفتح منسوخاً. التقرير المطبوع **تصدير لا عرض**: ملف يخرج من النظام، فيُفحص بـ`export` لا بـ`view`.
- **سجل مساءلة**: `activity('feedback')` بحدث `export` وخصائص فيها الصيغة والفلتر و**هل خرجت بيانات شخصية**. بلا subject (نفس سبب الحذف الجماعي).
- **سقف ٢٠٠٠ صف للتقرير المطبوع** (`RendersFeedbackPdf::MAX_ROWS`) مع تنبيه في رأس الصفحة — mpdf يبني الشجرة كلها في الذاكرة. مَن يريد الكل يستخدم Excel.
- الترتيب يُطبَّق **في المكوّن/الكنترولر لا في كلاس التصدير** — `FromQuery` يقطّع بـ`skip/take`، وبلا ترتيب ثابت يتكرّر صف ويسقط آخر.
- ملف المقترحات **ورقتان**: صف لكل مقترح (العناوين مجمّعة في خلية)، وورقة «العناوين تفصيلاً» بصف لكل (مقترح × عنوان) — الشكل الوحيد الصالح لجدول محوري.
- ملف اللوحة **ورقة لكل قسم**، وترتيب المقرات فيه **كامل** لا أعلى/أدنى خمسة (`DashboardReport::officesTable()`) — الشاشة تختصر لضيق المساحة، والملف لا يحتمل الاختصار.
- الرسوم في PDF **جداول وأشرطة** لا مخططات: mpdf لا يشغّل JS.
- ⚠️ **ثلاث مِزالق في شريط النسبة، كلها تُخرج عموداً يبدو فارغاً** (وقعت فيها الثلاث تباعاً — التفاصيل في `print/includes/feedback-bar.blade.php`):
  1. **الـ `div` الفارغ ذو `background-color` لا يرسمه mpdf أصلاً** → الشريط مبنيّ على **خلايا جدول**.
  2. **الجدول المتداخل داخل خلية لا تُحسب نسبته المئوية** → المستطيل يخرج بعرض `0.000` (قياساً في أوامر الرسم). لذلك العرض **مطلق بالمليمتر** (بارامتر `width`، ٤٥مم في التقرير)، وخليّة بعرض صفر تُحذف لا تُرسم.
  3. **لون الشريط `inline` لا بـ`class`** → قاعدة تخطيط الصفوف `.rt tbody tr:nth-child(even) td` مُحدِّد **نَسَبي** فتطال خلايا الجدول المتداخل، وأولويتها أعلى من `.bar-on`، فتصبغ الشريط بلون التخطيط: **يظهر صفاً ويختفي صفاً بالتناوب**. (CSS قياسية لا خصوصية mpdf — تقع في المتصفح أيضاً.)
- ⚠️ **اختبار الرسم يقيس أبعاد المستطيل المرسوم ولونه، لا مجرّد وجود اللون** (`PrintBarTest.php`، ويشمل حالة الصف المخطَّط). ثلاثة فخاخ أعطت «نجاحاً» كاذباً: فحص وجود اللون بينما العرض صفر · فحص **التقرير كاملاً** بينما رؤوس الجداول تستخدم نفس الذهبي (لذلك بيئة الاختبار **بلا `<thead>`**) · بيئة اختبار **بلا تخطيط صفوف** فلا تبلغ المِزلقة الثالثة.
- 📌 **القاعدة المستخلصة: أي اختبار انحدار اكسِر الكود عمداً وتأكّد أنه يسقط قبل اعتماده.** الفخاخ الثلاثة مرّت في اختبارات «ناجحة»، والمستخدمة هي التي اكتشفت العطل في كل مرة.
- **رأس العمود يشرح نفسه بلا حاشية** — هذه قاعدة تقرير لا تفضيل تسمية:
  - مسمّيات العدّ صريحة: «عدد الآراء» / «عدد المقترحات» / «عدد المحاولات» — لا «العدد» المجرّد، فالمعدود يختلف من جدول لآخر.
  - عمودا الشريط اسمهما «المتوسط بيانياً» / «النسبة بيانياً» لأنهما **لا يحملان معلومة جديدة** — هما رقم العمود المجاور مرسوماً (مصطلحا «المستوى»/«الحصة» أربكا القارئ).
  - عمود كفاية العينة يحمل الرقم المرجعي في رأسه: «كفاية العينة (٥ فأكثر)» — الحد من `config('feedback.min_ratings_for_ranking')`، ولا يُكتفى بذكره في الحاشية.
  - ⚠️ **mpdf يوزّع أعمدة الجدول حسب المحتوى ويتجاهل نِسَب `width` المعلَنة.** خلية المحور تحوي رقماً واحداً فيضيّقها لأضيق حد، فرأسها ينكسر سطرين فوق عرض نصّي معيّن (بلغ ٦٤.٩pt = ٤ أسطر بالعناوين الكاملة). **مقيسٌ أن كلاً مما يلي لا يمنعه**: توسيع العمود (٥٪/٥.٥٪/٦٪) · تصغير خط الرأس (٧.٥/٧pt) · تقليل الهوامش (٥مم) · `table-layout:fixed` · `keep_table_proportions` · `white-space:nowrap` · فرض عرض بالمليمتر على محتوى الخلية · حذف عمود بيانات المواطن.
  - **الحل: اسم مختصر مقيس** في `FeedbackRating::CRITERIA_SHORT` (السرعة · التعامل · الدور · النظافة · الوضوح · التيسير) و«التقييم العام» → «العام»، **مع بيان أسفل الجدول** بالعناوين الكاملة. ⚠️ الحدّ **عرض نصّي لا عدد حروف**: «التيسير» (٧) يسع بينما «الموظف» (٦) يكسر (ظ+ف عريضتان) — ولذلك «التعامل». **أي تعديل على هذه الكلمات يُقاس، لا يُقدَّر.**
  - ⚠️ **لا تقصّ عنواناً عربياً بـ`mb_substr` لتضييق عمود** — القصّ يقع وسط الكلمة بلا علامة قطع («سرعة إنجاز ا»).
  - ⚠️ **مجموع نِسَب `<th>` = ١٠٠٪ بالضبط** في كل تفريعة (مع/بلا بيانات المواطن) — الناقص يوزّعه mpdf عشوائياً.
  - الحارس: `RatingsPdfLayoutTest.php` **يبني الـPDF فعلاً ويقيس ارتفاع صف الرؤوس** (سطر واحد ≈ ١٨.٥pt، سطران ≈ ٢٨.٧pt). فحص الـHTML وحده لا يكشف الانكسار — الانكسار يقع في التخطيط لا في النص.
- المرفوضات **Excel فقط** — قيمتها تشغيلية لا تقريرية.

```
app/Exports/  FeedbackRatingsExport · FeedbackSuggestionsExport (+Sheet +TopicsSheet)
              FeedbackRejectedExport · FeedbackDashboardExport (+Sheet) · FormatsFeedbackSheet
app/Http/Controllers/  FeedbackDashboardPdfController · FeedbackRatingsPdfController
                       FeedbackSuggestionsPdfController · Concerns\RendersFeedbackPdf
resources/views/print/ feedback-dashboard-pdf · feedback-ratings-pdf · feedback-suggestions-pdf
                       includes/feedback-styles · includes/feedback-header
```
الراوتات داخل مجموعة `feedback-results.` نفسها: `dashboard.pdf` (`/feedback-results/pdf`) · `ratings.pdf` · `suggestions.pdf`.

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
`php artisan test tests/Feature/FeedbackResults` — **١١٢ اختباراً** (`BulkDeleteTest.php` ٢٠ للحذف الجماعي، `ExportTest.php` ٢٠ للتصدير، `PrintBarTest.php` ٤ لرسم أشرطة الـPDF، `RatingsPdfLayoutTest.php` ٦ لتخطيط جدول التقييمات المطبوع، `ScopeTest.php` ٢٨ للصلاحيات ونطاق المحافظات).

**النطاق والصلاحيات** (`ScopeTest.php`): فتح الشاشات الثلاث بـ`feedback.view` وحدها · **حجب المرفوضات وبطاقتها في اللوحة عمّن لا `feedback.rejected` له** · إتاحة الفرع لصاحبها · رؤية التقييمات/المقترحات/المحاولات في محافظاته وحدها · **استبعاد المحاولات بلا مقر** · أرقام اللوحة على نطاقه · صلاحية بلا محافظات = صفر · super-admin يرى الكل · **فلتر `?gov=` لمحافظة ليست له لا يُخرج شيئاً** · قوائم الفلتر لا تسرّب أسماء مقرات خارج النطاق · منع التصدير وراوتات الـPDF الثلاثة بلا `feedback.export` · الملف المصدَّر مقصور على النطاق · منع الحذف بلا `feedback.delete` · **معرّف مدسوس من محافظة أخرى لا يُحذف** ولا يمسّه «حدّد كل المطابق» · `?trashed=1` لا يفتح السلة لمن لا يحذف · `needsGovernorates` لدور رأي المواطن.
📌 كلها كُسِرت عمداً (٨ كسور: تعطيل النطاق · إضعاف الحُرّاس الأربعة · حذف الفرع من `GOVERNORATE_BRANCHES` · رفع قيد قائمة المقرات · رفع حارس بطاقة المرفوضات) وتأكّد سقوطها قبل الاعتماد.

**التصدير** (`ExportTest.php`): منع غير السوبر أدمن من راوتات الـ PDF الثلاثة · منع التصدير بعد سحب الدور والشاشة مفتوحة · **بيانات المواطن غائبة افتراضياً وحاضرة عند تفعيل الخانة** (بقراءة محتوى ملف xlsx فعلياً لا بمجرد نجاح الاستدعاء) · الملف يحترم فلتر المحافظة والبحث العربي المطبَّع · استبعاد المحذوف · تصدير السلة وحدها · التحذير بدل تصدير نطاق فارغ · ورقتا المقترحات · المرفوضات بلا PDF · توليد الـ PDF فعلياً (`%PDF`) · **رفض عمود ترتيب خارج القائمة البيضاء من الرابط** · تجاهل تاريخ تالف وفلتر يصل مصفوفةً · تسجيل النشاط ببيان خروج البيانات الشخصية.

**الحذف الجماعي** (`BulkDeleteTest.php`): السلة والاسترجاع والحذف النهائي مع صفوف الربط · بقاء منع التكرار بعد الحذف · خروج المحذوف من متوسطات الداشبورد · احترام الفلتر في «حدّد كل المطابق» · تجاهل معرّف مدسوس · تصفير التحديد · حذف المرفوضات نهائياً · تسجيل النشاط.
والباقي: منع غير السوبر أدمن من الشاشات الأربع · متوسط المحور الاختياري · حد العينة (للمقرات وللمحافظات) · فلاتر الفترة/المحافظة · **شمول يومَي طرفَي الفترة** · **تجاهل تاريخ تالف من الرابط** · تصفير المقر عند تغيير المحافظة · عدّ عناوين المقترحات مع الفلتر · البحث العربي المطبَّع **وداخل النصوص الحرة وعناوين الكتالوج** · **الترتيب وعكسه ورفض عمود خارج القائمة البيضاء** · **اختصارات الفترة** · **التجميع الشهري** · فلترة المرفوضات عبر علاقة المقر · المقر المحذوف · خروج الملاحظات من الجدول لصف التفاصيل.

**⚠️ نشر دفعة الحذف الجماعي:** `migrate --force` (عمود `deleted_at` على `feedback_ratings` و`feedback_suggestions`) + `view:cache`.
**⚠️ نشر دفعة التصدير:** لا هجرات — `route:cache` (راوتات الـ PDF الثلاثة) + `view:cache` + `config:cache`. الأصول مبنية ومرفوعة (`npm run build` اتنفّذ محلياً).
يحتاج السيرفر مجلد `storage/mpdf` قابلاً للكتابة (موجود أصلاً لتقارير المقرات).

### المتبقّي في هذا الموديول
- **ربط النتائج بصفحة المقر** (`/offices/{id}`) — تاب أو بطاقة بمتوسط تقييم المقر وآخر الآراء. **مؤجَّل بانتظار رأي العميل**؛ هو أعلى الإضافات قيمةً لأنه يضع المعلومة في سياق عمل المفتش بدل موديول منفصل.
- **إنشاء الدور نفسه من شاشة الأدوار** ثم ربط مستخدميه بمحافظاتهم — الصلاحيات مزروعة والدور لم يُنشأ بعد.

**⚠️ نشر دفعة الصلاحيات والنطاق:** `migrate --force` (زرع الصلاحيات الثلاث) + `config:cache` (تعريف الفرع) + `route:cache` (middleware الراوتات) + `view:cache`.

---

---

## موديول المراسلات 🔨 **قيد البناء — المرحلة أ منجزة**

**المرجع الواحد للبناء: [`documentation/correspondence-system.md`](documentation/correspondence-system.md)** — كل قرار وكل قاعدة وأثر كسرها مكتوب فيه. لا تبنِ من `plan-correspondence.md` (سجل اشتقاق).

### المُنجَز في الكود
```
app/Livewire/Correspondence/
  Entities/{Index,Create}.php        ← أطراف المراسلات (CRUD بنمط OfficeTypes)
  {Inbox,Outbox,Drafts,Assignments,Delegations}.php   ← سقالة بحراسة حقيقية
  Concerns/IsPlaceholderScreen.php   ← trait السقالة
  MenuCounters.php                   ← عدّادات المنيو (wire:poll.300s)
app/Support/
  CorrespondenceCounters.php         ← singleton memoized — المصدر الواحد للأرقام
  PermissionGroups.php               ← تجميع الصلاحيات: تقرأه شبكة الأدوار وفورم المستخدم
app/Models/CorrespondenceEntity.php
database/seeders/CorrespondenceRolesSeeder.php        ← ٣ أدوار
database/seeders/CorrespondenceDemoUsersSeeder.php    ← ٥ حسابات · يرفض production
```
- **الفرع** `correspondence` في `config/branches.php` · دخوله `correspondence.inbox`.
- **أطراف المراسلات تسكن فرع «إدارة النظام»** (`correspondence.settings`) كباقي القوائم المرجعية.
- **عمودان على `users`**: `correspondence_entity_id` (النطاق) + `job_title` (يُطبع في ختم الاعتماد).
- حسابات فحص محلية: `samir` `khaled` `mohamed` `yasser` `heba` — السر `1234`.

### ⚠️ قواعد لا تُكسر
- **المسمّى الوظيفي ليس الدور** — خالد وياسر نفس الدور بمسمّيين مختلفين.
- **مفتاح الأقسام المشروطة عنوان التصاريح لا بادئة الصلاحية** — البادئة تخطئ في اتجاهين: تفوّت دور `Vehicles` (يحتاج المحافظات بلا `offices.`) وتصطاد `offices.settings` خطأً (تحت إدارة النظام بلا نطاق).
- **super-admin: التجاوز يعطي صلاحية ولا يعطي طرفاً** — يرى كل الجهات ولا يُنشئ ولا يعتمد، فالرقم يُسحب من دفتر طرف و`from_entity_id = NULL` يفسد مفتاح الترقيم.
- **الرمز تابع صاحب الدفتر لا المكاتبة** — المكاتبة الواحدة برمزين (صادر الراسل · وارد المستلم).
- **لا بريد إلكتروني إطلاقاً** — التنبيه داخل النظام: عدّاد ثم جرس (بعد المرحلة د).
- **⚠️ أي `route()` في الـlayout يُحاط بحارس `Route::has`** — الظرف أوقع النظام بـ500 على **كل الصفحات** بـcache راوتات قديم.
- **الأدوار تُنشأ من الشاشة لا من الهجرات** — الكود لا يفحص اسم دور إلا `super-admin`.

### المتبقّي
1. **أ-٢ أنواع المكاتبات** — آخر قائمة مرجعية، نفس نمط الأطراف، بلا صلاحية جديدة. **التالي.**
2. **جدول `correspondences`** ⛔ **موقوف على س٦**: التسلسل واحد للجهة أم لكل نوع دفتره؟ (يحدّد الـ`UNIQUE`)، ومعه: هل الرقم عدد صحيح دائماً أم فيه «١٤١ مكرر»؟ — **صورة صفحة من دفتر الصادر تحسم الاثنين**.
3. مؤجَّل: المشاركة (قرار العميل) · الجرس · قفل رمز الطرف · دين الأدوار القديم (`Boss` فاضي · صلاحيات ميتة مسنَدة · أسماء إنجليزية).

### الاختبارات
`php artisan test tests/Feature/Correspondence` — **٤٩ اختباراً** (`EntitiesTest` · `UserScopeFieldsTest` · `RolesSeederTest` · `BranchScaffoldTest`) + `tests/Feature/BranchContextTest.php`.
📌 كلها كُسِرت عمداً وتأكّد سقوطها قبل الاعتماد — انظر قاعدة الكسر في `.claude` memory.

---

## موديول مدخلي البيانات 🔨 **قيد البناء — المرحلة (أ) منجزة**

**المرجع الواحد للبناء: [`documentation/data-entry-system.md`](documentation/data-entry-system.md)** — كل قرار وقاعدة وأثر كسرها مكتوب فيه.

طلب عميل (2026-09-04، ومراجعته 2026-09-05): الشهر العقاري متعاقد مع شركة توفّر **مدخلي بيانات** موزّعين على المقرات. المطلوب موديول **مستقل عن المقرات**: المفتش يسجّل أسماءهم وهواتفهم لكل مقر، ثم يتابع **حضورهم وغيابهم وإجازاتهم** (يعرفها بمكالمة مع رئيس الفرع يومياً أو أسبوعياً)، ويطلع تقارير أسبوعية/شهرية/سنوية للفرد أو المقر أو المحافظة أو الجمهورية.

### قرارات العميلة — لا تُراجَع بلا سؤالها
- **أقلّ داتا ممكنة**: يُسجَّل **الغياب والإجازات فقط**، و**كل يوم عملٍ بلا سجلّ حضورٌ بالاشتقاق**. الكشف يأتي من المكتب شهرياً لكل مدخل على حدة.
- النطاق **محافظة** (نفس pivot `governorate_user`)، ولا عمود مقر على `users`.
- ⚠️ **قرار «المفتش وحده يسجّل» أُلغي (2026-09-05)**: مَن مُنح `data-entry.attendance` يسجّل — مفتشاً كان أو **حساب شركة** بصلاحيات `data-entry.*` وحدها ومحافظاته من فورم المستخدم (بلا كود جديد). و**لا كيان «شركات»**: الأدمن ينشئ الحسابات ويحدّد نطاقها.
- البادئة **`data-entry.*`** والفرع اسمه «مدخلو البيانات».
- **التقرير عدٌّ لا نسبة**: «حضر ٢٠ · غاب ٢ · إجازة ٤». ولذلك **لا عمود «طبيعة الحالة»** على جدول الحالات — وأي نسبة مئوية لاحقاً تستلزم عموداً يحدّد أي الحالات تدخل المقام، وهو سؤال يُطرح عليها وقتها لا يُفترض.
- **التأخير والانصراف المبكر مؤجَّلان** («مش مهم دلوقتي») — ولا أعمدة وقت. وحين يُطلبان يُضاف صفّ «حضر متأخر» من شاشة الحالات **بلا تعديل كود**.

### الصلاحيات (سبع) والنطاق
| الصلاحية | تفتح | ملاحظة |
|---|---|---|
| `data-entry.index` | قائمة المدخلين + التقارير + دخول الفرع | |
| `data-entry.attendance` | تسجيل الحضور | **مفصولة عن `edit` عمداً**: مَن يسجّل يومياً ليس بالضرورة مَن يعدّل الأسماء والهواتف |
| `data-entry.create` / `.edit` / `.delete` | إدارة بيانات المدخلين | لا تفتح الفرع وحدها |
| `data-entry.export` | Excel + PDF | تحرس الملف لا الشاشة |
| `data-entry.settings` | **حالات الحضور** (تحت «إدارة النظام») | بلا نطاق محافظات |

- ⚠️ **لا صلاحية لمستوى التقرير** (فرع/محافظة/جمهورية) — النطاق يتكفّل به: مَن له محافظتان يجد «الجمهورية» محافظتيه.
- ⚠️ العنوان في **`PermissionGroups::GOVERNORATE_BRANCHES`** — بدونه يُحفظ المستخدم بلا محافظة فيجد الشاشة فارغة أبداً.
- ⚠️ و`data-entry.settings` **مستثناة** من بادئة الفرع (`except`) لأنها تحت «إدارة النظام» — ولولا الاستثناء لطالبت مديرَ القوائم المرجعية بمحافظاتٍ لا معنى لها.
- **الأدوار تُنشأ من شاشة الأدوار لا من هجرة** — الهجرة تزرع الصلاحيات وتمنحها لـ`super-admin`/`admin` فقط.

### الشاشات
```
app/Livewire/DataEntry/
  Operators.php · Attendance.php · Reports.php   ← سقالة بحراسة حقيقية
  Concerns/IsPlaceholderScreen.php
  Statuses/{Index,Create}.php                    ← حالات الحضور (قائمة مرجعية) ✅
app/Models/AttendanceStatus.php
resources/views/livewire/data-entry/{placeholder,statuses/*}.blade.php
```
راوتات الفرع `data-entry.{index,attendance,reports}`، وشاشة الحالات `attendance-statuses.*` **داخل فرع إدارة النظام** (نمط الراوت مختلف عن نمط الفرع فلا يلتبسان).
- صاحب `attendance` وحده **يهبط على شاشة الحضور مباشرة** (`entries` في الفرع) لا على قائمةٍ تردّه ٤٠٣.

### جدول `attendance_statuses`
`name` (فريد) · `color` (سداسي، **inline** لا فئة Tailwind) · `order` · `is_active` · `is_system`.
مزروع: حاضر · غائب · إجازة (الثلاثة `is_system`).
- ⚠️ **الأساسية لا تُحذف ولا تُعطَّل** — حذف «حاضر» يترك المفتش بلا حالةٍ يسجّل بها، والحارسان **في الإجراء لا في القالب** (الزرّ مخفيّ، والنداء يصل بلا زرّ).
- ⚠️ **`AttendanceStatus::isInUse()` تفحص وجود جدول `attendance_days` أولاً** — فحارس «الحالة المستعملة» يصير حقيقياً **لحظة إنشاء الجدول** بلا تعديلٍ يُنسى.
- المعطَّلة تختفي من شاشة التسجيل (`scopeSelectable`) وتبقى في السجلات القديمة.

### الجداول وحاسبة أيام العمل ✅ **المرحلة (أ)**
```
official_holidays        name · starts_on · ends_on            ← العطلات الرسمية (قومية)
data_entry_operators     name · phone · notes                  ← بلا مقر ولا محافظة ولا شركة
data_entry_assignments   operator_id · office_id · started_on · ended_on · end_reason
attendance_days          attendable_* · date · status_id · recorded_by   ← استثناءات فقط
app/Support/WorkingDays.php                                    ← المصدر الواحد للمعادلة
```
```
أيام العمل  = أيام المدى − الجُمَع − العطلات الرسمية
أيام الحضور = أيام العمل − الغياب − الإجازات
```
- ⚠️ **العطلة الواقعة يوم جمعة لا تُخصم مرتين** — الدولة تُرحّل العطلة وقد تقع على جمعةٍ مخصومةٍ أصلاً. الجمعة تُستبعد أولاً ثم العطلات.
- ⚠️ **الحساب لحظي لا مخزَّن** — عطلةٌ تُضاف متأخرة تصحّح تقارير الشهر الماضي معها. وهو المقصود: **شاشة العطلات مفتوحة** (تُضاف وتُعدَّل في أي وقت؛ الترحيل = تعديل تاريخ) لأن القرار يصل بعد وقوعه، وزرّ زرع السنة اختياريّ.
- ⚠️ **الحساب مقصور على مدة الخدمة** (من `data_entry_assignments`): مَن التحق يوم ١٥ لا يُحسب حاضراً من يوم ١.
- ⚠️ **لا يُحتسب استثناءٌ وقع في يوم غير عامل** (جمعة · عطلة · خارج الخدمة) — اليوم مخصوم أصلاً، فاحتسابه غياباً يخصمه مرة ثانية. يقع فعلاً حين تُضاف عطلةٌ **بعد** تسجيل ذلك اليوم.
- ⚠️ **التسكين تاريخيّ لا عمود مقر واحد**: تقرير مقرٍّ عن فترة ماضية ينسب أيامها إلى مقر المدخل **وقتها**. والجدول يستوعب الالتحاق والنقل وإنهاء الخدمة معاً، و**لا حذف لمدخل بل أرشفة** (إغلاق التسكين).
- ⚠️ **تداخل مدد التسكين لا يُعبَّر عنه في المحرّك** — حارسه `DataEntryAssignment::overlapsExisting()` قبل كل حفظ.
- ⚠️ **CarbonImmutable**: حلقات الأيام تُكتب `$day = $day->addDay()` — الصيغة المعتادة تُنتج حلقة لا نهائية.
- ⚠️ **شاشة العطلات للسوبر أدمن وحده** (قرار العميل) — أضيق من `data-entry.settings` عمداً: القائمة قومية، وعطلةٌ خاطئة تغيّر تقارير الجمهورية كلها.
- **خيار «الرقم الشهري»** (إدخال «أيام العمل في سبتمبر ٢٥» بدل شاشة عطلات) نوقش و**رُفض**: النظام لا يعرف حينها أي يومٍ هو العطلة، فإجازةٌ تُسجَّل فيه تُخصم مرة ثانية بلا وسيلة اكتشاف.

### شاشة العطلات الرسمية ✅ **(ب-١)**
`App\Livewire\OfficialHolidays\{Index,Create}` · راوتات `official-holidays.*` **خلف `role:super-admin`** داخل فرع إدارة النظام · بندها في السايدبار تحت «إعدادات مدخلي البيانات».
- **الشاشة مفتوحة**: إضافة وتعديل وحذف في أي وقت، والترحيل تعديلُ تاريخٍ لا صفٌّ جديد.
- زرّ **زرع العطلات الثابتة** (٧ ميلادية، بلا شمّ النسيم لأنه يتبع القيامة القبطي) يزرع في **السنة المعروضة في الفلتر** ويتجاهل المزروع فلا يُكرِّر.
- ⚠️ **حارس الإضافة بأثر رجعي**: عطلةٌ تُضاف على يومٍ فيه غياب/إجازة مسجَّل تعرض العدد ولا تُحفظ إلا بموافقة تحذف تلك السجلات — وإلا خُصم اليوم مرتين. والحارسان (عرض المودال · شرط `showConflict` في التأكيد) **في الإجراء لا في القالب**.
- ⚠️ سنة الفلتر تصل من الرابط فتُفحَص بـ`ctype_digit`، و**استخراج السنة يتفرّع بالمحرّك** (`YEAR()` لـMySQL و`strftime` لـsqlite).

### شاشة المدخلين ✅ **(ب-٢)**
`App\Livewire\DataEntry\Operators\{Index,Create}` · راوتات `data-entry.index` (القائمة) و`data-entry.operators.{create,edit}`.
- **النطاق في `App\Support\DataEntryScope`** — نقطة التوسيع الوحيدة (شاشة · منسدلة · تقرير · تسجيل · تصدير)، و**ثلاث حالات**: `null` بلا حدّ · `[]` لا يرى شيئاً · `[...]` محافظاته. النطاق عبر **مقر التسكين** لا عمود محافظة.
- ⚠️ **كل معرّف من العميل يُقرأ عبر النطاق** (`scopedOperator`/`allowsOffice`) لا بـ`findOrFail` — وإلا نُقل مدخلُ محافظةٍ أخرى بمعرّفٍ مدسوس. والمنسدلات مفلترة كالصفوف.
- **النقل يُغلق التسكين السابق في اليوم السابق** ثم يفتح الجديد — فلا يومَ بمقرّين (تقرير المقر هو الذي كان سيعدّه مرتين، لا عدّ أيام المدخل).
- **الفلاتر والترتيب على البنية المشتركة** (`x-filter-bar` + `WithTableSorting` + `WithPerPage`) — صفٌّ واحد بأربعة فلاتر، وترتيب بقائمة بيضاء (الاسم · الهاتف · تاريخ الالتحاق كعمود محسوب).
- ⚠️ **اسم المقر في المنسدلة مقصوص** بـ`ArabicText::shorten` والكامل في `title` — الاسم يبلغ ١٣٦ حرفاً والمنسدلة تتّسع لأطوله فيخرج جزؤها عن الشاشة.
- **مودالا النقل وإعادة التسكين بمنتقي محافظة** يحصر المقرات، يفتحان على محافظة المقر الحالي، وتغييرها يُصفّر المقر — وقائمتاهما مستقلتان عن فلتر الشاشة.
- **إعادة التسكين** على الصفّ المؤرشَف وحده (عودة المدخل · تصحيح إنهاءٍ بالخطأ) — **تسكين جديد لا فتحٌ للقديم**، وتاريخه يتجاوز نهاية آخر تسكين قطعاً فلا تتداخل المدد.
- **تعديل البيانات لا يمسّ التسكين** (التغيير من زرّي «نقل» و«إنهاء خدمة»)، و**الحذف لتصحيح إدخال خاطئ فقط**: مَن له سجل حضور تُنهى خدمته ولا يُحذف.

### استيراد دفعة من Excel ✅ **(ب-٣)**
`App\Livewire\DataEntry\Operators\Import` + `App\Support\DataEntry\{OperatorsTemplate,OperatorsImport}` · راوت `data-entry.operators.import` بصلاحية `data-entry.create`.
- **قالب لكل محافظة يُبنى لحظة التنزيل**: الاسم · التليفون · قائمة منسدلة بمقرات المحافظة.
- ⚠️ **عمود التليفون `FORMAT_TEXT`** — Excel يقرأ `01012345678` رقماً فيسقط الصفر الأول، والتنسيق يُطبَّق **قبل** الكتابة.
- ⚠️ **قائمة المقرات في ورقة مخفية بنطاق مسمّى** لا قائمةً مضمّنة في التحقق: المضمّنة محدودة بـ٢٥٥ حرفاً واسم المقر الواحد يبلغ ١٣٦.
- **قراءة ← عرض (جاهز/مكرَّر/خطأ) ← حفظ بموافقة**، واسم المقر يُطابَق بالتطبيع داخل مقرات المحافظة وحدها، والمكرَّر يُتجاوَز.
- ⚠️ **المقر يُعاد فحصه على النطاق قبل الحفظ** — الصفوف تعيش في حالة المكوّن بين طلبين.
- ⚠️ **`disconnectWorksheets()` بعد كل بناء وقراءة** (مراجع PhpSpreadsheet الدائرية)، ومعها `memory_limit=512M` في `phpunit.xml`: توليد xlsx في حزمة اختبارات واحدة يتجاوز ١٢٨م.

### المتبقّي
1. **(ج)** شاشة التسجيل: محافظة ← مدخل ← نتيجة شهر. **التالي.**
2. تفاصيل شاشة التسجيل: تُعلَّم فيها أيام الغياب/الإجازة، والجمعة والعطلة مُعطَّلتان، وشريط «أيام العمل ٢٥». (حلّت محلّ شبكة «أسبوع × مدخلين» بعد كلام العميل.)
3. **(د)** التقارير الثلاثة بمدى حرّ (مدخل · مقر · محافظة أو أكثر) + البيان العددي + التصدير.
4. **(هـ)** لوحة الفرع: التوزيع والأعداد ورسم الجمهورية، **بلا سجل نشاط المستخدمين**.
5. أسئلة مفتوحة: أيام العمل من `offices.working_days` لو اختلف مقرٌّ عن مقر؟ · **اليوم غير المسجَّل ليس غياباً** (تمييزه في العرض).

### الاختبارات
`php artisan test tests/Feature/DataEntry` — **١١٥ اختباراً** (`BranchScaffoldTest` ١٢ · `AttendanceStatusesTest` ١٥ · `WorkingDaysTest` ٢٠ · `OfficialHolidaysTest` ١٧ · `OperatorsTest` ٣٨ · `OperatorsImportTest` ١٣).
📌 كُسِرت حراساتها عمداً وتأكّد سقوطها قبل الاعتماد — الست الأولى (حارس الأساسية · شرط التأكيد المسبق · تعطيل الأساسية · `except` · تطبيع البحث · حارس الصلاحية)، وست المرحلة (أ) (خصم الجمعة · العطلة الواقعة في جمعة · حصر مدة الخدمة · تجاهل الاستثناء في يوم غير عامل · امتداد التسكين المفتوح · قيد `UNIQUE`).

**⚠️ النشر:** الأربعة كاملة — الهجرات (٣ سابقة + ٤ جديدة) + فرع في config + ١٢ راوتاً **ينادي السايدبار إحداها** + `npm run build` (منفَّذ محلياً، و`git add public/build` صراحةً).

---

## أوامر السيرفر المعلقة
بعد كل `git push` يجب تذكير المستخدم بتشغيل على السيرفر:
```bash
cd /var/www/rern-offices && git pull
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
⚠️ **الأربعة دائماً — لا تُختصر بحسب نوع الدفعة.** 2026-08-19: أُبلغت المستخدمة أن دفعة ملف لغة تحتاج `view:cache` وحده (صحيح لها)، فصار ذلك ما نُفِّذ في الدفعة التالية — التي أضافت راوتات وفرعاً في config. النتيجة: **٥٠٠ على كل الصفحات**.
📌 **تكلفة أمر cache زائد صفر، وتكلفة أمر ناقص النظام واقف.**
📌 **للتشخيص:** `grep -a ERROR storage/logs/laravel.log | tail -3 | cut -c1-500` — الرسالة في أول السطر، و`tail` وحده يعطي الـstack trace بلا الرسالة.
📌 **وللتحقق:** قارن `ls -l bootstrap/cache/` بـ`git log -1 --format=%cd` — cache أقدم من آخر commit يعني نشراً ناقصاً.
`composer install --no-dev` عند إضافة حزم فقط · `npm run build` يُنفَّذ **محلياً** و`public/build` متتبَّع في الريبو.

---

## ملاحظات مهمة
- `debt_amount` موجود في `$fillable` لكن غير مستخدم في الواجهة بعد
- `avg_daily_transactions` نُقل من step1 إلى صفحة الإحصائيات (معلّق في step1)
- `parent_office_id` موجود في الموديل لكن مخفي من الـ wizard حالياً
- `claims.blade.php` موجود لكن غير مفعّل في تابز الإحصائيات
- تحقق step3 مُعلَّق حالياً في `nextStep()`
