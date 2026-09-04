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
        // الوصف يذكر أثر كل صلاحية لا اسمها: الافتتاحي **يكتب** الرصيد كتابةً،
        // فمَن يمنحه يجب أن يقرأ ذلك في الشبكة لا أن يستنتجه
        'warehouses.opening'     => 'ضبط الرصيد الافتتاحي (يكتب الرصيد كتابةً)',
        'warehouses.incoming'    => 'تسجيل الوارد على المخزن الرئيسي',
        'warehouses.transfer'    => 'تسجيل النقل بين المخازن',
        'warehouses.issue'       => 'تسجيل الصرف للمقرات (يُنقص المخزن)',
        'warehouses.delete'      => 'حذف (بإرجاع الرصيد)',
        'warehouses.export'      => 'تصدير تقارير المخازن',
        'warehouses.attachments' => 'عرض وتنزيل مرفقات المخازن',
        'warehouses.settings'    => 'إدارة إعدادات المخازن (المخازن / الأصناف / الأنواع / الوحدات)',

        // النطاق محافظة: صاحب الصلاحية يرى بيانات محافظاته وحدها
        'feedback.view'   => 'عرض نتائج رأي المواطن (لوحة / تقييمات / مقترحات)',
        'feedback.export' => 'تصدير وطباعة نتائج رأي المواطن',
        'feedback.delete' => 'حذف تقييمات ومقترحات المواطنين',
        // شاشة أمنية (سبب الرفض/الـIP) لا تقريرية — فصلاحيتها مستقلة عن العرض
        'feedback.rejected' => 'عرض المحاولات المرفوضة على البوابة',

        // النطاق محافظة: صاحب الصلاحية يرى مدخلي مقرات محافظاته وحدها.
        // والوصف يذكر الأثر: التسجيل فعلٌ يومي مستقل عن تعديل بيانات المدخلين
        'data-entry.index'      => 'عرض مدخلي البيانات وتقارير الحضور',
        'data-entry.attendance' => 'تسجيل الحضور والغياب والإجازات',
        'data-entry.create'     => 'إضافة مدخل بيانات',
        'data-entry.edit'       => 'تعديل بيانات مدخل',
        'data-entry.export'     => 'تصدير وطباعة تقارير الحضور',
        'data-entry.delete'     => 'حذف مدخل بيانات',
        // تحت «إدارة النظام» — قائمة مرجعية بلا نطاق محافظات
        'data-entry.settings'   => 'إدارة حالات الحضور',
    ];

    // الصلاحيات مجمّعة حسب الفرع ← من المصدر الواحد App\Support\PermissionGroups،
    // وهو نفسه الذي تقرأه الأقسام المشروطة في فورم المستخدم. تعريفان منفصلان يفترقان.
    // الصلاحيات العامة بلا namespace (index/view/... و manage-*) مخفية — ميتة لا يفحصها الكود
    $branches = \App\Support\PermissionGroups::group($permissions);
@endphp

<div class="flex flex-col gap-6" x-data>
    {{-- فتح/طي الكل — الفروع مقفولة افتراضياً (ستة فروع تطوّل الصفحة بلا طائل)،
         فلازم طريقة واحدة تفتحها كلها لمن يريد المسح البصري. --}}
    <div class="flex items-center justify-end gap-3 -mb-2">
        <button type="button" @click="$dispatch('branches-toggle', { open: true })"
                class="text-xs text-[#c9a847] hover:text-[#b8962e] transition font-medium">
            فتح الكل
        </button>
        <span class="text-zinc-300 dark:text-zinc-600">·</span>
        <button type="button" @click="$dispatch('branches-toggle', { open: false })"
                class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition font-medium">
            طي الكل
        </button>
    </div>

    @foreach($branches as $branchKey => $groups)
        @php
            $branchPermNames = collect($groups)->flatMap(fn($g) => $g->pluck('name'))->unique()->values();
            // الفرع الذي للدور فيه صلاحية يُفتح: في شاشة التعديل تظهر صورة الدور
            // بلا تنقيب، وفي شاشة الإضافة (بلا تحديد) تبقى الفروع كلها مطوية.
            $branchOpen = $branchPermNames->intersect($selectedPermissions ?? [])->isNotEmpty();
        @endphp
        @if($branchPermNames->isNotEmpty())
            <div x-data="{
                    open: @js($branchOpen),
                    branchNames: @js($branchPermNames->toArray()),
                    get allChecked() { return this.branchNames.every(p => $wire.selectedPermissions.includes(p)); },
                    // عدّاد المطوي: بلا هذا يصير الفرع المقفول صندوقاً أسود — لا تعرف
                    // أفيه صلاحيات للدور أم لا إلا بفتح الستة واحداً واحداً.
                    get selectedCount() { return this.branchNames.filter(p => $wire.selectedPermissions.includes(p)).length; },
                    toggleAll() {
                        if (this.allChecked) {
                            $wire.selectedPermissions = $wire.selectedPermissions.filter(p => !this.branchNames.includes(p));
                        } else {
                            $wire.selectedPermissions = [...new Set([...$wire.selectedPermissions, ...this.branchNames])];
                        }
                    }
                 }"
                 x-on:branches-toggle.window="open = $event.detail.open"
                 class="rounded-xl border border-zinc-300 dark:border-zinc-600 overflow-hidden">

                {{-- رأس الفرع — خلفية ذهبية خفيفة.
                     ⚠️ كان `bg-zinc-100 dark:bg-zinc-800`، ورأس المجموعة `bg-zinc-50 dark:bg-zinc-800`:
                     أي **لونان متطابقان في الوضع الليلي**، فيختفي التدرّج بين الفرع ومجموعاته تماماً.
                     الذهبي لون النظام أصلاً، فيفرّق المستويين بلا إدخال لون جديد. --}}
                <div class="flex items-center justify-between px-4 py-3 bg-[#c9a847]/[0.14] dark:bg-[#c9a847]/16 border-b border-[#c9a847]/30">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 flex-1 text-start">
                        <div class="w-1.5 h-5 bg-[#c9a847] rounded-full"></div>
                        <span class="text-sm font-bold text-[#7a6215] dark:text-[#e0c46a]">{{ __($branchKey) }}</span>
                        <span x-show="selectedCount > 0"
                              x-text="selectedCount + ' من ' + {{ $branchPermNames->count() }}"
                              class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#c9a847] text-white font-medium shrink-0"></span>
                        <svg class="w-4 h-4 text-[#c9a847]/70 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                            <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                        </svg>
                    </button>
                    <button type="button" @click="toggleAll()" class="text-xs text-[#8a6f1f] dark:text-[#d8b856] hover:underline font-medium shrink-0">
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
                                {{-- أخفّ من رأس الفرع: zinc-800/50 لا zinc-800، وإلا تساوى المستويان ليلاً --}}
                                <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                                    <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ $groupName }}
                                    </span>
                                    <button type="button" @click="toggleAll()"
                                            class="text-xs text-[#c9a847] hover:text-[#b8962e] transition font-medium">
                                        <span x-text="allChecked ? 'إلغاء الكل' : 'تحديد الكل'"></span>
                                    </button>
                                </div>

                                {{-- الصلاحيات --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-px bg-zinc-100 dark:bg-zinc-700">
                                    {{-- تدرّج الخط مقصود: الفرع text-sm bold ← المجموعة text-xs semibold
                                         ← التصريح text-[11px]. فالتصريح أصغر من عنوان مجموعته دائماً. --}}
                                    @foreach($groupPermissions as $permission)
                                        <label class="flex items-center gap-2.5 px-3 py-2.5 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition">
                                            <input
                                                type="checkbox"
                                                wire:model="selectedPermissions"
                                                value="{{ $permission->name }}"
                                                class="w-3.5 h-3.5 rounded accent-[#c9a847] shrink-0"
                                            />
                                            <div class="flex flex-col min-w-0">
                                                <span class="text-[11px] text-zinc-800 dark:text-zinc-100 leading-snug">
                                                    {{ $labels[$permission->name] ?? $permission->name }}
                                                </span>
                                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-mono truncate">
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
