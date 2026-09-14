@php($questions = \App\Models\FeedbackDigitalRating::QUESTIONS)
@php($arDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','١٠'])

@push('styles')
@include('livewire.feedback.includes.form-styles')
@verbatim
<style>
/* لون شاشة المنصات الرقمية: أزرق */
.fb-main{--accent:var(--digital);--accent-strong:color-mix(in srgb,var(--digital) 78%,#000);
  --accent-tint:var(--digital-tint)}

/* السؤال الواحد */
.q{padding:16px 0;border-bottom:1px solid var(--line)}
.q:first-child{padding-top:0}
.q:last-child{border-bottom:none;padding-bottom:0}
.q-label{font-size:14px;font-weight:600;line-height:1.75;color:var(--ink);margin-bottom:11px}
.q-label .opt{font-weight:400;color:var(--ink-soft);font-size:12px;margin-inline-start:4px}
.q .err{margin-top:8px}
.q-enter{animation:reveal .35s cubic-bezier(.2,.7,.2,1)}

/* مقياس ٠–١٠: ١١ خانة في صف، وصفّان على الموبايل */
.scale{display:grid;grid-template-columns:repeat(11,1fr);gap:6px}
.scale label{position:relative;display:grid;place-items:center;height:42px;border-radius:10px;
  border:1px solid var(--line);background:var(--paper);font-size:14px;font-weight:600;color:var(--ink);
  cursor:pointer;transition:.16s;touch-action:manipulation}
.scale label:hover{border-color:color-mix(in srgb,var(--accent) 50%,var(--line))}
.scale input{position:absolute;opacity:0;pointer-events:none}
.scale label.sel{background:var(--accent);border-color:var(--accent);color:#fff}
.scale-ends{margin-top:7px;display:flex;justify-content:space-between;font-size:11.5px;color:var(--ink-soft)}

@media (max-width:520px){
  .scale{grid-template-columns:repeat(6,1fr)}
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
    <p>وصلنا رأيك في المنصات الرقمية بنجاح، وسيساعدنا في تطوير خدمات الحجز الإلكتروني. نقدّر وقتك ومشاركتك.</p>
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
  <div class="fb-hero">
    <span class="eyebrow">رأيك يهمنا</span>
    <h1>تقييم <span class="accent">المنصات الرقمية</span></h1>
    <p class="lead">شاركنا تجربتك مع الحجز الإلكتروني ومنصات الشهر العقاري الرقمية. لن يستغرق الأمر سوى دقائق.</p>
    <div class="stepper">
      <span class="dot on">١</span>
      <span class="sep"></span>
      <span class="dot {{ $this->showQuestions ? 'on' : '' }}">٢</span>
      <span>{{ $this->showQuestions ? 'الأسئلة' : 'بياناتك والمقر' }}</span>
    </div>
  </div>

  <form wire:submit="submit" class="fb-card">

    {{-- ===== الخطوة الأولى: الهوية والمقر ===== --}}
    @include('livewire.feedback.includes.identity-fields')

    {{-- ===== الخطوة الثانية: الأسئلة (تظهر بعد اختيار المقر، وكلٌّ منها حسب ما قبله) ===== --}}
    @if($this->showQuestions)
      <div class="stage2" x-data x-init="$el.scrollIntoView({behavior:'smooth',block:'nearest'})">

        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>تقييم المنصات الرقمية</h2></div>

          @foreach($this->visibleQuestions() as $key)
            @php([$type, $label, $options] = $questions[$key])
            <div class="q {{ $key === 'q201' ? '' : 'q-enter' }}" wire:key="dq-{{ $key }}">
              <div class="q-label">
                {{ $label }}
                @if($type === 'text')<span class="opt">(اختياري)</span>@endif
                @if($type === 'checkbox')<span class="opt">(ممكن تختار أكتر من إجابة)</span>@endif
              </div>

              @if($type === 'radio')
                <div class="pills">
                  @foreach($options as $value => $optionLabel)
                    <label class="pill {{ $this->{$key} === $value ? 'sel' : '' }}" wire:key="dq-{{ $key }}-{{ $value }}">
                      <input type="radio" wire:model.live="{{ $key }}" value="{{ $value }}" />
                      <span class="tick"></span>{{ $optionLabel }}
                    </label>
                  @endforeach
                </div>

              @elseif($type === 'checkbox')
                <div class="chips">
                  @foreach($options as $value => $optionLabel)
                    <label class="chip {{ in_array($value, $this->{$key}, true) ? 'sel' : '' }}" wire:key="dq-{{ $key }}-{{ $value }}">
                      <input type="checkbox" wire:model.live="{{ $key }}" value="{{ $value }}" />
                      <span class="box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                      </span>
                      {{ $optionLabel }}
                    </label>
                  @endforeach
                </div>

              @elseif($type === 'scale')
                <div class="scale" role="radiogroup" aria-label="{{ $label }}">
                  @for($i = $options[0]; $i <= $options[1]; $i++)
                    <label class="{{ $this->{$key} === $i ? 'sel' : '' }}" wire:key="dq-{{ $key }}-{{ $i }}">
                      <input type="radio" wire:model.live="{{ $key }}" value="{{ $i }}" />
                      {{ $arDigits[$i] }}
                    </label>
                  @endfor
                </div>
                <div class="scale-ends"><span>صفر = أقل درجة</span><span>عشرة = أعلى درجة</span></div>

              @elseif($type === 'text')
                <div class="field">
                  <textarea class="inp" rows="3" wire:model="{{ $key }}" placeholder="اكتب هنا..."></textarea>
                </div>
              @endif

              @error($key)<p class="err">{{ $message }}</p>@enderror
              @error($key.'.*')<p class="err">{{ $message }}</p>@enderror
            </div>
          @endforeach
        </div>

        @error('gate')<p class="gate-error">{{ $message }}</p>@enderror

        <button type="submit" class="submit-btn" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submit">إرسال التقييم</span>
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
          لا يتيح النظام تقييم المنصات الرقمية لهذا المقر أكثر من مرة كل أسبوع.<br>
          يمكنك المحاولة اعتباراً من <span class="date">{{ $gateRetryDate }}</span>.
        </p>
        @include('livewire.feedback.includes.other-forms-alt')
      </div>
    @else
      <div class="gate-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        اختر المحافظة والمقر لعرض الأسئلة.
      </div>
    @endif

  </form>
@endif

</div>

{{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
<div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</main>
