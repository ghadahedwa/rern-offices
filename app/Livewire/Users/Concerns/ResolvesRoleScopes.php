<?php

namespace App\Livewire\Users\Concerns;

use App\Support\PermissionGroups;
use App\Support\WarehouseScope;
use Spatie\Permission\Models\Role;

/**
 * منطق الأقسام المشروطة في فورم المستخدم — مشترك بين Create وEdit.
 *
 * الفورم لا يسأل «هو مراسلات أم مقرات؟» بل «صلاحيات دوره واقعة تحت أي عنوان؟»،
 * فلا حقل «نوع» على المستخدم ولا على الدور. والتجميع من App\Support\PermissionGroups
 * وهو نفسه الذي تقرأه شبكة الأدوار.
 */
trait ResolvesRoleScopes
{
    /**
     * نسخة ظِلّ من المحافظات المختارة — يُقاس عليها الفارق عند كل تغيير.
     *
     * ⚠️ Livewire يعطي القيمة الجديدة لا القديمة، ومن غير الظِلّ لا يُعرف
     *    **أي محافظةٍ أُضيفت وأيها نُزعت** — فيتعذّر ملء المخازن ملءً تفاضلياً،
     *    ولا يبقى إلا إعادة الملء كاملاً، وهي تمحو تعديل المستخدم اليدوي.
     */
    public array $governoratesShadow = [];

    /** أسماء صلاحيات الدور المختار. */
    public function rolePermissionNames(): array
    {
        if ($this->role === '') {
            return [];
        }

        return Role::with('permissions')
            ->where('name', $this->role)
            ->first()
            ?->permissions
            ->pluck('name')
            ->all() ?? [];
    }

    /** يُخلي الأقسام التي لم تعد تنطبق بمجرّد تغيير الدور — فلا يبقى إدخال معلَّق من دور سابق. */
    public function updatedRole(): void
    {
        $permissions = $this->rolePermissionNames();

        if (! PermissionGroups::needsGovernorates($permissions)) {
            $this->selectedGovernorates = [];
            $this->governoratesShadow   = [];
        }

        if (! PermissionGroups::needsEntity($permissions)) {
            $this->correspondence_entity_id = '';
            $this->job_title                = '';
        }

        if (! PermissionGroups::needsWarehouses($permissions)) {
            $this->selectedWarehouses = [];
            $this->allWarehouses      = false;
        }
    }

    /**
     * ملءٌ تفاضلي لمخازن المحافظات المختارة — **اقتراحٌ ظاهر لا اشتقاق صامت**.
     *
     * أُضيفت محافظة → تُعلَّم مخازنها · نُزعت → تُنزع علامتها. وما عدَّله
     * المستخدم بيده يثبت، لأن الملء لا يُعيد بناء القائمة بل يُعدّل طرفيها.
     *
     * ⚠️ والفارق عن الاشتقاق وقت الاستعلام (المرفوض) أن هذا **يُرى ويُعدَّل
     *    قبل الحفظ**: لا يُمنح حقُّ مخزنٍ من حيث لا يدري المدير.
     * ⚠️ و«المخزن الرئيسي بالمصلحة» بلا محافظة، فلا يبلغه هذا الملء أبداً —
     *    ولا يُختار إلا يدوياً، وهو الصواب.
     */
    public function updatedSelectedGovernorates(): void
    {
        $new = array_map('intval', $this->selectedGovernorates);
        $old = array_map('intval', $this->governoratesShadow);

        $added   = array_diff($new, $old);
        $removed = array_diff($old, $new);

        $current = array_map('intval', $this->selectedWarehouses);

        if ($added) {
            $current = array_merge($current, self::warehouseIdsOfGovernorates($added));
        }

        if ($removed) {
            $current = array_diff($current, self::warehouseIdsOfGovernorates($removed));
        }

        $this->selectedWarehouses = array_values(array_unique($current));
        $this->governoratesShadow = $new;
    }

    /**
     * قاعدة «مخازن محافظاته» — تعريفها الواحد في `WarehouseScope`، تقرأه هذه
     * الشاشة وشاشةُ الأدوار (الربط الجماعي) معاً.
     *
     * @param  array<int, int>  $governorateIds
     */
    protected static function warehouseIdsOfGovernorates(array $governorateIds): array
    {
        return WarehouseScope::warehouseIdsForGovernorates($governorateIds);
    }

    /** الطرف إلزامي لمن دوره يستلزمه — لا مكاتبة بلا دفتر. */
    protected function scopeRules(): array
    {
        $permissions = $this->rolePermissionNames();

        return [
            'correspondence_entity_id' => PermissionGroups::needsEntity($permissions)
                ? ['required', 'exists:correspondence_entities,id']
                : ['nullable'],
            'job_title' => ['nullable', 'string', 'max:255'],

            // ⚠️ نطاق المخازن إلزامي لمن دوره يستلزمه: القائمة الفارغة تعني
            //    «لا يرى شيئاً»، فحفظُه بلا اختيار يُخرج له شاشةً فارغة أبداً
            //    ولا يعرف لماذا. (وقع حرفياً في موديول رأي المواطن.)
            'selectedWarehouses' => PermissionGroups::needsWarehouses($permissions) && ! $this->allWarehouses
                ? ['required', 'array', 'min:1']
                : ['array'],
        ];
    }

    /**
     * القيم التي تُحفَظ فعلاً — ⚠️ النطاق غير المنطبق يُصفَّر لا يُترك كما هو:
     * القيمة تصل من العميل، ودور بلا صلاحيات مقرات لا يحتفظ بمحافظات من دور سابق.
     *
     * @return array{governorates: array, entity_id: ?int, job_title: ?string, warehouses: array, all_warehouses: bool}
     */
    protected function resolvedScope(): array
    {
        $permissions = $this->rolePermissionNames();

        $needsGovernorates = PermissionGroups::needsGovernorates($permissions);
        $needsEntity       = PermissionGroups::needsEntity($permissions);
        $needsWarehouses   = PermissionGroups::needsWarehouses($permissions);

        $allWarehouses = $needsWarehouses && $this->allWarehouses;

        return [
            'governorates' => $needsGovernorates ? $this->selectedGovernorates : [],
            'entity_id'    => $needsEntity && $this->correspondence_entity_id !== ''
                ? (int) $this->correspondence_entity_id
                : null,
            'job_title'    => $needsEntity ? ($this->job_title ?: null) : null,
            // «الكل» و«قائمة» لا يجتمعان: تخزينُهما معاً يترك قائمةً بائدة
            // تظهر في الفورم حين يُنزع «الكل» فتوهم بنطاقٍ لم يُقصد
            'warehouses'     => $needsWarehouses && ! $allWarehouses ? $this->selectedWarehouses : [],
            'all_warehouses' => $allWarehouses,
        ];
    }
}
