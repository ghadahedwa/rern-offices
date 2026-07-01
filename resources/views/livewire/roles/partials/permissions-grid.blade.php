@php
    $labels = [
        'index'          => 'عرض القوائم',
        'view'           => 'عرض السجل',
        'create'         => 'الإضافة',
        'edit'           => 'التعديل',
        'delete'         => 'الحذف',
        'export-report'  => 'تصدير التقارير',
        'manage-users'   => 'إدارة المستخدمين',
        'manage-roles'   => 'إدارة الأدوار',
        'offices.index'  => 'عرض قائمة المقرات',
        'offices.view'   => 'عرض تفاصيل المقر',
        'offices.create' => 'إضافة مقر',
        'offices.edit'   => 'تعديل مقر',
        'offices.delete' => 'حذف مقر',
        'offices.export' => 'تصدير بيانات المقرات',
        'claims.index'   => 'عرض المطالبات',
        'claims.edit'    => 'تعديل المطالبات',

        'vehicles.index'  => 'عرض قائمة السيارات',
        'vehicles.view'   => 'عرض تفاصيل السيارة',
        'vehicles.create' => 'إضافة سيارة',
        'vehicles.edit'   => 'تعديل سيارة',
        'vehicles.delete' => 'حذف سيارة',
        'vehicles.export' => 'تصدير بيانات السيارات',
    ];

    $groups = [
        'عام'               => $permissions->filter(fn($p) => !str_contains($p->name, '.')),
        'المقرات'           => $permissions->filter(fn($p) => str_starts_with($p->name, 'offices.')),
        'السيارات المتنقلة' => $permissions->filter(fn($p) => str_starts_with($p->name, 'vehicles.')),
        'المطالبات'         => $permissions->filter(fn($p) => str_starts_with($p->name, 'claims.')),
    ];
@endphp

<div class="flex flex-col gap-5">
    @foreach($groups as $groupName => $groupPermissions)
        @if($groupPermissions->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">

                {{-- Group Header with Select All --}}
                <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700"
                     x-data="{
                         get allChecked() {
                             return @js($groupPermissions->pluck('name')->toArray()).every(p => $wire.selectedPermissions.includes(p));
                         },
                         toggleAll() {
                             const names = @js($groupPermissions->pluck('name')->toArray());
                             if (this.allChecked) {
                                 $wire.selectedPermissions = $wire.selectedPermissions.filter(p => !names.includes(p));
                             } else {
                                 const merged = [...new Set([...$wire.selectedPermissions, ...names])];
                                 $wire.selectedPermissions = merged;
                             }
                         }
                     }">
                    <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                        {{ $groupName }}
                    </span>
                    <button type="button" @click="toggleAll()"
                            class="text-xs text-[#c9a847] hover:text-[#b8962e] transition font-medium">
                        <span x-text="allChecked ? 'إلغاء الكل' : 'تحديد الكل'"></span>
                    </button>
                </div>

                {{-- Permissions --}}
                <div class="grid grid-cols-2 gap-px bg-zinc-100 dark:bg-zinc-700">
                    @foreach($groupPermissions as $permission)
                        <label class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition">
                            <input
                                type="checkbox"
                                wire:model="selectedPermissions"
                                value="{{ $permission->name }}"
                                class="w-4 h-4 rounded accent-[#c9a847] shrink-0"
                            />
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm text-zinc-800 dark:text-zinc-100 leading-tight">
                                    {{ $labels[$permission->name] ?? $permission->name }}
                                </span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500 font-mono truncate">
                                    {{ $permission->name }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>

            </div>
        @endif
    @endforeach
</div>
