{{-- قصر الدور على أنواع مقرات و/أو أوصاف مواقع (دور «حياة كريمة») — المعنى في App\Support\OfficeScope.
     الخيارات كلها ظاهرة بلا تمرير: صندوقٌ بسكرول يُخفي ما اختير. --}}
@if(! (isset($role) && $role->name === 'super-admin'))
    <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.role_office_scope') }}</label>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 -mt-1">{{ __('home.role_office_scope_hint') }}</p>

        @foreach([
            ['model' => 'officeTypeIds',          'title' => 'home.role_office_scope_types',        'items' => $scopeOfficeTypes],
            ['model' => 'locationDescriptionIds', 'title' => 'home.role_office_scope_descriptions', 'items' => $scopeLocationDescriptions],
        ] as $group)
            @php $count = count($this->{$group['model']}); @endphp
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 mt-1">
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
@endif
