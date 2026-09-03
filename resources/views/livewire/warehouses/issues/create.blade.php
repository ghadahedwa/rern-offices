<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('warehouses.issues.index') }}" wire:navigate
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_issue_add') }}</h1>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6">
        <form wire:submit="save" class="space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- المخزن الصارف --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.wh_issue_warehouse') }} <span class="text-red-500">*</span>
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

                {{-- المقر المستلِم — مقار محافظة المخزن وحدها --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.wh_issue_office') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="office_id" @disabled(! $warehouse_id)
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847] disabled:opacity-50">
                        <option value="">
                            {{ $warehouse_id ? __('home.wh_issue_office_select') : __('home.wh_issue_office_pick_wh') }}
                        </option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                        @endforeach
                    </select>
                    @if($warehouse_id && $offices->isEmpty())
                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('home.wh_issue_no_offices') }}</p>
                    @else
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_issue_office_hint') }}</p>
                    @endif
                    @error('office_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- تاريخ الصرف --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.wh_issue_at') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="issued_at"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    @error('issued_at') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- نوع المستند --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.wh_document_type') }}</label>
                    <input type="text" wire:model="document_type" list="wh-issue-document-types"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    <datalist id="wh-issue-document-types">
                        <option value="إذن صرف"></option>
                        <option value="استمارة نقل عهدة"></option>
                    </datalist>
                    @error('document_type') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Section header: الأصناف --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.items_title') }}</h3>
                </div>

                @error('lines') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

                @include('livewire.warehouses.partials.item-category-filter')

                @php $chosen = collect($lines)->pluck('item_id')->filter()->map(fn ($v) => (int) $v)->all(); @endphp
                <div class="space-y-3">
                    @foreach($lines as $i => $line)
                        <div class="flex items-start gap-3" wire:key="line-{{ $i }}">
                            <div class="flex-1 flex flex-col gap-1">
                                <select wire:model.live="lines.{{ $i }}.item_id"
                                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                                    <option value="">— {{ __('home.item') }} —</option>
                                    @include('livewire.warehouses.partials.item-options')
                                </select>
                                {{-- المتاح بجوار الصنف — فلا يُصرف ما ليس في المخزن --}}
                                @if(($line['item_id'] ?? '') !== '')
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_issue_available') }}: {{ $stocks[(int) $line['item_id']] ?? 0 }}</p>
                                @endif
                                @error("lines.$i.item_id") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="w-32 flex flex-col gap-1">
                                <input type="number" min="1" wire:model="lines.{{ $i }}.quantity"
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

            {{-- المرفق --}}
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.wh_attachment') }} <span class="text-red-500">*</span>
                </label>
                <input type="file" wire:model="attachment" accept="image/*,.pdf"
                       class="block w-full text-sm text-zinc-600 dark:text-zinc-300 file:me-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#c9a847]/10 file:text-[#b8962e] hover:file:bg-[#c9a847]/20" />
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_attachment_required') }}</p>
                <div wire:loading wire:target="attachment" class="text-xs text-zinc-400">{{ __('home.uploading') }}</div>
                @error('attachment') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <button type="submit"
                        class="mt-4 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('warehouses.issues.index') }}" wire:navigate
                   class="mt-4 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
