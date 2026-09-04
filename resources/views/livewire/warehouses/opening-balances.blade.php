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
                                                   {{-- ⚠️ `.blur` لا `wire:model` مجرَّداً: القيمة المؤجَّلة قد لا تبلغ الخادم
                                                        قبل تبديل القسم، فيرى الخادمُ خاناتٍ فارغة ويمسح بلا تحذير.
                                                        والخروج من الخانة يسبق الضغط على المنسدلة دائماً. --}}
                                                   wire:model.blur="quantities.{{ $item->id }}"
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

    {{-- تحذير التبديل: المسح صحيح، وصمتُه هو الخطأ --}}
    <div x-show="$wire.showSwitchWarning"
         x-transition.opacity
         @click.self="$wire.cancelSwitch()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847] bg-white dark:bg-zinc-900">
            <div class="flex items-center gap-2.5 px-5 py-3.5 bg-[#c9a847]">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">{{ __('home.wh_opening_unsaved_title') }}</h3>
            </div>

            <div class="px-5 py-4">
                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ __('home.wh_opening_unsaved_body', ['count' => collect($quantities)->filter(fn ($v) => $v !== null && $v !== '')->count()]) }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                <button type="button" wire:click="cancelSwitch"
                        class="text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    {{ __('home.cancel') }}
                </button>
                <button type="button" wire:click="discardThenSwitch"
                        class="text-sm px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    {{ __('home.wh_opening_discard_switch') }}
                </button>
                <button type="button" wire:click="saveThenSwitch"
                        class="text-sm px-4 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white font-medium transition">
                    {{ __('home.wh_opening_save_and_switch') }}
                </button>
            </div>
        </div>
    </div>
    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
