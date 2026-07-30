<?php

namespace App\Rules;

use App\Models\Office;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * المقر المختار لازم يكون موجوداً ومن نوع ظاهر للمواطن (office_types.is_public).
 * الـ scope يُطبَّق على قوائم العرض فقط، فلولا هذه القاعدة كان طلب متلاعَب فيه
 * يقدر يعلّق رأي مواطن على مقر إداري داخلي.
 */
class PublicFeedbackOffice implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Office::publicFeedback()->whereKey($value)->exists()) {
            $fail('المقر المختار غير متاح لتلقّي آراء المواطنين.');
        }
    }
}
