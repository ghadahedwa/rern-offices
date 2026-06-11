{{--
    Multi-select dropdown قابل للبحث (Alpine + Livewire entangle).
    المتغيرات:
      $field   — اسم خاصية المصفوفة في الـ component
      $label   — عنوان الحقل
      $options — مصفوفة value => label (id=>name للموديلات، أو enum value=>نص)
--}}
@php
    $opts = collect($options ?? [])
        ->map(fn ($label, $value) => ['value' => $value, 'label' => (string) $label])
        ->values();
@endphp
<div
    wire:key="{{ $wireKey ?? $field }}"
    x-data="{
        open: false,
        search: '',
        selected: ($wire.get('{{ $field }}') || []).map(v => v),
        options: {{ \Illuminate\Support\Js::from($opts) }},
        sync() { $wire.set('{{ $field }}', this.selected, @js($live ?? false)); },
        get filtered() {
            if (! this.search.trim()) return this.options;
            const s = this.search.trim().toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(s));
        },
        has(v) { return this.selected.map(String).includes(String(v)); },
        get selectedLabels() {
            return this.options.filter(o => this.has(o.value)).map(o => o.label).join('، ');
        },
        toggle(v) {
            this.selected = this.has(v)
                ? this.selected.filter(x => String(x) !== String(v))
                : [...this.selected, v];
            this.sync();
        },
        clear() { this.selected = []; this.sync(); },
        get allFilteredSelected() {
            return this.filtered.length > 0 && this.filtered.every(o => this.has(o.value));
        },
        toggleAll() {
            const ids = this.filtered.map(o => String(o.value));
            if (this.allFilteredSelected) {
                this.selected = this.selected.filter(v => ! ids.includes(String(v)));
            } else {
                const merged = [...this.selected];
                this.filtered.forEach(o => { if (! this.has(o.value)) merged.push(o.value); });
                this.selected = merged;
            }
            this.sync();
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    @filters-reset.window="selected = ($wire.get('{{ $field }}') || []).map(v => v); search = ''"
    class="relative"
    :class="open && 'z-50'"
>
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ $label }}</label>

    {{-- Trigger --}}
    <button type="button" @click="open = ! open"
            class="w-full flex items-center justify-between gap-2 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
        <span class="truncate" :title="selectedLabels">
            <template x-if="selected.length === 0"><span>{{ __('home.report_select_hint') }}</span></template>
            <template x-if="selected.length > 0"><span x-text="selectedLabels"></span></template>
        </span>
        <svg class="w-4 h-4 text-zinc-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-transition.origin.top x-cloak
         class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-xl ring-1 ring-black/5">
        <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
            <input x-model="search" type="text" placeholder="{{ __('home.search') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-md px-2.5 py-1.5 text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-[#c9a847]" />
        </div>
        {{-- شريط: اختر الكل ⟷ مسح التحديد --}}
        <div x-show="filtered.length > 0"
             class="flex items-center justify-between gap-2 px-2.5 py-1.5 border-b border-zinc-100 dark:border-zinc-700">
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-[#b8962e] dark:text-[#c9a847]">
                <input type="checkbox" :checked="allFilteredSelected" @change="toggleAll()"
                       class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                <span>{{ __('home.report_select_all') }}</span>
            </label>
            <button type="button" x-show="selected.length > 0" @click="clear()"
                    class="text-xs text-red-600 hover:underline shrink-0">{{ __('home.report_clear_selection') }}</button>
        </div>
        <div class="max-h-52 overflow-y-auto p-1.5 space-y-0.5">
            <template x-for="o in filtered" :key="o.value">
                <label class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" :checked="has(o.value)" @change="toggle(o.value)"
                           class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                    <span x-text="o.label"></span>
                </label>
            </template>
            <p x-show="filtered.length === 0" class="px-2 py-2 text-xs text-zinc-400">{{ __('home.no_data') }}</p>
        </div>
    </div>
</div>
