@php($domains = \App\Livewire\Feedback\Suggestion::DOMAINS)
@php($arNum = ['١','٢','٣','٤','٥','٦'])

@push('styles')
@include('livewire.feedback.includes.form-styles')
@verbatim
<style>
/* لون شاشة المقترحات: أخضر */
.fb-main{--accent:var(--suggestion);--accent-strong:color-mix(in srgb,var(--suggestion) 78%,#000);
  --accent-tint:var(--suggestion-tint)}

/* مجموعات المجالات + العناوين (chips) */
.dgroup{margin-top:20px}
.dgroup:first-of-type{margin-top:0}
.dtitle{font-size:13.5px;font-weight:600;color:var(--ink);margin-bottom:11px;display:flex;align-items:center;gap:9px}
.dtitle .num{width:22px;height:22px;border-radius:7px;background:var(--accent-tint);color:var(--accent-strong);
  display:grid;place-items:center;font-size:11px;font-weight:700;flex:none}
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
    <h2>شكراً لمقترحك</h2>
    <p>وصلنا اقتراحك بنجاح، وسيُؤخذ في الاعتبار ضمن خطط تطوير الخدمة. نقدّر حرصك ومشاركتك.</p>
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
    <span class="eyebrow">صوتك مسموع</span>
    <h1>تقديم <span class="accent">اقتراح</span></h1>
    <p class="lead">لديك فكرة لتطوير الخدمة؟ اختر ما يخصّك من العناوين، أو اكتب اقتراحك بحرية. رأيك يصنع الفرق.</p>
    <div class="stepper">
      <span class="dot on">١</span>
      <span class="sep"></span>
      <span class="dot {{ $this->showTopics ? 'on' : '' }}">٢</span>
      <span>{{ $this->showTopics ? 'اختياراتك' : 'بياناتك والمقر' }}</span>
    </div>
  </div>

  <form wire:submit="submit" class="fb-card">

    {{-- ===== الخطوة الأولى: الهوية والمقر ===== --}}
    @include('livewire.feedback.includes.identity-fields')

    {{-- ===== الخطوة الثانية: مجالات المقترح (تظهر بعد اختيار المقر) ===== --}}
    @if($this->showTopics)
      <div class="stage2" x-data="{ topics: @entangle('topics') }"
           x-init="$el.scrollIntoView({behavior:'smooth',block:'nearest'})">

        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>مجال المقترح <span class="h2-opt">(يمكنك اختيار أكثر من عنوان)</span></h2></div>

          @foreach($domains as $domainKey => [$domainTitle, $topics])
            <div class="dgroup" wire:key="dom-{{ $domainKey }}">
              <div class="dtitle"><span class="num">{{ $arNum[$loop->index] }}</span>{{ $domainTitle }}</div>
              <div class="chips">
                @foreach($topics as $topicKey => $topicLabel)
                  <label class="chip" :class="topics.includes('{{ $topicKey }}') ? 'sel' : ''">
                    <input type="checkbox" value="{{ $topicKey }}" x-model="topics" />
                    <span class="box">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    </span>
                    {{ $topicLabel }}
                  </label>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>

        {{-- اقتراح آخر --}}
        <div class="sec">
          <div class="sec-head"><span class="bar"></span><h2>اقتراح آخر <span class="h2-opt">(اختياري)</span></h2></div>
          <div class="field">
            <textarea class="inp" rows="3" wire:model="other_suggestion"
                      placeholder="اكتب هنا أي مقترح آخر لا يوجد ضمن العناوين السابقة..."></textarea>
            @error('other_suggestion')<p class="err">{{ $message }}</p>@enderror
          </div>
        </div>

        @error('topics')<p class="err" style="margin-top:-8px">{{ $message }}</p>@enderror
        @error('gate')<p class="gate-error">{{ $message }}</p>@enderror

        <button type="submit" class="submit-btn" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submit">إرسال المقترح</span>
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
          لا يتيح النظام تقديم أكثر من مقترح لهذا المقر كل أسبوع.<br>
          يمكنك المحاولة اعتباراً من <span class="date">{{ $gateRetryDate }}</span>.
        </p>
        @include('livewire.feedback.includes.other-forms-alt')
      </div>
    @else
      <div class="gate-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        اختر المحافظة والمقر لعرض مجالات المقترح.
      </div>
    @endif

  </form>
@endif

</div>

{{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
<div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</main>
