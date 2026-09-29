<div class="space-y-3">
    <div class="flex items-center gap-3">
        <div class="w-1 h-4 bg-[#c9a847] rounded-full"></div>
        <h4 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">
            {{ __('home.wh_attachments') }}
            <span class="text-xs font-normal text-zinc-400">({{ __('home.wh_attachments_count', ['count' => $saved->count(), 'max' => \App\Models\WarehouseAttachment::MAX_PER_DOCUMENT]) }})</span>
        </h4>
    </div>

    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-lg overflow-hidden">
        @forelse($saved as $file)
            <li wire:key="saved-{{ $file->id }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                <a href="{{ $file->url() }}" target="_blank"
                   class="flex items-center gap-2 min-w-0 text-[#b8962e] hover:text-[#c9a847] font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                </a>
                <span class="shrink-0 text-xs text-zinc-400" title="{{ $file->uploader?->name }}">
                    {{ \App\Support\LocalTime::date($file->created_at) }}
                </span>
            </li>
        @empty
            <li class="px-3 py-2 text-sm text-zinc-400">{{ __('home.wh_attachments_none') }}</li>
        @endforelse
    </ul>

    @if($canAdd)
        @if($limit > 0)
            <form wire:submit="save" class="space-y-3 pt-1">
                @include('livewire.warehouses.partials.attachments-picker', [
                    'limit' => $limit,
                    'label' => __('home.wh_attachments_new'),
                    'hint'  => __('home.wh_attachments_add_hint', ['remaining' => $limit]),
                ])
                @if(count($attachments))
                    <button type="submit" wire:loading.attr="disabled" wire:target="save,pickedFiles"
                            class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-50">
                        {{ __('home.wh_attachments_save') }}
                    </button>
                @endif
            </form>
        @else
            <p class="text-xs text-zinc-400">{{ __('home.wh_attachments_full', ['max' => \App\Models\WarehouseAttachment::MAX_PER_DOCUMENT]) }}</p>
        @endif
    @endif
</div>
