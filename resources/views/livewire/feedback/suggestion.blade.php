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
.chips{display:flex;flex-wrap:wrap;gap:9px}
.chip{position:relative;display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:500;
  color:var(--ink);background:var(--paper);border:1px solid var(--line);border-radius:999px;
  padding:9px 14px;cursor:pointer;transition:.16s;-webkit-tap-highlight-color:transparent}
.chip:hover{border-color:color-mix(in srgb,var(--accent) 50%,var(--line))}
.chip input{position:absolute;opacity:0;pointer-events:none}
.chip .box{width:16px;height:16px;border-radius:5px;border:2px solid var(--line);flex:none;
  display:grid;place-items:center;transition:.16s}
.chip .box svg{width:11px;height:11px;color:#fff;opacity:0;transform:scale(.5);transition:.16s}
.chip.sel{border-color:var(--accent);background:var(--accent-tint)}
.chip.sel .box{background:var(--accent);border-color:var(--accent)}
.chip.sel .box svg{opacity:1;transform:none}
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
    <a href="{{ route('feedback') }}" wire:navigate class="back">
      العودة للرئيسية
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
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
          لا يتيح النظام تقديم أكثر من مقترح لهذا المقر كل أسبوعين.<br>
          يمكنك المحاولة اعتباراً من <span class="date">{{ $gateRetryDate }}</span>.
        </p>
        <a href="{{ route('feedback.rating') }}" wire:navigate class="alt">
          هل ترغب بتقييم الخدمة؟
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
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
