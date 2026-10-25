{{-- قصر الدور على أنواع مقرات و/أو أوصاف مواقع (دور «حياة كريمة») — المعنى في App\Support\OfficeScope.
     مطويٌّ افتراضياً بسطر ملخّص: أغلب الأدوار بلا قيد، والقائمتان المفتوحتان كانتا تطيلان كل فورم دور.
     ⚠️ يُفتح تلقائياً إن كان على الدور قيد (أو خطأ تحقق) — قيدٌ مخفيّ يُنسى.
     وداخله الخيارات كلها ظاهرة بلا تمرير: صندوقٌ بسكرول يُخفي ما اختير. --}}
@if(! (isset($role) && $role->name === 'super-admin'))
    @php
        $pickedTypes = $scopeOfficeTypes->whereIn('id', array_map('intval', $officeTypeIds))->pluck('name');
        $pickedDescs = $scopeLocationDescriptions->whereIn('id', array_map('intval', $locationDescriptionIds))->pluck('name');
        $restricted  = $pickedTypes->isNotEmpty() || $pickedDescs->isNotEmpty();
        $startOpen   = $restricted || $errors->has('officeTypeIds.*') || $errors->has('locationDescriptionIds.*');
    @endphp
    <div class="flex flex-col gap-2" x-data="{ open: @js($startOpen) }">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.role_office_scope') }}</label>

        {{-- سطر الملخّص — يفتح القسم ويطويه --}}
        <button type="button" @click="open = ! open"
                class="flex items-center justify-between gap-3 w-full rounded-lg border px-3 py-2.5 text-start transition
                    {{ $restricted
                        ? 'border-[#c9a847] bg-[#c9a847]/[0.07] dark:bg-[#c9a847]/10'
                        : 'border-zinc-300 dark:border-zinc-600 hover:border-[#c9a847]/60 hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}">
            <span class="min-w-0 text-sm {{ $restricted ? 'text-[#8a6f1f] dark:text-[#d8b856] font-semibold' : 'text-zinc-600 dark:text-zinc-300' }}">
                @if($restricted)
                    @if($pickedTypes->isNotEmpty())
                        <span class="block truncate" title="{{ $pickedTypes->join('، ') }}">{{ __('home.role_office_scope_summary_types', ['names' => $pickedTypes->join('، ')]) }}</span>
                    @endif
                    @if($pickedDescs->isNotEmpty())
                        <span class="block truncate" title="{{ $pickedDescs->join('، ') }}">{{ __('home.role_office_scope_summary_descriptions', ['names' => $pickedDescs->join('، ')]) }}</span>
                    @endif
                @else
                    {{ __('home.role_office_scope_summary_all') }}
                @endif
            </span>
            <svg class="w-4 h-4 shrink-0 text-zinc-400 transition-transform" :class="open && 'rotate-180'"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" @if(! $startOpen) style="display: none" @endif class="flex flex-col gap-2">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.role_office_scope_hint') }}</p>

            @foreach([
                ['model' => 'officeTypeIds',          'title' => 'home.role_office_scope_types',        'items' => $scopeOfficeTypes],
                ['model' => 'locationDescriptionIds', 'title' => 'home.role_office_scope_descriptions', 'items' => $scopeLocationDescriptions],
            ] as $group)
                @php $count = count($this->{$group['model']}); @endphp
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-[13px] font-semibold text-zinc-700 dark:text-zinc-200">{{ __($group['title']) }}</span>
                        <span class="text-[11px] {{ $count ? 'text-[#8a6f1f] dark:text-[#d8b856] font-semibold' : 'text-zinc-400 dark:text-zinc-500' }}">
                            {{ $count ? __('home.role_office_scope_selected', ['count' => $count]) : __('home.role_office_scope_all') }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1.5">
                        @foreach($group['items'] as $item)
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer min-w-0">
                                <input type="checkbox" wire:model.live="{{ $group['model'] }}" value="{{ $item->id }}"
                                       class="w-3.5 h-3.5 accent-[#c9a847] shrink-0" />
                                <span class="truncate" title="{{ $item->name }}">{{ $item->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @error('officeTypeIds.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('locationDescriptionIds.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>
    </div>
@endif
