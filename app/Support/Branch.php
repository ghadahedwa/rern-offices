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

    /** إعدادات فرع واحد. */
    public static function config(string $key): ?array
    {
        return config("branches.$key");
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

    /** الفرع الحالي من اسم الـ route؛ يرجع للفرع الافتراضي لو مفيش تطابق. */
    public static function current(): ?string
    {
        $routeName = request()->route()?->getName();

        if ($routeName) {
            foreach (static::all() as $key => $branch) {
                foreach ($branch['route_patterns'] ?? [] as $pattern) {
                    if (Str::is($pattern, $routeName)) {
                        return $key;
                    }
                }
            }
        }

        return static::defaultKeyFor();
    }

    /** أول فرع متاح للمستخدم (الافتراضي). */
    public static function defaultKeyFor($user = null): ?string
    {
        return static::accessibleFor($user)[0] ?? null;
    }

    /** الـ route الافتراضي للفرع الافتراضي للمستخدم (للتوجيه بعد اللوجين). */
    public static function defaultRouteFor($user = null): string
    {
        $key = static::defaultKeyFor($user);

        return $key ? (static::config($key)['default_route'] ?? 'dashboard') : 'dashboard';
    }
}
