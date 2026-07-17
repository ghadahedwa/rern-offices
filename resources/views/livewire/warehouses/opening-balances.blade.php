<div class="p-6 max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_opening_balances') }}</h1>
        <a href="{{ route('warehouses.dashboard') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-6">

            {{-- المخزن --}}
            <div class="flex flex-col gap-1 max-w-sm">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.warehouse') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model="warehouse_id"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    <option value="">— {{ __('home.warehouse') }} —</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }} @if($wh->type) ({{ $wh->type->name }}) @endif</option>
                    @endforeach
                </select>
                @error('warehouse_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            {{-- Section header: الأصناف --}}
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.items_title') }}</h3>
                </div>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-4">{{ __('home.wh_opening_balances_hint') }}</p>

                @error('lines') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

                @php $chosen = collect($lines)->pluck('item_id')->filter()->map(fn ($v) => (int) $v)->all(); @endphp
                <div class="space-y-3">
                    @foreach($lines as $i => $line)
                        <div class="flex items-start gap-3" wire:key="line-{{ $i }}">
                            <div class="flex-1 flex flex-col gap-1">
                                <select wire:model.live="lines.{{ $i }}.item_id"
                                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                                    <option value="">— {{ __('home.item') }} —</option>
                                    @foreach($items as $item)
                                        {{-- يُخفى الصنف لو مُختار في صف آخر (يبقى ظاهراً في صفّه هو) --}}
                                        @if(! in_array($item->id, $chosen, true) || (int) ($line['item_id'] ?? 0) === $item->id)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error("lines.$i.item_id") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="w-32 flex flex-col gap-1">
                                <input type="number" min="0" wire:model="lines.{{ $i }}.quantity"
                                       placeholder="{{ __('home.wh_quantity') }}"
                                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                                @error("lines.$i.quantity") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="removeLine({{ $i }})"
                                    class="mt-1 w-9 h-9 shrink-0 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                    title="{{ __('home.wh_remove_line') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addLine"
                        class="mt-4 inline-flex items-center gap-2 text-sm text-[#b8962e] hover:text-[#c9a847] font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('home.wh_add_line') }}
                </button>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <button type="submit"
                        class="mt-4 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('warehouses.dashboard') }}" wire:navigate
                   class="mt-4 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
