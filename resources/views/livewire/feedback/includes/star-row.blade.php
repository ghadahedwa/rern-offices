{{-- محور تقييم بنجوم — $label العنوان، $model اسم خاصية Livewire، $optional اختياري؟ --}}
<div class="crit" x-data="{ v: @entangle($model), hover: 0 }" wire:key="crit-{{ $model }}">
  <div class="crit-label">
    {{ $label }}
    @if($optional ?? false)<span class="opt">(اختياري)</span>@endif
  </div>
  <div class="stars-input" @mouseleave="hover = 0" role="radiogroup" aria-label="{{ $label }}">
    <template x-for="i in 5" :key="i">
      <button type="button" class="star"
              :class="(hover || v) >= i ? 'on' : ''"
              @mouseenter="hover = i"
              @click="v = (v === i ? {{ ($optional ?? false) ? 'null' : 'v' }} : i)"
              :aria-label="i + ' من 5'">
        <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
      </button>
    </template>
  </div>
</div>
@error($model)<p class="err">{{ $message }}</p>@enderror
