{{-- شاشة الصيانة وحدها — يعرضها BlockWhenSystemFrozen لطلبٍ لا يُحوَّل (Livewire) بعد إخراج صاحبه --}}
<x-layouts::auth :title="__('home.freeze_title')">
    @include('livewire.auth.partials.frozen-card')
</x-layouts::auth>
