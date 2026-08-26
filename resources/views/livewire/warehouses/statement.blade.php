<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_statement') }}</h1>

        @if($statement && $statement['rows']->isNotEmpty())
            <a href="{{ route('warehouses.statement.pdf', ['wh' => $warehouseId, 'category' => $categoryId]) }}"
               target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                {{ __('home.wh_statement_print') }}
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :columns="2">
        <x-filter-select :label="__('home.warehouse')" wire:model.live="warehouseId">
            <option value="">—</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :label="__('home.item_category')" wire:model.live="categoryId">
            <option value="">—</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-filter-select>
    </x-filter-bar>

    @if(! $statement)
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-10 text-center text-sm text-zinc-500">
            {{ __('home.wh_statement_pick') }}
        </div>
    @else
        {{-- ⚠️ يُقال قبل الطباعة لا بعدها: الغياب لا يُرى إلا في الورقة الخارجة --}}
        @if(blank($statement['warehouse']->letterhead))
            <div class="rounded-xl border border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                {{ __('home.wh_statement_no_letterhead') }}
            </div>
        @endif

        {{-- معاينة البيان: نفس أعمدة الورق وترتيبه، والصفر شرطةً كما يطبعه الدفتر --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between gap-4">
                <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                    {{ __('home.wh_statement_title', ['category' => $statement['category']->name]) }}
                </h2>
                <span class="text-xs text-zinc-500">
                    {{ $statement['warehouse']->name }}
                    · {{ __('home.wh_statement_rows') }}: {{ $statement['rows']->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                        <tr>
                            {{-- «م» في كل بيان، ورقم الصنف عمودٌ زائد عليها لا بديلٌ عنها --}}
                            <th class="px-4 py-3 font-medium w-16">{{ __('home.wh_statement_serial') }}</th>
                            @if($statement['hasCodes'])
                                <th class="px-4 py-3 font-medium w-28">{{ __('home.item_code') }}</th>
                            @endif
                            <th class="px-4 py-3 font-medium">{{ __('home.item_name') }}</th>
                            <th class="px-4 py-3 font-medium w-32">{{ __('home.wh_statement_count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($statement['rows'] as $row)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-4 py-2.5 text-zinc-500">{{ $loop->iteration }}</td>
                                @if($statement['hasCodes'])
                                    <td class="px-4 py-2.5 text-zinc-500">{{ $row->code }}</td>
                                @endif
                                <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-200">{{ $row->name }}</td>
                                <td class="px-4 py-2.5 font-medium {{ $row->quantity ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-400' }}">
                                    {{ \App\Reports\CategoryStatement::amount((int) $row->quantity) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-zinc-500">{{ __('home.wh_statement_empty') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($statement['rows']->isNotEmpty())
                        <tfoot class="bg-zinc-50 dark:bg-zinc-800 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                            <tr>
                                <td class="px-4 py-3" colspan="{{ $statement['hasCodes'] ? 3 : 2 }}">{{ __('home.wh_statement_total') }}</td>
                                <td class="px-4 py-3">{{ \App\Support\ArabicDigits::toArabic((string) $statement['total']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
</div>
