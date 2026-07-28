<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.offices') }}</h1>
        @if($canCreate)
            <a href="{{ route('offices.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_office') }}
            </a>
        @endif
    </div>

    {{-- Filters --}}
    @php $filterCls = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]'; @endphp
    <div class="space-y-3">
        <div class="grid grid-cols-4 gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.search') }}"
                   class="{{ $filterCls }}" />
            <select wire:model.live="governorate_id" class="{{ $filterCls }}">
                <option value="">{{ __('home.all_governorates') }}</option>
                @foreach ($governorates as $gov)
                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="type_id" class="{{ $filterCls }}">
                <option value="">— {{ __('home.office_type') }} —</option>
                @foreach ($officeTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="location_description_id" class="{{ $filterCls }}">
                <option value="">— {{ __('home.location_description') }} —</option>
                @foreach ($locationDescriptions as $desc)
                    <option value="{{ $desc->id }}">{{ $desc->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="$toggle('needs_visit')"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition
                    {{ $needs_visit
                        ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-900/20 dark:border-amber-600 dark:text-amber-400'
                        : 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 hover:border-amber-400 hover:text-amber-700' }}">
                <flux:icon.exclamation-triangle variant="outline" class="w-4 h-4" />
                {{ __('home.needs_visit_filter') }}
                @if($needs_visit)
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                @endif
            </button>

            <button wire:click="$toggle('is_vip')"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition
                    {{ $is_vip
                        ? 'bg-[#c9a847] border-[#c9a847] text-white shadow-sm'
                        : 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 hover:border-[#c9a847] hover:text-[#b8962e]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                {{ __('home.is_vip') }}
                @if($is_vip)
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                @endif
            </button>

            <button wire:click="$toggle('public_only')"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition
                    {{ $public_only
                        ? 'bg-[#c9a847] border-[#c9a847] text-white shadow-sm'
                        : 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 hover:border-[#c9a847] hover:text-[#b8962e]' }}">
                <flux:icon.eye variant="outline" class="w-4 h-4" />
                {{ __('home.public_visible') }}
                @if($public_only)
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                @endif
            </button>

            {{-- زر البحث المتقدم: خياراته لا تُحمَّل إلا عند الفتح --}}
            <button wire:click="toggleAdvanced"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition
                    {{ $showAdvanced
                        ? 'bg-[#c9a847]/10 border-[#c9a847] text-[#b8962e] dark:text-[#c9a847]'
                        : 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 hover:border-[#c9a847] hover:text-[#b8962e]' }}">
                <flux:icon.adjustments-horizontal variant="outline" class="w-4 h-4" />
                {{ __('home.advanced_search') }}
                <flux:icon.chevron-down variant="micro" class="w-3.5 h-3.5 transition {{ $showAdvanced ? 'rotate-180' : '' }}" />
            </button>
        </div>

        {{-- لوحة البحث المتقدم (كله single select، فوري) --}}
        @if($showAdvanced)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <select wire:model.live="work_system_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.work_system') }} —</option>
                    @foreach ($workSystems as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="working_hours_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.working_hours') }} —</option>
                    @foreach ($workingHoursOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="working_days" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.working_days') }} —</option>
                    <option value="full_week">{{ __('home.working_days_full_week') }}</option>
                    <option value="one_day">{{ __('home.working_days_one_day') }}</option>
                    <option value="two_days">{{ __('home.working_days_two_days') }}</option>
                    <option value="three_days">{{ __('home.working_days_three_days') }}</option>
                    <option value="four_days">{{ __('home.working_days_four_days') }}</option>
                    <option value="five_days">{{ __('home.working_days_five_days') }}</option>
                </select>
                <select wire:model.live="contractual_status_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.contractual_status') }} —</option>
                    @foreach ($contractualStatuses as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="structural_condition_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.structural_condition') }} —</option>
                    @foreach ($structuralConditions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="connection_type_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.connection_type') }} —</option>
                    @foreach ($connections as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="microfilm_option_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.microfilm_option') }} —</option>
                    @foreach ($microfilmOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="disabilities_access_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.disabilities_access_label') }} —</option>
                    @foreach ($disabilitiesAccess as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="document_photocopying_service_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.document_photocopying_service') }} —</option>
                    @foreach ($photocopyingOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="buffet_service_id" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.buffet_service') }} —</option>
                    @foreach ($buffetOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="surveillance_cameras" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.surveillance_cameras') }} —</option>
                    <option value="available">{{ __('home.option_available') }}</option>
                    <option value="not_available">{{ __('home.option_not_available') }}</option>
                    <option value="broken">{{ __('home.option_broken') }}</option>
                </select>
                <select wire:model.live="queue_management_system" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.queue_management_system') }} —</option>
                    <option value="working">{{ __('home.option_working') }}</option>
                    <option value="not_working">{{ __('home.option_not_working') }}</option>
                    <option value="not_available">{{ __('home.option_not_available') }}</option>
                </select>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.office_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.office_type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.location_description') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.connection_type') }}</th>
                    @if($canView || $canEdit || $canDelete)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($offices as $office)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $offices->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $office->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $office->governorate->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                              {{ $office->officeType->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                              {{ $office->locationDescription->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ $office->connectionType->name ?? '—' }}
                        </td>
                        @if($canView || $canEdit || $canDelete)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($canEdit)
                                        <a href="{{ route('offices.edit', $office) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ __('home.edit') }}
                                        </a>
                                    @endif
                                    @if($canView)
                                        <a href="{{ route('offices.show', $office) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            {{ __('home.view_office') }}
                                        </a>
                                    @endif
                                    @if($canDelete)
                                        <button
                                            wire:click="askDelete({{ $office->id }})"
                                            class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            {{ __('home.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_offices') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $offices->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
