<?php

namespace App\Http\Controllers;

use App\Support\Branch;

/**
 * صفحة «لا توجد صلاحيات» — لمستخدم مسجَّل دخوله ولا وجهة له (دورٌ بلا صلاحيات).
 *
 * بديل التوجيه إلى /dashboard الذي كان يلفّ بلا نهاية. ومَن صارت له وجهة (أُسندت له
 * صلاحيات بعد ظهور الصفحة) يُوجَّه إليها — فلا يعلق أحد هنا بعد إصلاح دوره.
 * ⚠️ الوجهة من landingUrlFor وحدها: هي التي تضمن ألّا ترجع /dashboard فيردّه إلى هنا.
 */
class NoAccessController extends Controller
{
    public function __invoke()
    {
        $target = Branch::landingUrlFor();

        if ($target !== route('no-access')) {
            return redirect($target);
        }

        return view('no-access');
    }
}
