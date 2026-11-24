<?php

namespace App\Http\Middleware;

use App\Support\SystemFreeze;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * وقت تجميد النظام: مَن كان داخلاً (أو يدخل بكوكي «تذكرني») يخرج مع أول طلب.
 *
 * على مجموعة `web` كلها لا على `auth` وحدها — فيمرّ به طلب Livewire (`/livewire/update`)
 * أيضاً، وإلا ظلّ مَن فتح صفحةً قبل التجميد يحفظ منها.
 * الزائر بلا دخول لا يُمسّ — **فبوابة رأي المواطن تعمل كما هي**.
 */
class BlockWhenSystemFrozen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! SystemFreeze::blocks($user)) {
            return $next($request);
        }

        SystemFreeze::logBlocked($user, 'session');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // موظفٌ داخل فتح البوابة العامة أو شاشة الدخول: يخرج ويكمل كزائر
        if ($request->routeIs('feedback', 'feedback.*', 'login')) {
            return $next($request);
        }

        // فتح صفحة ← شاشة الدخول (وهي شاشة الصيانة الآن)؛ طلب Livewire ← الشاشة نفسها
        if ($request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            return redirect()->route('login');
        }

        return response()->view('livewire.auth.system-frozen', [], 503);
    }
}
