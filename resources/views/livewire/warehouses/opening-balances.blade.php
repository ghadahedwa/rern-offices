<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_opening_balances') }}</h1>
        <a href="{{ route('warehouses.dashboard') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6">
        <form wire:submit="save" class="space-y-5">

            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.wh_opening_balances_hint') }}</p>

            {{-- المخزن والقسم — اختيارٌ مرة واحدة، ثم تظهر أصناف القسم كلها --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.warehouse') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="warehouse_id"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">— {{ __('home.warehouse') }} —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} @if($wh->type) ({{ $wh->type->name }}) @endif</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.item_category') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="category_id"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">— {{ __('home.item_category') }} —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                        <option value="none">{{ __('home.item_category_none') }}</option>
                    </select>
                    @error('category_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            @if($items->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 px-4 py-10 text-center text-sm text-zinc-400">
                    {{ ($warehouse_id && $category_id !== '') ? __('home.wh_opening_category_empty') : __('home.wh_opening_pick_category') }}
                </div>
            @else
                {{-- صورة البيان الورقي: كل أصناف القسم صفوفاً، وأمام كلٍّ خانة عدد --}}
                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                {{ __('home.items_title') }}
                            </h3>
                        </div>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500 tabular-nums">
                            {{ __('home.wh_opening_items_count', ['count' => $items->count()]) }}
                        </span>
                    </div>

                    @error('quantities') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2.5 font-medium w-10">#</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('home.item_name') }}</th>
                                    <th class="px-3 py-2.5 font-medium w-24">{{ __('home.item_unit') }}</th>
                                    <th class="px-3 py-2.5 font-medium w-24">{{ __('home.wh_opening_recorded') }}</th>
                                    <th class="px-3 py-2.5 font-medium w-32">{{ __('home.wh_opening_new') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($items as $item)
                                    @php $recorded = (int) ($current[$item->id] ?? 0); @endphp
                                    <tr wire:key="row-{{ $item->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition">
                                        <td class="px-3 py-2 text-zinc-400 tabular-nums">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 text-zinc-800 dark:text-zinc-100">
                                            {{ $item->name }}
                                            @if($item->code)
                                                <span class="mr-2 inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-normal text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">{{ $item->code }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-zinc-500 dark:text-zinc-400">{{ $item->unit?->name ?? '—' }}</td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{-- الصفر شرطةً كما في الدفتر --}}
                                            @if($recorded === 0)
                                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                            @else
                                                <span class="text-zinc-600 dark:text-zinc-300">{{ number_format($recorded) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="0" inputmode="numeric"
                                                   wire:model="quantities.{{ $item->id }}"
                                                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-2 py-1.5 text-sm tabular-nums bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                                            @error("quantities.{$item->id}") <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">{{ __('home.wh_opening_blank_note') }}</p>
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
            @endif

        </form>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
