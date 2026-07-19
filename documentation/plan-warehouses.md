# خطة: فرع «المخازن» (إدارة مخزون الأصناف)

> الحالة: خطة للمراجعة قبل التنفيذ. تاريخ: 2026-07-15.
> المصدر: `documentation/المخازن.docx`.

## الفكرة
موديول/فرع كامل لإدارة مخزون الأصناف عبر شبكة مخازن (رئيسي / إقليمي / فرعي):
إدخال أرصدة افتتاحية، تسجيل الوارد (للمخزن الرئيسي فقط)، نقل الأصناف بين المخازن،
سجل حركات كامل (وارد/صرف + الرصيد قبل/بعد)، ومجموعة تقارير — مع مرفقات (صور/PDF)
لمستندات الاستلام وأذون الصرف.

**فرع جديد** بنفس نمط الاجتماعات (`config/branches.php` + sidebar خاص + داشبورد).

---

## القرارات المؤكَّدة (من النقاش)
| البند | القرار |
|-------|--------|
| **نوع المخزن** | جدول مرجعي قابل للإدارة `warehouse_types` — **مع عمود `level`** (رئيسي=1، إقليمي=2، فرعي=3). قابل لإضافة أنواع جديدة مستقبلاً (كل نوع جديد يأخذ level ويلتزم بالقاعدة تلقائياً). نفس نمط `roles.level`. |
| **الحد الأدنى للمخزون** | **مطلوب** — عمود `min_stock` لكل صنف، يُفحَص على **المخزن الرئيسي فقط**. تنبيه في الداشبورد + تقرير «أصناف وصلت للحد الأدنى». |
| **تسجيل الوارد** | **للمخزن الرئيسي فقط** (نوعه level=1). شاشة الوارد لا تعرض إلا المخازن الرئيسية. الأصناف الجديدة تدخل الرئيسي أولاً ثم تتوزّع بالنقل. |
| **عدد الأصناف في المستند** | **مستند واحد = عدة أصناف**. الوارد والنقل بنية **رأس + بنود** (إذن/محضر واحد فيه صنف أو أكثر). |
| **المرفقات** | **إجبارية** على كل وارد ونقل (إذن صرف / محضر استلام / استمارة نقل عهدة / أي مستند). صورة أو PDF. |
| **الربط الهرمي** | **لا يوجد** `parent_warehouse_id`. النقل حر بين أي مخزنين طالما `from.level ≤ to.level`. |
| **الصلاحيات** | غير مرتبطة بالمحافظة (عكس المقرات) — أي مستخدم عنده صلاحية مخازن يرى كل المخازن. |

### قاعدة النقل (مشتقة من `level`) — مؤكَّدة من العميل
النقل مسموح من مستوى أقل أو **مساوٍ**، ويُمنَع الصعود لأعلى فقط: `from.type.level ≤ to.type.level`.
- رئيسي(1) → إقليمي(2) ✅
- رئيسي(1) → فرعي(3) ✅ (تخطّي المستوى مسموح)
- إقليمي(2) → فرعي(3) ✅
- إقليمي(2) → إقليمي(2) ✅ (نفس المستوى — باستمارة نقل عهدة)
- فرعي(3) → فرعي(3) ✅ (نفس المستوى)
- أي صعود لأعلى (فرعي→إقليمي، إقليمي→رئيسي...) ❌ (يمنعه التحقق)

---

## ✅ قرار مُعتمَد: مكان شاشات «إدارة المخازن» و«إدارة الأصناف»
> اتفقنا على **التوصية** (كل شيء تحت فرع المخازن `warehouses.*`).

الملف يقول: «شاشة **إدارة النظام** > إعدادات المخازن / إعدادات الأصناف».
لكن آلية تحديد الفرع الحالي (`Branch::current()`) تعتمد على **مطابقة اسم الـ route** بالأنماط،
وفرع المخازن سيحجز النمط `warehouses.*`. لو حطّينا شاشات الإدارة تحت «إدارة النظام»
لازم نسمّيها بـ prefix مختلف (مثل `warehouse-types.*`) عشان ما تتصنّفش غلط في فرع المخازن.

**التوصية (الأنظف هندسياً):** كل شاشات المخازن تحت prefix واحد `warehouses.*` وفرع واحد.
شاشات الإعداد (المخازن + الأصناف + الأنواع + الوحدات) تظهر داخل **فرع المخازن نفسه**
في قائمة فرعية «الإعدادات» محكومة بصلاحية `warehouses.settings` — بدل توزيعها على فرع النظام.
ميزة: كل حاجة في مكان واحد، صفر تعارض routes، اكتشاف الفرع يفضل بسيط.

**البديل (حرفياً كالملف):** شاشات الإدارة في فرع «إدارة النظام» بأسماء routes خارج `warehouses.`
(`warehouse-types.*`, `item-units.*`, `warehouse-list.*`, `item-list.*`) وتُضاف لأنماط فرع النظام.

> الخطة تحت مكتوبة على **التوصية**. لو تفضّلي البديل، التعديل محصور في: أسماء الـ routes
> وموضع بنود الـ sidebar فقط (نفس الجداول والمكوّنات).

**🔄 تحديث نهائي (2026-07-18):** اتطبّق حل هجين، مبني على تمييز حقيقي بين نوعين من الشاشات — كيان تشغيلي (زي المقرات) مقابل قائمة مرجعية (زي أنواع المقرات):
- **«إدارة المخازن»** (`warehouse-manage.*` — تسجيل/تعديل المخزن نفسه) → **انتقلت لفرع المخازن** كعنصر أساسي في الـ sidebar (مش تحت منيو إعدادات)، لأنها كيان تشغيلي بيُستخدَم يومياً (زي المقر بالظبط).
- **الأصناف / أنواع المخازن / وحدات الأصناف** (`items.*`, `warehouse-types.*`, `item-units.*`) → **فضلوا في فرع النظام** كقوائم مرجعية، زي أنواع المقرات وأنواع الأجهزة بالظبط.
- الصلاحية `warehouses.settings` لسه هي البوابة لكل الأربعة (مفيش تغيير في الصلاحيات) — بس اتغيّر بس *مكان الرابط* في الـ sidebar و`route_patterns` في `config/branches.php`.

---

## الجداول (Migrations)

### قوائم مرجعية
```
warehouse_types:  id, name, level(tinyint), order(int, default 0), timestamps
                  seed: رئيسي(level 1), إقليمي(level 2), فرعي(level 3)
item_units:       id, name, timestamps
                  seed: قطعة، جهاز، عبوة
```

### الكيانات الأساسية
```
warehouses:  id, name, governorate_id(FK→governorates, nullable), warehouse_type_id(FK→warehouse_types),
             is_active(bool, default true), timestamps

items:       id, name, item_unit_id(FK→item_units, nullable),
             min_stock(int, nullable),          ← الحد الأدنى (يُفحَص على المخزن الرئيسي فقط)
             is_active(bool, default true), timestamps

warehouse_stocks:  id, warehouse_id(FK), item_id(FK), quantity(int, default 0), timestamps
                   UNIQUE(warehouse_id, item_id)      ← الرصيد الحالي لكل صنف في كل مخزن
```

### الحركات (وارد / نقل) — بنية رأس + بنود (مستند واحد فيه عدة أصناف)
```
warehouse_incomings (رأس):   id, warehouse_id(FK ← رئيسي فقط), received_at(date),
                      supplier_name(string, nullable),
                      attachment_path(string, NOT NULL), attachment_original_name(string, NOT NULL),  ← إجباري
                      created_by(FK→users, nullable), timestamps
warehouse_incoming_items (بنود):  id, warehouse_incoming_id(FK), item_id(FK), quantity(int)

warehouse_transfers (رأس):   id, from_warehouse_id(FK), to_warehouse_id(FK), transferred_at(date),
                      document_type(string, nullable),   ← نوع المستند (إذن صرف / استمارة نقل عهدة)
                      attachment_path(string, NOT NULL), attachment_original_name(string, NOT NULL),  ← إجباري
                      created_by(FK→users, nullable), timestamps
warehouse_transfer_items (بنود):  id, warehouse_transfer_id(FK), item_id(FK), quantity(int)
```

### سجل الحركات (Ledger — البند «سادساً»)
```
warehouse_movements:  id, warehouse_id(FK), item_id(FK),
                      type(enum: opening|incoming|transfer_out|transfer_in),
                      quantity(int),                 ← موجب دائماً (الاتجاه من النوع)
                      balance_before(int), balance_after(int),
                      reference_type(string, nullable), reference_id(int, nullable), ← يشير للوارد/النقل
                      user_id(FK→users, nullable), created_at
```
كل عملية تُغذّي هذا السجل تلقائياً (**لكل بند/صنف في المستند**):
- **رصيد افتتاحي** → صف واحد `opening`.
- **وارد** (مستند فيه N صنف) → N صف `incoming` على المخزن الرئيسي.
- **نقل** (مستند فيه N صنف) → 2×N صف: لكل صنف `transfer_out` على المصدر (−) و`transfer_in` على المستلم (+).

> نوع الكمية `int` لأن الوحدات معدودة (قطعة/جهاز/عبوة). لو ظهرت وحدات كسرية لاحقاً نحوّلها `decimal`.

---

## منطق العمليات — `App\Support\WarehouseLedger` (خدمة مركزية)
عشان منطق الرصيد ما يتكررش في كل مكوّن (نفس فكرة `ArabicText`):
```php
WarehouseLedger::recordOpening(Warehouse $w, Item $i, int $qty, ?User $u): void
WarehouseLedger::recordIncoming(WarehouseIncoming $incoming): void   // يمرّ على كل بنوده: +qty على الرئيسي
WarehouseLedger::recordTransfer(WarehouseTransfer $transfer): void   // ضمن transaction، لكل بند
```
**`recordTransfer` داخل `DB::transaction` (يعالج كل بنود المستند دفعة واحدة):**
1. التحقق `from.type.level ≤ to.type.level` (قاعدة النقل — يمنع الصعود فقط).
2. لكل بند: التحقق أن رصيد الصنف في المصدر ≥ الكمية (وإلا استثناء + رسالة توضّح الصنف الناقص).
3. لكل بند: خصم الكمية من `warehouse_stocks` المصدر، إضافتها للمستلم (إنشاء صف لو مش موجود).
4. لكل بند: كتابة صفّي `warehouse_movements` (out + in) بالرصيد قبل/بعد.

> إمّا كل البنود تنجح أو لا شيء (transaction) — لو أي صنف رصيده لا يكفي يُلغى المستند كله.

---

## الصلاحيات (Spatie — غير مرتبطة بالمحافظة)
```
warehouses.index        عرض القوائم والأرصدة والحركات
warehouses.view         عرض تفاصيل حركة/سجل
warehouses.create       إضافة (رصيد افتتاحي / وارد / نقل)
warehouses.edit         تعديل
warehouses.delete       حذف
warehouses.export        تصدير التقارير
warehouses.attachments  رفع وتنزيل المرفقات
warehouses.settings     إدارة المخازن والأصناف والأنواع والوحدات
```
> يغطّي محاور الصلاحيات في البند «سابعاً»: إضافة/تعديل/حذف/عرض تقارير/مرفقات.

**دخول الفرع مُشتَق:** `permissions` للفرع = `[warehouses.index, warehouses.create, warehouses.export]`
(أي صلاحية منها تفتح الفرع؛ super-admin يشوفه دايماً).

---

## الـ Routes (كلها تحت `warehouses.`)
```php
// عمليات
warehouses.dashboard              Warehouses\Dashboard
warehouses.stock                  Warehouses\Stock            (تقرير/عرض الأرصدة)
warehouses.opening-balances       Warehouses\OpeningBalances  (إدخال الأرصدة الافتتاحية)
warehouses.incoming.index         Warehouses\Incoming\Index
warehouses.incoming.create        Warehouses\Incoming\Create  (create+edit)
warehouses.incoming.edit          Warehouses\Incoming\Create
warehouses.transfers.index        Warehouses\Transfers\Index
warehouses.transfers.create       Warehouses\Transfers\Create (create+edit)
warehouses.transfers.edit         Warehouses\Transfers\Create
warehouses.movements              Warehouses\Movements        (سجل الحركات)
warehouses.reports.*              Warehouses\Reports\...       (تقارير)

// إعدادات (محكومة warehouses.settings)
warehouses.manage.index/create/edit   Warehouses\Manage\...    (CRUD المخازن)
warehouses.items.index/create/edit    Warehouses\Items\...     (CRUD الأصناف)
warehouses.types.index/create/edit    Warehouses\Types\...     (أنواع المخازن)
warehouses.units.index/create/edit    Warehouses\Units\...     (وحدات الأصناف)
```
كل route عملياتي بـ `middleware('permission:warehouses.<action>')`، وشاشات الإعداد بـ
`middleware('permission:warehouses.settings')`.

---

## Livewire Components + Views
`app/Livewire/Warehouses/` و `resources/views/livewire/warehouses/` (نفس ستايل المقرات/الاجتماعات).

| المكوّن | الوصف |
|--------|-------|
| `Dashboard` | داشبورد الفرع: عدد المخازن/الأصناف، إجمالي حركات الشهر، آخر الحركات، **تنبيه أصناف وصلت للحد الأدنى** (على الرئيسي). |
| `Stock` | جدول الأرصدة (كل صنف × كل مخزن) + فلتر مخزن/صنف + بحث عربي. |
| `OpeningBalances` | إدخال الرصيد الافتتاحي (اختيار مخزن + صنف + كمية) → `recordOpening`. |
| `Incoming\Index` | قائمة الوارد + بحث/فلتر تاريخ + عرض/تعديل/حذف. |
| `Incoming\Create` | نموذج وارد (مخزن رئيسي فقط + مورد + **صفوف أصناف متعددة** [صنف+كمية] + مرفق إجباري) → `recordIncoming`. keepalive. |
| `Transfers\Index` | قائمة عمليات النقل + فلتر. |
| `Transfers\Create` | نموذج نقل (مصدر + مستلم + نوع المستند + **صفوف أصناف متعددة** [صنف+كمية] + مرفق إجباري) → `recordTransfer`. صفوف الأصناف بنمط `attendees` في الاجتماعات (add/remove). keepalive. |
| `Movements` | سجل الحركات (قراءة فقط) + فلاتر (مخزن/صنف/نوع/فترة) + pagination. |
| `Reports\*` | التقارير (تحت). |
| `Manage\*`, `Items\*`, `Types\*`, `Units\*` | CRUD الإعدادات (نمط `OfficeTypes\Index/Create`). |

**قواعد إلزامية مطبَّقة:**
- كل نص عربي → مفتاح في `lang/ar/home.php` (`warehouse_*`, `branch_warehouses`).
- أي بحث عربي → `App\Support\ArabicText` (normalize + sqlNormalize).
- keepalive في كل شاشة إدخال (الوارد/النقل/الأرصدة الافتتاحية).
- أسماء الحقول العربية في `lang/ar/validation.php` تحت `attributes`.
- المرفقات على disk `public` (نفس نمط `OfficeMedia`)؛ الحذف يمسح الملف.

---

## المرفقات (إجبارية)
`warehouse_incomings.attachment_path` و `warehouse_transfers.attachment_path` (صورة أو PDF).
- **إجباري** (`required`) مع كل وارد ونقل — قرار العميل. مستند واحد للمستند كله (رأس)، مش لكل صنف.
- الرفع في `Create` (WithFileUploads) — نوع: `image/*` أو `pdf`، حد حجم معقول.
- التنزيل/العرض محكوم بصلاحية `warehouses.attachments`.
- عند حذف السجل → حذف الملف من `storage/public` (في `booted()` بالموديل).

---

## التقارير (البند «سابعاً»)
1. أرصدة جميع المخازن (كل الأصناف × كل المخازن).
2. رصيد صنف داخل مخزن محدد.
3. الوارد خلال فترة زمنية.
4. الصرف (transfer_out) خلال فترة زمنية.
5. النقل بين المخازن (كل عمليات النقل بفلتر مصدر/مستلم/فترة).
6. المرفقات (قائمة مستندات الاستلام وأذون الصرف مع روابط تنزيل).
7. **الأصناف التي وصلت للحد الأدنى** (رصيد المخزن الرئيسي ≤ `items.min_stock`).

> نمط التقارير الحالي في `app/Livewire/Reports/` — نلتزم بيه (فلاتر + جدول + تصدير لاحقاً).

---

## خطة التنفيذ (مراحل)

### المرحلة أ — البنية والصلاحيات
1. Migrations للجداول (types, units, warehouses, items, stocks, incomings + incoming_items,
   transfers + transfer_items, movements) + الـ seed (`warehouse_types` بمستوياتها، `item_units`).
2. Migration `seed_warehouse_permissions` (نمط `seed_meeting_permissions`).
3. Migration `assign_warehouse_permissions_to_roles` (super-admin + admin).
4. تعريف الفرع في `config/branches.php` (`warehouses`, icon `archive-box`).
5. Models: `WarehouseType, ItemUnit, Warehouse, Item, WarehouseStock, WarehouseIncoming,
   WarehouseTransfer, WarehouseMovement` + العلاقات + `WarehouseLedger` service.
6. مفاتيح اللغة الأساسية في `home.php` + `validation.php`.

### المرحلة ب — الإعدادات (CRUD)
7. `Types`, `Units`, `Manage` (المخازن), `Items` (الأصناف) — Index+Create لكل واحد.

### المرحلة ج — العمليات
8. `OpeningBalances` → `recordOpening`.
9. `Incoming` (Index + Create) → `recordIncoming` + مرفق.
10. `Transfers` (Index + Create) → `recordTransfer` + مرفق + قاعدة `level`.
11. `Stock` + `Movements` (عرض).

### المرحلة د — الداشبورد والتقارير
12. `Dashboard` (KPIs).
13. التقارير الستة.

### المرحلة هـ — الدمج في الواجهة
14. بند فرع المخازن في `sidebar.blade.php` (بنود العمليات + قائمة إعدادات فرعية).
15. مجموعة صلاحيات المخازن في `permissions-grid.blade.php` (`home.branch_warehouses`).

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
