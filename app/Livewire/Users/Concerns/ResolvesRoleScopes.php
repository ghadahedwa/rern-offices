<?php

namespace App\Livewire\Users\Concerns;

use App\Support\PermissionGroups;
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
        if (! PermissionGroups::needsGovernorates($this->rolePermissionNames())) {
            $this->selectedGovernorates = [];
        }

        if (! PermissionGroups::needsEntity($this->rolePermissionNames())) {
            $this->correspondence_entity_id = '';
            $this->job_title                = '';
        }
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
        ];
    }

    /**
     * القيم التي تُحفَظ فعلاً — ⚠️ النطاق غير المنطبق يُصفَّر لا يُترك كما هو:
     * القيمة تصل من العميل، ودور بلا صلاحيات مقرات لا يحتفظ بمحافظات من دور سابق.
     *
     * @return array{governorates: array, entity_id: ?int, job_title: ?string}
     */
    protected function resolvedScope(): array
    {
        $permissions = $this->rolePermissionNames();

        $needsGovernorates = PermissionGroups::needsGovernorates($permissions);
        $needsEntity       = PermissionGroups::needsEntity($permissions);

        return [
            'governorates' => $needsGovernorates ? $this->selectedGovernorates : [],
            'entity_id'    => $needsEntity && $this->correspondence_entity_id !== ''
                ? (int) $this->correspondence_entity_id
                : null,
            'job_title'    => $needsEntity ? ($this->job_title ?: null) : null,
        ];
    }
}
