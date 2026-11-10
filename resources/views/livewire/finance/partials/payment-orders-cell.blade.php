{{-- خلية أوامر دفع شهرٍ محفوظ — للشاشة وللموبايل معاً. المعاملة: $record --}}
@if($record->paymentOrders->isEmpty())
    <span class="text-xs text-zinc-400">{{ __('home.fin_no_order') }}</span>
@else
    <div class="space-y-2">
        @foreach($record->paymentOrders as $order)
            <div class="flex items-center gap-3">
                @include('livewire.finance.partials.attachment-links', ['order' => $order])
                <div class="text-xs min-w-0">
                    <p class="text-zinc-800 dark:text-zinc-100">{{ __('home.fin_order_number_short', ['number' => $order->number]) }}</p>
                    <p class="text-zinc-500 dark:text-zinc-400"><span dir="ltr" class="inline-block whitespace-nowrap">{{ $order->issued_on->format('Y-m-d') }}</span></p>
                </div>
            </div>
        @endforeach
    </div>
@endif
