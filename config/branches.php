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

    'system' => [
        'label'         => 'home.branch_system',
        'icon'          => 'cog-6-tooth',
        'default_route' => 'users.index',
        'entries' => [
            'users.index'        => 'role:super-admin',
            'office-types.index' => 'offices.settings',
        ],
        'route_patterns' => [
            'users.*',
            'roles.*',
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
        ],
        'super_admin_only' => false,
        'permissions' => [
            // المستخدمون والأدوار للسوبر أدمن فقط حالياً؛ offices.settings يفتح الفرع لمدير الإعدادات
            'offices.settings',
        ],
    ],

];
