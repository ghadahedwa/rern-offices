@php($cfg = $this->config)

@push('styles')
@include('livewire.feedback.includes.form-styles')
<style>
/* لون الشاشة من نوع الرسالة (FeedbackMessage::TYPES[...]['color']) */
.fb-main{--accent:var(--{{ $cfg['color'] }});--accent-strong:color-mix(in srgb,var(--{{ $cfg['color'] }}) 78%,#000);
  --accent-tint:var(--{{ $cfg['color'] }}-tint)}
.field .field-hint{font-size:11.5px;color:var(--ink-soft);margin-top:5px;line-height:1.6}
</style>
@endpush

<main class="fb-main">
<div class="fb-wrap">

@if($submitted)
  {{-- ===== شكراً ===== --}}
  <div class="fb-card thanks">
    <div class="ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h2>{{ $cfg['thanks_title'] }}</h2>
    <p>{{ $cfg['thanks_text'] }}</p>
    <div class="actions">
      @foreach($this->otherForms() as $form)
        <a href="{{ $form['url'] }}" wire:navigate class="also">
          {{ $form['also'] }}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
      @endforeach
      <a href="{{ route('feedback') }}" wire:navigate class="back">العودة للرئيسية</a>
    </div>
  </div>
@else
  {{-- ===== هيدر ===== --}}
  @include('livewire.feedback.includes.back-link')
  <div class="fb-hero">
    <span class="eyebrow">{{ $cfg['eyebrow'] }}</span>
    <h1>{{ $cfg['title'][0] }} <span class="accent">{{ $cfg['title'][1] }}</span></h1>
    <p class="lead">{{ $cfg['lead'] }}</p>
    <div class="stepper">
      <span class="dot on">١</span>
      <span class="sep"></span>
      <span class="dot {{ $this->showMessage ? 'on' : '' }}">٢</span>
      <span>{{ $this->showMessage ? 'رسالتك' : 'بياناتك والمقر' }}</span>
    </div>
  </div>

  <form wire:submit="submit" class="fb-card">

    {{-- ===== الخطوة الأولى: الهوية والمقر ===== --}}
    @include('livewire.feedback.includes.identity-fields', ['identityRequired' => $this->identityRequired()])

    {{-- ===== الخطوة الثانية: الرسالة (تظهر بعد اختيار المقر) ===== --}}
    @if($this->showMessage)
      <div class="stage2" x-data x-init="$el.scrollIntoView({behavior:'smooth',block:'nearest'})">

        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>{{ $cfg['body_label'] }}</h2></div>

          @if($cfg['employee'])
            <div class="field">
              <label>{{ $cfg['employee_label'] }} @if($cfg['employee'] === 'required')<span class="req">*</span>@else<span class="opt">(اختياري)</span>@endif</label>
              <input type="text" class="inp" wire:model="employee_name" maxlength="150" />
              <p class="field-hint">{{ $cfg['employee_hint'] }}</p>
              @error('employee_name')<p class="err">{{ $message }}</p>@enderror
            </div>
          @endif

          <div class="field">
            <label>{{ $cfg['body_label'] }} <span class="req">*</span></label>
            <textarea class="inp" rows="5" wire:model="body" maxlength="2000" placeholder="{{ $cfg['body_placeholder'] }}"></textarea>
            @error('body')<p class="err">{{ $message }}</p>@enderror
          </div>
        </div>

        @error('gate')<p class="gate-error">{{ $message }}</p>@enderror

        <button type="submit" class="submit-btn" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submit">{{ $cfg['submit'] }}</span>
          <span wire:loading wire:target="submit">جارٍ الإرسال...</span>
          <svg wire:loading.remove wire:target="submit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        </button>
      </div>
    @elseif($gateBlocked)
      <div class="blocked">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <p>
          {{ $cfg['blocked_text'] }}<br>
          يمكنك المحاولة اعتباراً من <span class="date">{{ $gateRetryDate }}</span>.
        </p>
        @include('livewire.feedback.includes.other-forms-alt')
      </div>
    @else
      <div class="gate-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        {{ $cfg['stage_hint'] }}
      </div>
    @endif

  </form>
@endif

</div>

{{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
<div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</main>
