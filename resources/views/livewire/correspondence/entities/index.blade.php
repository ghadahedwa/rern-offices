<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.corr_entities_title') }}</h1>
        <a href="{{ route('correspondence-entities.create') }}" wire:navigate
           class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('home.corr_entity_add') }}
        </a>
    </div>

    {{-- Search + filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.search') }}"
               class="max-w-sm flex-1 min-w-50 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        <select wire:model.live="activeFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">— {{ __('home.corr_entity_status') }} —</option>
            <option value="yes">{{ __('home.corr_entity_active') }}</option>
            <option value="no">{{ __('home.corr_entity_inactive') }}</option>
        </select>
    </div>

    {{-- Table — table-fixed + نِسَب مجموعها ١٠٠٪ فيدخل عرض الحاوية بلا تمرير أفقي --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full table-fixed text-[13px] text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-[11px] uppercase">
                <tr>
                    {{-- مجموع النِسَب ١٠٠٪ بالضبط — الناقص يوزّعه المتصفح كما يشاء --}}
                    <th class="px-2 py-2.5 font-medium w-[5%]">#</th>
                    <th class="px-2 py-2.5 font-medium w-[30%]">{{ __('home.corr_entity_name') }}</th>
                    <th class="px-2 py-2.5 font-medium w-[12%]">{{ __('home.corr_entity_code') }}</th>
                    <th class="px-2 py-2.5 font-medium w-[13%]">{{ __('home.corr_entity_users') }}</th>
                    <th class="px-2 py-2.5 font-medium w-[11%]">{{ __('home.corr_entity_status') }}</th>
                    <th class="px-2 py-2.5 font-medium w-[8%]">{{ __('home.corr_entity_order') }}</th>
                    <th class="px-2 py-2.5 font-medium w-[21%]">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($entities as $entity)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-2 py-2.5 text-zinc-500">{{ $entities->firstItem() + $loop->index }}</td>
                        <td class="px-2 py-2.5 font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $entity->name }}">
                            {{ $entity->name }}
                        </td>
                        <td class="px-2 py-2.5">
                            {{-- مثال الرقم في تلميح الحقل بشاشة التعديل، لا في كل صف — كان يوسّع العمود بلا داعٍ --}}
                            <span class="inline-flex items-center text-[11px] font-medium px-2 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#d8b856]"
                                  title="{{ __('home.corr_entity_code_sample', ['code' => $entity->code]) }}">
                                {{ $entity->code }}
                            </span>
                        </td>
                        <td class="px-2 py-2.5">
                            @if($entity->users_count)
                                <span class="inline-flex items-center text-[11px] font-medium px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                    {{ trans_choice('home.corr_entity_users_count', $entity->users_count, ['count' => $entity->users_count]) }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-2.5">
                            @if($entity->is_active)
                                <span class="inline-flex items-center text-[11px] font-medium px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/25 text-emerald-700 dark:text-emerald-400">
                                    {{ __('home.corr_entity_active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center text-[11px] font-medium px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-300">
                                    {{ __('home.corr_entity_inactive') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-2 py-2.5 text-zinc-500">{{ $entity->order }}</td>
                        <td class="px-2 py-2.5">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('correspondence-entities.edit', $entity) }}" wire:navigate
                                   class="inline-flex items-center text-[11px] px-2.5 py-1 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                    {{ __('home.edit') }}
                                </a>
                                <button
                                    wire:click="askDelete({{ $entity->id }})"
                                    class="inline-flex items-center text-[11px] px-2.5 py-1 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    {{ __('home.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-2 py-10 text-center text-zinc-400">
                            {{ __('home.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $entities->links() }}</div>

    @include('livewire.partials.delete-modal', ['deletingWarning' => __('home.corr_entity_delete_warning')])

</div>
