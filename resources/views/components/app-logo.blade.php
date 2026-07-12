@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes }} class="flex items-center justify-center py-2">
        <img src="{{ asset('images/logo3.png') }}" class="w-35 h-35 object-contain" alt="الشعار" />
    </a>
@else
    <flux:brand name="{{ __('home.app_name') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="{{ asset('images/logo3.png') }}" class="size-8 object-contain" alt="الشعار" />
        </x-slot>
    </flux:brand>
@endif
