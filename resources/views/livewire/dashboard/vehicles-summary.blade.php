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
                    <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-1.5">{{ number_format($totalVehicles) }}</p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                            <span class="text-zinc-500 dark:text-zinc-400 truncate">{{ \App\Models\Vehicle::STATUSES['working'] }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200 ms-auto">{{ number_format($vehiclesWorking) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                            <span class="text-zinc-500 dark:text-zinc-400 truncate">{{ \App\Models\Vehicle::STATUSES['maintenance'] }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200 ms-auto">{{ number_format($vehiclesMaintenance) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            <span class="text-zinc-500 dark:text-zinc-400 truncate">{{ \App\Models\Vehicle::STATUSES['stopped'] }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200 ms-auto">{{ number_format($vehiclesStopped) }}</span>
                        </div>
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
