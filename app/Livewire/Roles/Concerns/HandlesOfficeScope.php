<?php

namespace App\Livewire\Roles\Concerns;

use App\Models\LocationDescription;
use App\Models\OfficeType;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * قصر الدور على أنواع مقرات و/أو أوصاف مواقع (دور «حياة كريمة») — مشترك بين إنشاء الدور وتعديله.
 * المعنى والحساب في `App\Support\OfficeScope`.
 */
trait HandlesOfficeScope
{
    /** @var list<int|string> */
    public array $officeTypeIds = [];

    /** @var list<int|string> */
    public array $locationDescriptionIds = [];

    protected function loadOfficeScope(Role $role): void
    {
        $this->officeTypeIds = DB::table('office_type_role')->where('role_id', $role->id)
            ->pluck('office_type_id')->map(fn ($id) => (string) $id)->all();
        $this->locationDescriptionIds = DB::table('location_description_role')->where('role_id', $role->id)
            ->pluck('location_description_id')->map(fn ($id) => (string) $id)->all();
    }

    protected function officeScopeRules(): array
    {
        return [
            'officeTypeIds'            => ['array'],
            'officeTypeIds.*'          => ['integer', 'exists:office_types,id'],
            'locationDescriptionIds'   => ['array'],
            'locationDescriptionIds.*' => ['integer', 'exists:location_descriptions,id'],
        ];
    }

    protected function syncOfficeScope(Role $role): void
    {
        DB::transaction(function () use ($role) {
            DB::table('office_type_role')->where('role_id', $role->id)->delete();
            DB::table('location_description_role')->where('role_id', $role->id)->delete();

            DB::table('office_type_role')->insert(array_map(
                fn ($id) => ['role_id' => $role->id, 'office_type_id' => (int) $id],
                array_values(array_unique($this->officeTypeIds)),
            ));
            DB::table('location_description_role')->insert(array_map(
                fn ($id) => ['role_id' => $role->id, 'location_description_id' => (int) $id],
                array_values(array_unique($this->locationDescriptionIds)),
            ));
        });
    }

    protected function officeScopeOptions(): array
    {
        return [
            'scopeOfficeTypes'           => OfficeType::orderBy('name')->get(['id', 'name']),
            'scopeLocationDescriptions'  => LocationDescription::orderBy('name')->get(['id', 'name']),
        ];
    }
}
