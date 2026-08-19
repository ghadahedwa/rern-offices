<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * مساعد فروع المنظومة — يحدد الفرع الحالي والفروع المتاحة للمستخدم.
 * الفروع مُعرّفة في config/branches.php.
 */
class Branch
{
    /** كل الفروع كما هي في الإعدادات. */
    public static function all(): array
    {
        return config('branches', []);
    }

    /**
     * إعدادات فرع واحد.
     * يقبل null لأن current()/defaultKeyFor() يرجعان null لمستخدم بلا فرع متاح
     * (مستخدم جديد بلا أدوار) — والقوالب تتعامل مع القيمة الفارغة.
     */
    public static function config(?string $key): ?array
    {
        return $key === null ? null : config("branches.$key");
    }

    /** هل يقدر المستخدم يدخل الفرع ده؟ (super-admin أو عنده أي صلاحية من صلاحياته) */
    public static function canAccess(string $key, $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        $branch = static::config($key);
        if (! $branch) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (! empty($branch['super_admin_only'])) {
            return false;
        }

        foreach ($branch['permissions'] ?? [] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /** مفاتيح الفروع المتاحة للمستخدم (بترتيب التعريف). */
    public static function accessibleFor($user = null): array
    {
        $user ??= Auth::user();

        return array_values(array_filter(
            array_keys(static::all()),
            fn ($key) => static::canAccess($key, $user)
        ));
    }

    /** مفتاح الجلسة الذي يحفظ آخر فرع زاره المستخدم. */
    public const SESSION_KEY = 'branch.last';

    /**
     * الفرع الحالي من اسم الـ route.
     *
     * وللشاشات التي لا تنتمي لفرع — الملف الشخصي والمظهر والأمان (`profile.edit`
     * و`appearance.edit` و`security.edit`) — **نبقى في الفرع الذي جاء منه المستخدم**.
     * ⚠️ قبل هذا كانت تسقط على `defaultKeyFor()` = أوّل فرع متاح، فيجد مَن دخل
     *    «المظهر» نفسه في قائمة المقرات — وهي شاشة شخصية لا تخصّ فرعاً بحال.
     */
    public static function current(): ?string
    {
        $routeName = request()->route()?->getName();

        if ($routeName) {
            foreach (static::all() as $key => $branch) {
                foreach ($branch['route_patterns'] ?? [] as $pattern) {
                    if (Str::is($pattern, $routeName)) {
                        static::remember($key);

                        return $key;
                    }
                }
            }
        }

        // ⚠️ يُعاد فحص الصلاحية: قيمة الجلسة قد تكون من قبل تغيير أدوار المستخدم
        $last = static::recalled();
        if ($last !== null && static::canAccess($last)) {
            return $last;
        }

        return static::defaultKeyFor();
    }

    /** يحفظ الفرع الحالي في الجلسة — للرجوع إليه في شاشة لا تنتمي لفرع. */
    protected static function remember(string $key): void
    {
        if (! request()->hasSession()) {
            return;
        }

        if (session(static::SESSION_KEY) !== $key) {
            session([static::SESSION_KEY => $key]);
        }
    }

    /** آخر فرع محفوظ في الجلسة، إن وُجدت جلسة وكان الفرع معرَّفاً. */
    protected static function recalled(): ?string
    {
        if (! request()->hasSession()) {
            return null;
        }

        $key = session(static::SESSION_KEY);

        return is_string($key) && static::config($key) !== null ? $key : null;
    }

    /** أول فرع متاح للمستخدم (الافتراضي). */
    public static function defaultKeyFor($user = null): ?string
    {
        return static::accessibleFor($user)[0] ?? null;
    }

    /** URL صفحة دخول الفرع حسب صلاحيات المستخدم (أول entry متاح له). */
    public static function entryUrlFor(string $key, $user = null): string
    {
        $user ??= Auth::user();
        $branch = static::config($key);

        foreach (($branch['entries'] ?? []) as $routeName => $ability) {
            if ($ability === null) {
                return route($routeName);
            }
            if ($ability === 'role:super-admin') {
                if ($user?->hasRole('super-admin')) {
                    return route($routeName);
                }
                continue;
            }
            if ($user?->can($ability)) {
                return route($routeName);
            }
        }

        return route($branch['default_route'] ?? 'dashboard');
    }

    /** URL التوجيه بعد اللوجين = صفحة دخول أول فرع متاح للمستخدم. */
    public static function defaultUrlFor($user = null): string
    {
        $key = static::defaultKeyFor($user);

        return $key ? static::entryUrlFor($key, $user) : route('dashboard');
    }
}
