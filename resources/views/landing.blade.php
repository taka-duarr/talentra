<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Talentra — Tumbuh · Uji · Berkembang</title>
<x-favicon />
<meta name="description" content="Talentra: satu tempat belajar dan berlatih TKA SD, SMP, SMA, UTBK-SNBT, CPNS, BUMN, dan Kedinasan, lengkap dengan analisis hasil AI dan rekomendasi belajar.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script type="importmap">
{ "imports": {
  "three": "https://unpkg.com/three@0.143.0/build/three.module.js",
  "three/examples/jsm/": "https://unpkg.com/three@0.143.0/examples/jsm/"
}}
</script>
<style>
:root{
  --bg:#f4f7fd; --ink:#0d1b3d; --muted:#4b5a7a; --muted-2:#7d89a6;
  --line:rgba(13,27,61,.08); --line-strong:rgba(13,27,61,.14);
  --accent:#14b8a6; --accent-2:#0d9488; --accent-soft:rgba(20,184,166,.12);
  --navy:#1b2f66; --navy-2:#13234d; --sun:#ffc23d; --coral:#ff7a6b; --sky:#5b8cff;
  --glass:rgba(255,255,255,.66); --glass-2:rgba(255,255,255,.86);
  --radius:20px; --radius-lg:28px; --maxw:1180px;
  --shadow:0 24px 60px -28px rgba(19,40,90,.35);
  --shadow-sm:0 10px 30px -18px rgba(19,40,90,.35);
  --font:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);color:var(--ink);background-color:var(--bg);background-image:linear-gradient(rgba(27,47,102,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(27,47,102,.045) 1px,transparent 1px);background-size:34px 34px;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;overflow-x:hidden}
a{color:inherit;text-decoration:none}
html.lenis,html.lenis body{height:auto}
.lenis.lenis-smooth{scroll-behavior:auto !important}
.lenis.lenis-stopped{overflow:hidden}
[data-reveal]{opacity:0;transform:translateY(26px);transition:opacity .7s cubic-bezier(.22,.7,.2,1),transform .7s cubic-bezier(.22,.7,.2,1);transition-delay:var(--rd,0ms)}
[data-reveal].in{opacity:1;transform:none}

/* soft color blobs + 3D canvas behind the page */
.bg-blobs{position:fixed;inset:0;z-index:0;pointer-events:none;background:
  radial-gradient(40vw 40vw at 85% 10%, rgba(20,184,166,.16), transparent 60%),
  radial-gradient(35vw 35vw at 5% 35%, rgba(91,140,255,.14), transparent 60%),
  radial-gradient(30vw 30vw at 70% 90%, rgba(255,194,61,.16), transparent 60%)}
.scene-canvas{position:fixed;inset:0;width:100vw;height:100vh;display:block;z-index:0;pointer-events:none;transition:opacity .4s ease}
.page{position:relative;z-index:1}
.section{position:relative;max-width:var(--maxw);margin:0 auto;padding:clamp(80px,11vw,140px) 24px}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--accent-2);margin-bottom:16px}
.eyebrow::before{content:"";width:18px;height:2px;border-radius:2px;background:var(--accent)}
.section-head{max-width:700px;margin:0 auto clamp(44px,6vw,72px);text-align:center}
.section-head .eyebrow::after{content:"";width:18px;height:2px;border-radius:2px;background:var(--accent)}
h2.title{font-size:clamp(30px,4.2vw,48px);font-weight:800;line-height:1.1;letter-spacing:-.025em}
.lead{margin-top:16px;color:var(--muted);font-size:clamp(16px,1.6vw,18px);line-height:1.7}
mark{background:none;color:inherit;position:relative;white-space:nowrap;z-index:0}
mark::after{content:"";position:absolute;left:-4px;right:-4px;bottom:.08em;height:.38em;background:var(--sun);border-radius:6px;z-index:-1;transform:rotate(-1.5deg);opacity:.85}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;font-family:inherit;font-size:15px;font-weight:700;padding:14px 24px;border-radius:14px;border:1.5px solid transparent;cursor:pointer;transition:transform .18s ease,box-shadow .25s ease,background .2s ease,border-color .2s ease;white-space:nowrap}
.btn svg{width:18px;height:18px}
.btn-primary{color:#fff;background:linear-gradient(180deg,#19c7b3,var(--accent-2));box-shadow:0 12px 26px -10px rgba(13,148,136,.65),inset 0 1px 0 rgba(255,255,255,.35)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 18px 34px -12px rgba(13,148,136,.75)}
.btn-outline{color:var(--ink);background:rgba(255,255,255,.75);border-color:var(--line-strong);backdrop-filter:blur(8px)}
.btn-outline:hover{border-color:var(--navy);transform:translateY(-2px)}
.btn-navy{color:#fff;background:var(--navy);box-shadow:0 12px 26px -12px rgba(27,47,102,.7)}
.btn-navy:hover{background:var(--navy-2);transform:translateY(-2px)}
.btn-sm{padding:10px 18px;font-size:14px;border-radius:12px}
svg.i{fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}

/* NAV */
.nav-wrap{position:sticky;top:0;z-index:20;padding:14px 16px 0}
.nav{max-width:var(--maxw);margin:0 auto;padding:10px 12px 10px 18px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-radius:18px;background:rgba(255,255,255,.72);border:1px solid var(--line);backdrop-filter:blur(14px);box-shadow:var(--shadow-sm)}
.brand{display:inline-flex;align-items:center;gap:10px;font-weight:800;font-size:20px;letter-spacing:-.01em;color:var(--navy)}
.brand-mark{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(150deg,var(--accent),var(--navy));color:#fff;box-shadow:0 6px 14px -6px rgba(20,184,166,.7)}
.brand-mark svg{width:20px;height:20px;stroke-width:2.4}
.nav-links{display:flex;gap:28px;font-size:14.5px;font-weight:600;color:var(--muted)}
.nav-links a:hover{color:var(--accent-2)}
.nav-actions{display:flex;gap:8px}

/* HERO */
.hero{position:relative;height:calc(100vh - 70px);min-height:560px}
.hero-inner{max-width:760px;margin:0 auto;padding:clamp(28px,6vh,72px) 24px 0;text-align:center;display:flex;flex-direction:column;align-items:center}
.hero-title{font-size:clamp(36px,5.2vw,64px);font-weight:800;line-height:1.04;letter-spacing:-.035em;color:var(--ink)}
.hero-sub{margin-top:18px;max-width:560px;font-size:clamp(15.5px,1.6vw,18px);line-height:1.65;color:var(--muted)}
.hero-actions{margin-top:28px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
.cats{max-width:var(--maxw);margin:0 auto;padding:clamp(40px,6vw,72px) 24px 0;text-align:center}
.cats-label{font-size:12.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-2);margin-bottom:16px}
.cats-row{display:flex;flex-wrap:wrap;justify-content:center;gap:10px}
.cat{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;background:rgba(255,255,255,.82);border:1px solid var(--line);font-size:14.5px;font-weight:700;color:var(--navy);box-shadow:0 6px 16px -12px rgba(19,40,90,.4);transition:transform .2s ease,border-color .2s ease}
.cat:hover{transform:translateY(-2px);border-color:var(--accent)}
.cat i{width:9px;height:9px;border-radius:50%;background:var(--c)}

/* FEATURES */
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.card{position:relative;padding:28px;border-radius:var(--radius);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(14px);box-shadow:var(--shadow-sm);transition:transform .25s ease,box-shadow .25s ease,background .25s ease}
.card:hover{background:var(--glass-2);box-shadow:var(--shadow)}
.card[data-reveal].in:hover{transform:translateY(-4px)}
.card-icon{width:50px;height:50px;border-radius:14px;display:grid;place-items:center;color:var(--c);background:color-mix(in srgb,var(--c) 14%,white);margin-bottom:20px}
.card-icon svg{width:24px;height:24px}
.card h3{font-size:19px;font-weight:800;margin-bottom:8px;letter-spacing:-.01em}
.card p{font-size:15px;line-height:1.65;color:var(--muted)}

/* STEPS */
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;counter-reset:step}
.step{padding:26px 24px;border-radius:var(--radius);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(12px);box-shadow:var(--shadow-sm)}
.step-num{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;font-weight:800;color:#fff;background:var(--navy);margin-bottom:16px}
.step:nth-child(2) .step-num{background:var(--accent-2)}
.step:nth-child(3) .step-num{background:var(--sky)}
.step:nth-child(4) .step-num{background:var(--coral)}
.step h3{font-size:17px;font-weight:800;margin-bottom:6px}
.step p{font-size:14.5px;line-height:1.6;color:var(--muted)}

/* SHOWCASE */
.showcase-grid{display:grid;grid-template-columns:1fr 1.05fr;gap:clamp(36px,6vw,72px);align-items:center}
.showcase-copy .btn{margin-top:28px}
.checks{list-style:none;margin-top:24px;display:grid;gap:12px}
.checks li{display:flex;gap:12px;align-items:flex-start;font-size:15.5px;line-height:1.55;color:var(--muted)}
.checks li svg{flex:none;width:22px;height:22px;padding:4px;border-radius:7px;color:#fff;background:var(--accent);stroke-width:3;margin-top:1px}
.dashboard{position:relative;padding:24px;border-radius:var(--radius-lg);background:var(--glass-2);border:1px solid var(--line);box-shadow:var(--shadow);backdrop-filter:blur(18px)}
.dash-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px}
.dash-top strong{font-size:15.5px;font-weight:800}
.dash-top small{display:block;font-size:12.5px;color:var(--muted-2);margin-top:2px}
.dash-score{text-align:right}
.dash-score b{font-size:28px;font-weight:800;color:var(--accent-2);letter-spacing:-.02em}
.dash-score span{font-size:12px;color:var(--muted-2);display:block}
.dash-caption{display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:var(--muted-2);margin:0 2px 8px}
.dash-bars{display:flex;align-items:flex-end;gap:10px;height:140px;padding:14px;background:rgba(27,47,102,.04);border:1px solid var(--line);border-radius:14px}
.dash-bar{flex:1;height:var(--h);background:linear-gradient(180deg,#2dd4bf,rgba(20,184,166,.35));border-radius:7px 7px 3px 3px;transform-origin:bottom;animation:grow 1s cubic-bezier(.22,.7,.2,1) both;animation-delay:var(--d)}
.dash-bar:last-child{background:linear-gradient(180deg,var(--navy),rgba(27,47,102,.45))}
@keyframes grow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
.dash-rows{margin-top:18px;display:grid;gap:11px}
.dash-row{display:grid;grid-template-columns:110px 1fr 40px;gap:14px;align-items:center;font-size:13.5px;font-weight:600;color:var(--muted)}
.dash-row b{color:var(--ink);text-align:right;font-size:13px}
.dash-track{height:9px;border-radius:999px;background:rgba(27,47,102,.07);overflow:hidden}
.dash-fill{height:100%;width:var(--w);border-radius:999px;background:linear-gradient(90deg,var(--accent-2),#2dd4bf);animation:fill 1.2s cubic-bezier(.22,.7,.2,1) .3s both}
.dash-fill.low{background:linear-gradient(90deg,#f0563f,var(--coral))}
@keyframes fill{from{width:0}to{width:var(--w)}}
.dash-ai{margin-top:18px;display:flex;gap:12px;padding:14px 16px;border-radius:14px;background:linear-gradient(135deg,rgba(255,194,61,.16),rgba(20,184,166,.1));border:1px solid rgba(255,194,61,.4);font-size:13.5px;line-height:1.6;color:var(--muted)}
.dash-ai svg{flex:none;width:20px;height:20px;color:#d99a00;margin-top:1px}
.dash-ai strong{color:var(--ink)}
.stats{margin-top:clamp(56px,8vw,90px);display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.stat{text-align:center;padding:28px 18px;border-radius:var(--radius);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(12px);box-shadow:var(--shadow-sm)}
.stat-value{font-size:clamp(30px,3.8vw,42px);font-weight:800;letter-spacing:-.03em;color:var(--navy)}
.stat-value em{font-style:normal;color:var(--accent-2)}
.stat-label{font-size:14px;font-weight:500;color:var(--muted);margin-top:6px}

/* PRICING */
.pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;align-items:stretch}
.plan{position:relative;display:flex;flex-direction:column;padding:32px 28px;border-radius:var(--radius-lg);background:var(--glass-2);border:1px solid var(--line);backdrop-filter:blur(14px);box-shadow:var(--shadow-sm)}
.plan.featured{background:linear-gradient(165deg,var(--navy),var(--navy-2));color:#fff;border-color:transparent;box-shadow:0 30px 60px -26px rgba(19,35,77,.8)}
.plan.featured .plan-desc,.plan.featured li,.plan.featured .plan-price span{color:rgba(255,255,255,.75)}
.plan.featured .plan-name{color:var(--sun)}
.plan-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);font-size:12px;font-weight:800;padding:6px 14px;border-radius:999px;color:var(--navy);background:var(--sun);box-shadow:0 8px 18px -8px rgba(217,154,0,.8)}
.plan-name{font-size:15px;font-weight:800;color:var(--accent-2)}
.plan-desc{margin-top:6px;font-size:14px;color:var(--muted);line-height:1.55;min-height:44px}
.plan-price{margin-top:20px;display:flex;align-items:baseline;gap:6px}
.plan-price strong{font-size:40px;font-weight:800;letter-spacing:-.03em}
.plan-price span{font-size:14px;color:var(--muted)}
.plan ul{list-style:none;margin:22px 0 30px;display:grid;gap:12px;font-size:14.5px;color:var(--muted)}
.plan li{display:flex;gap:10px;align-items:flex-start;line-height:1.5}
.plan li svg{flex:none;width:18px;height:18px;color:var(--accent);margin-top:2px;stroke-width:2.6}
.plan.featured li svg{color:var(--sun)}
.plan .btn{margin-top:auto;width:100%}
.plan-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:14px}
.plan-chips span{font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;background:rgba(27,47,102,.06);color:var(--navy)}
.pricing-note{margin-top:26px;text-align:center;font-size:14px;color:var(--muted-2)}

/* CTA + FOOTER */
.cta-card{position:relative;overflow:hidden;margin:0 auto;max-width:860px;text-align:center;padding:clamp(48px,7vw,76px) clamp(26px,5vw,64px);border-radius:var(--radius-lg);background:linear-gradient(150deg,var(--navy) 0%,#0f6f78 100%);color:#fff;box-shadow:0 34px 70px -30px rgba(19,35,77,.8)}
.cta-card::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);background-size:30px 30px;pointer-events:none}
.cta-card > *{position:relative}
.cta-card .eyebrow{color:var(--sun)}
.cta-card .eyebrow::before,.cta-card .eyebrow::after{background:var(--sun)}
.cta-card p{margin:16px auto 32px;max-width:520px;color:rgba(255,255,255,.78);font-size:18px;line-height:1.65}
.cta-card .hero-actions{justify-content:center;margin-top:0}
.cta-card .btn-outline{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.3)}
.footer{display:flex;justify-content:space-between;gap:48px;flex-wrap:wrap;margin-top:clamp(64px,9vw,100px);padding-top:44px;border-top:1px solid var(--line-strong)}
.footer-brand p{margin-top:14px;max-width:290px;font-size:14.5px;line-height:1.65;color:var(--muted)}
.footer-cols{display:flex;gap:clamp(40px,7vw,90px);flex-wrap:wrap}
.footer-col h4{font-size:12.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-2);font-weight:700;margin-bottom:14px}
.footer-col a{display:block;font-size:14.5px;font-weight:500;color:var(--muted);margin-bottom:10px}
.footer-col a:hover{color:var(--accent-2)}
.copyright{text-align:center;margin-top:48px;font-size:13px;color:var(--muted-2)}

/* RESPONSIVE */
@media (max-width:980px){
  .feature-grid{grid-template-columns:repeat(2,1fr)}
  .steps{grid-template-columns:repeat(2,1fr)}
  .showcase-grid{grid-template-columns:1fr}
  .showcase-copy{text-align:center}
  .checks{text-align:left;max-width:520px;margin-left:auto;margin-right:auto}
  .stats{grid-template-columns:repeat(2,1fr)}
  .pricing-grid{grid-template-columns:1fr;max-width:460px;margin:0 auto;gap:28px}
}
@media (max-width:720px){
  .nav-links{display:none}
  .section{padding:clamp(64px,16vw,100px) 20px}
}
@media (max-width:560px){
  .feature-grid,.steps{grid-template-columns:1fr}
  .dash-row{grid-template-columns:86px 1fr 36px}
  .hero-actions .btn{flex:1}
  .cta-card{padding:40px 22px}
  .footer{flex-direction:column}
  .nav-actions .btn-outline{display:none}
}
@media (max-width:380px){
  .stats{grid-template-columns:1fr}
}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  [data-reveal]{opacity:1;transform:none;transition:none}
  .dash-bar,.dash-fill{animation:none}
}
</style>
</head>
<body>
<div class="bg-blobs" aria-hidden="true"></div>
<canvas class="scene-canvas" aria-hidden="true"></canvas>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="ic-arrow" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></symbol>
    <symbol id="ic-book" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7"/><path d="M9 11h5"/></symbol>
    <symbol id="ic-timer" viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5"/><path d="M9 2h6"/></symbol>
    <symbol id="ic-analytics" viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-4"/><path d="M13 16V8"/><path d="M18 16v-7"/></symbol>
    <symbol id="ic-spark" viewBox="0 0 24 24"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z"/><path d="M19 3v4"/><path d="M17 5h4"/><path d="M5 17v4"/><path d="M3 19h4"/></symbol>
    <symbol id="ic-trophy" viewBox="0 0 24 24"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0Z"/><path d="M17 5h3v2a3 3 0 0 1-3 3"/><path d="M7 5H4v2a3 3 0 0 0 3 3"/></symbol>
    <symbol id="ic-chat" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h8"/><path d="M8 13h5"/></symbol>
    <symbol id="ic-check" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></symbol>
    <symbol id="ic-cap" viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v5"/></symbol>
    <symbol id="ic-logo" viewBox="0 0 24 24"><path d="M4 6h11"/><path d="M9.5 6v13"/><path d="m14 15 5-5"/><path d="M15.5 10H19v3.5"/></symbol>
  </defs>
</svg>

<div class="page" id="top">

  <div class="nav-wrap">
    <nav class="nav">
      <a href="#top" class="brand"><span class="brand-mark"><svg class="i"><use href="#ic-logo"/></svg></span>Talentra</a>
      <div class="nav-links">
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#analisis">Analisis AI</a>
        <a href="#harga">Harga</a>
      </div>
      <div class="nav-actions">
        <a href="{{ route('register') }}" class="btn btn-outline btn-sm">Daftar</a>
        <a href="{{ route('login') }}" class="btn btn-navy btn-sm">Masuk</a>
      </div>
    </nav>
  </div>

  {{-- HERO --}}
  <header class="hero">
    <div class="hero-inner" id="hero-copy">
      <h1 class="hero-title" data-reveal style="--rd:60ms">Belajar Terarah,<br>Lulus Lebih <mark>Pasti</mark></h1>
      <p class="hero-sub" data-reveal style="--rd:180ms">Latihan TKA, UTBK, CPNS, BUMN, dan Kedinasan dalam satu akun. Kerjakan try out, lalu biarkan AI menunjukkan bagian yang perlu kamu perbaiki.</p>
      <div class="hero-actions" data-reveal style="--rd:300ms">
        <a href="{{ route('register') }}" class="btn btn-primary">Coba Gratis Sekarang <svg class="i"><use href="#ic-arrow"/></svg></a>
        <a href="#harga" class="btn btn-outline">Lihat Paket</a>
      </div>
    </div>
  </header>

  <section class="cats" id="kategori">
    <div data-reveal>
      <div class="cats-label">Pilih tujuan ujianmu</div>
      <div class="cats-row">
        <span class="cat" style="--c:#ff7a6b"><i></i>TKA SD</span>
        <span class="cat" style="--c:#ffc23d"><i></i>TKA SMP</span>
        <span class="cat" style="--c:#5b8cff"><i></i>TKA SMA</span>
        <span class="cat" style="--c:#14b8a6"><i></i>UTBK-SNBT</span>
        <span class="cat" style="--c:#1b2f66"><i></i>CPNS</span>
        <span class="cat" style="--c:#a26bff"><i></i>BUMN</span>
        <span class="cat" style="--c:#0d9488"><i></i>Kedinasan</span>
      </div>
    </div>
  </section>

  {{-- FITUR --}}
  <section class="section" id="fitur">
    <div class="section-head">
      <span class="eyebrow" data-reveal>Semua dalam satu akun</span>
      <h2 class="title" data-reveal style="--rd:80ms">Bukan sekadar mengerjakan soal, tapi <mark>memahami</mark> hasilnya</h2>
      <p class="lead" data-reveal style="--rd:160ms">Materi, simulasi ujian, analisis, dan komunitas belajar ada di satu tempat. Tidak perlu berpindah-pindah aplikasi.</p>
    </div>
    <div class="feature-grid">
      <article class="card" data-reveal style="--rd:0ms">
        <div class="card-icon" style="--c:#14b8a6"><svg class="i"><use href="#ic-book"/></svg></div>
        <h3>Materi & bank soal</h3>
        <p>Materi per bab dan ribuan soal latihan lengkap dengan pembahasan, disusun mengikuti kisi-kisi terbaru.</p>
      </article>
      <article class="card" data-reveal style="--rd:90ms">
        <div class="card-icon" style="--c:#5b8cff"><svg class="i"><use href="#ic-timer"/></svg></div>
        <h3>Simulasi ujian CBT</h3>
        <p>Timer, navigasi soal, dan tanda ragu-ragu seperti ujian sungguhan, supaya kamu terbiasa sebelum hari-H.</p>
      </article>
      <article class="card" data-reveal style="--rd:180ms">
        <div class="card-icon" style="--c:#1b2f66"><svg class="i"><use href="#ic-analytics"/></svg></div>
        <h3>Analisis hasil</h3>
        <p>Skor per bagian, persentase penguasaan topik, dan grafik perkembangan dari setiap percobaan.</p>
      </article>
      <article class="card" data-reveal style="--rd:270ms">
        <div class="card-icon" style="--c:#e0a200"><svg class="i"><use href="#ic-spark"/></svg></div>
        <h3>Rekomendasi AI</h3>
        <p>AI membaca pola kesalahanmu lalu menyarankan materi dan latihan yang paling perlu dikerjakan berikutnya.</p>
      </article>
      <article class="card" data-reveal style="--rd:360ms">
        <div class="card-icon" style="--c:#ff7a6b"><svg class="i"><use href="#ic-trophy"/></svg></div>
        <h3>Ranking peserta</h3>
        <p>Bandingkan skormu dengan peserta lain per try out, per kategori, dan secara global setiap minggu.</p>
      </article>
      <article class="card" data-reveal style="--rd:450ms">
        <div class="card-icon" style="--c:#a26bff"><svg class="i"><use href="#ic-chat"/></svg></div>
        <h3>Forum diskusi</h3>
        <p>Tanya, berbagi tips, dan bahas soal sulit bersama pejuang ujian lain di forum global, kategori, dan materi.</p>
      </article>
    </div>
  </section>

  {{-- CARA KERJA --}}
  <section class="section" id="cara-kerja">
    <div class="section-head">
      <span class="eyebrow" data-reveal>Cara kerja</span>
      <h2 class="title" data-reveal style="--rd:80ms">Empat langkah menuju hari ujian</h2>
    </div>
    <div class="steps">
      <div class="step" data-reveal style="--rd:0ms"><div class="step-num">1</div><h3>Daftar & pilih tujuan</h3><p>Buat akun lalu pilih ujian yang sedang kamu persiapkan.</p></div>
      <div class="step" data-reveal style="--rd:90ms"><div class="step-num">2</div><h3>Pelajari materi</h3><p>Baca materi per bab dan kerjakan latihan singkat di akhir bab.</p></div>
      <div class="step" data-reveal style="--rd:180ms"><div class="step-num">3</div><h3>Kerjakan try out</h3><p>Simulasi ujian berbasis komputer lengkap dengan timer.</p></div>
      <div class="step" data-reveal style="--rd:270ms"><div class="step-num">4</div><h3>Lihat analisis</h3><p>Dapatkan skor, pembahasan, ranking, dan rekomendasi belajar dari AI.</p></div>
    </div>
  </section>

  {{-- ANALISIS --}}
  <section class="section" id="analisis">
    <div class="showcase-grid">
      <div class="showcase-copy">
        <span class="eyebrow" data-reveal>Analisis berbasis AI</span>
        <h2 class="title" data-reveal style="--rd:90ms">Tahu persis bagian mana yang harus diperbaiki</h2>
        <p class="lead" data-reveal style="--rd:160ms">Setelah try out, Talentra langsung menampilkan hasilmu secara rinci. Kamu tidak perlu lagi menebak-nebak harus belajar apa.</p>
        <ul class="checks" data-reveal style="--rd:230ms">
          <li><svg class="i"><use href="#ic-check"/></svg>Skor per bagian dibanding passing grade</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Topik yang masih lemah beserta pembahasannya</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Rencana belajar mingguan dari AI</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-primary" data-reveal style="--rd:300ms">Coba demo gratis <svg class="i"><use href="#ic-arrow"/></svg></a>
      </div>

      <div class="dashboard" data-reveal style="--rd:160ms" aria-label="Contoh hasil analisis try out">
        <div class="dash-top">
          <div><strong>Hasil Try Out SKD CPNS</strong><small>Try out ke-7</small></div>
          <div class="dash-score"><b>412</b><span>skor total</span></div>
        </div>
        <div class="dash-caption"><span>Perkembangan skor</span><span>Try out 1–7</span></div>
        <div class="dash-bars">
          <div class="dash-bar" style="--h:48%;--d:0ms"></div>
          <div class="dash-bar" style="--h:55%;--d:90ms"></div>
          <div class="dash-bar" style="--h:52%;--d:180ms"></div>
          <div class="dash-bar" style="--h:64%;--d:270ms"></div>
          <div class="dash-bar" style="--h:71%;--d:360ms"></div>
          <div class="dash-bar" style="--h:79%;--d:450ms"></div>
          <div class="dash-bar" style="--h:88%;--d:540ms"></div>
        </div>
        <div class="dash-rows">
          <div class="dash-row"><span>TWK</span><div class="dash-track"><div class="dash-fill" style="--w:82%"></div></div><b>82%</b></div>
          <div class="dash-row"><span>TIU</span><div class="dash-track"><div class="dash-fill" style="--w:64%"></div></div><b>64%</b></div>
          <div class="dash-row"><span>TKP</span><div class="dash-track"><div class="dash-fill" style="--w:91%"></div></div><b>91%</b></div>
          <div class="dash-row"><span>Deret angka</span><div class="dash-track"><div class="dash-fill low" style="--w:38%"></div></div><b>38%</b></div>
        </div>
        <div class="dash-ai">
          <svg class="i"><use href="#ic-spark"/></svg>
          <span><strong>Rekomendasi AI:</strong> skor TIU naik 12 poin, tapi deret angka masih di bawah target. Ulangi bab Pola Bilangan dan kerjakan 20 soal deret minggu ini.</span>
        </div>
      </div>
    </div>

    <div class="stats">
      <div class="stat" data-reveal style="--rd:0ms"><div class="stat-value">7</div><div class="stat-label">Kategori ujian</div></div>
      <div class="stat" data-reveal style="--rd:80ms"><div class="stat-value">1</div><div class="stat-label">Akun untuk semua tes</div></div>
      <div class="stat" data-reveal style="--rd:160ms"><div class="stat-value">24<em>/</em>7</div><div class="stat-label">Akses kapan saja</div></div>
      <div class="stat" data-reveal style="--rd:240ms"><div class="stat-value"><em>AI</em></div><div class="stat-label">Analisis di setiap hasil</div></div>
    </div>
  </section>

  {{-- HARGA --}}
  <section class="section" id="harga">
    <div class="section-head">
      <span class="eyebrow" data-reveal>Paket langganan</span>
      <h2 class="title" data-reveal style="--rd:80ms">Pilih paket sesuai tujuanmu</h2>
      <p class="lead" data-reveal style="--rd:160ms">Mulai gratis dengan demo. Lanjutkan dengan satu kategori, atau ambil Semua Akses untuk berlatih semua ujian dengan satu langganan.</p>
    </div>
    <div class="pricing-grid">
      <div class="plan" data-reveal style="--rd:0ms">
        <div class="plan-name">Gratis</div>
        <p class="plan-desc">Coba dulu sebelum berlangganan.</p>
        <div class="plan-price"><strong>Rp0</strong></div>
        <ul>
          <li><svg class="i"><use href="#ic-check"/></svg>1 demo try out di setiap kategori</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Materi bab pertama</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Analisis AI versi ringkas</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Akses forum diskusi</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-outline">Mulai Gratis</a>
      </div>

      <div class="plan" data-reveal style="--rd:90ms">
        <div class="plan-name">Per Kategori</div>
        <p class="plan-desc">Fokus pada satu ujian yang sedang kamu kejar.</p>
        <div class="plan-price"><strong>Rp49rb</strong><span>/ bulan</span></div>
        <div class="plan-chips"><span>TKA SD</span><span>TKA SMP</span><span>TKA SMA</span><span>UTBK</span><span>CPNS</span><span>BUMN</span><span>Kedinasan</span></div>
        <ul>
          <li><svg class="i"><use href="#ic-check"/></svg>Semua materi & try out di kategori pilihan</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Pembahasan lengkap setiap soal</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Analisis AI penuh + rencana belajar</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Masuk ranking peserta</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-outline">Pilih Kategori</a>
      </div>

      <div class="plan featured" data-reveal style="--rd:180ms">
        <span class="plan-badge">Paling hemat</span>
        <div class="plan-name">Semua Akses</div>
        <p class="plan-desc">Satu langganan untuk semua kategori ujian.</p>
        <div class="plan-price"><strong>Rp99rb</strong><span>/ bulan</span></div>
        <ul>
          <li><svg class="i"><use href="#ic-check"/></svg>Semua kategori: TKA, UTBK, CPNS, BUMN, Kedinasan</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Semua fitur paket Per Kategori</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Ranking di semua kategori</li>
          <li><svg class="i"><use href="#ic-check"/></svg>Cocok untuk persiapan lebih dari satu ujian</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-primary">Ambil Semua Akses <svg class="i"><use href="#ic-arrow"/></svg></a>
      </div>
    </div>
    <p class="pricing-note" data-reveal>Tersedia durasi 1, 3, 6, dan 12 bulan. Makin panjang, makin hemat.</p>
  </section>

  {{-- CTA + FOOTER --}}
  <section class="section" id="cta">
    <div class="cta-card" data-reveal>
      <span class="eyebrow">Siap mulai?</span>
      <h2 class="title">Tumbuh, uji, dan berkembang bersama Talentra</h2>
      <p>Daftar dalam satu menit, coba demo try out gratis, dan lihat sendiri analisis hasilmu.</p>
      <div class="hero-actions">
        <a href="{{ route('register') }}" class="btn btn-primary">Coba Gratis Sekarang <svg class="i"><use href="#ic-arrow"/></svg></a>
        <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
      </div>
    </div>

    <footer class="footer">
      <div class="footer-brand">
        <a href="#top" class="brand"><span class="brand-mark"><svg class="i"><use href="#ic-logo"/></svg></span>Talentra</a>
        <p>Platform belajar dan try out online untuk semua jenjang. Tumbuh · Uji · Berkembang.</p>
      </div>
      <div class="footer-cols">
        <div class="footer-col">
          <h4>Produk</h4>
          <a href="#fitur">Fitur</a>
          <a href="#analisis">Analisis AI</a>
          <a href="#harga">Harga</a>
        </div>
        <div class="footer-col">
          <h4>Komunitas</h4>
          <a href="{{ route('forum.index') }}">Forum</a>
          <a href="{{ route('ranking.index') }}">Ranking</a>
          <a href="#">Blog</a>
        </div>
        <div class="footer-col">
          <h4>Legal</h4>
          <a href="#">Kebijakan Privasi</a>
          <a href="#">Syarat & Ketentuan</a>
          <a href="#">Kontak</a>
        </div>
      </div>
    </footer>
    <p class="copyright">© <span id="year"></span> Talentra. Tumbuh · Uji · Berkembang.</p>
  </section>
</div>

<script type="module">
import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';

document.getElementById('year').textContent = new Date().getFullYear();
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- smooth scroll + reveal ---------- */
if (!reducedMotion) {
  import('https://unpkg.com/lenis@1.3.23/dist/lenis.mjs').then(({ default: Lenis }) => {
    const lenis = new Lenis({ duration: 1.15, smoothWheel: true, touchMultiplier: 1.5 });
    const raf = (t) => { lenis.raf(t); requestAnimationFrame(raf); };
    requestAnimationFrame(raf);
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
      a.addEventListener('click', (e) => {
        const id = a.getAttribute('href');
        if (id.length < 2) { return; }
        const el = document.querySelector(id);
        if (el) { e.preventDefault(); lenis.scrollTo(el, { offset: -80 }); }
      });
    });
  }).catch(() => {});
}

const revealEls = document.querySelectorAll('[data-reveal]');
if (reducedMotion || !('IntersectionObserver' in window)) {
  revealEls.forEach((el) => el.classList.add('in'));
} else {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });
  revealEls.forEach((el) => io.observe(el));
  /* anything already on screen at load is revealed right away, without waiting for the observer */
  setTimeout(() => {
    const vh = window.innerHeight || document.documentElement.clientHeight;
    revealEls.forEach((el) => {
      if (el.getBoundingClientRect().top < vh * 0.95) { el.classList.add('in'); io.unobserve(el); }
    });
  }, 60);
}

/* ---------- 3D graduation cap ---------- */
const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
const lerp = (a, b, t) => a + (b - a) * t;
const smooth = (a, b, x) => { const t = clamp((x - a) / (b - a), 0, 1); return t * t * (3 - 2 * t); };
const sample = (stops, p) => {
  if (p <= stops[0].p) { return stops[0].v; }
  for (let i = 0; i < stops.length - 1; i++) {
    const a = stops[i], b = stops[i + 1];
    if (p <= b.p) { let t = (p - a.p) / (b.p - a.p); t = t * t * (3 - 2 * t); return lerp(a.v, b.v, t); }
  }
  return stops[stops.length - 1].v;
};

const canvas = document.querySelector('.scene-canvas');
const viewport = () => ({
  vw: window.innerWidth || document.documentElement.clientWidth,
  vh: window.innerHeight || document.documentElement.clientHeight,
});
let renderer = null;
try { renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true }); } catch (e) { renderer = null; }

if (renderer) {
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  const vp0 = viewport();
  renderer.setSize(vp0.vw, vp0.vh);
  renderer.setClearColor(0x000000, 0);
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 0.95;

  const scene = new THREE.Scene();
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

  const CAM_Z = 12, FOV = 35;
  const camera = new THREE.PerspectiveCamera(FOV, vp0.vw / vp0.vh, 0.1, 100);
  camera.position.set(0, 0, CAM_Z);

  scene.add(new THREE.HemisphereLight(0xffffff, 0xdbe6ff, 0.35));
  const keyLight = new THREE.DirectionalLight(0xffffff, 1.1);
  keyLight.position.set(3, 7, 6);
  const rimLight = new THREE.DirectionalLight(0x8ff0e0, 0.9);
  rimLight.position.set(-6, 1, -4);
  scene.add(keyLight, rimLight);

  const lin = (hex) => new THREE.Color(hex).convertSRGBToLinear();
  const mat = (color, opts = {}) => {
    const o = { ...opts };
    if (o.emissive !== undefined) { o.emissive = lin(o.emissive); }
    return new THREE.MeshStandardMaterial({ color: lin(color), roughness: 0.5, metalness: 0, envMapIntensity: 0.5, ...o });
  };
  const C = { navy: 0x1b2f66, navyDark: 0x0e1a3d, teal: 0x14b8a6, sun: 0xffc23d, gold: 0xe7a812 };

  /* hierarchy: world (scroll position + zoom) > tilt (x) > spin (y) > cap */
  const world = new THREE.Group();
  const tilt = new THREE.Group();
  const spin = new THREE.Group();
  world.add(tilt);
  tilt.add(spin);
  scene.add(world);

  /* soft halo behind the cap */
  {
    const c = document.createElement('canvas');
    c.width = c.height = 256;
    const g = c.getContext('2d');
    const grad = g.createRadialGradient(128, 128, 0, 128, 128, 128);
    grad.addColorStop(0, 'rgba(20,184,166,0.55)');
    grad.addColorStop(0.45, 'rgba(91,140,255,0.18)');
    grad.addColorStop(1, 'rgba(91,140,255,0)');
    g.fillStyle = grad;
    g.fillRect(0, 0, 256, 256);
    const tex = new THREE.CanvasTexture(c);
    tex.encoding = THREE.sRGBEncoding;
    const halo = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
    halo.scale.setScalar(7.5);
    halo.position.set(0, 0.2, -2.5);
    world.add(halo);
  }

  /* the cap */
  const capNavy = mat(C.navy, { roughness: 0.62 });
  const BOARD = 2.7, CORNER = (BOARD / 2) * Math.SQRT2;
  const board = new THREE.Mesh(new RoundedBoxGeometry(BOARD, 0.13, BOARD, 4, 0.045), capNavy);
  board.position.y = 0.6;
  board.rotation.y = Math.PI / 4;
  const skull = new THREE.Mesh(new THREE.CylinderGeometry(1.0, 1.1, 0.82, 72), capNavy);
  skull.position.y = 0.16;
  const inner = new THREE.Mesh(new THREE.CircleGeometry(1.04, 72), mat(C.navyDark, { roughness: 0.9, side: THREE.DoubleSide }));
  inner.rotation.x = Math.PI / 2;
  inner.position.y = -0.249;
  const band = new THREE.Mesh(new THREE.CylinderGeometry(1.11, 1.11, 0.16, 72, 1, true), mat(C.teal, { roughness: 0.4, side: THREE.DoubleSide }));
  band.position.y = -0.17;
  const button = new THREE.Mesh(new THREE.SphereGeometry(0.13, 32, 20), mat(C.sun, { metalness: 0.35, roughness: 0.3 }));
  button.position.y = 0.7;
  button.scale.y = 0.6;
  const cordTop = new THREE.Mesh(new THREE.CylinderGeometry(0.032, 0.032, CORNER - 0.05, 12), mat(C.sun, { roughness: 0.45 }));
  cordTop.rotation.z = Math.PI / 2;
  cordTop.position.set((CORNER - 0.05) / 2, 0.69, 0);

  /* tassel hangs from the corner and swings with the spin */
  const tassel = new THREE.Group();
  tassel.position.set(CORNER - 0.05, 0.69, 0);
  const cordDrop = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.95, 12), mat(C.sun, { roughness: 0.45 }));
  cordDrop.position.y = -0.47;
  const knot = new THREE.Mesh(new THREE.SphereGeometry(0.075, 20, 14), mat(C.gold, { roughness: 0.4 }));
  knot.position.y = -0.95;
  const fringe = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.17, 0.5, 24), mat(C.sun, { roughness: 0.75 }));
  fringe.position.y = -1.25;
  tassel.add(cordDrop, knot, fringe);

  spin.add(board, skull, inner, band, button, cordTop, tassel);

  /* sparse dust for depth */
  {
    const n = 140, pos = new Float32Array(n * 3), col = new Float32Array(n * 3);
    const palette = [0x14b8a6, 0x5b8cff, 0xffc23d, 0x1b2f66].map(lin);
    for (let i = 0; i < n; i++) {
      pos[i * 3] = (Math.random() - 0.5) * 24;
      pos[i * 3 + 1] = (Math.random() - 0.5) * 16;
      pos[i * 3 + 2] = -2 - Math.random() * 8;
      const c = palette[i % palette.length];
      col[i * 3] = c.r; col[i * 3 + 1] = c.g; col[i * 3 + 2] = c.b;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('color', new THREE.BufferAttribute(col, 3));
    const dot = document.createElement('canvas');
    dot.width = dot.height = 64;
    const d = dot.getContext('2d');
    d.beginPath(); d.arc(32, 32, 28, 0, Math.PI * 2); d.fillStyle = '#fff'; d.fill();
    const dust = new THREE.Points(geo, new THREE.PointsMaterial({ size: 0.09, map: new THREE.CanvasTexture(dot), vertexColors: true, transparent: true, opacity: 0.45, depthWrite: false }));
    scene.add(dust);
    scene.userData.dust = dust;
  }

  /* layout: hero size/position comes from the real page layout */
  const halfH = Math.tan(THREE.MathUtils.degToRad(FOV / 2)) * CAM_Z;
  const heroCopy = document.getElementById('hero-copy');
  const sections = ['fitur', 'cara-kerja', 'analisis', 'harga'].map((id) => document.getElementById(id));
  const HERO_TILT = 0.42, LATER_TILT = 0.3;

  const layout = () => {
    const { vw, vh } = viewport();
    if (!vw || !vh) { return L; }
    const halfW = halfH * (vw / vh);
    const narrow = vw < 980;
    const maxScroll = Math.max(1, document.documentElement.scrollHeight - vh);

    const heroScale = Math.min(narrow ? 1.7 : 2.05, (halfW * 2 * (narrow ? 0.9 : 0.5)) / (CORNER * 2));
    const textBottom = (heroCopy.getBoundingClientRect().bottom + window.scrollY) / vh;
    const topFrac = clamp(textBottom + 0.07, 0.48, 0.8);
    const capTop = 0.6 * Math.cos(HERO_TILT) + CORNER * Math.sin(HERO_TILT);
    const heroY = halfH * (1 - 2 * topFrac) - capTop * heroScale;

    const mid = narrow ? clamp(halfW / 3.2, 0.42, 0.7) : clamp(heroScale * 0.4, 0.75, 1.0);
    const side = narrow ? 0 : 1;
    const pOf = (el) => clamp((el.offsetTop + el.offsetHeight / 2 - vh / 2) / maxScroll, 0.02, 0.98);
    const [pF, pC, pA, pH] = sections.map(pOf);

    return {
      narrow,
      X: [{ p: 0, v: 0 }, { p: pF, v: -halfW * 0.62 * side }, { p: pC, v: halfW * 0.62 * side }, { p: pA, v: halfW * 0.8 * side }, { p: pH, v: -halfW * 0.66 * side }, { p: 1, v: halfW * 0.68 * side }],
      Y: [{ p: 0, v: heroY }, { p: pF, v: 0.3 }, { p: pC, v: 0.2 }, { p: pA, v: narrow ? 0.1 : halfH * 0.62 }, { p: pH, v: 0.3 }, { p: 1, v: narrow ? 0.4 : 1.0 }],
      S: [{ p: 0, v: heroScale }, { p: pF, v: mid }, { p: pC, v: mid * 0.92 }, { p: pA, v: mid * 0.72 }, { p: pH, v: mid * 0.85 }, { p: 1, v: mid * 0.7 }],
      pF,
    };
  };
  let L = null;
  L = layout();

  let mouseX = 0, mouseY = 0;
  window.addEventListener('pointermove', (e) => {
    mouseX = e.clientX / window.innerWidth - 0.5;
    mouseY = e.clientY / window.innerHeight - 0.5;
  }, { passive: true });

  let curP = 0, curX = sample(L.X, 0), curY = sample(L.Y, 0), curS = sample(L.S, 0);
  let idle = 0, prevSpin = 0, swing = 0, swingVel = 0, flare = 0;
  const ENTRY_DUR = 1.8, ENTRY_DROP = -4.5;
  const start = performance.now() / 1000;
  let last = start;

  function tick() {
    requestAnimationFrame(tick);
    const now = performance.now() / 1000;
    const dt = Math.min(0.05, now - last);
    last = now;
    const motion = reducedMotion ? 0 : 1;

    const { vh } = viewport();
    const maxScroll = document.documentElement.scrollHeight - vh;
    const pTarget = clamp(maxScroll > 0 ? window.scrollY / maxScroll : 0, 0, 1);
    curP = reducedMotion ? pTarget : curP + (pTarget - curP) * Math.min(1, dt * 4.5);

    const k = reducedMotion ? 1 : Math.min(1, dt * 3.2);
    curX += (sample(L.X, curP) - curX) * k;
    curY += (sample(L.Y, curP) - curY) * k;
    curS += (sample(L.S, curP) - curS) * k;

    const e = reducedMotion ? 1 : clamp((now - start - 0.2) / ENTRY_DUR, 0, 1);
    const entry = ENTRY_DROP * Math.pow(1 - e, 3);
    world.position.set(curX, curY + entry, 0);
    world.scale.setScalar(curS);

    /* tilt eases from "looking down at the board" to a 3/4 view; mouse adds a little parallax */
    const heroToSection = smooth(0, L.pF, curP);
    tilt.rotation.x = lerp(HERO_TILT, LATER_TILT, heroToSection) + mouseY * 0.12 * motion;
    tilt.rotation.z = lerp(0, -0.14, heroToSection) + Math.sin(now * 0.6) * 0.02 * motion;

    /* spin: slow idle turn + scroll-driven turns */
    idle += dt * 0.18 * motion;
    const spinY = -0.6 + idle + curP * Math.PI * 3 + mouseX * 0.35 * motion;
    spin.rotation.y = spinY;
    world.position.y += Math.sin(now * 0.9) * 0.05 * motion * (1 - heroToSection * 0.5);

    /* tassel: pendulum driven by angular velocity */
    const angVel = dt > 0 ? (spinY - prevSpin) / dt : 0;
    prevSpin = spinY;
    const swingTarget = clamp(angVel * 0.22, -0.75, 0.75);
    swingVel += ((swingTarget - swing) * 28 - swingVel * 4.5) * dt;
    swing += swingVel * dt;
    flare += (clamp(Math.abs(angVel) * 0.14, 0, 0.55) - flare) * Math.min(1, dt * 4);
    tassel.rotation.x = swing * motion;
    tassel.rotation.z = flare * motion;

    scene.userData.dust.position.y = curP * 6;
    scene.userData.dust.rotation.y = now * 0.01 * motion;

    canvas.style.opacity = L.narrow ? String(1 - smooth(0.25, 0.85, window.scrollY / Math.max(1, vh)) * 0.72) : '1';

    renderer.render(scene, camera);
  }
  tick();

  let resizeTimer = null;
  const relayout = () => { L = layout(); };
  window.addEventListener('resize', () => {
    const { vw, vh } = viewport();
    if (!vw || !vh) { return; }
    camera.aspect = vw / vh;
    camera.updateProjectionMatrix();
    renderer.setSize(vw, vh);
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(relayout, 120);
  });
  window.addEventListener('load', relayout);
  if (document.fonts && document.fonts.ready) { document.fonts.ready.then(relayout); }
}
</script>
</body>
</html>
