{{-- ملخص السيارات المتنقلة --}}
<div class="space-y-3">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ __('home.vehicles_title') }}
        </h3>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            {{-- إجمالي السيارات + توزيع الحالات (صف واحد) --}}
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.truck variant="outline" class="w-4 h-4 text-[#b8962e] dark:text-[#c9a847]" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.kpi_total_vehicles') }}</p>
                    <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-1">{{ number_format($totalVehicles) }}</p>
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs font-semibold text-green-600 dark:text-green-400">
                            {{ \App\Models\Vehicle::STATUSES['working'] }}: {{ number_format($vehiclesWorking) }}
                        </span>
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                            {{ \App\Models\Vehicle::STATUSES['maintenance'] }}: {{ number_format($vehiclesMaintenance) }}
                        </span>
                        <span class="text-xs font-semibold text-red-600 dark:text-red-400">
                            {{ \App\Models\Vehicle::STATUSES['stopped'] }}: {{ number_format($vehiclesStopped) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- إحصائيات التوثيق (آخر سنة فقط، كل نوع في عمود مستقل) --}}
            @foreach($vehicleStatsSummary as $stat)
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.document-text variant="outline" class="w-4 h-4 text-[#b8962e] dark:text-[#c9a847]" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">
                        {{ $stat['label'] }} @if($stat['latestYear']) ({{ $stat['latestYear'] }}) @endif
                    </p>
                    <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">
                        {{ $stat['latestYear'] ? number_format($stat['latestTotal']) : '—' }}
                    </p>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</div>
