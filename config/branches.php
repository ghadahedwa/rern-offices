<?php

/*
|--------------------------------------------------------------------------
| فروع المنظومة (Branches)
|--------------------------------------------------------------------------
| كل فرع = منظومة ليها sidebar وداشبورد خاص. الفروع مجرد تجميع للتنقّل —
| مفيش صلاحية فرع تتخزّن؛ دخول الفرع مُشتَق من صلاحيات المستخدم:
|   - super_admin_only = true  → الفرع للسوبر أدمن فقط
|   - permissions[]            → المستخدم يدخل لو عنده أي صلاحية منها (أو super-admin)
|
| current branch يتحدد بمطابقة اسم الـ route الحالي مع route_patterns.
| ملاحظة: بدون closures عشان config:cache.
*/

return [

    'offices' => [
        'label'         => 'home.branch_offices',
        'icon'          => 'building-office-2',
        'default_route' => 'dashboard',
        // صفحة الدخول للفرع حسب صلاحية المستخدم (أول متاح). null = متاح لأي حد يدخل الفرع.
        // القيمة الخاصة 'role:super-admin' تعني الدور مش صلاحية.
        'entries' => [
            'dashboard' => null,
        ],
        'route_patterns' => [
            'dashboard',
            'offices.*',
            'vehicles.*',
            'claims.*',
            'reports.*',
            'governorates.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            'offices.index',
            'offices.export',
            'offices.phone-directory',
            'vehicles.index',
            'vehicles.export',
            'claims.index',
            'governorates.index',
        ],
    ],

    // نتائج بوابة رأي المواطن — بعد المقرات مباشرة: نطاقه نفس نطاقها (المحافظة)
    // وقارئه هو قارئها، فيقع في المنيو بجوارها لا في آخر القائمة.
    'feedback' => [
        'label'         => 'home.branch_feedback',
        'icon'          => 'chat-bubble-left-right',
        'default_route' => 'feedback-results.dashboard',
        // مَن له المرفوضات وحدها يدخل عليها مباشرة — اللوحة تردّه ٤٠٣
        'entries' => [
            'feedback-results.dashboard' => 'feedback.view',
            'feedback-results.rejected'  => 'feedback.rejected',
        ],
        'route_patterns' => [
            // ملاحظة: البوابة العامة اسمها 'feedback.*' — النمط ده بيخصّ الشاشات الإدارية فقط
            'feedback-results.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            'feedback.view',
            'feedback.export',
            'feedback.delete',
            'feedback.rejected',
        ],
    ],

    'meetings' => [
        'label'         => 'home.branch_meetings',
        'icon'          => 'calendar-days',
        'default_route' => 'meetings.index',
        'entries' => [
            'meetings.index' => null,
        ],
        'route_patterns' => [
            'meetings.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            'meetings.index',
            'meetings.view',
            'meetings.create',
            'meetings.edit',
            'meetings.delete',
        ],
    ],

    'warehouses' => [
        'label'         => 'home.branch_warehouses',
        'icon'          => 'archive-box',
        'default_route' => 'warehouses.dashboard',
        'entries' => [
            'warehouses.dashboard' => null,
        ],
        'route_patterns' => [
            'warehouses.*',
            // إدارة المخازن (تسجيل/تعديل المخازن نفسها) — كيان تشغيلي زي المقرات، مش قائمة مرجعية
            'warehouse-manage.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            'warehouses.index',
            'warehouses.create',
            'warehouses.export',
            'warehouses.settings',
        ],
    ],

    // المراسلات — الوارد والصادر والمسودات والتكليفات والتفويض.
    // ⚠️ `correspondence-entities.*` (أطراف المراسلات) تبقى في فرع «إدارة النظام»
    //    كباقي القوائم المرجعية — والنمط هنا `correspondence.*` بنقطة، فلا يطابقها.
    'correspondence' => [
        'label'         => 'home.branch_correspondence',
        'icon'          => 'envelope',
        'default_route' => 'correspondence.inbox',
        'entries' => [
            'correspondence.inbox' => 'correspondence.index',
        ],
        'route_patterns' => [
            'correspondence.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            'correspondence.index',
            'correspondence.create',
            'correspondence.export',
        ],
    ],

    'system' => [
        'label'         => 'home.branch_system',
        'icon'          => 'cog-6-tooth',
        'default_route' => 'system-dashboard',
        'entries' => [
            // داشبورد النظام هي صفحة الدخول للجميع (متاحة لكل من يدخل الفرع)
            'system-dashboard' => null,
        ],
        'route_patterns' => [
            'system-dashboard',
            'users.*',
            'roles.*',
            // إعدادات المخازن (الأصناف والأنواع والوحدات — قوائم مرجعية). إدارة المخازن نفسها انتقلت لفرع المخازن.
            'items.*',
            'warehouse-types.*',
            'item-units.*',
            // إعدادات المقرات (القوائم المرجعية للمقرات والسيارات) — تهيئة يديرها الأدمن
            'office-types.*',
            'location-descriptions.*',
            'work-systems.*',
            'working-hours.*',
            'connection-types.*',
            'device-types.*',
            'contractual-statuses.*',
            'structural-conditions.*',
            'disabilities-access.*',
            'fire-safety.*',
            'document-photocopying-services.*',
            'buffet-services.*',
            'cleanliness-contracts.*',
            'microfilm-options.*',
            'vehicle-types.*',
            'vehicle-brands.*',
            'vehicle-work-systems.*',
            'vehicle-working-hours.*',
            'vehicle-device-types.*',
            // إعدادات المراسلات (أطراف المراسلات — قائمة مرجعية).
            // فرع المراسلات المستقل يُنشأ مع شاشات الوارد والصادر لا قبلها.
            'correspondence-entities.*',
        ],
        'super_admin_only' => false,
        'permissions' => [
            // المستخدمون والأدوار للسوبر أدمن فقط حالياً؛ offices.settings/warehouses.settings يفتح الفرع لمدير الإعدادات
            'offices.settings',
            'warehouses.settings',
            'correspondence.settings',
        ],
    ],

];
