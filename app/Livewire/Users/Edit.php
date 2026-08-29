<?php

namespace App\Livewire\Users;

use App\Models\CorrespondenceEntity;
use App\Models\Governorate;
use App\Models\Warehouse;
use App\Models\User;
use App\Support\PermissionGroups;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('تعديل مستخدم')]
class Edit extends Component
{
    use Concerns\ResolvesRoleScopes;

    public User $user;

    public string $name     = '';
    public string $username = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role     = '';

    /** نطاق المقرات */
    public array $selectedGovernorates = [];

    /** نطاق المخازن — القائمة الفارغة تعني «لا يرى شيئاً»، و allWarehouses تعني «بلا حدّ» */
    public array $selectedWarehouses = [];

    public bool $allWarehouses = false;

    /** نطاق المراسلات */
    public string $correspondence_entity_id = '';
    public string $job_title = '';

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($user->id === 1) {
            Flux::toast(variant: 'danger', text: __('home.cannot_edit_superadmin'));
            $this->redirect(route('users.index'), navigate: true);
            return;
        }

        $this->user                     = $user;
        $this->name                     = $user->name;
        $this->username                 = $user->username ?? '';
        $this->email                    = $user->email ?? '';
        $this->role                     = $user->roles->first()?->name ?? '';
        $this->selectedGovernorates     = $user->governorates->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->correspondence_entity_id = (string) ($user->correspondence_entity_id ?? '');
        $this->job_title                = $user->job_title ?? '';
        $this->selectedWarehouses       = $user->warehouses->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->allWarehouses            = (bool) $user->all_warehouses;

        // ⚠️ الظِلّ يُبدأ بالمحفوظ لا فارغاً: بدونه يُحسب كل محافظات المستخدم
        //    «مضافةً» عند أول تعديل، فتُعاد إليه مخازنُ نزعها بيده من قبل.
        $this->governoratesShadow = array_map('intval', $this->selectedGovernorates);
    }

    public function save(): void
    {
        $rules = [
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:255', "unique:users,username,{$this->user->id}"],
            'email'                => ['nullable', 'email', 'max:255'],
            'role'                 => ['required', 'exists:roles,name'],
            'selectedGovernorates' => ['array'],
            ...$this->scopeRules(),
        ];

        if ($this->password) {
            $rules['password'] = ['string', 'min:4', 'confirmed'];
        }

        $this->validate($rules);

        $scope = $this->resolvedScope();

        $data = [
            'name'                     => $this->name,
            'username'                 => $this->username,
            'email'                    => $this->email ?: null,
            'correspondence_entity_id' => $scope['entity_id'],
            'job_title'                => $scope['job_title'],
            'all_warehouses'           => $scope['all_warehouses'],
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $this->user->update($data);
        $this->user->syncRoles([$this->role]);
        $this->user->governorates()->sync($scope['governorates']);
        $this->user->warehouses()->sync($scope['warehouses']);

        Flux::toast(variant: 'success', text: __('home.user_updated'));
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.edit', [
            'roles'             => Role::all(),
            'governorates'      => Governorate::orderBy('order')->orderBy('id')->get(),
            'entities'          => CorrespondenceEntity::where('is_active', true)->orderBy('order')->orderBy('id')->get(),
            'needsGovernorates' => PermissionGroups::needsGovernorates($this->rolePermissionNames()),
            'needsEntity'       => PermissionGroups::needsEntity($this->rolePermissionNames()),
            'needsWarehouses'   => PermissionGroups::needsWarehouses($this->rolePermissionNames()),
            'warehouses'        => Warehouse::ordered()->get(),
        ]);
    }
}
