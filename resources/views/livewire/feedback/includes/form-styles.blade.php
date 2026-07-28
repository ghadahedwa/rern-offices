{{-- ستايل مشترك لفورمات البوابة العامة (التقييم/المقترحات). --}}
{{-- كل شاشة تحدد لونها عبر: .fb-main{--accent;--accent-strong;--accent-tint} --}}
@verbatim
<style>
.fb-main{position:relative;z-index:1;flex:1;display:flex;flex-direction:column;align-items:center;
  justify-content:flex-start;padding:clamp(18px,4vh,40px) 18px 40px}
.fb-wrap{width:100%;max-width:640px}

/* hero */
.fb-hero{text-align:center;margin-bottom:22px}
.fb-hero .eyebrow{font-family:'Reem Kufi',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.14em;
  color:var(--accent-strong);display:inline-flex;align-items:center;gap:10px;margin-bottom:12px}
.fb-hero .eyebrow::before,.fb-hero .eyebrow::after{content:"";width:24px;height:1px;background:var(--accent)}
.fb-hero h1{font-family:'Reem Kufi',sans-serif;font-weight:700;font-size:clamp(26px,4.5vw,36px);
  color:var(--ink);line-height:1.2}
.fb-hero h1 .accent{color:var(--accent-strong)}
.fb-hero .lead{margin-top:8px;font-size:13.5px;line-height:1.7;color:var(--ink-soft);max-width:44ch;margin-inline:auto}
.stepper{margin-top:14px;display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;
  color:var(--ink-soft);background:var(--paper-2);border:1px solid var(--line);border-radius:999px;padding:5px 14px}
.stepper .dot{width:16px;height:16px;border-radius:50%;display:grid;place-items:center;font-size:10px;
  background:var(--line);color:var(--ink-soft);transition:.25s}
.stepper .dot.on{background:var(--accent);color:#fff}
.stepper .sep{width:14px;height:1px;background:var(--line)}

/* card */
.fb-card{background:var(--paper-2);border:1px solid var(--line);border-radius:22px;
  box-shadow:var(--shadow);padding:clamp(20px,4vw,30px)}

/* section head */
.sec{margin-top:28px}
.sec:first-child{margin-top:0}
.sec-head{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.sec-head .bar{width:4px;height:18px;border-radius:99px;background:var(--accent)}
.sec-head h2{font-family:'Reem Kufi',sans-serif;font-size:15px;font-weight:600;color:var(--ink)}
.sec-head .h2-opt{font-weight:400;color:var(--ink-soft);font-size:12px}

/* fields */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{margin-bottom:14px}
.field:last-child{margin-bottom:0}
.field label{display:block;font-size:12.5px;font-weight:600;color:var(--ink);margin-bottom:6px}
.field label .req{color:var(--danger);margin-inline-start:2px}
/* 16px إلزامي على الموبايل لمنع الـ zoom التلقائي في iOS عند التركيز على الحقل */
.field .inp{width:100%;font-family:inherit;font-size:16px;color:var(--ink);background:var(--paper);
  border:1px solid var(--line);border-radius:12px;padding:11px 13px;transition:.18s;outline:none}
.field .inp::placeholder{color:var(--ink-soft);opacity:.7}
.field .inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 22%,transparent)}
.field textarea.inp{resize:vertical;min-height:90px;line-height:1.6}
.field select.inp{appearance:none;cursor:pointer;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235b667f' stroke-width='2' stroke-linecap='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:left 12px center;background-size:16px;padding-inline-start:36px}
.field select.inp:disabled{opacity:.55;cursor:not-allowed}
.err{color:var(--danger);font-size:11.5px;margin-top:5px}

/* submit */
.submit-btn{margin-top:26px;width:100%;font-family:'Reem Kufi',sans-serif;font-size:15px;font-weight:600;
  color:#fff;background:var(--accent);border:none;border-radius:14px;padding:14px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:9px;transition:.2s;
  box-shadow:0 8px 20px -8px color-mix(in srgb,var(--accent) 70%,transparent)}
.submit-btn:hover{background:var(--accent-strong)}
.submit-btn svg{width:18px;height:18px}
.submit-btn:disabled{opacity:.6;cursor:progress}

/* reveal + gate */
.stage2{animation:reveal .45s cubic-bezier(.2,.7,.2,1)}
@keyframes reveal{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
.gate-hint{margin-top:22px;display:flex;align-items:center;gap:10px;font-size:13px;color:var(--ink-soft);
  background:var(--paper);border:1px dashed var(--line);border-radius:12px;padding:14px 16px}
.gate-hint svg{width:18px;height:18px;flex:none;color:var(--accent)}

/* حجب التكرار (قاعدة الأسبوعين) */
.blocked{margin-top:22px;text-align:center;background:var(--danger-tint);
  border:1px solid color-mix(in srgb,var(--danger) 35%,var(--line));border-radius:16px;padding:24px 20px}
.blocked .ic{width:56px;height:56px;border-radius:50%;background:var(--paper-2);color:var(--danger);
  display:grid;place-items:center;margin:0 auto 14px}
.blocked .ic svg{width:28px;height:28px}
.blocked p{font-size:13.5px;line-height:1.8;color:var(--ink)}
.blocked .date{font-weight:700;color:var(--danger)}
.blocked .alt{margin-top:16px;display:inline-flex;align-items:center;gap:7px;font-family:'Reem Kufi',sans-serif;
  font-size:13.5px;font-weight:600;color:var(--accent-strong);text-decoration:none;border:1px solid var(--accent);
  border-radius:12px;padding:9px 18px;transition:.2s}
.blocked .alt:hover{background:var(--accent-tint)}
.blocked .alt svg{width:15px;height:15px}

/* خطأ حد الجهاز */
.gate-error{margin-top:16px;font-size:13px;color:var(--danger);background:var(--danger-tint);
  border:1px solid color-mix(in srgb,var(--danger) 30%,var(--line));border-radius:12px;padding:12px 15px;text-align:center}

/* thank you */
.thanks{text-align:center;padding:34px 24px}
.thanks .ic{width:76px;height:76px;border-radius:50%;background:var(--accent-tint);color:var(--accent);
  display:grid;place-items:center;margin:0 auto 18px}
.thanks .ic svg{width:40px;height:40px}
.thanks h2{font-family:'Reem Kufi',sans-serif;font-size:22px;font-weight:700;color:var(--ink)}
.thanks p{margin-top:8px;font-size:13.5px;line-height:1.7;color:var(--ink-soft)}
.thanks .actions{margin-top:22px;display:flex;flex-direction:column;gap:10px;align-items:center}
.thanks .also{display:inline-flex;align-items:center;gap:8px;font-family:'Reem Kufi',sans-serif;
  font-size:14px;font-weight:600;color:#fff;background:var(--accent);border:none;border-radius:12px;
  padding:11px 22px;text-decoration:none;transition:.2s}
.thanks .also:hover{background:var(--accent-strong)}
.thanks .also svg{width:16px;height:16px}
.thanks .back{display:inline-flex;align-items:center;gap:8px;font-family:'Reem Kufi',sans-serif;
  font-size:14px;font-weight:600;color:var(--accent-strong);text-decoration:none;border:1px solid var(--accent);
  border-radius:12px;padding:10px 20px;transition:.2s}
.thanks .back:hover{background:var(--accent-tint)}

@media (max-width:520px){
  .grid2{grid-template-columns:1fr}
}
</style>
@endverbatim
