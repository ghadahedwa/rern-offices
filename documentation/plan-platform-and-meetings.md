# خطة: تحويل المشروع لمنظومة قطاع الشهر العقاري والتوثيق (فروع) + فرع الاجتماعات

> الحالة: خطة للمراجعة قبل التنفيذ. تاريخ: 2026-07-11.

## المفهوم الصحيح لـ «الفرع»
**الفرع = منظومة كاملة ليها قائمتها (sidebar) الخاصة ولوحة تحكم خاصة** — مش موديول واحد.
- كل اللي اتبنى لحد دلوقتي (المقرات + السيارات + المطالبات + التقارير + دليل الهاتف + المحافظات)
  = **فرع واحد اسمه «إدارة المقرات»**.
- **الاجتماعات** = فرع تاني مستقل.
- **إدارة النظام** (المستخدمين + الأدوار + إعدادات البرنامج / القوائم المرجعية) = تتفصل كفرع/منطقة مستقلة.

الفروع مجرد **تجميع للتنقّل والتنظيم** — مفيش تغيير في نظام الصلاحيات/المستويات (يفضل عام كما هو).
تطبيق Laravel واحد + قاعدة بيانات واحدة + سيرفر واحد (modular monolith).

## توزيع البنود على الفروع
| الفرع | البنود (sidebar خاص بيه، أول بند = داشبورد الفرع) |
|-------|--------------------------------------------------|
| **🏢 إدارة المقرات** | داشبورد المقرات، المقرات، السيارات، المطالبات، دليل الهاتف، التقارير، المحافظات |
| **📅 الاجتماعات** | داشبورد الاجتماعات، أجندة الاجتماعات |
| **⚙️ إدارة النظام** | المستخدمين، الأدوار، إعدادات البرنامج (كل الـ 19 قائمة مرجعية) |

## طريقة الدخول/التنقل بين الفروع — مبدّل فرع في أعلى القائمة (Workspace Switcher)
مش صفحة بوابة كروت (أسلوب قديم). بدلها زر أعلى الـ sidebar يوضّح الفرع الحالي، وبضغطة يفتح
قائمة الفروع المتاحة — نمط Slack/Notion.
- بعد اللوجين → الدخول مباشرة على **آخر فرع** (أو الافتراضي)، على داشبورده — بدون صفحة وسيطة.
- المبدّل يعرض **بس الفروع اللي للمستخدم صلاحية عليها**. لو فرع واحد بس → المبدّل يختفي.
- **في أي لحظة الـ sidebar يعرض بنود فرع واحد بس** (حتى للسوبر أدمن) — يتحدد من الـ route prefix.
  ده اللي بيحل مشكلة القائمة الطويلة: مفيش حد يشوف كل البنود مرة واحدة.

## نظام الصلاحيات مع الفروع (مؤكَّد)
نفضل بنظام Spatie الحالي — **مفيش صلاحية جديدة تتخزّن للفرع**.
- **دخول الفرع = مُشتَق تلقائياً**: لو الدور عنده أي صلاحية جوا الفرع → الفرع يظهر له في المبدّل
  والـ sidebar. (يمنع تناقض: صلاحية تفعيل بدون رؤية الفرع.) super-admin يشوف كل الفروع.
- **شاشة الأدوار** (`permissions-grid.blade.php`): الصلاحيات المسطّحة الحالية تتجمّع في
  **أقسام قابلة للطي حسب الفرع**، كل قسم فوقه زر **«تحديد كل الفرع»**.
  - زر «تحديد كل الفرع» = **اختصار واجهة فقط** (يحدّد صلاحيات الفرع كلها بضغطة)،
    **مش** صلاحية تتخزّن لوحدها.
  - القسم المطوي مايظهرش صلاحياته لحد ما يُفتَح = تجربة «اختَر الفرع فتظهر صلاحياته».
- خريطة تجميع الصلاحيات:
  - 🏢 إدارة المقرات (تشغيلي): `offices.*` + `vehicles.*` + `governorates.*` + `claims.*`
    + `offices.phone-directory` + صلاحيات التقارير.
  - 📅 الاجتماعات: `meetings.*`.
  - ⚙️ إدارة النظام (تهيئة): `users.manage` (المستخدمين) + `roles` (super-admin فقط، حساس)
    + `offices.settings` (القوائم المرجعية للمقرات والسيارات — تحت عنوان «إعدادات المقرات»).
- **قرار:** القوائم المرجعية (بيانات تعريف/تهيئة يديرها الأدمن) مكانها فرع **إدارة النظام** مجمّعة
  بعناوين حسب الدومين، مش فرع المقرات (اللي يفضل تشغيلي نضيف). أي فرع جديد يضيف إعداداته هنا كقسم.
- **bypass مركزي:** `Gate::before` يخلّي super-admin يتجاوز كل الصلاحيات (بدل الفحص اليدوي المتكرر).

---

## الـ Conventions لكل فرع/موديول جديد (بديل الـ prefix القديم co_ / pg_)
| الطبقة | القاعدة | مثال (الاجتماعات) |
|--------|---------|-------------------|
| الجداول | prefix باسم الموديول | `meetings`, `meeting_*` |
| الصلاحيات | `<module>.<action>` | `meetings.index/view/create/edit/delete` |
| الكود | مجلد/namespace مستقل | `app/Livewire/Meetings/` |
| الـ views | مجلد مستقل | `resources/views/livewire/meetings/` |
| الـ routes | prefix + `.index/.create/...` | `meetings.index` |
| اللغة | مفاتيح في `lang/ar/home.php` | `home.meeting_*` |

**مش محتاجين package** (زي nwidart/laravel-modules) — الـ convention أخف وأنضف.

---

## خطة التنفيذ

### المرحلة أ — تقسيم الـ sidebar لفروع + المبدّل (بنية المنظومة)
1. تعريف الفروع (اسم، أيقونة، شرط الصلاحية، مجموعة route prefixes) — مصدر واحد (helper/config).
   - إدارة المقرات: `dashboard`, `offices.*`, `vehicles.*`, `claims.*`, `reports.*`,
     `offices.phone-directory`, `governorates.*`.
   - الاجتماعات: `meetings.*`.
   - إدارة النظام: `users.*`, `roles.*`, وكل قوائم الإعدادات (`office-types.*` ... `vehicle-device-types.*`).
2. `resources/views/layouts/app/sidebar.blade.php`: يحدد الفرع الحالي من الـ route، ويرندر مجموعة
   بنوده فقط + مبدّل الفرع أعلى القائمة.
3. تحديد الفرع الافتراضي بعد اللوجين (أعلى فرع متاح للمستخدم).

### المرحلة ب — فرع الاجتماعات (أول تطبيق للفكرة)
**الحقول** (مقر الاجتماع + المعني = **نص حر**، أجندة مركزية بدون `governorate_id`):
`date`(إلزامي)، `time`(إلزامي)، `subject`(إلزامي)، `location`(nullable)،
`concerned_party`(nullable)، `result`(text nullable)، `notes`(text nullable)، timestamps.

**الملفات:**
1. Migration `create_meetings_table`.
2. Migration `seed_meeting_permissions` (`meetings.index/view/create/edit/delete`) —
   نمط `2026_04_28_000003_seed_office_permissions.php`.
3. Migration `assign_meeting_permissions_to_roles` —
   نمط `2026_05_03_100000_assign_office_permissions_to_roles.php`.
4. Model `app/Models/Meeting.php` — fillable + trait `LogsActivity` (زي Office).
5. Livewire:
   - `Meetings/Index.php` — جدول + بحث (بالكلمة/التاريخ/الموضوع) + عرض/تعديل/حذف/إضافة، صلاحيات في `mount()`.
   - `Meetings/Create.php` — create + edit (نفس المكوّن، `$isEditing`).
   - `Meetings/Dashboard.php` — داشبورد الفرع (KPIs بسيطة: عدد الاجتماعات، القادمة، آخر النشاط).
6. Views بنفس ستايل المقرات (`max-w-4xl mx-auto p-6 space-y-6` + الهيدر + section header + input classes).
   **إلزامي:** keepalive في صفحة الإدخال.
7. Routes:
   ```php
   Route::livewire('meetings', \App\Livewire\Meetings\Index::class)->name('meetings.index');
   Route::livewire('meetings/create', \App\Livewire\Meetings\Create::class)->name('meetings.create');
   Route::livewire('meetings/{meeting}', \App\Livewire\Meetings\Show::class)->name('meetings.show');
   Route::livewire('meetings/{meeting}/edit', \App\Livewire\Meetings\Create::class)->name('meetings.edit');
   ```
8. مفاتيح اللغة في `lang/ar/home.php`: `meetings_title`, `meeting_date`, `meeting_time`,
   `meeting_subject`, `meeting_location`, `meeting_concerned_party`, `meeting_result`,
   `meeting_notes`, `add_meeting` ...
9. إضافة مجموعة الاجتماعات في `resources/views/livewire/roles/partials/permissions-grid.blade.php`.

### المرحلة ج — التسمية
تغيير اسم البرنامج لـ «قطاع الشهر العقاري والتوثيق» (`config/app.php` + مفاتيح اللغة + الشعار).

---

## أوامر السيرفر بعد كل push
```bash
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
