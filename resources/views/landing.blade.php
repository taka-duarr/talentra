<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Talentra — Tumbuh · Uji · Berkembang</title>
<meta name="description" content="Talentra: satu tempat berlatih TKA SD, SMP, SMA, UTBK-SNBT, CPNS, BUMN, dan Kedinasan, lengkap dengan analisis hasil AI dan rekomendasi belajar.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script type="importmap">
{ "imports": {
  "three": "https://unpkg.com/three@0.143.0/build/three.module.js",
  "three/examples/jsm/": "https://unpkg.com/three@0.143.0/examples/jsm/"
}}
</script>
<style>
:root{
  --bg:#04060f; --bg-2:#060a18; --ink:#f3f6ff; --muted:#9aa6c4; --muted-2:#6b7796;
  --line:rgba(150,175,230,.12); --line-strong:rgba(150,175,230,.22);
  --accent:#4ff0d0; --accent-2:#18c6a6; --accent-ink:#022a24;
  --glass:rgba(12,18,38,.55); --glass-2:rgba(16,24,48,.65);
  --radius:18px; --radius-lg:26px; --maxw:1180px;
  --shadow:0 30px 80px -30px rgba(0,0,0,.75);
  --font:'Inter',-apple-system,BlinkMacSystemFont,system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);color:var(--ink);background:var(--bg);-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;overflow-x:hidden}
a{color:inherit;text-decoration:none}
html.lenis,html.lenis body{height:auto}
.lenis.lenis-smooth{scroll-behavior:auto !important}
.lenis.lenis-smooth [data-lenis-prevent]{overscroll-behavior:contain}
.lenis.lenis-stopped{overflow:hidden}
[data-reveal]{opacity:0;transform:translateY(26px);transition:opacity .7s cubic-bezier(.22,.7,.2,1),transform .7s cubic-bezier(.22,.7,.2,1);transition-delay:var(--rd,0ms);will-change:opacity,transform}
[data-reveal].in{opacity:1;transform:none}
.planet-canvas{position:fixed;inset:0;width:100vw;height:100vh;display:block;z-index:0;pointer-events:none}
.page{position:relative;z-index:1}
.section{position:relative;z-index:1;max-width:var(--maxw);margin:0 auto;padding:clamp(80px,12vw,160px) 24px}
.eyebrow{display:inline-block;font-size:12px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--accent);margin-bottom:18px}
.section-head{max-width:680px;margin:0 auto clamp(48px,6vw,80px);text-align:center}
.section-head h2,.showcase-copy h2,.cta-card h2{font-size:clamp(30px,4.4vw,50px);font-weight:800;line-height:1.05;letter-spacing:-.02em;text-shadow:0 2px 40px rgba(0,0,0,.6)}
.section-head p{margin-top:18px;color:var(--muted);font-size:18px;line-height:1.65;text-shadow:0 1px 24px rgba(0,0,0,.55)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;font-size:15px;font-weight:600;padding:13px 22px;border-radius:999px;border:1px solid transparent;cursor:pointer;transition:transform .18s ease,box-shadow .25s ease,background .2s ease,border-color .2s ease;white-space:nowrap;font-family:inherit}
.btn svg{width:17px;height:17px}
.btn-primary{color:var(--accent-ink);background:linear-gradient(180deg,var(--accent),var(--accent-2));box-shadow:0 10px 30px -8px rgba(79,240,208,.55),inset 0 1px 0 rgba(255,255,255,.4)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 16px 38px -8px rgba(79,240,208,.7)}
.btn-outline{color:var(--ink);background:rgba(255,255,255,.04);border-color:var(--line-strong);backdrop-filter:blur(10px)}
.btn-outline:hover{background:rgba(255,255,255,.09);transform:translateY(-2px)}
.btn-ghost{color:var(--ink);background:rgba(255,255,255,.05);border-color:var(--line-strong);padding:9px 18px}
.btn-ghost:hover{background:rgba(255,255,255,.1)}
svg.i{fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
svg.i.arrow{stroke-width:2}

/* HERO */
.hero{min-height:100vh;display:flex;flex-direction:column;background:radial-gradient(125% 62% at 50% -10%, rgba(4,6,15,.82) 0%, rgba(4,6,15,.28) 44%, transparent 68%)}
.nav{width:100%;max-width:var(--maxw);margin:0 auto;padding:22px 24px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:inline-flex;align-items:center;gap:9px;font-weight:800;font-size:19px;letter-spacing:.16em}
.brand-mark{color:var(--accent);width:20px;height:20px;transform:translateY(-1px);stroke-width:2.4}
.nav-links{display:flex;gap:30px;font-size:14.5px;color:var(--muted)}
.nav-links a{transition:color .2s ease}
.nav-links a:hover{color:var(--ink)}
.nav-actions{display:flex;gap:10px;align-items:center}
.hero-inner{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 24px;transform:translateY(-10vh)}
.hero-title{font-size:clamp(34px,5.2vw,62px);font-weight:800;line-height:1.04;letter-spacing:-.03em;max-width:15ch;text-shadow:0 2px 50px rgba(0,0,0,.55)}
.hero-title em{font-style:normal;background:linear-gradient(180deg,var(--accent),var(--accent-2));-webkit-background-clip:text;background-clip:text;color:transparent}
.hero-sub{margin-top:24px;max-width:560px;font-size:clamp(16px,2vw,19px);line-height:1.6;color:var(--muted)}
.hero-actions{margin-top:38px;display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.logos{text-align:center;padding:0 24px clamp(36px,6vw,64px)}
.logos-label{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--muted-2);margin-bottom:18px}
.logos-row{display:flex;flex-wrap:wrap;justify-content:center;gap:12px clamp(24px,5vw,56px)}
.logo{font-size:17px;font-weight:700;color:var(--muted-2);opacity:.8;transition:color .2s ease,opacity .2s ease}
.logo:hover{color:var(--ink);opacity:1}

/* FEATURES */
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.card{position:relative;overflow:hidden;padding:30px;border-radius:var(--radius);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(14px);transition:transform .25s ease,border-color .25s ease,background .25s ease}
.card::before{content:"";position:absolute;inset:0;background:radial-gradient(120% 120% at 0% 0%, rgba(79,240,208,.08), transparent 45%);opacity:0;transition:opacity .3s ease;pointer-events:none}
.card:hover{transform:translateY(-4px);border-color:var(--line-strong);background:var(--glass-2)}
.card:hover::before{opacity:1}
.card[data-reveal].in:hover{transform:translateY(-4px)}
.card-icon{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:var(--accent);background:rgba(79,240,208,.1);border:1px solid rgba(79,240,208,.18);margin-bottom:20px}
.card-icon svg{width:22px;height:22px}
.card h3{font-size:19px;font-weight:700;margin-bottom:10px}
.card p{font-size:15px;line-height:1.6;color:var(--muted)}

/* SHOWCASE */
.showcase-grid{display:grid;grid-template-columns:1fr 1.05fr;gap:clamp(36px,6vw,72px);align-items:center}
.showcase-copy p{margin-top:18px;color:var(--muted);font-size:18px;line-height:1.65;text-shadow:0 1px 24px rgba(0,0,0,.55)}
.showcase-copy .btn{margin-top:30px}
.dashboard{position:relative;padding:24px;border-radius:var(--radius-lg);background:var(--glass-2);border:1px solid var(--line-strong);box-shadow:var(--shadow);backdrop-filter:blur(18px)}
.dashboard::after{content:"";position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(160deg, rgba(79,240,208,.35), transparent 40%);-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.dash-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-weight:700;font-size:15px}
.dash-live{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:var(--accent)}
.dash-live::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 10px var(--accent)}
.dash-caption{display:flex;justify-content:space-between;font-size:12px;color:var(--muted-2);margin:0 4px 8px}
.dash-bars{display:flex;align-items:flex-end;gap:10px;height:150px;padding:14px;background:rgba(0,0,0,.25);border:1px solid var(--line);border-radius:14px}
.dash-bar{flex:1;height:var(--h);background:linear-gradient(180deg,var(--accent),rgba(24,198,166,.25));border-radius:6px 6px 3px 3px;transform-origin:bottom;animation:grow 1s cubic-bezier(.22,.7,.2,1) both;animation-delay:var(--d)}
@keyframes grow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
.dash-rows{margin-top:20px;display:grid;gap:12px}
.dash-row{display:grid;grid-template-columns:120px 1fr 38px;gap:14px;align-items:center;font-size:13.5px;color:var(--muted)}
.dash-row b{font-weight:600;color:var(--ink);text-align:right;font-size:13px}
.dash-track{height:8px;border-radius:999px;background:rgba(255,255,255,.06);overflow:hidden}
.dash-fill{height:100%;width:var(--w);border-radius:999px;background:linear-gradient(90deg,var(--accent-2),var(--accent));animation:fill 1.2s cubic-bezier(.22,.7,.2,1) .3s both}
.dash-fill.low{background:linear-gradient(90deg,#d9822b,#ffc06b)}
@keyframes fill{from{width:0}to{width:var(--w)}}
.dash-ai{margin-top:20px;display:flex;gap:12px;padding:14px 16px;border-radius:14px;background:rgba(79,240,208,.07);border:1px solid rgba(79,240,208,.18);font-size:13.5px;line-height:1.55;color:var(--muted)}
.dash-ai svg{flex:none;width:18px;height:18px;color:var(--accent);margin-top:2px}
.dash-ai strong{color:var(--ink);font-weight:600}
.stats{margin-top:clamp(56px,8vw,96px);display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.stat{text-align:center;padding:30px 18px;border-radius:var(--radius);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(12px)}
.stat-value{font-size:clamp(30px,4vw,44px);font-weight:800;letter-spacing:-.02em;background:linear-gradient(180deg,#fff,var(--accent));-webkit-background-clip:text;background-clip:text;color:transparent}
.stat-label{font-size:14px;color:var(--muted);margin-top:8px}

/* PRICING */
.pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;align-items:stretch}
.plan{position:relative;display:flex;flex-direction:column;padding:32px 28px;border-radius:var(--radius-lg);background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(14px)}
.plan.featured{background:radial-gradient(120% 90% at 50% 0%, rgba(79,240,208,.14), transparent 60%), var(--glass-2);border-color:rgba(79,240,208,.4);box-shadow:var(--shadow)}
.plan-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:6px 12px;border-radius:999px;color:var(--accent-ink);background:linear-gradient(180deg,var(--accent),var(--accent-2))}
.plan-name{font-size:15px;font-weight:700;color:var(--accent);letter-spacing:.04em}
.plan-desc{margin-top:8px;font-size:14px;color:var(--muted);line-height:1.55;min-height:44px}
.plan-price{margin-top:22px;display:flex;align-items:baseline;gap:6px}
.plan-price strong{font-size:40px;font-weight:800;letter-spacing:-.02em}
.plan-price span{font-size:14px;color:var(--muted)}
.plan ul{list-style:none;margin:24px 0 30px;display:grid;gap:12px;font-size:14.5px;color:var(--muted)}
.plan li{display:flex;gap:10px;align-items:flex-start;line-height:1.5}
.plan li svg{flex:none;width:17px;height:17px;color:var(--accent);margin-top:2px;stroke-width:2}
.plan .btn{margin-top:auto;width:100%}
.plan-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:14px}
.plan-chips span{font-size:12px;padding:4px 9px;border-radius:999px;border:1px solid var(--line-strong);color:var(--muted)}
.pricing-note{margin-top:28px;text-align:center;font-size:13.5px;color:var(--muted-2)}

/* CTA + FOOTER */
.cta-card{margin:0 auto;max-width:760px;text-align:center;padding:clamp(48px,7vw,80px) clamp(28px,5vw,64px);border-radius:var(--radius-lg);background:radial-gradient(120% 140% at 50% 0%, rgba(79,240,208,.12), transparent 55%), var(--glass-2);border:1px solid var(--line-strong);box-shadow:var(--shadow);backdrop-filter:blur(18px)}
.cta-card p{margin:18px auto 34px;max-width:520px;color:var(--muted);font-size:18px;line-height:1.65}
.cta-card .hero-actions{margin-top:0}
.footer{display:flex;justify-content:space-between;gap:48px;flex-wrap:wrap;margin-top:clamp(64px,9vw,110px);padding-top:48px;border-top:1px solid var(--line)}
.footer-brand p{margin-top:14px;max-width:280px;font-size:14.5px;line-height:1.6;color:var(--muted)}
.footer-cols{display:flex;gap:clamp(40px,7vw,90px);flex-wrap:wrap}
.footer-col h4{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-2);font-weight:600;margin-bottom:16px}
.footer-col a{display:block;font-size:14.5px;color:var(--muted);margin-bottom:10px;transition:color .2s ease}
.footer-col a:hover{color:var(--ink)}
.copyright{text-align:center;margin-top:56px;font-size:13px;color:var(--muted-2)}

/* RESPONSIVE */
@media (max-width:980px){
  .feature-grid{grid-template-columns:repeat(2,1fr)}
  .showcase-grid{grid-template-columns:1fr}
  .showcase-copy{text-align:center}
  .stats{grid-template-columns:repeat(2,1fr)}
  .pricing-grid{grid-template-columns:1fr;max-width:460px;margin:0 auto;gap:28px}
}
@media (max-width:720px){
  .nav-links{display:none}
  .nav{padding:18px 20px}
  .section{padding:clamp(64px,16vw,110px) 20px}
}
@media (max-width:560px){
  .feature-grid{grid-template-columns:1fr}
  .dash-row{grid-template-columns:90px 1fr 34px}
  .hero-actions .btn{flex:1}
  .cta-card{padding:40px 22px}
  .footer{flex-direction:column}
  .nav-actions .btn-outline{display:none}
}
@media (max-width:380px){
  .stats{grid-template-columns:1fr}
  .logo{font-size:15px}
}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  [data-reveal]{opacity:1;transform:none;transition:none}
  .dash-bar,.dash-fill{animation:none}
}
</style>
</head>
<body>
<canvas class="planet-canvas" aria-hidden="true"></canvas>

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
    <symbol id="ic-logo" viewBox="0 0 24 24"><path d="M3 5h12"/><path d="M9 5v15"/><path d="m13.5 15.5 6-6"/><path d="M15 9.5h4.5V14"/></symbol>
  </defs>
</svg>

<div class="page" id="top">

  {{-- SECTION 1 · HERO --}}
  <header class="hero">
    <nav class="nav">
      <a href="#top" class="brand"><svg class="i brand-mark"><use href="#ic-logo"/></svg>TALENTRA</a>
      <div class="nav-links">
        <a href="#fitur">Fitur</a>
        <a href="#analisis">Analisis AI</a>
        <a href="#harga">Harga</a>
        <a href="#kategori">Kategori</a>
      </div>
      <div class="nav-actions">
        <a href="{{ url('/register') }}" class="btn btn-ghost btn-outline">Daftar</a>
        <a href="{{ url('/login') }}" class="btn btn-ghost">Masuk</a>
      </div>
    </nav>

    <div class="hero-inner">
      <h1 class="hero-title" data-reveal style="--rd:60ms">Latihan Terarah,<br>Lulus Lebih <em>Pasti</em></h1>
      <p class="hero-sub" data-reveal style="--rd:180ms">Satu tempat untuk berlatih TKA, UTBK, CPNS, BUMN, dan Kedinasan, lengkap dengan simulasi ujian, analisis hasil berbasis AI, dan rekomendasi belajar.</p>
      <div class="hero-actions" data-reveal style="--rd:300ms">
        <a href="{{ url('/register') }}" class="btn btn-primary">Coba Gratis Sekarang <svg class="i arrow"><use href="#ic-arrow"/></svg></a>
        <a href="#harga" class="btn btn-outline">Lihat Paket Langganan</a>
      </div>
    </div>

    <div class="logos" id="kategori" data-reveal style="--rd:420ms">
      <div class="logos-label">Pilih tujuan ujianmu</div>
      <div class="logos-row">
        <span class="logo">TKA SD</span>
        <span class="logo">TKA SMP</span>
        <span class="logo">TKA SMA</span>
        <span class="logo">UTBK-SNBT</span>
        <span class="logo">CPNS</span>
        <span class="logo">BUMN</span>
        <span class="logo">Kedinasan</span>
      </div>
    </div>
  </header>

  {{-- SECTION 2 · FITUR --}}
  <section class="section features" id="fitur">
    <div class="section-head">
      <span class="eyebrow" data-reveal>Semua dalam satu akun</span>
      <h2 data-reveal style="--rd:80ms">Bukan sekadar mengerjakan soal, tapi memahami hasilnya</h2>
      <p data-reveal style="--rd:160ms">Tidak perlu berpindah-pindah aplikasi. Talentra menyatukan materi, simulasi ujian, analisis, dan komunitas belajar untuk semua ujian penting.</p>
    </div>
    <div class="feature-grid">
      <article class="card" data-reveal style="--rd:0ms">
        <div class="card-icon"><svg class="i"><use href="#ic-book"/></svg></div>
        <h3>Materi & bank soal</h3>
        <p>Materi terstruktur per bab dan ribuan soal latihan lengkap dengan pembahasan, disusun mengikuti kisi-kisi terbaru.</p>
      </article>
      <article class="card" data-reveal style="--rd:90ms">
        <div class="card-icon"><svg class="i"><use href="#ic-timer"/></svg></div>
        <h3>Simulasi ujian CBT</h3>
        <p>Tampilan seperti ujian sungguhan: timer, navigasi soal, dan tanda ragu-ragu, supaya kamu terbiasa sebelum hari-H.</p>
      </article>
      <article class="card" data-reveal style="--rd:180ms">
        <div class="card-icon"><svg class="i"><use href="#ic-analytics"/></svg></div>
        <h3>Analisis hasil</h3>
        <p>Skor per bagian, persentase penguasaan topik, dan grafik perkembangan dari setiap percobaan.</p>
      </article>
      <article class="card" data-reveal style="--rd:270ms">
        <div class="card-icon"><svg class="i"><use href="#ic-spark"/></svg></div>
        <h3>Rekomendasi AI</h3>
        <p>AI membaca pola kesalahanmu lalu menyarankan materi dan latihan yang paling perlu dikerjakan berikutnya.</p>
      </article>
      <article class="card" data-reveal style="--rd:360ms">
        <div class="card-icon"><svg class="i"><use href="#ic-trophy"/></svg></div>
        <h3>Ranking peserta</h3>
        <p>Bandingkan skormu dengan peserta lain per try out, per kategori, dan secara global setiap minggu.</p>
      </article>
      <article class="card" data-reveal style="--rd:450ms">
        <div class="card-icon"><svg class="i"><use href="#ic-chat"/></svg></div>
        <h3>Forum diskusi</h3>
        <p>Tanya, berbagi tips, dan bahas soal sulit bersama pejuang ujian lain di forum global, kategori, dan materi.</p>
      </article>
    </div>
  </section>

  {{-- SECTION 3 · SHOWCASE + STATS --}}
  <section class="section showcase" id="analisis">
    <div class="showcase-grid">
      <div class="showcase-copy">
        <span class="eyebrow" data-reveal>Analisis berbasis AI</span>
        <h2 data-reveal style="--rd:90ms">Tahu persis bagian mana yang harus diperbaiki</h2>
        <p data-reveal style="--rd:180ms">Setelah try out, Talentra langsung menampilkan skor per bagian, topik yang masih lemah, dan rencana belajar dari AI. Kamu tidak lagi menebak-nebak harus belajar apa.</p>
        <a href="{{ url('/register') }}" class="btn btn-primary" data-reveal style="--rd:270ms">Coba demo gratis <svg class="i arrow"><use href="#ic-arrow"/></svg></a>
      </div>

      <div class="dashboard" data-reveal style="--rd:160ms" aria-label="Contoh hasil analisis try out">
        <div class="dash-top">
          <span>Hasil Try Out · SKD CPNS</span>
          <span class="dash-live">Dianalisis AI</span>
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
      <div class="stat" data-reveal style="--rd:160ms"><div class="stat-value">24/7</div><div class="stat-label">Akses kapan saja</div></div>
      <div class="stat" data-reveal style="--rd:240ms"><div class="stat-value">AI</div><div class="stat-label">Analisis di setiap hasil</div></div>
    </div>
  </section>

  {{-- SECTION 4 · HARGA --}}
  <section class="section pricing" id="harga">
    <div class="section-head">
      <span class="eyebrow" data-reveal>Paket langganan</span>
      <h2 data-reveal style="--rd:80ms">Pilih paket sesuai tujuanmu</h2>
      <p data-reveal style="--rd:160ms">Mulai gratis dengan demo. Lanjutkan dengan paket satu kategori, atau ambil Semua Akses untuk berlatih semua ujian dengan satu langganan.</p>
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
        <a href="{{ url('/register') }}" class="btn btn-outline">Mulai Gratis</a>
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
        <a href="{{ url('/register') }}" class="btn btn-outline">Pilih Kategori</a>
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
        <a href="{{ url('/register') }}" class="btn btn-primary">Ambil Semua Akses <svg class="i arrow"><use href="#ic-arrow"/></svg></a>
      </div>
    </div>
    <p class="pricing-note" data-reveal>Tersedia durasi 1, 3, 6, dan 12 bulan. Makin panjang, makin hemat.</p>
  </section>

  {{-- SECTION 5 · CTA + FOOTER --}}
  <section class="section cta" id="cta">
    <div class="cta-card">
      <span class="eyebrow" data-reveal style="--rd:60ms">Siap mulai?</span>
      <h2 data-reveal style="--rd:140ms">Tumbuh, uji, dan berkembang bersama Talentra</h2>
      <p data-reveal style="--rd:220ms">Daftar dalam satu menit, coba demo try out gratis, dan lihat sendiri analisis hasilmu.</p>
      <div class="hero-actions" data-reveal style="--rd:300ms">
        <a href="{{ url('/register') }}" class="btn btn-primary">Coba Gratis Sekarang <svg class="i arrow"><use href="#ic-arrow"/></svg></a>
        <a href="{{ url('/login') }}" class="btn btn-outline">Masuk</a>
      </div>
    </div>

    <footer class="footer">
      <div class="footer-brand">
        <a href="#top" class="brand"><svg class="i brand-mark"><use href="#ic-logo"/></svg>TALENTRA</a>
        <p>Platform latihan dan tes online untuk semua jenjang. Tumbuh · Uji · Berkembang.</p>
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
          <a href="#">Forum</a>
          <a href="#">Ranking</a>
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
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass.js';
import { ShaderPass } from 'three/examples/jsm/postprocessing/ShaderPass.js';
import { GammaCorrectionShader } from 'three/examples/jsm/shaders/GammaCorrectionShader.js';
import { CopyShader } from 'three/examples/jsm/shaders/CopyShader.js';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

document.getElementById('year').textContent = new Date().getFullYear();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Lenis + reveal ---------- */
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
        if (el) { e.preventDefault(); lenis.scrollTo(el); }
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
  }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });
  revealEls.forEach((el) => io.observe(el));
}

/* ---------- WebGL planet ---------- */
const ASSET_BASE_URL = 'https://api.getlayers.ai/storage/v1/object/public/public/assets/ascend-d9857ad1f2';
const PLANET_GLB = `${ASSET_BASE_URL}/planet.glb`;
const PLANET_LIGHTS_GLB = `${ASSET_BASE_URL}/planet-lights.glb`;
const PLANET_CLOUDS_PNG = `${ASSET_BASE_URL}/planet-clouds.png`;

const CONFIG = {
  rimColor:'#c1faff', rimPower:2.4, nightLights:10, terrainDepth:0.33, terrainShade:1.3,
  oceanGlint:0.45, oceanDeep:0.12, oceanFlow:3, oceanFlowSpeed:0.8, oceanFlowScale:2.1,
  glowColor:'#3a6cff', glowIntensity:3.35, planetRadius:1.95, spin:0.03, initRotation:2.07,
  tilt:0.37, autoRotate:0,
  cloud1Height:1.005, cloud1Opacity:0.6, cloud1Spin:0.06,
  cloud2Height:1.03, cloud2Opacity:0.5, cloud2Spin:0.14,
  cloud3Height:1.075, cloud3Opacity:0.5, cloud3Spin:0.1,
  bgColor:'#040a1e', flameColor:'#3a6cff', flameColor2:'#c1faff', flameAmt:0.15,
  atmoColor:'#9fc4ff', atmoCount:320, atmoSize:22, atmoSpeed:0.8,
  starColor:'#cfe0ff', starCount:1400, starSize:1.6, starFlicker:1,
  markerColor:'#ffd27a', markerCount:60, markerSize:16, markerSpeed:0.5,
};
const LAYERS = { NONE:0, TORUS_SCENE:1, BLOOM_SCENE:2, ENTIRE_SCENE:3 };

const Lerp = (a, b, t) => a + (b - a) * t;
const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
const hexToVec3 = (hex) => { const c = new THREE.Color(hex); return new THREE.Vector3(c.r, c.g, c.b); };
const sample = (stops, p) => {
  if (p <= stops[0].p) { return stops[0].v; }
  for (let i = 0; i < stops.length - 1; i++) {
    const a = stops[i], b = stops[i + 1];
    if (p <= b.p) { let t = (p - a.p) / (b.p - a.p); t = t * t * (3 - 2 * t); return Lerp(a.v, b.v, t); }
  }
  return stops[stops.length - 1].v;
};

const SNOISE = `
vec4 permute(vec4 x){return mod(((x*34.0)+1.0)*x, 289.0);}
vec4 taylorInvSqrt(vec4 r){return 1.79284291400159 - 0.85373472095314 * r;}
float snoise(vec3 v){
  const vec2 C = vec2(1.0/6.0, 1.0/3.0); const vec4 D = vec4(0.0, 0.5, 1.0, 2.0);
  vec3 i = floor(v + dot(v, C.yyy)); vec3 x0 = v - i + dot(i, C.xxx);
  vec3 g = step(x0.yzx, x0.xyz); vec3 l = 1.0 - g;
  vec3 i1 = min(g.xyz, l.zxy); vec3 i2 = max(g.xyz, l.zxy);
  vec3 x1 = x0 - i1 + 1.0 * C.xxx; vec3 x2 = x0 - i2 + 2.0 * C.xxx; vec3 x3 = x0 - 1.0 + 3.0 * C.xxx;
  i = mod(i, 289.0);
  vec4 p = permute(permute(permute(i.z + vec4(0.0, i1.z, i2.z, 1.0)) + i.y + vec4(0.0, i1.y, i2.y, 1.0)) + i.x + vec4(0.0, i1.x, i2.x, 1.0));
  float n_ = 1.0/7.0; vec3 ns = n_ * D.wyz - D.xzx;
  vec4 j = p - 49.0 * floor(p * ns.z *ns.z);
  vec4 x_ = floor(j * ns.z); vec4 y_ = floor(j - 7.0 * x_);
  vec4 x = x_ *ns.x + ns.yyyy; vec4 y = y_ *ns.x + ns.yyyy; vec4 h = 1.0 - abs(x) - abs(y);
  vec4 b0 = vec4(x.xy, y.xy); vec4 b1 = vec4(x.zw, y.zw);
  vec4 s0 = floor(b0)*2.0 + 1.0; vec4 s1 = floor(b1)*2.0 + 1.0; vec4 sh = -step(h, vec4(0.0));
  vec4 a0 = b0.xzyw + s0.xzyw*sh.xxyy; vec4 a1 = b1.xzyw + s1.xzyw*sh.zzww;
  vec3 p0 = vec3(a0.xy,h.x); vec3 p1 = vec3(a0.zw,h.y); vec3 p2 = vec3(a1.xy,h.z); vec3 p3 = vec3(a1.zw,h.w);
  vec4 norm = taylorInvSqrt(vec4(dot(p0,p0), dot(p1,p1), dot(p2, p2), dot(p3,p3)));
  p0 *= norm.x; p1 *= norm.y; p2 *= norm.z; p3 *= norm.w;
  vec4 m = max(0.5 - vec4(dot(x0,x0), dot(x1,x1), dot(x2,x2), dot(x3,x3)), 0.0); m = m * m;
  return 42.0 * dot(m*m, vec4(dot(p0,x0), dot(p1,x1), dot(p2,x2), dot(p3,x3)));
}
`;

const canvas = document.querySelector('.planet-canvas');
let renderer;
try {
  renderer = new THREE.WebGL1Renderer({ canvas, antialias: true });
} catch (e) {
  renderer = null;
}

if (renderer) {
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.VSMShadowMap;

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0x000000);

  const camera = new THREE.PerspectiveCamera(40, window.innerWidth / window.innerHeight, 0.1, 200);
  camera.position.set(0, 0, 8);
  camera.layers.enable(LAYERS.TORUS_SCENE);
  camera.layers.enable(LAYERS.BLOOM_SCENE);
  camera.layers.enable(LAYERS.ENTIRE_SCENE);
  scene.add(camera);

  const onEntire = (obj) => obj.traverse((o) => o.layers.set(LAYERS.ENTIRE_SCENE));

  const ambient = new THREE.AmbientLight(0xffffff, 1.8);
  const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
  dirLight.position.set(0, 10, 2);
  ambient.layers.set(LAYERS.ENTIRE_SCENE);
  dirLight.layers.set(LAYERS.ENTIRE_SCENE);
  scene.add(ambient, dirLight);

  /* composers */
  const size = new THREE.Vector2(window.innerWidth, window.innerHeight);
  const torusComposer = new EffectComposer(renderer);
  torusComposer.renderToScreen = false;
  torusComposer.addPass(new RenderPass(scene, camera));
  torusComposer.addPass(new ShaderPass(GammaCorrectionShader));
  torusComposer.addPass(new UnrealBloomPass(size, 0.22, 0.2, 0));
  torusComposer.addPass(new ShaderPass(CopyShader));

  const bloomComposer = new EffectComposer(renderer);
  bloomComposer.renderToScreen = false;
  bloomComposer.addPass(new RenderPass(scene, camera));
  bloomComposer.addPass(new UnrealBloomPass(size, 0.5, 0.6, 0));
  bloomComposer.addPass(new ShaderPass(GammaCorrectionShader));

  const FinalPass = {
    uniforms: {
      iTime:{value:0}, tDiffuse:{value:null}, torusTexture:{value:null}, bloomTexture:{value:null}, haloTexture:{value:null},
      uBg:{value:hexToVec3(CONFIG.bgColor)}, uFlameA:{value:hexToVec3(CONFIG.flameColor)},
      uFlameB:{value:hexToVec3(CONFIG.flameColor2)}, uFlameAmt:{value:CONFIG.flameAmt},
    },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position, 1.0); }`,
    fragmentShader: `
      uniform float iTime; uniform sampler2D tDiffuse; uniform sampler2D bloomTexture; uniform sampler2D torusTexture; uniform sampler2D haloTexture;
      uniform vec3 uBg; uniform vec3 uFlameA; uniform vec3 uFlameB; uniform float uFlameAmt;
      varying vec2 vUv;
      vec3 warp3d(vec3 pos, float t){ float curv=.8,a=1.9,b=0.7; pos*=2.;
        pos.x+=curv*sin(t+a*pos.y)+t*b; pos.y+=curv*cos(t+a*pos.x);
        pos.y+=curv*sin(t+a*pos.z)+t*b; pos.z+=curv*cos(t+a*pos.y);
        pos.z+=curv*sin(t+a*pos.x)+t*b; pos.x+=curv*cos(t+a*pos.z);
        return 0.5+0.5*cos(pos.xyz+vec3(1,2,4)); }
      void main(){
        vec2 uv = 2.*vUv - 1.;
        vec3 w = pow(warp3d(vec3(uv.x, sin(uv.y), uv.y), iTime*1.5), vec3(1.5));
        vec3 flame = 1.5*uFlameA*w.x; flame*=w.y; flame += uFlameB*w.z;
        flame *= smoothstep(0.25, 1., abs(uv.y));
        float md = smoothstep(-0.7, 1., -uv.y*uv.x); flame *= md*md;
        vec3 bg = uBg * (1.0 - 0.4 * length(uv));
        vec3 halo = texture2D(haloTexture, vUv).xyz;
        gl_FragColor = vec4(bg + flame*uFlameAmt + texture2D(bloomTexture, vUv).xyz + texture2D(torusTexture, vUv).xyz + texture2D(tDiffuse, vUv).xyz + halo, 1.);
      }`,
  };
  const finalComposer = new EffectComposer(renderer);
  finalComposer.addPass(new RenderPass(scene, camera));
  const finalPass = new ShaderPass(FinalPass);
  finalPass.uniforms.bloomTexture.value = bloomComposer.renderTarget1.texture;
  finalPass.uniforms.torusTexture.value = torusComposer.renderTarget1.texture;
  finalComposer.addPass(finalPass);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.06;
  controls.enablePan = false;
  controls.enabled = false;
  controls.autoRotate = true;
  controls.autoRotateSpeed = CONFIG.autoRotate;
  controls.minDistance = 3.5;
  controls.maxDistance = 16;
  controls.target.set(0, 0, 0);

  /* scroll stops */
  const STOPS_X = [{p:0,v:0},{p:0.32,v:-3.1},{p:0.64,v:3.2},{p:1,v:0}];
  const STOPS_Y = [{p:0,v:-4.5},{p:0.32,v:0.55},{p:0.64,v:0.45},{p:1,v:0.15}];
  const STOPS_S = [{p:0,v:2.15},{p:0.32,v:1.0},{p:0.64,v:0.92},{p:1,v:1.12}];

  /* groups */
  const worldGroup = new THREE.Group();
  const planetGroup = new THREE.Group();
  const cloudGroup = new THREE.Group();
  planetGroup.rotation.z = CONFIG.tilt;
  cloudGroup.rotation.z = CONFIG.tilt;
  cloudGroup.visible = false;
  worldGroup.add(planetGroup, cloudGroup);
  worldGroup.position.set(STOPS_X[0].v, STOPS_Y[0].v, 0);
  worldGroup.scale.setScalar(STOPS_S[0].v);
  scene.add(worldGroup);

  const planetTime = { value: 0 };
  const cloudTime = { value: 0 };
  const starTime = { value: 0 };
  const markerTime = { value: 0 };
  const resUniform = { value: new THREE.Vector2(window.innerWidth, window.innerHeight) };

  /* atmosphere halo */
  const glowMesh = new THREE.Mesh(
    new THREE.PlaneGeometry(2, 2),
    new THREE.ShaderMaterial({
      uniforms: { uGlow: { value: hexToVec3(CONFIG.glowColor) }, uIntensity: { value: CONFIG.glowIntensity } },
      vertexShader: `varying vec2 vUv; void main(){ vUv=uv; gl_Position = projectionMatrix*modelViewMatrix*vec4(position,1.0); }`,
      fragmentShader: `
        uniform vec3 uGlow; uniform float uIntensity; varying vec2 vUv;
        void main(){
          float d = length(vUv - 0.5) * 2.0;
          float a = pow(clamp(1.0 - d, 0.0, 1.0), 2.2);
          gl_FragColor = vec4(uGlow * a * uIntensity, a);
        }`,
      transparent: true, side: THREE.DoubleSide, depthWrite: false, blending: THREE.AdditiveBlending,
    })
  );
  glowMesh.scale.setScalar(CONFIG.planetRadius * 2.3);
  worldGroup.add(glowMesh);

  /* clouds */
  const cloudMeshes = [];
  new THREE.TextureLoader().load(PLANET_CLOUDS_PNG, (tex) => {
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(5, 5);
    const layers = [
      { h: CONFIG.cloud1Height, o: CONFIG.cloud1Opacity, s: 'cloud1Spin', ry: 0.0, phase: 0.0 },
      { h: CONFIG.cloud2Height, o: CONFIG.cloud2Opacity, s: 'cloud2Spin', ry: 2.2, phase: 13.0 },
      { h: CONFIG.cloud3Height, o: CONFIG.cloud3Opacity, s: 'cloud3Spin', ry: 4.3, phase: 27.0 },
    ];
    layers.forEach((layer) => {
      const mat = new THREE.MeshStandardMaterial({ map: tex, transparent: true, depthWrite: false });
      mat.onBeforeCompile = (shader) => {
        shader.uniforms.uTime = cloudTime;
        shader.uniforms.noiseScale = { value: 20 };
        shader.uniforms.uSpeedX = { value: 1 };
        shader.uniforms.uSpeedY = { value: 2 };
        shader.uniforms.uSpeedZ = { value: 2 };
        shader.uniforms.uOpacity = { value: layer.o };
        shader.uniforms.uPhase = { value: layer.phase };
        shader.vertexShader = 'varying vec2 vCloudUv;\n' + shader.vertexShader.replace('void main() {', 'void main() {\n vCloudUv = uv;');
        shader.fragmentShader = `uniform float uTime; uniform float noiseScale; uniform float uSpeedX; uniform float uSpeedY; uniform float uSpeedZ; uniform float uOpacity; uniform float uPhase;
varying vec2 vCloudUv;
${SNOISE}
` + shader.fragmentShader.replace('#include <dithering_fragment>', `#include <dithering_fragment>
gl_FragColor.rgb = vec3(1.0);
float cloudNoise = snoise(vec3(vCloudUv.x * noiseScale + uTime * uSpeedX + uPhase, vCloudUv.y * noiseScale - uTime * uSpeedY + uPhase, uTime * uSpeedZ + uPhase));
float cloudNdv = max(dot(normalize(vNormal), normalize(vViewPosition)), 0.0);
float cloudEdge = pow(1.0 - cloudNdv, 3.0);
float cloudMod = mix(cloudNoise, 1.0, cloudEdge);
float cloudNdl = dot(normalize(vNormal), normalize(vec3(-0.9, 0.18, 0.4)));
float cloudDay = 1.0 - smoothstep(0.30, -0.30, cloudNdl) * 0.9;
gl_FragColor.a *= cloudMod * uOpacity * cloudDay;`);
      };
      const mesh = new THREE.Mesh(new THREE.SphereGeometry(CONFIG.planetRadius * layer.h, 64, 64), mat);
      mesh.rotation.y = layer.ry;
      mesh.renderOrder = 2;
      mesh.userData.spinKey = layer.s;
      cloudGroup.add(mesh);
      cloudMeshes.push(mesh);
    });
    onEntire(cloudGroup);
  });

  /* ambient motes */
  {
    const n = CONFIG.atmoCount;
    const pos = new Float32Array(n * 3), sizes = new Float32Array(n), seeds = new Float32Array(n);
    for (let i = 0; i < n; i++) {
      pos[i * 3] = Math.random() * 2 - 1; pos[i * 3 + 1] = Math.random() * 2 - 1; pos[i * 3 + 2] = Math.random() * 2 - 1;
      sizes[i] = CONFIG.atmoSize * (0.4 + Math.random());
      seeds[i] = Math.random();
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
    geo.setAttribute('seed', new THREE.BufferAttribute(seeds, 1));
    const mat = new THREE.ShaderMaterial({
      uniforms: { uTime: { value: 0 }, uRes: resUniform, uColor: { value: hexToVec3(CONFIG.atmoColor) } },
      vertexShader: `
        attribute float size; attribute float seed; uniform float uTime; uniform vec2 uRes;
        varying float vA;
        vec3 warp(vec3 p, float t){ float c=0.9,a=1.9,b=0.02,s=0.05; p*=2.;
          p.x+=c*sin(s*t+a*p.y)+t*b; p.y+=c*cos(s*t+a*p.x); p.y+=c*sin(s*t+a*p.z)+t*b;
          p.z+=c*cos(s*t+a*p.y); p.z+=c*sin(s*t+a*p.x)+t*b; p.x+=c*cos(s*t+a*p.z);
          return cos(p+vec3(1,2,4)); }
        void main(){
          vec3 v = position*4.0 + warp(position, uTime)*1.2;
          vec4 mv = modelViewMatrix * vec4(v, 1.0);
          float r = length(v); float farF = 1.0 - smoothstep(5.0, 6.5, r); float nearF = smoothstep(0.0, 0.5, -mv.z);
          vA = farF * nearF;
          gl_PointSize = size * uRes.y / 900.0 / -mv.z; gl_PointSize = max(gl_PointSize, 1.0);
          gl_Position = projectionMatrix * mv;
        }`,
      fragmentShader: `
        uniform vec3 uColor; varying float vA;
        void main(){ vec2 p = gl_PointCoord - 0.5; float l = length(p); if (l > 0.5) discard;
          float tex = smoothstep(0.5, 0.0, l); gl_FragColor = vec4(uColor * tex, tex * vA * 0.55); }`,
      transparent: true, blending: THREE.AdditiveBlending, depthWrite: false, depthTest: false,
    });
    const motes = new THREE.Points(geo, mat);
    motes.frustumCulled = false;
    motes.onBeforeRender = () => {
      const t = performance.now() / 1000;
      mat.uniforms.uTime.value = t * CONFIG.atmoSpeed * 8;
      motes.position.copy(camera.position);
      finalPass.uniforms.iTime.value = t;
    };
    onEntire(motes);
    scene.add(motes);
  }

  /* starfield */
  {
    const n = CONFIG.starCount;
    const pos = new Float32Array(n * 3), seeds = new Float32Array(n), bright = new Float32Array(n);
    for (let i = 0; i < n; i++) {
      const u = Math.random() * 2 - 1, th = Math.random() * Math.PI * 2, s = Math.sqrt(1 - u * u);
      pos[i * 3] = 90 * s * Math.cos(th); pos[i * 3 + 1] = 90 * u; pos[i * 3 + 2] = 90 * s * Math.sin(th);
      seeds[i] = Math.random() * 100;
      bright[i] = 0.35 + Math.random() * 0.65;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('seed', new THREE.BufferAttribute(seeds, 1));
    geo.setAttribute('bright', new THREE.BufferAttribute(bright, 1));
    const stars = new THREE.Points(geo, new THREE.ShaderMaterial({
      uniforms: { uTime: starTime, uSize: { value: CONFIG.starSize }, uFlicker: { value: CONFIG.starFlicker }, uColor: { value: hexToVec3(CONFIG.starColor) }, uRes: resUniform },
      vertexShader: `
        attribute float seed; attribute float bright;
        uniform float uTime; uniform float uSize; uniform float uFlicker; uniform vec2 uRes;
        varying float vTw;
        void main(){
          vec4 mv = modelViewMatrix * vec4(position, 1.0);
          float tw = 0.6 + 0.4 * sin(uTime * uFlicker + seed);
          vTw = bright * tw;
          gl_PointSize = max(uSize * uRes.y / 900.0 * (90.0 / max(-mv.z, 1.0)), 1.0);
          gl_Position = projectionMatrix * mv;
        }`,
      fragmentShader: `
        uniform vec3 uColor; varying float vTw;
        void main(){
          vec2 p = gl_PointCoord - 0.5; float l = length(p); if (l > 0.5) discard;
          float core = smoothstep(0.5, 0.0, l);
          gl_FragColor = vec4(uColor, core * vTw);
        }`,
      transparent: true, blending: THREE.AdditiveBlending, depthWrite: false, depthTest: true,
    }));
    stars.frustumCulled = false;
    onEntire(stars);
    scene.add(stars);
  }

  /* land markers */
  function buildMarkers(mesh) {
    const map = mesh.material.map;
    const img = map && map.image;
    if (!img) { return; }
    const w = img.width, h = img.height;
    const cvs = document.createElement('canvas');
    cvs.width = w; cvs.height = h;
    const ctx = cvs.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(img, 0, 0);
    let pixels;
    try { pixels = ctx.getImageData(0, 0, w, h).data; } catch (e) { return; }

    const geo = mesh.geometry;
    const posA = geo.attributes.position, uvA = geo.attributes.uv;
    if (!uvA) { return; }
    const index = geo.index;
    const triCount = index ? index.count / 3 : posA.count / 3;
    const idx = (i) => (index ? index.getX(i) : i);
    const cum = new Float32Array(triCount);
    const a = new THREE.Vector3(), b = new THREE.Vector3(), c = new THREE.Vector3();
    let total = 0;
    for (let t = 0; t < triCount; t++) {
      a.fromBufferAttribute(posA, idx(t * 3)); b.fromBufferAttribute(posA, idx(t * 3 + 1)); c.fromBufferAttribute(posA, idx(t * 3 + 2));
      total += new THREE.Triangle(a, b, c).getArea();
      cum[t] = total;
    }
    const pickTri = (r) => { let lo = 0, hi = triCount - 1; while (lo < hi) { const mid = (lo + hi) >> 1; if (cum[mid] < r) { lo = mid + 1; } else { hi = mid; } } return lo; };

    const out = [], seeds = [];
    const ua = new THREE.Vector2(), ub = new THREE.Vector2(), uc = new THREE.Vector2();
    let tries = 0;
    while (out.length < CONFIG.markerCount * 3 && tries < 20000) {
      tries++;
      const t = pickTri(Math.random() * total);
      let r1 = Math.random(), r2 = Math.random();
      if (r1 + r2 > 1) { r1 = 1 - r1; r2 = 1 - r2; }
      const r0 = 1 - r1 - r2;
      ua.fromBufferAttribute(uvA, idx(t * 3)); ub.fromBufferAttribute(uvA, idx(t * 3 + 1)); uc.fromBufferAttribute(uvA, idx(t * 3 + 2));
      let u = ua.x * r0 + ub.x * r1 + uc.x * r2;
      let v = ua.y * r0 + ub.y * r1 + uc.y * r2;
      u = ((u % 1) + 1) % 1; v = ((v % 1) + 1) % 1;
      const px = Math.min(w - 1, Math.floor(u * w));
      const py = Math.min(h - 1, Math.floor((map.flipY ? 1 - v : v) * h));
      const o = (py * w + px) * 4;
      const cr = pixels[o], cg = pixels[o + 1], cb = pixels[o + 2];
      if (cb > cr + 6 && cb > cg + 6) { continue; }
      a.fromBufferAttribute(posA, idx(t * 3)); b.fromBufferAttribute(posA, idx(t * 3 + 1)); c.fromBufferAttribute(posA, idx(t * 3 + 2));
      const p = a.multiplyScalar(r0).add(b.multiplyScalar(r1)).add(c.multiplyScalar(r2)).multiplyScalar(1.012);
      out.push(p.x, p.y, p.z);
      seeds.push(Math.random());
    }
    if (!out.length) { return; }
    const mgeo = new THREE.BufferGeometry();
    mgeo.setAttribute('position', new THREE.Float32BufferAttribute(out, 3));
    mgeo.setAttribute('seed', new THREE.Float32BufferAttribute(seeds, 1));
    const markers = new THREE.Points(mgeo, new THREE.ShaderMaterial({
      uniforms: { uTime: markerTime, uColor: { value: hexToVec3(CONFIG.markerColor) }, uSize: { value: CONFIG.markerSize }, uSpeed: { value: CONFIG.markerSpeed }, uRes: resUniform },
      vertexShader: `
        attribute float seed; uniform float uSize; uniform vec2 uRes;
        varying float vSeed; varying float vFade;
        void main(){
          vSeed = seed;
          vec4 mv = modelViewMatrix * vec4(position, 1.0);
          vec3 vn = normalize(normalMatrix * normalize(position));
          vec3 vd = normalize(-mv.xyz);
          vFade = smoothstep(0.15, 0.5, dot(vn, vd));
          gl_PointSize = max(uSize * uRes.y / 900.0 * (7.0 / max(-mv.z, 1.0)), 2.0);
          gl_Position = projectionMatrix * mv;
        }`,
      fragmentShader: `
        uniform vec3 uColor; uniform float uTime; uniform float uSpeed;
        varying float vSeed; varying float vFade;
        void main(){
          if (vFade <= 0.001) discard;
          vec2 p = gl_PointCoord - 0.5;
          float d = length(p) * 2.0;
          if (d > 1.0) discard;
          float core = smoothstep(0.30, 0.0, d) * 1.2;
          float ph = fract(uTime * uSpeed + vSeed);
          float ring = smoothstep(0.07, 0.0, abs(d - ph)) * (1.0 - ph);
          gl_FragColor = vec4(uColor, clamp(core + ring, 0.0, 1.0) * vFade);
        }`,
      transparent: true, blending: THREE.AdditiveBlending, depthTest: true, depthWrite: false,
    }));
    markers.frustumCulled = false;
    markers.layers.set(LAYERS.ENTIRE_SCENE);
    mesh.add(markers);
  }

  /* planet */
  let entryStart = -1;
  const ENTRY_DUR = 1.9, ENTRY_START_Y = -6.5;
  const draco = new DRACOLoader();
  draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.5/');
  const gltfLoader = new GLTFLoader();
  gltfLoader.setDRACOLoader(draco);

  const firstMesh = (root) => { let m = null; root.traverse((o) => { if (!m && o.isMesh) { m = o; } }); return m; };

  gltfLoader.load(PLANET_LIGHTS_GLB, (lightsGltf) => {
    const lightsMesh = firstMesh(lightsGltf.scene);
    const nightTex = lightsMesh ? lightsMesh.material.map : null;
    gltfLoader.load(PLANET_GLB, (gltf) => {
      const mesh = firstMesh(gltf.scene);
      if (!mesh) { return; }
      mesh.removeFromParent();
      mesh.geometry.computeBoundingSphere();
      const bs = mesh.geometry.boundingSphere;
      const s = CONFIG.planetRadius / bs.radius;
      mesh.scale.setScalar(s);
      mesh.position.copy(bs.center).multiplyScalar(-s);
      mesh.rotation.set(0, 0, 0);

      const mat = mesh.material.clone();
      mat.metalness = 0; mat.roughness = 1; mat.envMapIntensity = 0;
      mat.onBeforeCompile = (shader) => {
        Object.assign(shader.uniforms, {
          time: planetTime, noiseScale: { value: 30 }, speedX: { value: 1.5 }, speedY: { value: 2.0 }, speedZ: { value: 2.5 },
          rimColor: { value: new THREE.Color(CONFIG.rimColor) }, rimPower: { value: CONFIG.rimPower },
          nightBlendTexture: { value: nightTex }, nightLights: { value: CONFIG.nightLights },
          terrainDepth: { value: CONFIG.terrainDepth }, terrainShade: { value: CONFIG.terrainShade },
          oceanGlint: { value: CONFIG.oceanGlint }, oceanDeep: { value: CONFIG.oceanDeep }, oceanFlow: { value: CONFIG.oceanFlow },
          oceanFlowSpeed: { value: CONFIG.oceanFlowSpeed }, oceanFlowScale: { value: CONFIG.oceanFlowScale },
        });
        shader.vertexShader = 'varying vec2 vCustomUv;\n' + shader.vertexShader.replace('void main() {', 'void main() {\n vCustomUv = uv;');
        shader.fragmentShader = `uniform float time; uniform float noiseScale; uniform float speedX; uniform float speedY; uniform float speedZ;
uniform vec3 rimColor; uniform float rimPower; uniform sampler2D nightBlendTexture; uniform float nightLights;
uniform float terrainDepth; uniform float terrainShade; uniform float oceanGlint; uniform float oceanDeep;
uniform float oceanFlow; uniform float oceanFlowSpeed; uniform float oceanFlowScale;
varying vec2 vCustomUv;
${SNOISE}
` + shader.fragmentShader.replace('#include <dithering_fragment>', `#include <dithering_fragment>
vec3 normalizedNormal = normalize(vNormal);
vec3 viewDir = normalize(vViewPosition);
float rim = 1.0 - max(dot(viewDir, normalizedNormal), 0.0);
rim = pow(rim, rimPower); rim = pow(rim, 1.5); rim *= 0.7;
vec3 currentColor = gl_FragColor.rgb;
float blueDom = currentColor.b - max(currentColor.r, currentColor.g);
float waterMask = clamp(smoothstep(-0.005, 0.03, blueDom), 0.0, 1.0);
float shimmer = snoise(vec3(vCustomUv.x * noiseScale + time * speedX, vCustomUv.y * noiseScale - time * speedY, time * speedZ));
gl_FragColor.rgb += waterMask * shimmer * 0.025;
float fT = time * oceanFlowSpeed * 4.0;
float fS = 4.0 * oceanFlowScale;
float warp = snoise(vec3(vCustomUv.x * fS - fT * 0.5, vCustomUv.y * fS + fT * 0.4, fT * 0.5));
float flow = snoise(vec3(vCustomUv.x * fS * 2.0 + fT * 0.6 + warp, vCustomUv.y * fS * 2.0 - fT * 0.5, fT * 0.7));
flow = warp * 0.6 + flow * 0.4;
gl_FragColor.rgb += waterMask * flow * 0.12 * oceanFlow;
gl_FragColor.rgb = mix(gl_FragColor.rgb, vec3(0.01, 0.06, 0.16), waterMask * oceanDeep);
vec3 finalColor = mix(gl_FragColor.rgb, rimColor, rim);
gl_FragColor = vec4(finalColor, 1.0);
vec3 surfPos = -vViewPosition;
float terrH = dot(texture2D(map, vCustomUv).rgb, vec3(0.299, 0.587, 0.114));
vec3 sigX = dFdx(surfPos), sigY = dFdy(surfPos);
vec3 vR1 = cross(sigY, normalizedNormal), vR2 = cross(normalizedNormal, sigX);
float fDet = dot(sigX, vR1);
vec3 vGrad = sign(fDet) * (dFdx(terrH) * vR1 + dFdy(terrH) * vR2);
vec3 bumpedNormal = normalize(abs(fDet) * normalizedNormal - terrainDepth * vGrad);
vec3 shadeNormal = mix(bumpedNormal, normalizedNormal, waterMask);
vec3 cityLights = texture2D(nightBlendTexture, vCustomUv).rgb * gl_FragColor.rgb * nightLights;
vec3 viewSunDir = normalize(vec3(-0.9, 0.18, 0.4));
float ndl = dot(normalizedNormal, viewSunDir);
float dayAmt = smoothstep(-0.05, 0.35, ndl);
float relief = dot(shadeNormal, viewSunDir) - ndl;
gl_FragColor.rgb *= clamp(1.0 + relief * terrainShade * dayAmt, 0.55, 1.6);
float nightFactor  = smoothstep(0.18, -0.30, ndl);
float lightsFactor = smoothstep(0.30, -0.35, ndl);
gl_FragColor.rgb = mix(gl_FragColor.rgb, gl_FragColor.rgb * 0.08, nightFactor);
gl_FragColor.rgb += cityLights * lightsFactor;
vec3 halfDir = normalize(viewSunDir + viewDir);
float ripple = snoise(vec3(vCustomUv * 240.0, time * 4.0));
float ndh = max(dot(normalizedNormal, halfDir) + ripple * 0.02, 0.0);
float glint = pow(ndh, 140.0);
gl_FragColor.rgb += glint * waterMask * dayAmt * oceanGlint * vec3(1.0, 0.97, 0.88);`);
      };
      mesh.material = mat;
      planetGroup.add(mesh);
      onEntire(planetGroup);
      buildMarkers(mesh);
      cloudGroup.visible = true;
      entryStart = reducedMotion ? -2 : performance.now() / 1000;
    });
  });

  onEntire(worldGroup);

  /* loop */
  let curP = 0, curX = STOPS_X[0].v, curY = STOPS_Y[0].v, curS = STOPS_S[0].v, spinPhase = 0;
  let last = performance.now() / 1000;

  function tick() {
    requestAnimationFrame(tick);
    const now = performance.now() / 1000;
    const dt = Math.min(0.05, now - last);
    last = now;

    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    const pTarget = clamp(maxScroll > 0 ? window.scrollY / maxScroll : 0, 0, 1);
    curP += (pTarget - curP) * Math.min(1, dt * 4.5);

    const sideScale = clamp(window.innerWidth / 1200, 0.5, 1);
    const k = Math.min(1, dt * 3.2);
    curX += (sample(STOPS_X, curP) * sideScale - curX) * k;
    curY += (sample(STOPS_Y, curP) - curY) * k;
    curS += (sample(STOPS_S, curP) - curS) * k;

    let entryY = 0;
    if (entryStart === -1) {
      entryY = ENTRY_START_Y;
    } else if (entryStart > 0) {
      const t = clamp((now - entryStart) / ENTRY_DUR, 0, 1);
      entryY = ENTRY_START_Y * (1 - (1 - Math.pow(1 - t, 3)));
    }
    worldGroup.position.set(curX, curY + entryY, 0);
    worldGroup.scale.setScalar(curS);

    spinPhase += dt * CONFIG.spin;
    planetGroup.rotation.y = CONFIG.initRotation + spinPhase + curP * Math.PI * 1.6;
    cloudMeshes.forEach((m) => { m.rotation.y += dt * CONFIG[m.userData.spinKey]; });
    glowMesh.quaternion.copy(camera.quaternion);

    planetTime.value += dt / 12;
    cloudTime.value += dt / 20;
    starTime.value += dt;
    markerTime.value += dt;

    controls.update();

    camera.layers.set(LAYERS.TORUS_SCENE); torusComposer.render();
    camera.layers.set(LAYERS.BLOOM_SCENE); bloomComposer.render();
    camera.layers.set(LAYERS.ENTIRE_SCENE); finalComposer.render();
  }
  tick();

  window.addEventListener('resize', () => {
    const w = window.innerWidth, h = window.innerHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
    [torusComposer, bloomComposer, finalComposer].forEach((c) => { c.setPixelRatio(window.devicePixelRatio); c.setSize(w, h); });
    resUniform.value.set(w, h);
  });
}
</script>
</body>
</html>
