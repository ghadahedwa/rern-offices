@php($waitTimes = \App\Livewire\Feedback\Rating::WAIT_TIMES)
@php($criteria = \App\Livewire\Feedback\Rating::CRITERIA)

@push('styles')
@include('livewire.feedback.includes.form-styles')
@verbatim
<style>
/* لون شاشة التقييم: ذهبي */
.fb-main{--accent:var(--rating);--accent-strong:var(--gold-dark);--accent-tint:var(--rating-tint)}

/* wait-time pills */
.pills{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pill{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:500;color:var(--ink);
  background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:11px 13px;cursor:pointer;
  transition:.18s}
.pill:hover{border-color:color-mix(in srgb,var(--accent) 50%,var(--line))}
.pill input{position:absolute;opacity:0;pointer-events:none}
.pill .tick{width:18px;height:18px;border-radius:50%;border:2px solid var(--line);flex:none;
  display:grid;place-items:center;transition:.18s}
.pill .tick::after{content:"";width:8px;height:8px;border-radius:50%;background:var(--accent);
  transform:scale(0);transition:.18s}
.pill.sel{border-color:var(--accent);background:var(--accent-tint)}
.pill.sel .tick{border-color:var(--accent)}
.pill.sel .tick::after{transform:scale(1)}

/* criteria stars */
.crit{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;
  border-bottom:1px solid var(--line)}
.crit:last-of-type{border-bottom:none}
.crit-label{font-size:13.5px;font-weight:500;color:var(--ink)}
.crit-label .opt{font-size:11px;font-weight:400;color:var(--ink-soft);margin-inline-start:4px}
.stars-input{display:flex;gap:4px;flex:none}
.stars-input .star{background:none;border:none;padding:5px;cursor:pointer;line-height:0;color:var(--line);
  touch-action:manipulation;transition:transform .12s,color .12s}
.stars-input .star svg{width:26px;height:26px;fill:currentColor;stroke:none;display:block}
.stars-input .star:hover{transform:scale(1.15)}
.stars-input .star.on{color:var(--accent)}

/* overall — بارز */
.overall{margin-top:6px;text-align:center;background:var(--accent-tint);
  border:1px solid color-mix(in srgb,var(--accent) 40%,var(--line));border-radius:16px;padding:18px}
.overall .lbl{font-family:'Reem Kufi',sans-serif;font-size:14px;font-weight:600;color:var(--ink);margin-bottom:10px}
.overall .stars-input{justify-content:center;gap:8px}
.overall .stars-input .star svg{width:38px;height:38px}

@media (max-width:520px){
  .pills{grid-template-columns:1fr}
  .crit{flex-direction:column;align-items:flex-start;gap:6px}
  .stars-input .star svg{width:30px;height:30px}
}
</style>
@endverbatim
@endpush

<main class="fb-main">
<div class="fb-wrap">

@if($submitted)
  {{-- ===== شكراً ===== --}}
  <div class="fb-card thanks">
    <div class="ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h2>شكراً لتقييمك</h2>
    <p>وصلنا رأيك بنجاح، ونعمل على تحسين الخدمة باستمرار. نقدّر وقتك ومشاركتك.</p>
    <a href="{{ route('feedback') }}" wire:navigate class="back">
      العودة للرئيسية
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
  </div>
@else
  {{-- ===== هيدر ===== --}}
  <div class="fb-hero">
    <span class="eyebrow">رأيك يهمنا</span>
    <h1>تقييم <span class="accent">الخدمة</span></h1>
    <p class="lead">قيّم تجربتك في المقر ليصلنا مدى رضاك عن الخدمة المقدَّمة. لن يستغرق الأمر سوى دقائق.</p>
    <div class="stepper">
      <span class="dot on">١</span>
      <span class="sep"></span>
      <span class="dot {{ $this->showRating ? 'on' : '' }}">٢</span>
      <span>{{ $this->showRating ? 'بنود التقييم' : 'بياناتك والمقر' }}</span>
    </div>
  </div>

  <form wire:submit="submit" class="fb-card">

    {{-- ===== الخطوة الأولى: الهوية والمقر ===== --}}
    @include('livewire.feedback.includes.identity-fields')

    {{-- ===== الخطوة الثانية: بنود التقييم (تظهر بعد اختيار المقر) ===== --}}
    @if($this->showRating)
      <div class="stage2" x-data x-init="$el.scrollIntoView({behavior:'smooth',block:'nearest'})">

        {{-- مدة الانتظار --}}
        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>مدة الانتظار الفعلية</h2></div>
          <div class="pills">
            @foreach($waitTimes as $key => $label)
              <label class="pill {{ $wait_time === $key ? 'sel' : '' }}">
                <input type="radio" wire:model.live="wait_time" value="{{ $key }}" />
                <span class="tick"></span>{{ $label }}
              </label>
            @endforeach
          </div>
          @error('wait_time')<p class="err">{{ $message }}</p>@enderror
        </div>

        {{-- محاور التقييم --}}
        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>محاور التقييم</h2></div>
          @foreach($criteria as $model => [$label, $optional])
            @include('livewire.feedback.includes.star-row', ['label' => $label, 'model' => $model, 'optional' => $optional])
          @endforeach
        </div>

        {{-- التقييم العام --}}
        <div class="sec">
          <div class="overall" x-data="{ v: @entangle('overall_rating'), hover: 0 }" @mouseleave="hover = 0">
            <div class="lbl">التقييم العام للتجربة</div>
            <div class="stars-input" role="radiogroup" aria-label="التقييم العام">
              <template x-for="i in 5" :key="i">
                <button type="button" class="star" :class="(hover || v) >= i ? 'on' : ''"
                        @mouseenter="hover = i" @click="v = i" :aria-label="i + ' من 5'">
                  <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
                </button>
              </template>
            </div>
            @error('overall_rating')<p class="err">{{ $message }}</p>@enderror
          </div>
        </div>

        {{-- ملاحظات --}}
        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>ملاحظات إضافية <span style="font-weight:400;color:var(--ink-soft);font-size:12px">(اختياري)</span></h2></div>
          <div class="field">
            <textarea class="inp" rows="3" wire:model="notes" placeholder="أي ملاحظة تودّ إضافتها..."></textarea>
            @error('notes')<p class="err">{{ $message }}</p>@enderror
          </div>
        </div>

        <button type="submit" class="submit-btn" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submit">إرسال التقييم</span>
          <span wire:loading wire:target="submit">جارٍ الإرسال...</span>
          <svg wire:loading.remove wire:target="submit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        </button>
      </div>
    @else
      <div class="gate-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        اختر المحافظة والمقر لعرض بنود التقييم.
      </div>
    @endif

  </form>
@endif

</div>

{{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
<div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</main>
