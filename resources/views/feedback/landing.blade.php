<x-layouts.feedback>

@push('styles')
@verbatim
<style>
/* ============ Hero (خاص بصفحة الـ landing) ============ */
main{position:relative;z-index:1;flex:1;min-height:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;padding:16px 20px;text-align:center}
.eyebrow{font-family:'Reem Kufi',sans-serif;font-size:13px;font-weight:500;letter-spacing:.14em;
  color:var(--gold-dark);display:inline-flex;align-items:center;gap:10px;margin-bottom:14px}
.eyebrow::before,.eyebrow::after{content:"";width:26px;height:1px;background:var(--gold)}
h1{font-family:'Reem Kufi',sans-serif;font-weight:700;font-size:clamp(30px,5vw,46px);
  color:var(--ink);text-wrap:balance;line-height:1.2}
h1 .accent{color:var(--gold-dark)}
.lead{margin-top:10px;max-width:46ch;font-size:clamp(13px,1.8vw,15px);line-height:1.75;
  color:var(--ink-soft)}

/* ============ Cards ============ */
.cards{margin-top:clamp(18px,3vh,34px);width:100%;max-width:700px;
  display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
.card{--c:var(--gold);--tint:var(--rating-tint);
  position:relative;background:var(--paper-2);border:1px solid var(--line);border-radius:20px;
  padding:28px 24px 24px;text-align:center;cursor:pointer;text-decoration:none;color:inherit;
  display:flex;flex-direction:column;align-items:center;
  box-shadow:var(--shadow);overflow:hidden;
  transition:transform .28s cubic-bezier(.2,.7,.2,1),box-shadow .28s,border-color .28s}
.card::before{content:"";position:absolute;top:0;inset-inline:0;height:4px;background:var(--c);
  transform:scaleX(.4);transform-origin:center;transition:transform .3s}
.card:hover,.card:focus-visible{transform:translateY(-6px);box-shadow:var(--shadow-hover);
  border-color:color-mix(in srgb,var(--c) 55%,var(--line));outline:none}
.card:hover::before,.card:focus-visible::before{transform:scaleX(1)}
.card .ic{width:62px;height:62px;border-radius:18px;background:var(--tint);color:var(--c);
  display:grid;place-items:center;margin-bottom:16px;transition:transform .28s}
.card:hover .ic{transform:scale(1.06) rotate(-3deg)}
.card .ic svg{width:32px;height:32px}
.card h3{font-family:'Reem Kufi',sans-serif;font-weight:600;font-size:20px;color:var(--ink)}
.card p{margin-top:8px;font-size:13px;line-height:1.7;color:var(--ink-soft);max-width:24ch}
.card .go{margin-top:16px;display:inline-flex;align-items:center;gap:8px;font-size:13px;
  font-weight:600;color:var(--c)}
.card .go svg{width:16px;height:16px;transition:transform .28s}
.card:hover .go svg{transform:translateX(-5px)}
.card .stars{display:flex;gap:3px;margin-top:2px}
.card .stars svg{width:15px;height:15px;fill:var(--rating);stroke:none}

@media (max-width:820px){
  .cards{grid-template-columns:1fr;max-width:420px;gap:16px}
  .card{flex-direction:row;text-align:start;padding:20px;align-items:center;gap:18px}
  .card .ic{margin-bottom:0;width:60px;height:60px;border-radius:18px;flex:none}
  .card .ic svg{width:30px;height:30px}
  .card .body{flex:1}
  .card .go{margin-top:10px}
  .card p{max-width:none}
}

/* ============ شريط إرشادي — الشكاوى الرسمية ============ */
.official-note{margin-top:clamp(16px,2.4vh,26px);width:100%;max-width:700px;
  display:flex;align-items:flex-start;gap:14px;text-align:start;
  background:var(--paper-2);border:1px solid var(--line);border-inline-start:3px solid var(--danger);
  border-radius:14px;padding:14px 18px;box-shadow:var(--shadow)}
.official-note .on-ic{width:40px;height:40px;flex:none;border-radius:12px;
  background:var(--danger-tint);color:var(--danger);display:grid;place-items:center}
.official-note .on-ic svg{width:22px;height:22px}
.official-note .on-title{font-size:12.5px;line-height:1.6;color:var(--ink);font-weight:500}
.official-note .on-title b{font-weight:700}
.official-note .on-links{margin-top:8px;display:flex;flex-wrap:wrap;gap:7px 16px}
.official-note .on-links a{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;
  color:var(--ink-soft);text-decoration:none;transition:.18s}
.official-note .on-links a:hover{color:var(--danger)}
.official-note .on-links a svg{width:15px;height:15px;flex:none}
@media (max-width:520px){
  .official-note{padding:13px 15px;gap:11px}
  .official-note .on-links{gap:7px 12px}
}
</style>
@endverbatim
@endpush

{{-- ===== Hero ===== --}}
<main>
  <span class="eyebrow">نسعد بخدمتك</span>
  <h1>رأيك <span class="accent">يهمنا</span></h1>
  <p class="lead">
    شاركنا رأيك في خدماتنا، ولن يستغرق الأمر سوى دقائق.
  </p>

  <div class="cards">

    {{-- اقتراح --}}
    <a href="{{ route('feedback.suggestion') }}" wire:navigate class="card" style="--c:var(--suggestion);--tint:var(--suggestion-tint)">
      <div class="ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-4 10.5c.6.6 1 1.4 1 2.5h6c0-1.1.4-1.9 1-2.5A6 6 0 0 0 12 3Z"/>
        </svg>
      </div>
      <div class="body">
        <h3>تقديم اقتراح</h3>
        <p>لديك فكرة لتطوير الخدمة؟ نرحّب بمقترحاتك ونوليها كل اهتمام.</p>
        <span class="go">ابدأ الآن
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </span>
      </div>
    </a>

    {{-- تقييم --}}
    <a href="{{ route('feedback.rating') }}" wire:navigate class="card" style="--c:var(--rating);--tint:var(--rating-tint)">
      <div class="ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/>
        </svg>
      </div>
      <div class="body">
        <h3>تقييم الخدمة</h3>
        <p>قيّم تجربتك في المقر ليصلنا مدى رضاك عن الخدمة.</p>
        <div class="stars" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
          <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
          <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
          <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
          <svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L4.5 9.2l5.9-.9Z"/></svg>
        </div>
      </div>
    </a>

  </div>

  {{-- ===== شريط إرشادي: الشكاوى الرسمية — مُخفى بمفتاح الإعداد لحين قرار العميل ===== --}}
  @if(config('feedback.show_complaints_bar'))
  <div class="official-note">
    <div class="on-ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
      </svg>
    </div>
    <div class="on-body">
      <p class="on-title">لتقديم شكوى رسمية، توجّه إلى <b>منظومة الشكاوى الحكومية الموحدة بمجلس الوزراء</b></p>
      <div class="on-links">
        <a href="tel:16528">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
          16528
        </a>
        <a href="https://wa.me/201555516528" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.4-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.3-1.7c-.2-.3 0-.4.1-.6l.4-.5.3-.4v-.4c0-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1 2.7 11.8 11.8 0 0 0 4.6 4c.6.3 1.1.4 1.5.6a3.6 3.6 0 0 0 1.6.1 2.7 2.7 0 0 0 1.7-1.2 2.2 2.2 0 0 0 .2-1.2c-.1-.1-.2-.2-.5-.3z"/></svg>
          واتساب ٠١٥٥٥٥١٦٥٢٨
        </a>
        <a href="https://www.shakwa.eg" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20 15.3 15.3 0 0 1 0-20z"/></svg>
          shakwa.eg
        </a>
      </div>
    </div>
  </div>
  @endif
</main>

</x-layouts.feedback>
