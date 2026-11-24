<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * تجميد النظام — المصدر الواحد (طلب العميل 2026-11-24: شكٌّ في سرقة حسابات).
 *
 * وهو مفعَّل: لا يدخل إلا super-admin، ومَن كان داخلاً يخرج مع أول طلب
 * (`BlockWhenSystemFrozen`)، وشاشة الدخول تعرض رسالة الصيانة. **بوابة رأي المواطن
 * لا تتأثر** — راوتاتها خارج `auth`، والحجب لا يقع إلا على مستخدمٍ داخل.
 *
 * ⚠️ لا حذف ولا تعديل على الحسابات — الفكّ يعيد كل شيء كما كان.
 */
class SystemFreeze
{
    public const KEY_ACTIVE  = 'freeze.active';
    public const KEY_CONTACT = 'freeze.contact';

    /** اسم سجلّ النشاط لأحداث التجميد ومحاولات الدخول المحجوبة. */
    public const LOG = 'system-freeze';

    public static function isActive(): bool
    {
        return self::get(self::KEY_ACTIVE) === '1';
    }

    /**
     * مَن يُرجع إليه (الاسم في الإطار الذهبي بشاشة الصيانة) — يُكتب من صفحة التجميد
     * لا في الكود، فتغيّر الاسم لا يحتاج نشراً.
     */
    public static function contact(): string
    {
        $contact = trim((string) self::get(self::KEY_CONTACT));

        return $contact !== '' ? $contact : __('home.freeze_default_contact');
    }

    /** وقت التفعيل ومَن فعّله (للشاشة الإدارية). */
    public static function activeSince(): ?object
    {
        if (! self::isActive()) {
            return null;
        }

        $row = DB::table('system_settings')
            ->leftJoin('users', 'users.id', '=', 'system_settings.updated_by')
            ->where('key', self::KEY_ACTIVE)
            ->first(['system_settings.updated_at', 'users.name']);

        // صفّ query builder خام: التاريخ نصٌّ لا كائن (LocalTime يطلب كائناً)
        if ($row?->updated_at) {
            $row->updated_at = Carbon::parse($row->updated_at, 'UTC');
        }

        return $row;
    }

    /** هل يُحجب هذا المستخدم الآن؟ super-admin لا يُحجب أبداً (ليحقق ويفكّ التجميد). */
    public static function blocks(?Authenticatable $user): bool
    {
        return $user instanceof User
            && ! $user->hasRole('super-admin')
            && self::isActive();
    }

    /**
     * يفعّل التجميد ويُخرج كل المستخدمين الداخلين فوراً — إلا super-admin.
     *
     * حذف الجلسات يُخرجهم حتى لو لم يرسلوا طلباً بعد (ويُفرغ «المتصلون الآن»)،
     * والـmiddleware طبقة ثانية لما لا يمرّ بجدول الجلسات (كوكي «تذكرني»).
     */
    public static function activate(User $by, string $contact): void
    {
        DB::transaction(function () use ($by, $contact) {
            self::put(self::KEY_CONTACT, trim($contact), $by);
            self::put(self::KEY_ACTIVE, '1', $by);
        });

        if (config('session.driver') === 'database') {
            $superAdminIds = User::role('super-admin')->pluck('id');

            DB::table(config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->whereNotIn('user_id', $superAdminIds)
                ->delete();
        }

        activity(self::LOG)->causedBy($by)->event('activated')
            ->withProperties(['contact' => self::contact(), 'ip' => request()->ip()])
            ->log('تجميد النظام');
    }

    public static function deactivate(User $by): void
    {
        self::put(self::KEY_ACTIVE, '0', $by);

        activity(self::LOG)->causedBy($by)->event('deactivated')
            ->withProperties(['ip' => request()->ip()])
            ->log('فكّ تجميد النظام');
    }

    /**
     * يسجّل محاولةً محجوبة — هي الدليل المطلوب (مَن حاول · من أي IP · بأي جهاز).
     * `login` = دخل بكلمة سر صحيحة · `session` = كان داخلاً (أو بكوكي «تذكرني») فأُخرج.
     */
    public static function logBlocked(User $user, string $kind): void
    {
        activity(self::LOG)->causedBy($user)->event('blocked_'.$kind)
            ->withProperties([
                'ip'         => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 300),
            ])
            ->log($kind === 'login' ? 'محاولة دخول أثناء التجميد' : 'إخراج جلسة أثناء التجميد');
    }

    /**
     * ⚠️ جدولٌ غير موجود (نشرٌ بلا migrate) = «غير مجمَّد» لا ٥٠٠ على كل الصفحات —
     *    الـmiddleware يمرّ بكل طلب في الموقع.
     */
    private static function get(string $key): ?string
    {
        try {
            return DB::table('system_settings')->where('key', $key)->value('value');
        } catch (QueryException) {
            return null;
        }
    }

    private static function put(string $key, string $value, User $by): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $by->id, 'updated_at' => now()],
        );
    }
}
