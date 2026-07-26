<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $title ?? 'رأيك يهمنا — منظومة قطاع الشهر العقاري والتوثيق' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&family=reem-kufi:400,500,600,700" rel="stylesheet" />
@verbatim
<style>
/* ============ Design tokens (مصدر موحّد للبوابة العامة كلها) ============ */
:root{
  --paper:#f7f4ec; --paper-2:#fffdf8; --ink:#26314f; --ink-soft:#5b667f;
  --line:#e6dfcf; --gold:#c9a847; --gold-dark:#b8962e;
  --rating:#c9a847; --rating-tint:#f6eecf;
  --suggestion:#2f7d78; --suggestion-tint:#e2f0ee;
  --danger:#b8544a; --danger-tint:#f7e7e4;
  --overlay:rgba(247,244,236,.80);
  --shadow:0 1px 2px rgba(38,49,79,.05), 0 12px 30px -12px rgba(38,49,79,.18);
  --shadow-hover:0 2px 4px rgba(38,49,79,.06), 0 22px 45px -14px rgba(38,49,79,.32);
}
@media (prefers-color-scheme: dark){
  :root{
    --overlay:rgba(12,15,22,.88);
    --paper:#12161f; --paper-2:#1b2130; --ink:#eae6d9; --ink-soft:#98a1ba;
    --line:#2a3143; --gold:#d8b856; --gold-dark:#c9a847;
    --rating:#d8b856; --rating-tint:#2e281680;
    --suggestion:#5bb3ad; --suggestion-tint:#1b302e80;
    --danger:#e07d72; --danger-tint:#33232180;
    --shadow:0 1px 2px rgba(0,0,0,.4), 0 14px 34px -14px rgba(0,0,0,.6);
    --shadow-hover:0 2px 6px rgba(0,0,0,.5), 0 26px 50px -14px rgba(0,0,0,.75);
  }
}
:root[data-theme="light"]{
  --overlay:rgba(247,244,236,.80);
  --paper:#f7f4ec; --paper-2:#fffdf8; --ink:#26314f; --ink-soft:#5b667f;
  --line:#e6dfcf; --gold:#c9a847; --gold-dark:#b8962e;
  --rating:#c9a847; --rating-tint:#f6eecf;
  --suggestion:#2f7d78; --suggestion-tint:#e2f0ee;
  --danger:#b8544a; --danger-tint:#f7e7e4;
  --shadow:0 1px 2px rgba(38,49,79,.05), 0 12px 30px -12px rgba(38,49,79,.18);
  --shadow-hover:0 2px 4px rgba(38,49,79,.06), 0 22px 45px -14px rgba(38,49,79,.32);
}
:root[data-theme="dark"]{
  --overlay:rgba(12,15,22,.88);
  --paper:#12161f; --paper-2:#1b2130; --ink:#eae6d9; --ink-soft:#98a1ba;
  --line:#2a3143; --gold:#d8b856; --gold-dark:#c9a847;
  --rating:#d8b856; --rating-tint:#2e281680;
  --suggestion:#5bb3ad; --suggestion-tint:#1b302e80;
  --danger:#e07d72; --danger-tint:#33232180;
  --shadow:0 1px 2px rgba(0,0,0,.4), 0 14px 34px -14px rgba(0,0,0,.6);
  --shadow-hover:0 2px 6px rgba(0,0,0,.5), 0 26px 50px -14px rgba(0,0,0,.75);
}

*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;text-size-adjust:100%}
body{
  font-family:'Cairo','Segoe UI',Tahoma,sans-serif;
  color:var(--ink);min-height:100dvh;
  display:flex;flex-direction:column;position:relative;
  transition:color .3s ease;
  background:var(--paper) url('/images/bg-image.jpg') center/cover no-repeat fixed;
}
body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
  background:var(--overlay);transition:background .3s ease}
.dots{position:fixed;width:190px;height:190px;opacity:.5;pointer-events:none;z-index:0;
  background-image:radial-gradient(circle,var(--gold) 1.4px,transparent 1.4px);
  background-size:17px 17px;-webkit-mask-image:radial-gradient(closest-side,#000,transparent);
          mask-image:radial-gradient(closest-side,#000,transparent);}
.dots.tr{top:-30px;left:-30px}
.dots.bl{bottom:-30px;right:-30px}

/* ============ Top bar ============ */
.topbar{position:relative;z-index:2;flex:none;display:flex;align-items:center;
  justify-content:space-between;padding:12px 26px;gap:16px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand .emblem{width:54px;height:54px;flex:none;object-fit:contain}
.brand .titles{display:flex;flex-direction:column;line-height:1.25}
.brand .titles b{font-family:'Reem Kufi',sans-serif;font-weight:600;font-size:15px;color:var(--ink)}
.theme-btn{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);
  background:var(--paper-2);color:var(--ink-soft);cursor:pointer;display:grid;place-items:center;transition:.2s}
.theme-btn:hover{color:var(--gold);border-color:var(--gold)}
.theme-btn:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.theme-btn svg{width:19px;height:19px}
.theme-btn .moon{display:none}
:root[data-theme="dark"] .theme-btn .sun{display:none}
:root[data-theme="dark"] .theme-btn .moon{display:block}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]) .theme-btn .sun{display:none}
  :root:not([data-theme="light"]) .theme-btn .moon{display:block}}

/* ============ Footer ============ */
footer{position:relative;z-index:1;flex:none;text-align:center;padding:12px 20px;
  border-top:1px solid var(--line);color:var(--ink-soft);font-size:12px;display:flex;
  align-items:center;justify-content:center;gap:8px;flex-wrap:wrap}
footer svg{width:14px;height:14px}

@media (max-width:520px){
  .topbar{padding:10px 16px;gap:10px}
  .brand{gap:10px}
  .brand .emblem{width:50px;height:50px}
  .brand .titles b{font-size:12.5px;line-height:1.4}
  .theme-btn{width:36px;height:36px}
}
@media (prefers-reduced-motion:reduce){*{transition:none!important}html{scroll-behavior:auto}}
</style>
@endverbatim
@stack('styles')
@livewireStyles
</head>
<body>
<div class="dots tr"></div>
<div class="dots bl"></div>

{{-- ===== Top bar ===== --}}
<header class="topbar">
  <a class="brand" href="{{ route('feedback') }}" wire:navigate>
    <img class="emblem" src="{{ asset('images/logo3.png') }}" alt="شعار وزارة العدل" />
    <div class="titles">
      <b>منظومة قطاع الشهر العقاري والتوثيق</b>
    </div>
  </a>
  <button class="theme-btn" onclick="toggleTheme()" aria-label="تبديل المظهر">
    <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
  </button>
</header>

{{ $slot }}

{{-- ===== Footer ===== --}}
<footer>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
  بياناتك آمنة وتُستخدم لتحسين الخدمة فقط · جميع الحقوق محفوظة &copy; وزارة العدل
</footer>

<script>
function toggleTheme(){
  const root=document.documentElement;
  const now=root.getAttribute('data-theme')
    || (matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');
  root.setAttribute('data-theme', now==='dark'?'light':'dark');
}
// أرقام فقط: يحوّل الأرقام العربية/الفارسية لإنجليزية ثم يزيل أي محرف غير رقمي
function digitsOnly(v){
  return String(v)
    .replace(/[٠-٩]/g, d => d.charCodeAt(0) - 0x0660)
    .replace(/[۰-۹]/g, d => d.charCodeAt(0) - 0x06F0)
    .replace(/\D/g, '');
}
</script>
@livewireScripts
</body>
</html>
