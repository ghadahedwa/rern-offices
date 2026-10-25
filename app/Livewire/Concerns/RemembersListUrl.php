<?php

namespace App\Livewire\Concerns;

use App\Support\ListReturnUrl;
use Livewire\Features\SupportQueryString\BaseUrl;
use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;

/**
 * تحفظ القائمة رابطها الحالي في الجلسة مع كل طلب، ليرجع إليه زرّ الرجوع في صفحاتها
 * (`ListReturnUrl::back()`).
 *
 * المعاملات تُقرأ من تعريفات `#[Url]` نفسها (اسمها في الرابط · قيمتها الافتراضية) —
 * فلترٌ جديد يُضاف للرابط يُحفظ معه بلا قائمةٍ تُنسى. ويُضاف رقم الصفحة.
 * ⚠️ لذلك **فلترٌ ليس في الرابط لا يرجع** — الحلّ وسمه `#[Url]` لا كتابته هنا.
 */
trait RemembersListUrl
{
    /** اسم راوت القائمة */
    abstract protected function listReturnRoute(): string;

    /** معاملات راوت القائمة (`['type' => …]`) */
    protected function listReturnParams(): array
    {
        return [];
    }

    /** يُنادى من Livewire مع كل طلب، بعد تطبيق التغييرات */
    public function dehydrateRemembersListUrl(): void
    {
        ListReturnUrl::remember($this->listReturnRoute(), $this->listReturnParams(), $this->listReturnQuery());
    }

    protected function listReturnQuery(): array
    {
        $query = [];

        foreach ((new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $url = $property->getAttributes(BaseUrl::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            if (! $url) {
                continue;
            }

            $value   = $property->getValue($this);
            $default = $property->hasDefaultValue() ? $property->getDefaultValue() : null;

            // يُهمَل ما يساوي الافتراضي وحده — فلترٌ افتراضيه غير فارغ (حالة العاملين «على رأس العمل»)
            // ثم فُرِّغ يُحفظ فارغاً، وإلا رجع الرجوعُ إلى الافتراضي لا إلى اختيار المستخدم
            $empty = fn ($v) => $v === null || $v === '' || $v === false || $v === [];
            if ($value === $default || ($empty($value) && $empty($default))) {
                continue;
            }

            $query[$url->newInstance()->as ?? $property->getName()] = is_bool($value) ? (int) $value : ($value ?? '');
        }

        if (method_exists($this, 'getPage') && $this->getPage() > 1) {
            $query['page'] = $this->getPage();
        }

        return $query;
    }
}
