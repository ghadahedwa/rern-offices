@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="قطاع الشهر العقاري" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="{{ asset('images/logo2.png') }}" class="size-8 object-contain" alt="الشعار" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="قطاع الشهر العقاري" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="{{ asset('images/logo2.png') }}" class="size-8 object-contain" alt="الشعار" />
        </x-slot>
    </flux:brand>
@endif
