<?php

namespace App\Livewire\Roles;

use App\Models\Warehouse;
use App\Support\PermissionGroups;
use App\Support\WarehouseScope;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('تعديل الدور')]
class Edit extends Component
{
    public Role $role;
    public string $name = '';
    public int $level = 1;
    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->role                = $role;
        $this->name                = $role->name;
        $this->level               = $role->level ?? 1;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    /** مودال تأكيد الربط التلقائي — يظهر قبل الحفظ لا بعده. */
    public bool $showLinkConfirm = false;

    /**
     * الربط التلقائي لمخازن مستخدمي الدور.
     *
     * ⚠️ **الصلاحية تُمنح بالدور والنطاق يُربط بالمستخدم** — فتعديلُ الدور
     *    وحده يُخرج لمستخدميه فرعاً فارغاً (الفراغ = لا يرى شيئاً، لا الكل).
     *    وهذا الربط يسدّ الفجوة بلا أن يُلغي القاعدة: يقترح ما يقترحه فورم
     *    المستخدم بالضبط، ويعرضه للتأكيد قبل التنفيذ.
     *
     * @return array<int, array{user: \App\Models\User, warehouses: \Illuminate\Support\Collection}>
     */
    public function linkCandidates(): array
    {
        if (! PermissionGroups::needsWarehouses($this->selectedPermissions)) {
            return [];
        }

        $candidates = [];

        foreach ($this->role->users()->with(['governorates', 'warehouses'])->get() as $user) {
            // ⚠️ مَن له ربطٌ قائم أو «كل المخازن» لا يُمسّ — الربط لا يمحو
            //    تعديلاً بيد المدير، ولذلك يُقصر على مَن لا نطاق له بعد
            if ($user->all_warehouses || $user->warehouses->isNotEmpty()) {
                continue;
            }

            $ids = WarehouseScope::warehouseIdsForGovernorates($user->governorates->pluck('id')->all());

            $candidates[] = [
                'user'       => $user,
                // ⚠️ `warehouses.id` مؤهَّلاً: `ordered()` يضمّ الأنواع والمحافظات
                //    ولكلٍّ عمود `id`، فالمجرَّد ملتبس (نظير `items.is_active`)
                'warehouses' => Warehouse::whereIn('warehouses.id', $ids)->ordered()->get(),
            ];
        }

        return $candidates;
    }

    /** يطلب التأكيد إن كان ثمّ مَن يُربط، وإلا حفظ مباشرةً. */
    public function save(): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255', "unique:roles,name,{$this->role->id}"],
            'level'               => ['required', 'integer', 'between:1,3'],
            'selectedPermissions' => ['array'],
        ]);

        if ($this->linkCandidates() !== []) {
            $this->showLinkConfirm = true;

            return;
        }

        $this->persist(link: false);
    }

    public function saveAndLink(): void
    {
        $this->persist(link: true);
    }

    public function saveWithoutLink(): void
    {
        $this->persist(link: false);
    }

    protected function persist(bool $link): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255', "unique:roles,name,{$this->role->id}"],
            'level'               => ['required', 'integer', 'between:1,3'],
            'selectedPermissions' => ['array'],
        ]);

        // ⚠️ المرشّحون يُحسبوا **قبل** المزامنة: بعدها تصير الصلاحيات هي
        //    المعروضة على أي حال، لكن الحساب المبكر يبقي القائمة هي التي
        //    عُرضت في المودال — فلا يُربط أحدٌ لم يره المدير.
        $candidates = $link ? $this->linkCandidates() : [];

        if ($this->role->name !== 'super-admin') {
            $this->role->name = $this->name;
        }

        $this->role->level = $this->level;
        $this->role->save();

        $this->role->syncPermissions($this->selectedPermissions);

        $linked = 0;

        foreach ($candidates as $candidate) {
            $ids = $candidate['warehouses']->pluck('id')->all();

            if ($ids === []) {
                // مستخدمٌ بلا محافظات، أو محافظاتُه بلا مخازن («مكتب تملك
                // الاجانب») — يبقى بلا نطاق، ويُبلَّغ به في الحصيلة لا يُسكَت عنه
                continue;
            }

            // ⚠️ `syncWithoutDetaching` لا `sync`: المرشّح بلا ربطٍ أصلاً،
            //    لكن الصيغة تبقى آمنة لو تغيّر الشرط يوماً
            $candidate['user']->warehouses()->syncWithoutDetaching($ids);
            $linked++;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->event('updated')
            ->withProperties([
                'name'            => $this->role->name,
                'warehouse_links' => $linked,
            ])
            ->log('تعديل دور');

        Flux::toast(variant: 'success', text: $linked > 0
            ? __('home.role_updated_with_links', ['count' => $linked])
            : __('home.role_updated'));

        $this->redirect(route('roles.index'), navigate: true);
    }

    public function render()
    {
        $this->ensurePermissions();
        $permissions = Permission::all();

        return view('livewire.roles.edit', [
            'permissions'     => $permissions,
            // تُحسب عند العرض ليطابق المودالُ ما سيقع فعلاً عند التأكيد
            'linkCandidates'  => $this->showLinkConfirm ? $this->linkCandidates() : [],
        ]);
    }

    private function ensurePermissions(): void
    {
        foreach (['vehicles.index', 'vehicles.view', 'vehicles.create', 'vehicles.edit', 'vehicles.delete', 'vehicles.export', 'offices.phone-directory', 'governorates.index', 'governorates.create', 'governorates.edit', 'governorates.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
