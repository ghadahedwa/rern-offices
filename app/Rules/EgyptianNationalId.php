<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * تحقّق من صحة الرقم القومي المصري (١٤ رقماً) بناءً على تركيبه:
 * [1] القرن (2=19xx، 3=20xx) · [2-3] السنة · [4-5] الشهر · [6-7] اليوم
 * · [8-9] كود المحافظة · [10-13] تسلسل · [14] خانة تحقق (غير مفحوصة هنا).
 * لا نفحص خانة التحقق (14) لتجنّب رفض أرقام صحيحة بخوارزمية غير موثّقة.
 */
class EgyptianNationalId implements ValidationRule
{
    /** أكواد المحافظات المعتمدة في الرقم القومي (88 = مواليد خارج مصر). */
    private const GOVERNORATE_CODES = [
        '01', '02', '03', '04', '11', '12', '13', '14', '15', '16', '17', '18',
        '19', '21', '22', '23', '24', '25', '26', '27', '28', '29', '31', '32',
        '33', '34', '35', '88',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = (string) $value;

        if (! preg_match('/^\d{14}$/', $v)) {
            $fail('الرقم القومي يجب أن يتكوّن من ١٤ رقماً.');

            return;
        }

        $century = $v[0];
        if (! in_array($century, ['2', '3'], true)) {
            $fail('الرقم القومي غير صحيح (خانة القرن يجب أن تكون ٢ أو ٣).');

            return;
        }

        $year = ($century === '2' ? 1900 : 2000) + (int) substr($v, 1, 2);
        $month = (int) substr($v, 3, 2);
        $day = (int) substr($v, 5, 2);

        if ($month < 1 || $month > 12) {
            $fail('الرقم القومي غير صحيح (الشهر في تاريخ الميلاد يجب أن يكون بين ١ و ١٢).');

            return;
        }

        if (! checkdate($month, $day, $year)) {
            $fail('الرقم القومي غير صحيح (اليوم في تاريخ الميلاد غير صالح لهذا الشهر).');

            return;
        }

        if (sprintf('%04d-%02d-%02d', $year, $month, $day) > date('Y-m-d')) {
            $fail('الرقم القومي غير صحيح (تاريخ الميلاد في المستقبل).');

            return;
        }

        // كود المحافظة (خانتا 8-9) — أزل هذا الشرط لو تسبّب في رفض حالة صحيحة.
        if (! in_array(substr($v, 7, 2), self::GOVERNORATE_CODES, true)) {
            $fail('الرقم القومي غير صحيح (كود المحافظة غير معروف).');

            return;
        }
    }
}
