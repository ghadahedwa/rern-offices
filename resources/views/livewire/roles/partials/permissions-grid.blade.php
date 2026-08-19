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
        'offices.phone-directory' => 'دليل الهاتف للمقرات',
        'offices.settings' => 'إدارة إعدادات المقرات (القوائم المرجعية)',
        'governorates.index'  => 'عرض المحافظات',
        'governorates.create' => 'إضافة محافظة',
        'governorates.edit'   => 'تعديل محافظة',
        'governorates.delete' => 'حذف محافظة',
        'claims.index'   => 'عرض المطالبات',
        'claims.edit'    => 'تعديل المطالبات',
        'claims.export'  => 'تصدير بيانات المطالبات',

        'vehicles.index'  => 'عرض قائمة السيارات',
        'vehicles.view'   => 'عرض تفاصيل السيارة',
        'vehicles.create' => 'إضافة سيارة',
        'vehicles.edit'   => 'تعديل سيارة',
        'vehicles.delete' => 'حذف سيارة',
        'vehicles.export' => 'تصدير بيانات السيارات',

        'meetings.index'  => 'عرض أجندة الاجتماعات',
        'meetings.view'   => 'عرض اجتماع',
        'meetings.create' => 'إضافة اجتماع',
        'meetings.edit'   => 'تعديل اجتماع',
        'meetings.delete' => 'حذف اجتماع',

        'correspondence.index'       => 'عرض صناديق المراسلات',
        'correspondence.view'        => 'عرض تفاصيل مكاتبة',
        'correspondence.create'      => 'إنشاء مكاتبة والرد عليها',
        'correspondence.approve'     => 'الاعتماد والإرسال باسم الجهة',
        'correspondence.delegate'    => 'إدارة التفويض (الإنابة)',
        'correspondence.assign'      => 'إنشاء التكليفات ومتابعتها',
        'correspondence.share'       => 'مشاركة مكاتبة مع غير مستلم',
        'correspondence.delete'      => 'حذف مكاتبة',
        'correspondence.export'      => 'تصدير وطباعة المكاتبات',
        'correspondence.attachments' => 'رفع وتنزيل مرفقات المراسلات',
        'correspondence.stamp'       => 'ختم مستند بالتوقيع',
        'correspondence.settings'    => 'إدارة أطراف المراسلات',

        'warehouses.index'       => 'عرض قوائم المخازن والأرصدة والحركات',
        'warehouses.view'        => 'عرض تفاصيل حركة/مستند',
        'warehouses.create'      => 'إضافة (رصيد افتتاحي / وارد / نقل)',
        'warehouses.edit'        => 'تعديل',
        'warehouses.delete'      => 'حذف (بإرجاع الرصيد)',
        'warehouses.export'      => 'تصدير تقارير المخازن',
        'warehouses.attachments' => 'عرض وتنزيل مرفقات المخازن',
        'warehouses.settings'    => 'إدارة إعدادات المخازن (المخازن / الأصناف / الأنواع / الوحدات)',
    ];

    // الصلاحيات مجمّعة حسب الفرع ← من المصدر الواحد App\Support\PermissionGroups،
    // وهو نفسه الذي تقرأه الأقسام المشروطة في فورم المستخدم. تعريفان منفصلان يفترقان.
    // الصلاحيات العامة بلا namespace (index/view/... و manage-*) مخفية — ميتة لا يفحصها الكود
    $branches = \App\Support\PermissionGroups::group($permissions);
@endphp

<div class="flex flex-col gap-6">
    @foreach($branches as $branchKey => $groups)
        @php
            $branchPermNames = collect($groups)->flatMap(fn($g) => $g->pluck('name'))->unique()->values();
        @endphp
        @if($branchPermNames->isNotEmpty())
            <div x-data="{
                    open: true,
                    branchNames: @js($branchPermNames->toArray()),
                    get allChecked() { return this.branchNames.every(p => $wire.selectedPermissions.includes(p)); },
                    toggleAll() {
                        if (this.allChecked) {
                            $wire.selectedPermissions = $wire.selectedPermissions.filter(p => !this.branchNames.includes(p));
                        } else {
                            $wire.selectedPermissions = [...new Set([...$wire.selectedPermissions, ...this.branchNames])];
                        }
                    }
                 }"
                 class="rounded-xl border border-zinc-300 dark:border-zinc-600 overflow-hidden">

                {{-- رأس الفرع --}}
                <div class="flex items-center justify-between px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 flex-1 text-start">
                        <div class="w-1.5 h-5 bg-[#c9a847] rounded-full"></div>
                        <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ __($branchKey) }}</span>
                        <svg class="w-4 h-4 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                            <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                        </svg>
                    </button>
                    <button type="button" @click="toggleAll()" class="text-xs text-[#c9a847] hover:text-[#b8962e] font-medium shrink-0">
                        <span x-text="allChecked ? 'إلغاء كل الفرع' : 'تحديد كل الفرع'"></span>
                    </button>
                </div>

                {{-- موديولات الفرع --}}
                <div x-show="open" x-transition class="p-4 flex flex-col gap-5">
                    @foreach($groups as $groupName => $groupPermissions)
                        @if($groupPermissions->isNotEmpty())
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden"
                                 x-data="{
                                     get allChecked() {
                                         return @js($groupPermissions->pluck('name')->toArray()).every(p => $wire.selectedPermissions.includes(p));
                                     },
                                     toggleAll() {
                                         const names = @js($groupPermissions->pluck('name')->toArray());
                                         if (this.allChecked) {
                                             $wire.selectedPermissions = $wire.selectedPermissions.filter(p => !names.includes(p));
                                         } else {
                                             $wire.selectedPermissions = [...new Set([...$wire.selectedPermissions, ...names])];
                                         }
                                     }
                                 }">

                                {{-- رأس المجموعة مع تحديد الكل --}}
                                <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                                    <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ $groupName }}
                                    </span>
                                    <button type="button" @click="toggleAll()"
                                            class="text-xs text-[#c9a847] hover:text-[#b8962e] transition font-medium">
                                        <span x-text="allChecked ? 'إلغاء الكل' : 'تحديد الكل'"></span>
                                    </button>
                                </div>

                                {{-- الصلاحيات --}}
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
            </div>
        @endif
    @endforeach
</div>
