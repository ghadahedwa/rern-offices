<div class="p-6 space-y-6">

    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.de_import_title') }}</h1>
        <a href="{{ route('data-entry.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-4xl">{{ __('home.de_import_hint') }}</p>

    {{-- ١) المحافظة والقالب --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.de_import_step_template') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_governorate') }}</label>
                <select wire:model.live="governorate"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    <option value="">—</option>
                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                    @endforeach
                </select>
                @error('governorate') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <button type="button" wire:click="downloadTemplate"
                        class="inline-flex items-center gap-2 border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                    </svg>
                    {{ __('home.de_import_download') }}
                </button>
            </div>
        </div>

        <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('home.de_import_template_note') }}</p>
    </div>

    {{-- ٢) رفع الملف المملوء --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.de_import_step_upload') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_import_file') }}</label>
                <input type="file" wire:model="file" accept=".xlsx,.xls"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 dark:file:bg-zinc-700 file:px-3 file:py-1 file:text-xs file:text-zinc-700 dark:file:text-zinc-200" />
                <p wire:loading wire:target="file" class="text-xs text-zinc-500">{{ __('home.de_import_reading') }}</p>
                @error('file') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_import_started_on') }}</label>
                <input type="date" wire:model="startedOn"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_import_started_on_hint') }}</p>
                @error('startedOn') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ٣) نتيجة القراءة --}}
    @if($parsed)
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.de_import_step_review') }}</h3>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        {{ __('home.de_import_count_ok', ['count' => $counts['ok']]) }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                        {{ __('home.de_import_count_duplicate', ['count' => $counts['duplicate']]) }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                        {{ __('home.de_import_count_error', ['count' => $counts['error']]) }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm text-right table-fixed min-w-140">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium w-[8%]">{{ __('home.de_import_line') }}</th>
                            <th class="px-4 py-3 font-medium w-[24%]">{{ __('home.de_operator_name') }}</th>
                            <th class="px-4 py-3 font-medium w-[16%]">{{ __('home.de_operator_phone') }}</th>
                            <th class="px-4 py-3 font-medium w-[28%]">{{ __('home.de_operator_office') }}</th>
                            <th class="px-4 py-3 font-medium w-[24%]">{{ __('home.de_import_row_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($rows as $row)
                            <tr class="{{ $row['status'] === 'error' ? 'bg-red-50/60 dark:bg-red-900/10' : ($row['status'] === 'duplicate' ? 'bg-amber-50/60 dark:bg-amber-900/10' : '') }}">
                                <td class="px-4 py-2.5 text-zinc-500">{{ $row['line'] }}</td>
                                <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100 truncate" title="{{ $row['name'] }}">{{ $row['name'] ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $row['phone'] ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-300 truncate" title="{{ $row['office'] }}">{{ $row['office'] ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    @if($row['status'] === 'ok')
                                        <span class="text-green-700 dark:text-green-400">{{ __('home.de_import_row_ok') }}</span>
                                    @else
                                        <span class="{{ $row['status'] === 'error' ? 'text-red-600' : 'text-amber-700 dark:text-amber-400' }}">{{ $row['message'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-zinc-400">{{ __('home.de_import_empty_file') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="import" @disabled($counts['ok'] === 0)
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('home.de_import_confirm', ['count' => $counts['ok']]) }}
                </button>
                <a href="{{ route('data-entry.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
