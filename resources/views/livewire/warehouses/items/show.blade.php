<div class="p-6 max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            {{-- الوجهة تُحسب في المكوّن: للصفحة مدخلان بصلاحيتين مختلفتين --}}
            <a href="{{ $this->backRoute() }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $item->name }}
                    @if($item->code)
                        <span class="mr-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-normal align-middle text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">{{ $item->code }}</span>
                    @endif
                </h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $item->category?->name ?? __('home.item_category_none') }}
                    &mdash; {{ $item->unit?->name ?? __('home.item_unit_none') }}
                    @if($item->min_stock !== null)
                        &mdash; {{ __('home.wh_item_min_stock_is', ['count' => number_format($item->min_stock)]) }}
                    @endif
                </p>
            </div>
        </div>

        @if($canManage)
            <a href="{{ route('items.edit', $item) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
                {{ __('home.edit') }}
            </a>
        @endif
    </div>

    @unless($item->is_active)
        <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
            {{ __('home.wh_item_inactive_notice') }}
        </div>
    @endunless

    {{-- بطاقات الرأس: محسوبة على كل مخازن نطاق القارئ لا على ما بقي بعد فلتر الجدول.
         وبطاقة الرئيسي تُخفى لمن ليس الرئيسي في نطاقه — فتصير البطاقات اثنتين. --}}
    <div class="grid grid-cols-1 {{ $summary['showMain'] ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }} gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_total_balance') }}</p>
            <p class="mt-1 text-2xl font-bold text-[#b8962e] tabular-nums">{{ number_format($summary['total']) }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_total_balance_hint') }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_warehouses_with') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-800 dark:text-zinc-100 tabular-nums">{{ number_format($summary['withStock']) }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_warehouses_of', ['count' => number_format($summary['warehousesAll'])]) }}</p>
        </div>

        @if($summary['showMain'])
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_main_balance') }}</p>
            @if($summary['mainWarehouse'])
                <p class="mt-1 text-2xl font-bold text-zinc-800 dark:text-zinc-100 tabular-nums">{{ number_format($summary['mainQuantity']) }}</p>
                <p class="mt-1 text-xs">
                    {{-- ⚠️ الحد الأدنى يُقاس على المخزن الرئيسي وحده — لا شارة في غيره --}}
                    @if($item->min_stock === null)
                        <span class="text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_no_min_stock') }}</span>
                    @elseif($summary['mainBelowMin'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30">
                            {{ __('home.wh_below_min_stock') }}
                        </span>
                    @else
                        <span class="text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_min_stock_is', ['count' => number_format($item->min_stock)]) }}</span>
                    @endif
                </p>
            @else
                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.wh_no_main_warehouse') }}</p>
            @endif
        </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            <button type="button" wire:click="setTab('balances')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'balances' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.wh_item_balances') }}
            </button>
            <button type="button" wire:click="setTab('movements')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'movements' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.wh_movements') }}
            </button>
        </nav>
    </div>

    @if($tab === 'balances')
        @include('livewire.warehouses.items.includes.show-tab-balances')
    @else
        @include('livewire.warehouses.items.includes.show-tab-movements')
    @endif

</div>
