@php
    $rupiah = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $isEn = $locale === 'en';
    $num = fn ($v, $dec = 0) => number_format((float) $v, $dec, $isEn ? '.' : ',', $isEn ? ',' : '.');
    $storage = function ($bytes) use ($isEn) {
        if (!$bytes) return __('Tanpa batas');
        return $bytes >= 1073741824
            ? rtrim(rtrim(number_format($bytes / 1073741824, 1, $isEn ? '.' : ',', $isEn ? ',' : '.'), '0'), $isEn ? '.' : ',') . ' GB'
            : round($bytes / 1048576) . ' MB';
    };
    $limit = fn ($v, $unit) => $v === null ? __('Tanpa batas :unit', ['unit' => __($unit)]) : $num($v) . ' ' . __($unit);
    $ctaUrl = auth()->check() ? url('/dashboard/home') : route('register');
    $ctaLabel = auth()->check() ? __('Buka Dashboard') : __('Coba Gratis :days Hari', ['days' => $trialDays]);
    $img = fn ($path) => \App\Services\Landing\LandingContent::imageUrl($path);
    $iconLine = fn ($line, $fallback = 'bi-check2-circle') => \App\Services\Landing\LandingContent::iconLine($line, $fallback);
    $homeUrl = $isEn ? route('landing.en') : route('landing');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $c['seo']['title'] }}</title>
<meta name="description" content="{{ $c['seo']['description'] }}">
<meta name="theme-color" content="#0b2447">
<link rel="canonical" href="{{ $homeUrl }}">
<link rel="alternate" hreflang="id" href="{{ route('landing') }}">
<link rel="alternate" hreflang="en" href="{{ route('landing.en') }}">
<link rel="alternate" hreflang="x-default" href="{{ route('landing') }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="{{ $isEn ? 'en_US' : 'id_ID' }}">
<meta property="og:url" content="{{ $homeUrl }}">
<meta property="og:title" content="{{ $c['seo']['title'] }}">
<meta property="og:description" content="{{ $c['seo']['description'] }}">
<meta property="og:image" content="{{ $img($c['seo']['og_image']) ?? asset('assets/images/brand/vasetra-logo.png') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
<style>
:root{
  --navy:#0b2447; --navy-2:#12305c; --navy-3:#071a35;
  --blue:#1f5fbf; --blue-2:#2f7be0; --teal:#14b8a6; --cyan:#22d3ee;
  --page:#f4f7fb; --surface:#fff; --ink:#0f1b2d; --ink-2:#4b5a6e; --muted:#8592a6;
  --line:rgba(15,27,45,.08); --line-2:rgba(15,27,45,.12);
  --grad:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%);
  --grad-text:linear-gradient(100deg,#1f5fbf 0%,#14b8a6 100%);
  --shadow:0 1px 2px rgba(15,27,45,.04),0 12px 32px -16px rgba(15,27,45,.18);
  --shadow-lg:0 2px 6px rgba(15,27,45,.05),0 40px 80px -32px rgba(11,36,71,.35);
  --r:18px; --r-sm:12px; --max:1200px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{margin:0;background:var(--page);color:var(--ink-2);font-family:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:16px;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
h1,h2,h3,h4{color:var(--ink);margin:0;letter-spacing:-.025em;line-height:1.15;font-weight:800}
p{margin:0}
.wrap{max-width:var(--max);margin:0 auto;padding-inline:24px}
.grad-text{background:var(--grad-text);-webkit-background-clip:text;background-clip:text;color:transparent}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;font-weight:700;font-size:.95rem;border-radius:999px;padding:.8rem 1.4rem;border:1px solid transparent;transition:transform .15s,box-shadow .2s,background .2s,color .2s;cursor:pointer;white-space:nowrap}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:var(--grad);color:#fff;box-shadow:0 10px 24px -10px rgba(31,95,191,.65)}
.btn-primary:hover{box-shadow:0 14px 30px -10px rgba(31,95,191,.8)}
.btn-ghost{background:#fff;color:var(--navy);border-color:var(--line-2)}
.btn-ghost:hover{border-color:var(--blue)}
.btn-light{background:#fff;color:var(--navy)}
.btn-outline-light{color:#fff;border-color:rgba(255,255,255,.35)}
.btn-outline-light:hover{background:rgba(255,255,255,.08)}
.btn-lg{padding:1rem 1.7rem;font-size:1rem}
.btn-sm{padding:.55rem 1.05rem;font-size:.88rem}

/* nav (pil melayang) */
.nav{position:sticky;top:14px;z-index:50;padding-inline:16px;margin-top:14px}
.nav .wrap{display:flex;align-items:center;justify-content:space-between;height:64px;gap:24px;max-width:1120px;padding:0 10px 0 22px;background:rgba(255,255,255,.78);backdrop-filter:saturate(180%) blur(16px);-webkit-backdrop-filter:saturate(180%) blur(16px);border:1px solid var(--line-2);border-radius:999px;box-shadow:0 10px 30px -18px rgba(11,36,71,.35);transition:box-shadow .2s,background .2s}
.nav.scrolled .wrap{background:rgba(255,255,255,.94);box-shadow:0 14px 34px -16px rgba(11,36,71,.4)}
.nav-logo img{height:34px;width:auto}
@media(max-width:480px){.nav-logo img{height:32px}}
.nav-links{display:flex;gap:30px;font-weight:600;font-size:.93rem;color:var(--ink-2)}
.nav-links a:hover{color:var(--blue)}
.nav-cta{display:flex;align-items:center;gap:10px}
.nav-login{font-weight:700;font-size:.93rem;color:var(--navy);padding:.5rem .8rem}
.lang-switch{display:inline-flex;background:var(--page);border:1px solid var(--line-2);border-radius:999px;padding:3px}
.lang-switch a{font-size:.74rem;font-weight:800;letter-spacing:.04em;color:var(--muted);padding:.32rem .62rem;border-radius:999px}
.lang-switch a.on{background:var(--navy);color:#fff}
.lang-switch a:not(.on):hover{color:var(--navy)}
.nav-toggle{display:none;background:none;border:0;font-size:1.6rem;color:var(--navy);cursor:pointer;padding:4px}
.nav-links .nav-mobile-only{display:none}
@media(max-width:960px){.nav-links .nav-mobile-only{display:block}}
@media(max-width:960px){
  .nav-links{display:none;position:absolute;top:74px;left:16px;right:16px;flex-direction:column;gap:0;background:#fff;border:1px solid var(--line-2);border-radius:20px;padding:8px 22px 14px;box-shadow:var(--shadow-lg)}
  .nav-links a{padding:12px 0;border-bottom:1px solid var(--line)}
  .nav.open .nav-links{display:flex}
  .nav-toggle{display:block}
  .nav-login{display:none}
}
@media(max-width:480px){.nav-cta .btn{display:none}.nav.open .nav-links::after{content:none}}

/* hero */
.hero{position:relative;padding:56px 0 40px;margin-top:-92px;padding-top:148px;overflow:hidden}
.hero::before{content:"";position:absolute;inset:-20% -10% auto -10%;height:120%;background:
  radial-gradient(40% 50% at 15% 20%,rgba(47,123,224,.16),transparent 70%),
  radial-gradient(35% 45% at 90% 10%,rgba(20,184,166,.18),transparent 70%),
  radial-gradient(40% 40% at 70% 80%,rgba(34,211,238,.10),transparent 70%);pointer-events:none}
.hero::after{content:"";position:absolute;inset:0;background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(60% 60% at 50% 30%,#000 20%,transparent 75%);-webkit-mask-image:radial-gradient(60% 60% at 50% 30%,#000 20%,transparent 75%);pointer-events:none;opacity:.6}
.hero .wrap{position:relative;z-index:1}
.hero-copy{text-align:center;max-width:920px;margin:0 auto}
.pill{display:inline-flex;align-items:center;gap:.55rem;background:#fff;border:1px solid var(--line-2);border-radius:999px;padding:.4rem .9rem .4rem .45rem;font-size:.85rem;font-weight:600;color:var(--navy);box-shadow:var(--shadow)}
.pill .tag{background:var(--grad);color:#fff;border-radius:999px;padding:.15rem .6rem;font-size:.75rem;font-weight:700}
.hero h1{font-size:clamp(2.1rem,4.6vw,3.55rem);margin:22px 0 18px}
.hero .lead{font-size:clamp(1.02rem,1.6vw,1.2rem);max-width:690px;margin:0 auto;color:var(--ink-2)}
.hero-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:30px}
.hero-checks{display:flex;gap:22px;justify-content:center;flex-wrap:wrap;margin-top:22px;font-size:.88rem;font-weight:600;color:var(--ink-2)}
.hero-checks i{color:var(--teal);margin-right:6px}

/* dashboard mockup */
.stage{position:relative;max-width:1080px;margin:56px auto 0}
.browser{background:#fff;border:1px solid var(--line-2);border-radius:20px;box-shadow:var(--shadow-lg);overflow:hidden}
.browser-bar{display:flex;align-items:center;gap:8px;padding:12px 16px;border-bottom:1px solid var(--line);background:#fafcff}
.browser-bar span{width:11px;height:11px;border-radius:50%;background:#e2e8f0}
.browser-bar span:nth-child(1){background:#ff6b6b}.browser-bar span:nth-child(2){background:#ffc145}.browser-bar span:nth-child(3){background:#2ecc71}
.browser-url{margin-left:14px;flex:1;max-width:340px;background:#eef2f8;border-radius:8px;font-size:.75rem;color:var(--muted);padding:5px 12px;display:flex;align-items:center;gap:6px}
.app{display:grid;grid-template-columns:200px 1fr;min-height:440px}
.app-side{background:var(--navy);padding:18px 14px;color:rgba(255,255,255,.7);font-size:.8rem}
.app-side .logo{display:flex;align-items:center;gap:8px;color:#fff;font-weight:800;letter-spacing:.08em;margin-bottom:22px;font-size:.95rem}
.app-side .logo img{width:28px;height:auto}
.app-side .item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;margin-bottom:2px}
.app-side .item.active{background:rgba(255,255,255,.12);color:#fff;font-weight:600}
.app-side .grp{font-size:.66rem;text-transform:uppercase;letter-spacing:.1em;opacity:.5;margin:16px 10px 6px}
.app-main{padding:20px 22px;background:var(--page);min-width:0}
.app-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.app-top h4{font-size:1.05rem}
.app-top small{color:var(--muted);font-size:.75rem}
.avatar{width:30px;height:30px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:.72rem;font-weight:700}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px}
.kpi small{display:block;color:var(--muted);font-size:.7rem;font-weight:600}
.kpi b{display:block;color:var(--ink);font-size:1.15rem;margin-top:2px}
.kpi .up{font-size:.68rem;color:var(--teal);font-weight:700}
.kpi .warn{font-size:.68rem;color:#d97706;font-weight:700}
.app-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:12px}
.panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px}
.panel h5{margin:0 0 10px;color:var(--ink);font-size:.82rem;font-weight:700;display:flex;justify-content:space-between}
.panel h5 span{color:var(--muted);font-weight:600}
.bars{display:flex;align-items:flex-end;gap:7px;height:205px;padding-top:6px}
.bars div{flex:1;border-radius:6px 6px 2px 2px;background:linear-gradient(180deg,#2f7be0,#1f5fbf);opacity:.9}
.bars div:nth-child(n+10){background:linear-gradient(180deg,#22d3ee,#14b8a6)}
.bars-x{display:flex;gap:7px;margin-top:6px}
.bars-x span{flex:1;text-align:center;font-size:.6rem;color:var(--muted)}
.mlist .row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed var(--line)}
.mlist .row:last-child{border-bottom:0}
.mlist .ic{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;font-size:.9rem;flex:none}
.mlist b{display:block;font-size:.76rem;color:var(--ink);font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mlist small{font-size:.66rem;color:var(--muted)}
.mlist .badge{margin-left:auto;font-size:.62rem;font-weight:700;padding:3px 8px;border-radius:999px;white-space:nowrap}
.b-red{background:#fee2e2;color:#b91c1c}.b-amber{background:#fef3c7;color:#92400e}.b-teal{background:#ccfbf1;color:#0f766e}.b-blue{background:#dbeafe;color:#1d4ed8}
.ic-red{background:#fee2e2;color:#dc2626}.ic-amber{background:#fef3c7;color:#d97706}.ic-teal{background:#ccfbf1;color:#0d9488}.ic-blue{background:#dbeafe;color:#2563eb}

.float{position:absolute;background:#fff;border:1px solid var(--line-2);border-radius:16px;box-shadow:var(--shadow-lg);padding:14px 16px;display:flex;gap:12px;align-items:flex-start;max-width:300px;animation:bob 6s ease-in-out infinite}
.float .ic{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;font-size:1.1rem;flex:none}
.float b{display:block;color:var(--ink);font-size:.85rem;line-height:1.3}
.float small{display:block;color:var(--ink-2);font-size:.76rem;line-height:1.4;margin-top:2px}
.float .time{color:var(--muted);font-size:.68rem;margin-top:4px}
.f1{left:-46px;bottom:-28px}
.f2{right:-40px;top:-30px;animation-delay:-3s}
@keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@media(max-width:1180px){.f1{left:-10px}.f2{right:-10px}}
@media(max-width:900px){.app{grid-template-columns:1fr}.app-side{display:none}.kpis{grid-template-columns:repeat(2,1fr)}.app-grid{grid-template-columns:1fr}.float{display:none}}

/* sections */
section{padding:96px 0}
.head{text-align:center;max-width:760px;margin:0 auto 56px}
.kicker{display:inline-block;font-size:.8rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--blue);margin-bottom:12px}
.head h2{font-size:clamp(1.8rem,3.6vw,2.7rem)}
.head p{margin-top:14px;font-size:1.06rem}
@media(max-width:640px){section{padding:72px 0}.head{margin-bottom:40px}}

/* audience */
.audience{padding:28px 0 8px}
.audience p{text-align:center;font-size:.85rem;font-weight:700;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
.aud-list{display:flex;flex-wrap:wrap;justify-content:center;gap:12px 34px;margin-top:18px}
.aud-list span{display:flex;align-items:center;gap:8px;font-weight:700;color:var(--navy-2);font-size:.98rem}
.aud-list i{color:var(--blue-2);font-size:1.15rem}

/* features */
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.feature{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:28px;transition:transform .2s,box-shadow .2s,border-color .2s}
.feature:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:rgba(31,95,191,.25)}
.feature .ic{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:1.35rem;color:#fff;background:var(--grad);margin-bottom:18px;box-shadow:0 10px 20px -10px rgba(31,95,191,.7)}
.feature h3{font-size:1.12rem;margin-bottom:8px}
.feature p{font-size:.94rem}
@media(max-width:960px){.features-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:620px){.features-grid{grid-template-columns:1fr}}

/* lifecycle */
.lifecycle{background:var(--navy-3);color:rgba(255,255,255,.72);position:relative;overflow:hidden}
.lifecycle::before{content:"";position:absolute;inset:0;background:radial-gradient(50% 60% at 10% 0%,rgba(31,95,191,.45),transparent 70%),radial-gradient(40% 50% at 100% 100%,rgba(20,184,166,.35),transparent 70%)}
.lifecycle .wrap{position:relative}
.lifecycle .head h2{color:#fff}.lifecycle .kicker{color:var(--cyan)}
.steps{display:grid;grid-template-columns:repeat(var(--cols,6),1fr);gap:16px;position:relative;counter-reset:s}
.steps::before{content:"";position:absolute;top:31px;left:8%;right:8%;height:2px;background:linear-gradient(90deg,rgba(47,123,224,.0),rgba(47,123,224,.8),rgba(20,184,166,.8),rgba(20,184,166,0))}
.step{text-align:center;position:relative}
.step .dot{width:64px;height:64px;border-radius:20px;margin:0 auto 18px;display:grid;place-items:center;font-size:1.5rem;color:#fff;background:linear-gradient(145deg,#163f86,#1f5fbf);border:1px solid rgba(255,255,255,.18);box-shadow:0 0 0 6px var(--navy-3),0 16px 30px -12px rgba(34,211,238,.5)}
.step:nth-child(n+4) .dot{background:linear-gradient(145deg,#0f766e,#14b8a6)}
.step .no{font-size:.72rem;font-weight:800;letter-spacing:.12em;color:var(--cyan)}
.step h3{color:#fff;font-size:1.02rem;margin:4px 0 6px}
.step p{font-size:.85rem;line-height:1.5}
@media(max-width:960px){.steps{grid-template-columns:repeat(3,1fr);row-gap:36px}.steps::before{display:none}}
@media(max-width:560px){.steps{grid-template-columns:1fr 1fr}}

/* request flow */
.split{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.split h2{font-size:clamp(1.8rem,3.4vw,2.5rem);margin-bottom:16px}
.split .lead{font-size:1.05rem}
.flow-list{list-style:none;padding:0;margin:28px 0 0;display:grid;gap:18px}
.flow-list li{display:flex;gap:16px}
.flow-list .n{width:36px;height:36px;border-radius:12px;flex:none;display:grid;place-items:center;font-weight:800;color:var(--blue);background:#e8f0fd}
.flow-list b{display:block;color:var(--ink);font-size:1rem}
.flow-list span{font-size:.92rem}
.ticket{background:#fff;border:1px solid var(--line-2);border-radius:22px;box-shadow:var(--shadow-lg);padding:24px}
.ticket-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.ticket-head b{color:var(--ink);display:block;font-size:1.02rem}
.ticket-head small{color:var(--muted);font-size:.8rem}
.status{font-size:.75rem;font-weight:800;padding:5px 11px;border-radius:999px;white-space:nowrap}
.timeline{list-style:none;margin:18px 0 0;padding:0;position:relative}
.timeline::before{content:"";position:absolute;left:15px;top:6px;bottom:6px;width:2px;background:var(--line-2)}
.timeline li{display:flex;gap:14px;padding:8px 0;position:relative}
.timeline .t-ic{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-size:.85rem;flex:none;position:relative;z-index:1;border:3px solid #fff}
.timeline b{display:block;color:var(--ink);font-size:.9rem}
.timeline small{color:var(--muted);font-size:.78rem}
.timeline .photos{display:flex;gap:6px;margin-top:8px}
.timeline .photos span{width:46px;height:34px;border-radius:8px;background:linear-gradient(135deg,#dbeafe,#ccfbf1);display:grid;place-items:center;color:var(--blue);font-size:.85rem}
@media(max-width:900px){.split{grid-template-columns:1fr;gap:40px}}

/* roles */
.roles{background:#fff;border-block:1px solid var(--line)}
.roles-grid{display:grid;grid-template-columns:repeat(var(--cols,3),1fr);gap:20px}
.role{border:1px solid var(--line);border-radius:var(--r);padding:30px;background:var(--page);position:relative;overflow:hidden}
.role::after{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:var(--grad)}
.role .ic{width:52px;height:52px;border-radius:16px;display:grid;place-items:center;font-size:1.45rem;background:#fff;border:1px solid var(--line-2);color:var(--blue);margin-bottom:18px}
.role h3{font-size:1.25rem}
.role .sub{font-size:.88rem;color:var(--muted);font-weight:600;margin:4px 0 16px}
.role ul{list-style:none;padding:0;margin:0;display:grid;gap:10px;font-size:.93rem}
.role li{display:flex;gap:10px}
.role li i{color:var(--teal);margin-top:2px}
.roles-note{text-align:center;margin-top:28px;font-size:.95rem}
.roles-note i{color:var(--blue)}
@media(max-width:900px){.roles-grid{grid-template-columns:1fr}}

/* mobile */
.mobile-card{background:var(--grad);border-radius:28px;padding:56px;display:grid;grid-template-columns:1.2fr .8fr;gap:40px;align-items:center;color:rgba(255,255,255,.85);overflow:hidden;position:relative}
.mobile-card h2{color:#fff;font-size:clamp(1.7rem,3.2vw,2.4rem);margin:14px 0}
.soon{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:.3rem .8rem;font-size:.8rem;font-weight:700;color:#fff}
.mobile-feats{display:grid;grid-template-columns:1fr 1fr;gap:12px 20px;margin-top:24px;font-size:.93rem}
.mobile-feats span{display:flex;gap:8px;align-items:center}
.mobile-feats i{color:var(--cyan)}
.phone{width:240px;margin:0 auto;background:#0b1220;border-radius:36px;padding:10px;box-shadow:0 40px 80px -30px rgba(0,0,0,.6);transform:rotate(-4deg)}
.phone-screen{background:var(--page);color:var(--ink-2);border-radius:28px;overflow:hidden;min-height:440px}
.phone-top{background:var(--navy);color:#fff;padding:26px 16px 18px}
.phone-top small{opacity:.7;font-size:.7rem}
.phone-top b{display:block;font-size:1rem}
.phone-scan{margin:-12px 14px 0;background:#fff;border-radius:14px;padding:12px;display:flex;gap:10px;align-items:center;box-shadow:var(--shadow);font-size:.75rem;color:var(--ink)}
.phone-scan .q{width:38px;height:38px;border-radius:10px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:1.2rem}
.phone-list{padding:14px}
.phone-item{background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;margin-bottom:8px;font-size:.72rem}
.phone-item b{display:block;color:var(--ink);font-size:.78rem}
@media(max-width:900px){.mobile-card{grid-template-columns:1fr;padding:40px 28px}.phone{display:none}}
@media(max-width:480px){.mobile-feats{grid-template-columns:1fr}}

/* pricing */
.trial-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;max-width:980px;margin:0 auto 40px}
.trial-step{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px}
.trial-step .n{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;flex:none;background:var(--grad);color:#fff;font-weight:800;font-size:.9rem}
.trial-step b{display:block;color:var(--ink);font-size:.98rem}
.trial-step span{font-size:.86rem}
@media(max-width:800px){.trial-steps{grid-template-columns:1fr}}
.trial-card{display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap;background:#fff;border:2px solid transparent;background-image:linear-gradient(#fff,#fff),var(--grad);background-origin:border-box;background-clip:padding-box,border-box;border-radius:22px;padding:28px 32px;margin-bottom:28px;box-shadow:var(--shadow)}
.trial-card h3{font-size:1.4rem}
.trial-card .meta{display:flex;flex-wrap:wrap;gap:8px 20px;margin-top:10px;font-size:.92rem}
.trial-card .meta i{color:var(--teal);margin-right:6px}
.toggle{display:flex;justify-content:center;margin-bottom:28px}
.toggle-inner{display:inline-flex;background:#fff;border:1px solid var(--line-2);border-radius:999px;padding:4px}
.toggle button{border:0;background:none;font:inherit;font-weight:700;font-size:.9rem;color:var(--ink-2);padding:.55rem 1.2rem;border-radius:999px;cursor:pointer}
.toggle button.active{background:var(--navy);color:#fff}
.toggle .save{font-size:.72rem;background:#ccfbf1;color:#0f766e;border-radius:999px;padding:2px 7px;margin-left:6px}
.toggle button.active .save{background:rgba(255,255,255,.18);color:#fff}
.plans{display:grid;grid-template-columns:repeat(var(--cols,3),1fr);gap:20px;align-items:stretch}
.plan{background:#fff;border:1px solid var(--line);border-radius:22px;padding:30px;display:flex;flex-direction:column;position:relative}
.plan.rec{border-color:transparent;background-image:linear-gradient(#fff,#fff),var(--grad);background-origin:border-box;background-clip:padding-box,border-box;border:2px solid transparent;box-shadow:var(--shadow-lg);transform:translateY(-8px)}
.plan .ribbon{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--grad);color:#fff;font-size:.74rem;font-weight:800;padding:5px 14px;border-radius:999px;letter-spacing:.04em;white-space:nowrap}
.plan h3{font-size:1.25rem}
.plan .desc{font-size:.9rem;margin:6px 0 18px;min-height:2.9em}
.price{display:flex;align-items:baseline;gap:6px;color:var(--ink)}
.price b{font-size:2.2rem;font-weight:800;letter-spacing:-.03em}
.price span{font-size:.9rem;color:var(--muted);font-weight:600}
.price-note{font-size:.8rem;color:var(--teal);font-weight:700;min-height:1.3em;margin-top:2px}
.plan ul{list-style:none;padding:0;margin:22px 0 26px;display:grid;gap:11px;font-size:.92rem;flex:1}
.plan li{display:flex;gap:10px}
.plan li i{color:var(--teal);margin-top:1px}
.plan li.hl{color:var(--ink);font-weight:700}
.plan .btn{width:100%}
.pricing-foot{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:36px}
.pricing-foot div{display:flex;gap:12px;font-size:.9rem;background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px}
.pricing-foot i{font-size:1.3rem;color:var(--blue)}
.pricing-foot b{display:block;color:var(--ink)}
@media(max-width:980px){.plans{grid-template-columns:1fr;max-width:480px;margin:0 auto}.plan.rec{transform:none}.pricing-foot{grid-template-columns:1fr}}

/* faq */
.faq{max-width:820px;margin:0 auto;display:grid;gap:12px}
.faq details{background:#fff;border:1px solid var(--line);border-radius:16px;padding:0 22px;transition:border-color .2s}
.faq details[open]{border-color:rgba(31,95,191,.3);box-shadow:var(--shadow)}
.faq summary{list-style:none;cursor:pointer;padding:20px 0;font-weight:700;color:var(--ink);display:flex;justify-content:space-between;gap:16px;align-items:center}
.faq summary::-webkit-details-marker{display:none}
.faq summary i{transition:transform .2s;color:var(--blue)}
.faq details[open] summary i{transform:rotate(45deg)}
.faq details p{padding:0 0 20px;font-size:.95rem}

/* final cta */
.final{padding:24px 0 96px}
.final-card{background:var(--navy-3);border-radius:28px;padding:64px 40px;text-align:center;position:relative;overflow:hidden}
.final-card::before{content:"";position:absolute;inset:0;background:radial-gradient(45% 70% at 0% 0%,rgba(31,95,191,.55),transparent 70%),radial-gradient(40% 70% at 100% 100%,rgba(20,184,166,.45),transparent 70%)}
.final-card > *{position:relative}
.final-card h2{color:#fff;font-size:clamp(1.8rem,3.6vw,2.7rem);max-width:720px;margin:0 auto 14px}
.final-card p{color:rgba(255,255,255,.75);font-size:1.05rem;max-width:620px;margin:0 auto 30px}
.final-card .acts{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

/* footer */
footer{background:#fff;border-top:1px solid var(--line);padding:56px 0 28px;font-size:.92rem}
.foot{display:grid;grid-template-columns:1.5fr .8fr .8fr 1.3fr;gap:40px}
.foot-contact li{display:flex;align-items:flex-start;gap:10px}
.foot-contact li i{color:var(--ink);font-size:1rem;line-height:1.35;flex:none}
.foot-contact a{transition:color .15s}
.foot-contact .addr{text-decoration:underline;text-decoration-color:var(--line);text-underline-offset:3px;line-height:1.55}
@media(max-width:980px){.foot{grid-template-columns:1fr 1fr}.foot > div:first-child{grid-column:1/-1}}
.foot img{height:40px;width:auto;margin-bottom:14px}
.foot h4{font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:14px}
.foot ul{list-style:none;padding:0;margin:0;display:grid;gap:9px}
.foot a:hover{color:var(--blue)}
.copy{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:40px;padding-top:22px;border-top:1px solid var(--line);font-size:.85rem;color:var(--muted)}
@media(max-width:760px){.foot{grid-template-columns:1fr 1fr}.foot > div:first-child{grid-column:1/-1}}

/* shared: tab pill */
.tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-bottom:34px}
.tab{border:1px solid var(--line-2);background:#fff;color:var(--ink);font:inherit;font-weight:700;font-size:.93rem;padding:.65rem 1.25rem;border-radius:999px;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:8px}
.tab:hover{border-color:var(--blue)}
.tab.active{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 10px 22px -10px rgba(31,95,191,.7)}
.underline{position:relative;display:inline-block}
.underline::after{content:"";position:absolute;left:4%;right:4%;bottom:-6px;height:4px;border-radius:4px;background:var(--grad-text);opacity:.85}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.06em}

/* before / after */
.compare-wrap{background:linear-gradient(180deg,#fff 0%,var(--page) 100%)}
.compare{position:relative;height:540px;border-radius:24px;overflow:hidden;border:1px solid var(--line-2);box-shadow:var(--shadow-lg);background:#fff;--pos:50%;user-select:none}
.cmp-layer{position:absolute;inset:0}
.cmp-after{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr}
.cmp-cell{padding:24px 26px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);overflow:hidden}
.cmp-cell:nth-child(2n){border-right:0}.cmp-cell:nth-child(n+3){border-bottom:0}
.cmp-label{display:flex;align-items:center;gap:10px;font-size:.78rem;font-weight:800;color:var(--ink);margin-bottom:8px}
.cmp-label i{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#e8f0fd;color:var(--blue);font-size:.95rem}
.cmp-cell p{font-size:.88rem;margin-bottom:14px}
.mini-table{border:1px solid var(--line);border-radius:12px;overflow:hidden;font-size:.72rem}
.mini-table div{display:grid;grid-template-columns:.9fr 1.6fr 1fr;gap:8px;padding:7px 12px;border-bottom:1px solid var(--line);align-items:center}
.mini-table div:first-child{background:#f6f9fd;font-weight:800;color:var(--muted);font-size:.64rem;letter-spacing:.06em}
.mini-table div:last-child{border-bottom:0}
.mini-table b{color:var(--ink);font-weight:700}
.chip{display:inline-block;font-size:.62rem;font-weight:800;padding:2px 8px;border-radius:999px}
.mini-kanban{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.mini-kanban > div{background:#f6f9fd;border:1px solid var(--line);border-radius:12px;padding:8px}
.mini-kanban small{display:block;font-size:.6rem;font-weight:800;color:var(--muted);margin-bottom:6px}
.mini-kanban span{display:block;background:#fff;border:1px solid var(--line);border-radius:8px;padding:6px 8px;font-size:.66rem;color:var(--ink);font-weight:600;margin-bottom:5px}
.mini-kanban span em{display:block;font-style:normal;color:var(--muted);font-weight:500;font-size:.6rem}
.mini-tl{list-style:none;padding:0;margin:0;font-size:.74rem}
.mini-tl li{display:flex;gap:10px;align-items:flex-start;padding:6px 0;border-bottom:1px dashed var(--line)}
.mini-tl li:last-child{border-bottom:0}
.mini-tl i{color:var(--teal)}
.mini-tl b{color:var(--ink)}
.cmp-before{clip-path:inset(0 0 0 var(--pos));background-color:#fbfbfc;background-image:linear-gradient(rgba(15,27,45,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(15,27,45,.05) 1px,transparent 1px);background-size:32px 32px}
.note{position:absolute;background:#fff;border:1px solid var(--line-2);border-radius:10px;padding:10px 14px;box-shadow:0 10px 24px -14px rgba(15,27,45,.35);display:flex;gap:10px;align-items:flex-start;max-width:250px;font-size:.78rem}
.note i{font-size:1rem;color:var(--muted);margin-top:1px}
.note b{display:block;color:var(--ink);font-size:.8rem}
.note small{color:var(--muted);font-size:.7rem;font-style:italic}
.note .dot-red{position:absolute;top:-8px;right:-8px;min-width:20px;height:20px;padding:0 5px;border-radius:999px;background:#ef4444;color:#fff;font-size:.65rem;font-weight:800;display:grid;place-items:center}
.note.yellow{background:#fff8d6;border-color:#f3e3a1;transform:rotate(-3deg)}
.chaos-center{position:absolute;left:73%;top:50%;transform:translate(-50%,-50%);background:#fff;border:1px solid #fecaca;border-radius:14px;padding:22px 26px;text-align:center;width:300px;box-shadow:0 20px 40px -20px rgba(239,68,68,.35)}
.chaos-center .q{width:38px;height:38px;border-radius:50%;background:#fee2e2;color:#ef4444;display:grid;place-items:center;margin:0 auto 10px;font-weight:800}
.chaos-center b{display:block;color:var(--ink);font-size:1rem}
.chaos-center small{color:var(--ink-2);font-size:.82rem}
.dash{position:absolute;border-top:2px dashed rgba(15,27,45,.12)}
.cmp-handle{position:absolute;top:0;bottom:0;left:var(--pos);width:3px;margin-left:-1.5px;background:var(--blue-2);pointer-events:none;z-index:3}
.cmp-handle::after{content:"\2039 \203A";position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:44px;height:44px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-weight:800;font-size:1.1rem;letter-spacing:4px;box-shadow:0 8px 20px -6px rgba(31,95,191,.8)}
.cmp-range{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:ew-resize;z-index:4;margin:0}
.cmp-tag{position:absolute;bottom:16px;z-index:2;font-size:.72rem;font-weight:800;padding:5px 12px;border-radius:999px;letter-spacing:.04em}
.cmp-tag.l{left:16px;background:var(--grad);color:#fff}
.cmp-tag.r{right:16px;background:#fee2e2;color:#b91c1c}
.compare-foot{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;max-width:820px;margin:28px auto 0;background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px 24px;box-shadow:var(--shadow)}
.compare-foot b{color:var(--ink);display:block}
.compare-foot span{font-size:.9rem}
@media(max-width:900px){.compare{height:auto}.cmp-after{position:relative;grid-template-columns:1fr;grid-template-rows:none}.cmp-cell{border-right:0!important;border-bottom:1px solid var(--line)!important}.cmp-before,.cmp-handle,.cmp-range,.cmp-tag.r{display:none}.compare-switch{display:none}}

/* product tour */
.tour-frame{background:#fff;border:1px solid var(--line-2);border-radius:20px;box-shadow:var(--shadow-lg);overflow:hidden;max-width:1100px;margin:0 auto}
.tour-bar{display:flex;align-items:center;gap:14px;padding:12px 18px;border-bottom:1px solid var(--line);background:#fafcff}
.tour-bar .dots{display:flex;gap:7px}.tour-bar .dots span{width:11px;height:11px;border-radius:50%;background:#e2e8f0}
.tour-url{flex:1;max-width:460px;margin:0 auto;background:#eef2f8;border-radius:8px;font-size:.76rem;color:var(--muted);padding:6px 12px;text-align:center}
.tour-body{display:grid;grid-template-columns:64px 1fr;min-height:500px}
.tour-rail{background:var(--navy);display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px 0}
.tour-rail img{width:30px;margin-bottom:12px}
.tour-rail span{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;color:rgba(255,255,255,.55);font-size:1.05rem}
.tour-rail span.on{background:rgba(255,255,255,.14);color:#fff}
.tour-main{padding:22px 26px;background:var(--page);min-width:0}
.tour-panel{display:none;animation:fadeIn .35s ease}
.tour-panel.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.tp-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.tp-head h4{font-size:1.1rem}
.tp-head small{display:block;color:var(--muted);font-size:.78rem;font-weight:500}
.tp-actions{display:flex;gap:8px;flex-wrap:wrap}
.tp-btn{font-size:.74rem;font-weight:700;padding:7px 12px;border-radius:9px;border:1px solid var(--line-2);background:#fff;color:var(--ink);display:inline-flex;gap:6px;align-items:center}
.tp-btn.pri{background:var(--grad);color:#fff;border-color:transparent}
.tp-card{background:#fff;border:1px solid var(--line);border-radius:14px}
.tp-filter{display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--line);font-size:.74rem}
.tp-search{flex:1;min-width:160px;background:var(--page);border-radius:9px;padding:7px 12px;color:var(--muted)}
.tp-filter .chip{padding:6px 10px;font-size:.7rem;background:#f1f5fb;color:var(--ink-2);border-radius:9px}
.tp-table{width:100%;border-collapse:collapse;font-size:.78rem}
.tp-table th{text-align:left;font-size:.64rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:800;padding:10px 14px;border-bottom:1px solid var(--line);background:#fafcff}
.tp-table td{padding:10px 14px;border-bottom:1px solid var(--line);color:var(--ink-2);white-space:nowrap}
.tp-table tr:last-child td{border-bottom:0}
.tp-table td b{color:var(--ink);font-weight:700}
.ava{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;font-size:.6rem;font-weight:800;color:#fff;margin-right:6px;vertical-align:middle}
.tp-scroll{overflow-x:auto}
.tp-detail{display:grid;grid-template-columns:1.35fr 1fr;gap:14px}
.tp-photo{height:170px;border-radius:12px;background:linear-gradient(135deg,#dbeafe,#ccfbf1);display:grid;place-items:center;color:var(--blue);font-size:3.4rem;margin-bottom:14px}
.tp-info{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.tp-info div{background:var(--page);border-radius:10px;padding:9px 11px}
.tp-info small{display:block;font-size:.64rem;color:var(--muted);font-weight:700}
.tp-info b{color:var(--ink);font-size:.82rem}
.qr-card{text-align:center;padding:18px}
.qr-card svg{width:132px;height:132px;margin:0 auto 10px;display:block}
.qr-card b{color:var(--ink);display:block}
.holder{display:flex;align-items:center;gap:10px;padding:12px 16px;border-top:1px solid var(--line);font-size:.8rem;text-align:left}
.tp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.tp-stats div{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px 14px}
.tp-stats small{display:block;font-size:.68rem;font-weight:700;color:var(--muted)}
.tp-stats b{font-size:1.35rem;color:var(--ink)}
.tp-kanban{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.tp-col{background:#eef3fa;border-radius:14px;padding:12px}
.tp-col > small{display:flex;justify-content:space-between;font-size:.72rem;font-weight:800;color:var(--ink);margin-bottom:10px}
.tp-col > small span{background:#fff;border-radius:999px;padding:0 8px;color:var(--muted)}
.tk{background:#fff;border:1px solid var(--line);border-radius:12px;padding:11px 12px;margin-bottom:9px;font-size:.74rem}
.tk b{display:block;color:var(--ink);font-size:.8rem}
.tk .meta{display:flex;justify-content:space-between;align-items:center;margin-top:8px;color:var(--muted);font-size:.68rem}
.tk .take{font-size:.66rem;font-weight:800;color:#fff;background:var(--grad);border-radius:7px;padding:3px 9px}
.cal{display:grid;grid-template-columns:repeat(7,1fr);border:1px solid var(--line);border-radius:14px;overflow:hidden;background:#fff}
.cal div{min-height:68px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:6px;font-size:.66rem;color:var(--muted)}
.cal div:nth-child(7n){border-right:0}
.cal .dow{min-height:auto;background:#fafcff;font-weight:800;text-align:center;letter-spacing:.06em;padding:8px 4px}
.cal .today{background:#f0f6ff}
.cal .today > span:first-child{background:var(--blue);color:#fff;border-radius:6px;padding:1px 6px}
.ev{display:block;margin-top:4px;border-radius:5px;padding:2px 6px;font-weight:700;font-size:.6rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ev.m{background:#dbeafe;color:#1d4ed8}.ev.r{background:#fef3c7;color:#92400e}.ev.t{background:#ccfbf1;color:#0f766e}.ev.x{background:#fee2e2;color:#b91c1c}
@media(max-width:900px){.tour-body{grid-template-columns:1fr}.tour-rail{display:none}.tp-detail{grid-template-columns:1fr}.tp-stats{grid-template-columns:repeat(2,1fr)}.tp-kanban{grid-template-columns:1fr}.tp-info{grid-template-columns:repeat(2,1fr)}.cal div{min-height:48px}.ev{font-size:0;padding:0;height:6px}}

/* solutions */
.sol-card{max-width:1000px;margin:0 auto;background:#fff;border:1px solid var(--line-2);border-radius:26px;box-shadow:var(--shadow-lg);padding:44px;display:grid;grid-template-columns:1.15fr .85fr;gap:36px;align-items:center}
.sol-panel{display:none}.sol-panel.active{display:contents}
.sol-panel h3{font-size:1.75rem;margin-bottom:12px}
.sol-panel .txt p{font-size:1rem;margin-bottom:20px}
.sol-panel .txt ul{list-style:none;padding:0;margin:0;display:grid;gap:12px}
.sol-panel .txt li{display:flex;gap:10px;color:var(--ink);font-weight:600}
.sol-panel .txt li i{color:var(--blue);font-size:1.15rem}
.sol-side{background:var(--page);border:1px solid var(--line);border-radius:20px;padding:10px 22px 22px}
.sol-side .row{display:flex;gap:12px;align-items:center;padding:13px 0;border-bottom:1px solid var(--line);font-size:.9rem;color:var(--ink);font-weight:600}
.sol-side .row i{color:var(--blue);font-size:1.05rem}
.sol-side .btn{width:100%;margin-top:18px;position:relative}
.sol-side .free{position:absolute;top:-12px;right:-10px;background:var(--teal);color:#fff;font-size:.66rem;font-weight:800;padding:5px 9px;border-radius:999px;transform:rotate(8deg);box-shadow:0 6px 14px -6px rgba(20,184,166,.8)}
@media(max-width:860px){.sol-card{grid-template-columns:1fr;padding:28px}}

/* gambar dari CMS */
.cms-img{display:block;width:100%;height:auto;border-radius:20px;border:1px solid var(--line-2);box-shadow:var(--shadow-lg);background:#fff}

/* ===== animasi ringan (pengganti GIF/video) ===== */
.stage.in .bars div{animation:grow 1.1s cubic-bezier(.2,.8,.2,1) both}
@keyframes grow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
.bars div{transform-origin:bottom}
.float .live-msg{transition:opacity .35s ease,transform .35s ease}
.float .live-msg.out{opacity:0;transform:translateY(6px)}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;margin-right:6px;box-shadow:0 0 0 0 rgba(34,197,94,.6);animation:ping 1.8s infinite}
@keyframes ping{0%{box-shadow:0 0 0 0 rgba(34,197,94,.55)}80%,100%{box-shadow:0 0 0 8px rgba(34,197,94,0)}}
.timeline.play li{animation:stepIn .5s ease both}
.timeline.play li:nth-child(2){animation-delay:.9s}
.timeline.play li:nth-child(3){animation-delay:1.8s}
.timeline.play li:nth-child(4){animation-delay:2.7s}
@keyframes stepIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:none}}
.ticket .status{transition:background .3s,color .3s}
.phone-scan{position:relative;overflow:hidden}
.phone-scan::after{content:"";position:absolute;left:0;right:0;height:2px;top:0;background:linear-gradient(90deg,transparent,#22d3ee,transparent);animation:scan 2.4s ease-in-out infinite}
@keyframes scan{0%,100%{top:8%}50%{top:88%}}
.tab .bar{display:none}
.tabs.autoplay .tab.active{position:relative;overflow:hidden}
.tabs.autoplay .tab.active .bar{display:block;position:absolute;left:0;bottom:0;height:3px;background:rgba(255,255,255,.75);animation:fill var(--dur,6s) linear forwards}
@keyframes fill{from{width:0}to{width:100%}}
.cmp-hint{position:absolute;left:50%;bottom:64px;transform:translateX(-50%);z-index:5;background:rgba(11,36,71,.85);color:#fff;font-size:.75rem;font-weight:700;padding:6px 12px;border-radius:999px;pointer-events:none;transition:opacity .4s}
.cmp-hint.hide{opacity:0}
@media(prefers-reduced-motion:reduce){.stage.in .bars div,.timeline.play li,.phone-scan::after,.live-dot,.tabs.autoplay .tab.active .bar{animation:none}}

/* reveal */
.reveal{opacity:0;transform:translateY(18px);transition:opacity .7s ease,transform .7s ease}
.reveal.in{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.reveal{opacity:1;transform:none;transition:none}.float{animation:none}.btn,.feature{transition:none}}
</style>
@include('analytics._ga4')
</head>
<body>

{{-- ================= NAV ================= --}}
<nav class="nav" id="nav">
  <div class="wrap">
    <a href="{{ $homeUrl }}" class="nav-logo" aria-label="Vasetra">
      <img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra — Asset Lifecycle Platform" width="195" height="40">
    </a>
    <div class="nav-links" id="navLinks">
      <a href="#fitur">{{ __('Fitur') }}</a>
      <a href="#tur">{{ __('Produk') }}</a>
      <a href="#alur">{{ __('Alur Kerja') }}</a>
      <a href="#solusi">{{ __('Solusi') }}</a>
      <a href="#harga">{{ __('Harga') }}</a>
      @guest
        <a class="nav-mobile-only" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> {{ __('Masuk') }}</a>
        <a class="nav-mobile-only" href="{{ route('register') }}" style="color:var(--blue);font-weight:800">{{ __('Coba gratis :days hari →', ['days' => $trialDays]) }}</a>
      @endguest
    </div>
    <div class="nav-cta">
      <div class="lang-switch" role="group" aria-label="{{ __('Pilih bahasa') }}">
        <a href="{{ route('landing') }}" hreflang="id" lang="id" class="{{ $isEn ? '' : 'on' }}" data-lang-link>ID</a>
        <a href="{{ route('landing.en') }}" hreflang="en" lang="en" class="{{ $isEn ? 'on' : '' }}" data-lang-link>EN</a>
      </div>
      @auth
        <a class="btn btn-primary btn-sm" href="{{ url('/dashboard/home') }}">{{ __('Buka Dashboard') }} <i class="bi bi-arrow-right"></i></a>
      @else
        <a class="nav-login" href="{{ route('login') }}">{{ __('Masuk') }}</a>
        <a class="btn btn-primary btn-sm" href="{{ route('register') }}">{{ __('Coba Gratis') }}</a>
      @endauth
      <button class="nav-toggle" id="navToggle" aria-label="{{ __('Buka menu') }}" aria-expanded="false"><i class="bi bi-list"></i></button>
    </div>
  </div>
</nav>

{{-- ================= HERO ================= --}}
<header class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <span class="pill"><span class="tag">{{ __('Gratis :days hari', ['days' => $trialDays]) }}</span> {{ $c['hero']['badge'] }}</span>
      <h1>{{ $c['hero']['title'] }}@if($c['hero']['highlight'])<br><span class="grad-text">{{ $c['hero']['highlight'] }}</span>@endif</h1>
      <p class="lead">{{ $c['hero']['lead'] }}</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="{{ $ctaUrl }}">{{ $ctaLabel }} <i class="bi bi-arrow-right"></i></a>
        @if($c['hero']['secondary_cta'])<a class="btn btn-ghost btn-lg" href="#alur"><i class="bi bi-diagram-3"></i> {{ $c['hero']['secondary_cta'] }}</a>@endif
      </div>
      <div class="hero-checks">
        @foreach($c['hero']['checks'] as $check)<span><i class="bi bi-check-circle-fill"></i>{{ $check }}</span>@endforeach
      </div>
    </div>

    @if($img($c['hero']['image']))
      {{-- Gambar / GIF dari CMS --}}
      <div class="stage reveal">
        <img src="{{ $img($c['hero']['image']) }}" alt="{{ $c['hero']['title'] }} {{ $c['hero']['highlight'] }}" class="cms-img" loading="eager">
      </div>
    @else
    {{-- Ilustrasi dashboard (data contoh) --}}
    <div class="stage reveal" aria-hidden="true">
      <div class="browser">
        <div class="browser-bar"><span></span><span></span><span></span><div class="browser-url"><i class="bi bi-lock-fill"></i> app.vasetra.com/dashboard</div></div>
        <div class="app">
          <aside class="app-side">
            <div class="logo"><img src="{{ asset('assets/images/brand/vasetra-mark.png') }}" alt="" width="28" height="20">VASETRA</div>
            <div class="item active"><i class="bi bi-grid-1x2-fill"></i> {{ __('Beranda') }}</div>
            <div class="item"><i class="bi bi-box-seam"></i> {{ __('Aset') }}</div>
            <div class="item"><i class="bi bi-qr-code"></i> {{ __('Cetak QR') }}</div>
            <div class="grp">Maintenance</div>
            <div class="item"><i class="bi bi-tools"></i> {{ __('Jadwal') }}</div>
            <div class="item"><i class="bi bi-inboxes"></i> {{ __('Request') }}</div>
            <div class="item"><i class="bi bi-calendar3"></i> {{ __('Kalender') }}</div>
            <div class="grp">{{ __('Pengaturan') }}</div>
            <div class="item"><i class="bi bi-people"></i> {{ __('User & Role') }}</div>
            <div class="item"><i class="bi bi-shield-check"></i> Audit Trail</div>
          </aside>
          <div class="app-main">
            <div class="app-top">
              <div><h4>{{ __('Selamat pagi, Rina 👋') }}</h4><small>{{ __('Ringkasan aset PT Contoh Sejahtera') }}</small></div>
              <div class="avatar">RS</div>
            </div>
            <div class="kpis">
              <div class="kpi"><small>{{ __('Total Aset') }}</small><b data-count="1248">{{ $num(1248) }}</b><span class="up"><i class="bi bi-arrow-up-short"></i>{{ __('32 bulan ini') }}</span></div>
              <div class="kpi"><small>{{ __('Nilai Aset') }}</small><b>{{ __('Rp4,82 M') }}</b><span class="up">{{ __('nilai buku') }}</span></div>
              <div class="kpi"><small>{{ __('Maintenance Minggu Ini') }}</small><b data-count="12">12</b><span class="warn">{{ __('3 terlambat') }}</span></div>
              <div class="kpi"><small>{{ __('Request Aktif') }}</small><b data-count="5">5</b><span class="up">{{ __('2 menunggu diambil') }}</span></div>
            </div>
            <div class="app-grid">
              <div class="panel">
                <h5>{{ __('Pertumbuhan aset') }} <span>{{ __('12 bulan') }}</span></h5>
                <div class="bars">
                  @foreach([38,44,41,52,57,55,63,68,66,74,82,90] as $h)<div style="height:{{ $h }}%"></div>@endforeach
                </div>
                <div class="bars-x">@foreach([__('Des'),'Jan','Feb','Mar','Apr',__('Mei'),'Jun','Jul',__('Agu'),'Sep',__('Okt'),'Nov'] as $m)<span>{{ $m }}</span>@endforeach</div>
              </div>
              <div class="panel mlist">
                <h5>{{ __('Maintenance terdekat') }} <span>{{ __('Lihat semua') }}</span></h5>
                <div class="row"><span class="ic ic-red"><i class="bi bi-snow"></i></span><div style="min-width:0"><b>{{ __('AC Split Ruang Rapat') }}</b><small>{{ __('Servis berkala') }}</small></div><span class="badge b-red">{{ __('Terlambat') }}</span></div>
                <div class="row"><span class="ic ic-amber"><i class="bi bi-truck"></i></span><div style="min-width:0"><b>{{ __('Mobil Operasional B 1234') }}</b><small>{{ __('Ganti oli') }}</small></div><span class="badge b-amber">{{ __('Hari ini') }}</span></div>
                <div class="row"><span class="ic ic-blue"><i class="bi bi-printer"></i></span><div style="min-width:0"><b>Printer Finance</b><small>{{ __('Inspeksi') }}</small></div><span class="badge b-blue">{{ __('Kamis') }}</span></div>
                <div class="row"><span class="ic ic-teal"><i class="bi bi-laptop"></i></span><div style="min-width:0"><b>Laptop Dell Latitude</b><small>{{ __('Kalibrasi baterai') }}</small></div><span class="badge b-teal">{{ __('Minggu ini') }}</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="float f1" id="liveFeed">
        <span class="ic ic-teal live-msg" data-live-icon><i class="bi bi-person-check-fill"></i></span>
        <div class="live-msg" data-live-body><b>{{ __('Request perbaikan diambil') }}</b><small>{{ __('Laptop Dell Latitude sedang dikerjakan oleh') }} <strong>{{ __('Budi (Teknisi)') }}</strong></small><div class="time"><span class="live-dot"></span>{{ __('baru saja') }}</div></div>
      </div>
      <div class="float f2">
        <span class="ic ic-blue"><i class="bi bi-qr-code-scan"></i></span>
        <div><b>{{ __('AST-000124 · Proyektor Epson') }}</b><small>{{ __('Dipegang: Sari · Ruang Training') }}<br>{{ __('Maintenance berikutnya: 12 Des') }}</small></div>
      </div>
    </div>
    @endif
  </div>
</header>


{{-- ================= SEBELUM / SESUDAH ================= --}}
<section class="compare-wrap" id="beda">
  <div class="wrap">
    @include('landing._head', ['h' => $c['compare'], 'underline' => true])

    <div class="tabs compare-switch reveal">
      <button type="button" class="tab active" data-pos="100">{{ __('Dengan Vasetra') }}</button>
      <button type="button" class="tab" data-pos="0">{{ __('Tanpa Vasetra') }}</button>
    </div>

    <div class="compare reveal" id="compare">
      <span class="cmp-tag l">{{ __('DENGAN VASETRA') }}</span>
      <span class="cmp-hint" id="cmpHint"><i class="bi bi-arrows"></i> {{ __('Geser untuk membandingkan') }}</span>
      <span class="cmp-tag r">{{ __('TANPA VASETRA') }}</span>

      {{-- Sesudah: rapi & terhubung --}}
      <div class="cmp-layer cmp-after">
        <div class="cmp-cell">
          <div class="cmp-label mono"><i class="bi bi-box-seam"></i>{{ __('DAFTAR ASET TERPUSAT') }}</div>
          <p>{{ __('Satu sumber data: siapa pemegangnya, di mana lokasinya, bagaimana kondisinya.') }}</p>
          <div class="mini-table">
            <div><span>{{ __('KODE') }}</span><span>{{ __('ASET') }}</span><span>{{ __('PEMEGANG') }}</span></div>
            <div><b>AST-0087</b><span>Laptop Dell Latitude</span><span>Sari</span></div>
            <div><b>AST-0124</b><span>{{ __('Proyektor Epson') }}</span><span>R. Training</span></div>
            <div><b>AST-0201</b><span>{{ __('Genset 50 kVA') }}</span><span>{{ __('Gedung B') }}</span></div>
          </div>
        </div>
        <div class="cmp-cell">
          <div class="cmp-label mono"><i class="bi bi-calendar2-check"></i>{{ __('JADWAL MAINTENANCE') }}</div>
          <p>{{ __('Pengingat otomatis — tidak ada lagi servis yang terlewat.') }}</p>
          <div class="mini-table">
            <div><span>{{ __('ASET') }}</span><span>{{ __('JENIS') }}</span><span>STATUS</span></div>
            <div><b>{{ __('Genset') }}</b><span>{{ __('Servis 250 jam') }}</span><span><em class="chip b-amber" style="font-style:normal">{{ __('Hari ini') }}</em></span></div>
            <div><b>{{ __('AC Lt. 3') }}</b><span>{{ __('Cuci berkala') }}</span><span><em class="chip b-blue" style="font-style:normal">{{ __('Kamis') }}</em></span></div>
            <div><b>{{ __('Lift') }}</b><span>{{ __('Inspeksi') }}</span><span><em class="chip b-teal" style="font-style:normal">{{ __('Terjadwal') }}</em></span></div>
          </div>
        </div>
        <div class="cmp-cell">
          <div class="cmp-label mono"><i class="bi bi-inboxes"></i>{{ __('REQUEST PERBAIKAN') }}</div>
          <p>{{ __('Laporan masuk ke antrean tim teknisi dan jelas siapa yang mengerjakan.') }}</p>
          <div class="mini-kanban">
            <div><small>{{ __('MENUNGGU') }}</small><span>{{ __('AC Lt. 3') }}<em>{{ __('oleh Dewi') }}</em></span></div>
            <div><small>{{ __('DIKERJAKAN') }}</small><span>Laptop Sari<em>{{ __('Budi · teknisi') }}</em></span></div>
            <div><small>{{ __('SELESAI') }}</small><span>Printer<em>{{ __('Rp85.000') }}</em></span></div>
          </div>
        </div>
        <div class="cmp-cell">
          <div class="cmp-label mono"><i class="bi bi-shield-check"></i>{{ __('RIWAYAT & AUDIT') }}</div>
          <p>{{ __('Setiap perubahan tercatat, siap ditunjukkan saat audit.') }}</p>
          <ul class="mini-tl">
            <li><i class="bi bi-check-circle-fill"></i><span><b>Laptop</b> {{ __('diserahkan ke Sari · 2 Jan') }}</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span><b>{{ __('Genset') }}</b> {{ __('diservis · biaya Rp1,2 jt') }}</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span><b>Rina</b> {{ __('mengekspor data aset · 08.14') }}</span></li>
          </ul>
        </div>
      </div>

      {{-- Sebelum: tercecer --}}
      <div class="cmp-layer cmp-before" aria-hidden="true">
        <div class="dash" style="left:24%;top:30%;width:22%;transform:rotate(18deg)"></div>
        <div class="dash" style="left:56%;top:68%;width:20%;transform:rotate(-14deg)"></div>
        <div class="note" style="left:7%;top:12%"><i class="bi bi-file-earmark-excel"></i><div><b>aset_FINAL_revisi_v7.xlsx</b><small>{{ __('3 versi berbeda di 3 laptop') }}</small></div><span class="dot-red">!</span></div>
        <div class="note" style="right:6%;top:9%"><i class="bi bi-chat-dots"></i><div><b>{{ __('Grup WA Teknisi') }}</b><small>{{ __('"AC lantai 3 rusak lagi, siapa yang cek?"') }}</small></div><span class="dot-red">214</span></div>
        <div class="note yellow" style="left:5%;bottom:13%"><i class="bi bi-sticky"></i><div><b>{{ __('Servis genset') }}</b><small>{{ __('terakhir kapan ya? lupa…') }}</small></div></div>
        <div class="note" style="right:8%;bottom:16%"><i class="bi bi-file-earmark-text"></i><div><b>{{ __('Form serah terima') }}</b><small>{{ __('kertasnya hilang di laci') }}</small></div><span class="dot-red">!</span></div>
        <div class="note" style="left:53%;top:8%"><i class="bi bi-laptop"></i><div><b>LTP-021</b><small>{{ __('dipegang siapa sekarang?') }}</small></div></div>
        <div class="chaos-center"><div class="q">?</div><b>{{ __('Data aset tercecer di banyak tempat') }}</b><small>{{ __('Tim sibuk mencari, bukan bekerja.') }}</small></div>
      </div>

      <div class="cmp-handle"></div>
      <input type="range" class="cmp-range" min="0" max="100" value="50" aria-label="{{ __('Geser untuk membandingkan sebelum dan sesudah Vasetra') }}">
    </div>

    <div class="compare-foot reveal">
      <span><b>{{ $c['compare']['footer_title'] }}</b>{{ $c['compare']['footer_text'] }}</span>
      <a class="btn btn-primary" href="#tur">{{ __('Lihat isi aplikasinya') }} <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>

{{-- ================= FEATURES ================= --}}
<section id="fitur">
  <div class="wrap">
    @include('landing._head', ['h' => $c['features']])
    <div class="features-grid">
      @foreach($c['features']['items'] as $f)
        <div class="feature reveal">
          <div class="ic"><i class="bi {{ $f['icon'] }}"></i></div>
          <h3>{{ $f['title'] }}</h3>
          <p>{{ $f['text'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= PRODUCT TOUR ================= --}}
@php
    // Pola QR ilustrasi (deterministik) dengan tiga finder pattern.
    $qrCells = [];
    $n = 25;
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $inFinder = ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
            if ($inFinder) continue;
            if ((crc32("vasetra-$x-$y") % 100) < 47) $qrCells[] = [$x, $y];
        }
    }
    $finders = [[0, 0], [$n - 7, 0], [0, $n - 7]];
    $people = [['SA', 'Sari Anggraini', '#2f7be0'], ['BW', 'Budi Wicaksono', '#14b8a6'], ['DP', 'Dewi Pratiwi', '#8b5cf6'], ['AH', 'Andi Hidayat', '#f59e0b'], ['RS', 'Rina Susanti', '#ef4444']];
    // Screenshot/GIF dari CMS menggantikan ilustrasi tab terkait.
    $shots = collect(['aset', 'detail', 'jadwal', 'request', 'kalender'])->mapWithKeys(fn ($k) => [$k => $img($c['tour']['shot_' . $k] ?? null)])->all();
@endphp
<section id="tur" style="background:#fff;border-block:1px solid var(--line)">
  <div class="wrap">
    @include('landing._head', ['h' => $c['tour']])

    <div class="tabs reveal" role="tablist">
      <button type="button" class="tab active" data-tour="aset" data-url="app.vasetra.com/dashboard/assets"><i class="bi bi-box-seam"></i>{{ __('Daftar Aset') }}<span class="bar"></span></button>
      <button type="button" class="tab" data-tour="detail" data-url="app.vasetra.com/dashboard/assets/view/AST-000087"><i class="bi bi-qr-code"></i>{{ __('Detail & QR') }}<span class="bar"></span></button>
      <button type="button" class="tab" data-tour="jadwal" data-url="app.vasetra.com/dashboard/maintenance"><i class="bi bi-calendar2-check"></i>{{ __('Jadwal Maintenance') }}<span class="bar"></span></button>
      <button type="button" class="tab" data-tour="request" data-url="app.vasetra.com/dashboard/maintenance-requests"><i class="bi bi-inboxes"></i>{{ __('Request Tim') }}<span class="bar"></span></button>
      <button type="button" class="tab" data-tour="kalender" data-url="app.vasetra.com/dashboard/calendar"><i class="bi bi-calendar3"></i>{{ __('Kalender') }}<span class="bar"></span></button>
    </div>

    <div class="tour-frame reveal" aria-hidden="true">
      <div class="tour-bar"><div class="dots"><span></span><span></span><span></span></div><div class="tour-url" id="tourUrl"><i class="bi bi-lock-fill"></i> app.vasetra.com/dashboard/assets</div><div style="width:47px"></div></div>
      <div class="tour-body">
        <div class="tour-rail">
          <img src="{{ asset('assets/images/brand/vasetra-mark.png') }}" alt="">
          <span><i class="bi bi-grid-1x2"></i></span>
          <span class="on" data-rail="aset detail"><i class="bi bi-box-seam"></i></span>
          <span data-rail="jadwal"><i class="bi bi-tools"></i></span>
          <span data-rail="request"><i class="bi bi-inboxes"></i></span>
          <span data-rail="kalender"><i class="bi bi-calendar3"></i></span>
          <span><i class="bi bi-people"></i></span>
        </div>
        <div class="tour-main">
          @foreach($shots as $shotKey => $shotUrl)
            @if($shotUrl)
              <div class="tour-panel {{ $shotKey === 'aset' ? 'active' : '' }}" data-panel="{{ $shotKey }}"><img src="{{ $shotUrl }}" alt="{{ __('Tampilan Vasetra') }}" class="cms-img" loading="lazy"></div>
            @endif
          @endforeach

          {{-- Daftar aset --}}
          <div class="tour-panel {{ $shots['aset'] ? '' : 'active' }}" data-panel="{{ $shots['aset'] ? 'mock-aset' : 'aset' }}">
            <div class="tp-head">
              <h4>{{ __('Data Aset') }} <small>{{ __('1.248 aset · 6 kategori · 3 lokasi') }}</small></h4>
              <div class="tp-actions"><span class="tp-btn"><i class="bi bi-upload"></i>Import Excel</span><span class="tp-btn"><i class="bi bi-qr-code"></i>{{ __('Cetak QR') }}</span><span class="tp-btn pri"><i class="bi bi-plus-lg"></i>{{ __('Tambah Aset') }}</span></div>
            </div>
            <div class="tp-card">
              <div class="tp-filter"><span class="tp-search"><i class="bi bi-search"></i> {{ __('Cari kode, nama, serial…') }}</span><span class="chip">{{ __('Kategori: Semua') }}</span><span class="chip">{{ __('Status: Aktif') }}</span><span class="chip">{{ __('Lokasi: Semua') }}</span></div>
              <div class="tp-scroll">
                <table class="tp-table">
                  <tr><th>{{ __('Kode') }}</th><th>{{ __('Nama aset') }}</th><th>{{ __('Kategori') }}</th><th>{{ __('Pemegang') }}</th><th>{{ __('Lokasi') }}</th><th>{{ __('Kondisi') }}</th></tr>
                  @foreach([
                    ['AST-000087', __('Laptop Dell Latitude 5440'), __('Elektronik'), 0, __('Kantor Pusat'), [__('Baik'), 'b-teal']],
                    ['AST-000124', __('Proyektor Epson EB-X51'), __('Elektronik'), null, __('Ruang Training'), [__('Baik'), 'b-teal']],
                    ['AST-000201', __('Genset Perkins 50 kVA'), __('Mesin'), 1, __('Gedung B'), [__('Servis'), 'b-amber']],
                    ['AST-000233', __('AC Split Daikin 1 PK'), __('Elektronik'), null, __('Lantai 3'), [__('Perbaikan'), 'b-red']],
                    ['AST-000310', 'Toyota Avanza B 1234 XY', __('Kendaraan'), 3, __('Pool Kendaraan'), [__('Baik'), 'b-teal']],
                    ['AST-000356', __('Meja Kerja Ergonomis'), __('Furnitur'), 2, __('Lantai 2'), [__('Baik'), 'b-teal']],
                  ] as [$code, $name, $cat, $p, $loc, [$cond, $cls]])
                    <tr>
                      <td><b>{{ $code }}</b></td><td>{{ $name }}</td><td>{{ $cat }}</td>
                      <td>@if($p !== null)<span class="ava" style="background:{{ $people[$p][2] }}">{{ $people[$p][0] }}</span>{{ $people[$p][1] }}@else<span style="color:var(--muted)">—</span>@endif</td>
                      <td>{{ $loc }}</td><td><span class="chip {{ $cls }}">{{ $cond }}</span></td>
                    </tr>
                  @endforeach
                </table>
              </div>
            </div>
          </div>

          {{-- Detail & QR --}}
          <div class="tour-panel" data-panel="{{ $shots['detail'] ? 'mock-detail' : 'detail' }}">
            <div class="tp-head">
              <h4>Laptop Dell Latitude 5440 <small>{{ __('AST-000087 · Elektronik › Laptop') }}</small></h4>
              <div class="tp-actions"><span class="tp-btn"><i class="bi bi-arrow-left-right"></i>{{ __('Pindahkan') }}</span><span class="tp-btn"><i class="bi bi-wrench"></i>{{ __('Request perbaikan') }}</span><span class="tp-btn pri"><i class="bi bi-pencil"></i>{{ __('Ubah') }}</span></div>
            </div>
            <div class="tp-detail">
              <div class="tp-card" style="padding:16px">
                <div class="tp-photo"><i class="bi bi-laptop"></i></div>
                <div class="tp-info">
                  <div><small>Vendor</small><b>PT Mitra Komputer</b></div>
                  <div><small>{{ __('Tanggal beli') }}</small><b>14 Feb 2025</b></div>
                  <div><small>{{ __('Harga beli') }}</small><b>Rp18.500.000</b></div>
                  <div><small>{{ __('Nilai buku') }}</small><b>Rp13.875.000</b></div>
                  <div><small>{{ __('Garansi s/d') }}</small><b>14 Feb 2028</b></div>
                  <div><small>{{ __('Maintenance berikutnya') }}</small><b>{{ __('12 Des 2026') }}</b></div>
                </div>
              </div>
              <div class="tp-card">
                <div class="qr-card">
                  <svg viewBox="0 0 {{ $n }} {{ $n }}" shape-rendering="crispEdges">
                    @foreach($finders as [$fx, $fy])
                      <rect x="{{ $fx }}" y="{{ $fy }}" width="7" height="7" fill="#0b2447"/><rect x="{{ $fx + 1 }}" y="{{ $fy + 1 }}" width="5" height="5" fill="#fff"/><rect x="{{ $fx + 2 }}" y="{{ $fy + 2 }}" width="3" height="3" fill="#0b2447"/>
                    @endforeach
                    @foreach($qrCells as [$x, $y])<rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#0b2447"/>@endforeach
                  </svg>
                  <b>AST-000087</b><small style="color:var(--muted);font-size:.75rem">{{ __('Scan untuk lihat detail & lapor kerusakan') }}</small>
                </div>
                <div class="holder"><span class="ava" style="background:#2f7be0">SA</span><div><b style="color:var(--ink)">Sari Anggraini</b><br><small style="color:var(--muted)">{{ __('Pemegang sejak 2 Jan 2026 · Finance') }}</small></div></div>
                <div class="holder"><i class="bi bi-clock-history" style="color:var(--blue)"></i><div><small style="color:var(--muted)">{{ __('Riwayat') }}</small><br><b style="color:var(--ink);font-size:.78rem">{{ __('3 penugasan · 2 maintenance · Rp450.000') }}</b></div></div>
              </div>
            </div>
          </div>

          {{-- Jadwal maintenance --}}
          <div class="tour-panel" data-panel="{{ $shots['jadwal'] ? 'mock-jadwal' : 'jadwal' }}">
            <div class="tp-head"><h4>{{ __('Jadwal Maintenance') }} <small>{{ __('Pengingat otomatis berdasarkan interval setiap aset') }}</small></h4><div class="tp-actions"><span class="tp-btn"><i class="bi bi-funnel"></i>Filter</span></div></div>
            <div class="tp-stats">
              <div><small>{{ __('Terlambat') }}</small><b style="color:#dc2626">3</b></div>
              <div><small>{{ __('Hari ini') }}</small><b style="color:#d97706">2</b></div>
              <div><small>{{ __('Minggu ini') }}</small><b style="color:var(--blue)">7</b></div>
              <div><small>{{ __('Biaya tahun ini') }}</small><b>{{ __('Rp38,4 jt') }}</b></div>
            </div>
            <div class="tp-card tp-scroll">
              <table class="tp-table">
                <tr><th>{{ __('Aset') }}</th><th>{{ __('Jenis') }}</th><th>Interval</th><th>{{ __('Jadwal') }}</th><th>Status</th></tr>
                @foreach([
                  [__('AC Split Daikin Lt. 3'), __('Preventif'), __('Tiap 3 bulan'), __('28 Okt 2026'), [__('Terlambat 8 hari'), 'b-red']],
                  [__('Genset Perkins 50 kVA'), __('Preventif'), __('Tiap 6 bulan'), __('Hari ini'), [__('Hari ini'), 'b-amber']],
                  [__('Lift Penumpang A'), __('Inspeksi'), __('Tiap bulan'), __('Sabtu, 7 Nov'), [__('Minggu ini'), 'b-blue']],
                  [__('Timbangan Digital Gudang'), __('Kalibrasi'), __('Tiap tahun'), '15 Nov 2026', [__('Terjadwal'), 'b-teal']],
                  ['Toyota Avanza B 1234 XY', __('Preventif'), __('Tiap 6 bulan'), '20 Nov 2026', [__('Terjadwal'), 'b-teal']],
                ] as [$a, $t, $i, $d, [$s, $chipCls]])
                  <tr><td><b>{{ $a }}</b></td><td>{{ $t }}</td><td>{{ $i }}</td><td>{{ $d }}</td><td><span class="chip {{ $chipCls }}">{{ $s }}</span></td></tr>
                @endforeach
              </table>
            </div>
          </div>

          {{-- Request tim --}}
          <div class="tour-panel" data-panel="{{ $shots['request'] ? 'mock-request' : 'request' }}">
            <div class="tp-head"><h4>Maintenance Requests <small>{{ __('Ambil request yang menunggu — otomatis hilang dari antrean teknisi lain') }}</small></h4></div>
            <div class="tp-kanban">
              <div class="tp-col"><small>{{ __('Menunggu diambil') }} <span>2</span></small>
                <div class="tk"><b>{{ __('AC Split Daikin Lt. 3') }}</b>{{ __('Tidak dingin sejak pagi') }}<div class="meta"><span>{{ __('Dewi · 10 mnt lalu') }}</span><span class="take">{{ __('Ambil') }}</span></div></div>
                <div class="tk"><b>Printer Finance</b>{{ __('Kertas sering macet') }}<div class="meta"><span>{{ __('Andi · 1 jam lalu') }}</span><span class="take">{{ __('Ambil') }}</span></div></div>
              </div>
              <div class="tp-col"><small>{{ __('Sedang dikerjakan') }} <span>2</span></small>
                <div class="tk"><b>Laptop Dell Latitude</b>{{ __('Layar berkedip') }}<div class="meta"><span><span class="ava" style="background:#14b8a6;width:20px;height:20px">BW</span>Budi</span><span class="chip b-blue">{{ __('2 progres') }}</span></div></div>
                <div class="tk"><b>{{ __('Genset Perkins') }}</b>{{ __('Suara kasar saat start') }}<div class="meta"><span><span class="ava" style="background:#8b5cf6;width:20px;height:20px">FH</span>Fajar</span><span class="chip b-blue">{{ __('sejak 09.10') }}</span></div></div>
              </div>
              <div class="tp-col"><small>{{ __('Selesai') }} <span>18</span></small>
                <div class="tk"><b>{{ __('Proyektor Epson') }}</b>{{ __('Lampu redup — diganti') }}<div class="meta"><span>Budi</span><span class="chip b-teal">Rp650.000</span></div></div>
                <div class="tk"><b>{{ __('Kursi Ruang Rapat') }}</b>{{ __('Roda patah — diganti') }}<div class="meta"><span>Fajar</span><span class="chip b-teal">Rp120.000</span></div></div>
              </div>
            </div>
          </div>

          {{-- Kalender --}}
          <div class="tour-panel" data-panel="{{ $shots['kalender'] ? 'mock-kalender' : 'kalender' }}">
            <div class="tp-head"><h4>November 2026 <small>{{ __('Maintenance, request, dan meeting tim dalam satu kalender') }}</small></h4><div class="tp-actions"><span class="tp-btn"><i class="bi bi-chevron-left"></i></span><span class="tp-btn">{{ __('Hari ini') }}</span><span class="tp-btn"><i class="bi bi-chevron-right"></i></span><span class="tp-btn pri"><i class="bi bi-plus-lg"></i>{{ __('Agenda') }}</span></div></div>
            @php
              $events = [3 => [['m', __('Inspeksi lift')]], 5 => [['x', __('Servis genset')], ['r', __('AC Lt. 3')]], 8 => [['t', __('Rapat aset Q4')]], 12 => [['m', __('Cuci AC')]], 15 => [['m', __('Kalibrasi timbangan')]], 18 => [['t', __('Briefing teknisi')]], 20 => [['m', __('Servis Avanza')]], 24 => [['r', 'Printer Finance']], 27 => [['t', __('Audit inventaris')]]];
            @endphp
            <div class="cal">
              @foreach([__('SEN'), __('SEL'), __('RAB'), __('KAM'), __('JUM'), __('SAB'), __('MIN')] as $d)<div class="dow">{{ $d }}</div>@endforeach
              @for($i = 0; $i < 6; $i++)<div></div>@endfor
              @for($d = 1; $d <= 30; $d++)
                <div class="{{ $d === 5 ? 'today' : '' }}"><span>{{ $d }}</span>
                  @foreach($events[$d] ?? [] as [$evCls, $t])<span class="ev {{ $evCls }}">{{ $t }}</span>@endforeach
                </div>
              @endfor
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

{{-- ================= LIFECYCLE ================= --}}
<section class="lifecycle" id="alur">
  <div class="wrap">
    @include('landing._head', ['h' => $c['lifecycle'], 'dark' => true])
    <div class="steps" style="--cols:{{ min(6, max(1, count($c['lifecycle']['steps']))) }}">
      @foreach($c['lifecycle']['steps'] as $i => $step)
        <div class="step reveal" style="transition-delay:{{ $i * 70 }}ms">
          <div class="dot"><i class="bi {{ $step['icon'] }}"></i></div>
          <div class="no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
          <h3>{{ $step['title'] }}</h3>
          <p>{{ $step['text'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= REQUEST FLOW ================= --}}
<section>
  <div class="wrap split">
    <div class="reveal">
      @if($c['request']['kicker'])<span class="kicker">{{ $c['request']['kicker'] }}</span>@endif
      <h2>{{ $c['request']['title'] }}@if($c['request']['highlight'])<br><span class="grad-text">{{ $c['request']['highlight'] }}</span>@endif</h2>
      <p class="lead">{{ $c['request']['subtitle'] }}</p>
      <ol class="flow-list">
        @foreach($c['request']['steps'] as $i => $step)
          <li><span class="n">{{ $i + 1 }}</span><div><b>{{ $step['title'] }}</b><span>{{ $step['text'] }}</span></div></li>
        @endforeach
      </ol>
    </div>
    @if($img($c['request']['image']))
      <div class="reveal"><img src="{{ $img($c['request']['image']) }}" alt="{{ $c['request']['title'] }}" class="cms-img" loading="lazy"></div>
    @else
    <div class="ticket reveal" aria-hidden="true">
      <div class="ticket-head">
        <div><b>Laptop Dell Latitude</b><small>{{ __('AST-000087 · Perbaikan · Layar berkedip') }}</small></div>
        <span class="status b-blue" id="ticketStatus">{{ __('Sedang dikerjakan') }}</span>
      </div>
      <ul class="timeline" id="ticketTimeline">
        <li><span class="t-ic ic-amber"><i class="bi bi-megaphone-fill"></i></span><div><b>{{ __('Request dibuat oleh Sari') }}</b><small>{{ __('Senin 08.12 · Tim teknisi diberi tahu') }}</small></div></li>
        <li><span class="t-ic ic-blue"><i class="bi bi-hand-index-thumb-fill"></i></span><div><b>{{ __('Diambil oleh Budi (Teknisi)') }}</b><small>{{ __('Senin 08.20 · Hilang dari antrean teknisi lain') }}</small></div></li>
        <li><span class="t-ic ic-blue"><i class="bi bi-camera-fill"></i></span><div><b>{{ __('Progres: kabel fleksibel longgar') }}</b><small>{{ __('Senin 10.05 · 2 foto') }}</small><div class="photos"><span><i class="bi bi-image"></i></span><span><i class="bi bi-image"></i></span></div></div></li>
        <li><span class="t-ic ic-teal"><i class="bi bi-check-lg"></i></span><div><b>{{ __('Selesai — kabel diganti') }}</b><small>{{ __('Senin 14.30 · Biaya Rp150.000 · Sari diberi tahu') }}</small></div></li>
      </ul>
    </div>
    @endif
  </div>
</section>

{{-- ================= ROLES ================= --}}
<section class="roles" id="peran">
  <div class="wrap">
    @include('landing._head', ['h' => $c['roles']])
    <div class="roles-grid" style="--cols:{{ min(4, max(1, count($c['roles']['items']))) }}">
      @foreach($c['roles']['items'] as $role)
        <div class="role reveal">
          <div class="ic"><i class="bi {{ $role['icon'] }}"></i></div>
          <h3>{{ $role['title'] }}</h3>
          <div class="sub">{{ $role['sub'] }}</div>
          <ul>
            @foreach($role['points'] as $point)<li><i class="bi bi-check2-circle"></i>{{ $point }}</li>@endforeach
          </ul>
        </div>
      @endforeach
    </div>
    @if($c['roles']['note'])<p class="roles-note reveal"><i class="bi bi-sliders"></i> {{ $c['roles']['note'] }}</p>@endif
  </div>
</section>

{{-- ================= SOLUSI ================= --}}
@if(count($c['solutions']['items']))
<section id="solusi" style="padding-top:40px">
  <div class="wrap">
    @include('landing._head', ['h' => $c['solutions']])
    <div class="tabs reveal" role="tablist">
      @foreach($c['solutions']['items'] as $i => $sol)
        <button type="button" class="tab {{ $loop->first ? 'active' : '' }}" data-sol="s{{ $i }}"><i class="bi {{ $sol['icon'] }}"></i>{{ $sol['label'] }}</button>
      @endforeach
    </div>
    <div class="sol-card reveal">
      @foreach($c['solutions']['items'] as $i => $sol)
        <div class="sol-panel {{ $loop->first ? 'active' : '' }}" data-sol-panel="s{{ $i }}">
          <div class="txt">
            <h3 class="grad-text">{{ $sol['title'] }}</h3>
            <p>{{ $sol['text'] }}</p>
            <ul>@foreach($sol['checks'] as $chk)<li><i class="bi bi-check-circle"></i>{{ $chk }}</li>@endforeach</ul>
          </div>
          <div class="sol-side">
            @foreach($sol['features'] as $line)
              @php [$ic, $t] = $iconLine($line); @endphp
              <div class="row"><i class="bi {{ $ic }}"></i>{{ $t }}</div>
            @endforeach
            <a class="btn btn-primary" href="{{ $ctaUrl }}">{{ auth()->check() ? __('Buka Dashboard') : __('Coba untuk :name', ['name' => $sol['label']]) }} <i class="bi bi-arrow-right"></i>@guest<span class="free">{{ __('GRATIS') }}</span>@endguest</a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ================= MOBILE ================= --}}
@php $m = $c['mobile']; @endphp
<section>
  <div class="wrap">
    <div class="mobile-card reveal">
      <div>
        @if($m['badge'])<span class="soon"><i class="bi bi-phone"></i> {{ $m['badge'] }}</span>@endif
        <h2>{{ $m['title'] }}</h2>
        <p>{{ $m['text'] }}</p>
        <div class="mobile-feats">
          @foreach($m['points'] as $line)
            @php [$ic, $t] = $iconLine($line); @endphp
            <span><i class="bi {{ $ic }}"></i>{{ $t }}</span>
          @endforeach
        </div>
        @if($m['play_store_url'] || $m['app_store_url'])
          <div class="d-flex" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:24px">
            @if($m['play_store_url'])<a class="btn btn-light" href="{{ $m['play_store_url'] }}" target="_blank" rel="noopener"><i class="bi bi-google-play"></i> Google Play</a>@endif
            @if($m['app_store_url'])<a class="btn btn-light" href="{{ $m['app_store_url'] }}" target="_blank" rel="noopener"><i class="bi bi-apple"></i> App Store</a>@endif
          </div>
        @endif
      </div>
      @if($img($m['image']))
        <div><img src="{{ $img($m['image']) }}" alt="{{ $m['title'] }}" style="max-height:460px;width:auto;margin:0 auto;border-radius:24px" loading="lazy"></div>
      @else
      <div class="phone" aria-hidden="true">
        <div class="phone-screen">
          <div class="phone-top"><small>{{ __('Selamat pagi') }}</small><b>{{ __('Budi · Teknisi') }}</b></div>
          <div class="phone-scan"><span class="q"><i class="bi bi-qr-code-scan"></i></span><div><b style="display:block">{{ __('Scan aset') }}</b>{{ __('Arahkan kamera ke label QR') }}</div></div>
          <div class="phone-list">
            <div class="phone-item"><span class="badge b-amber" style="float:right;font-size:.6rem;padding:2px 6px;border-radius:99px">{{ __('Menunggu') }}</span><b>{{ __('AC Split Lt. 3') }}</b>{{ __('Tidak dingin · oleh Dewi') }}</div>
            <div class="phone-item"><span class="badge b-blue" style="float:right;font-size:.6rem;padding:2px 6px;border-radius:99px">{{ __('Dikerjakan') }}</span><b>Laptop Dell Latitude</b>{{ __('Layar berkedip · oleh Sari') }}</div>
            <div class="phone-item"><span class="badge b-teal" style="float:right;font-size:.6rem;padding:2px 6px;border-radius:99px">{{ __('Selesai') }}</span><b>Printer Finance</b>{{ __('Kertas macet · oleh Andi') }}</div>
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</section>

{{-- ================= PRICING ================= --}}
@php $pr = $c['pricing']; @endphp
<section id="harga" style="padding-top:40px">
  <div class="wrap">
    @include('landing._head', ['h' => $pr])

    @if(count($pr['steps']))
      <div class="trial-steps reveal" style="grid-template-columns:repeat({{ count($pr['steps']) }},1fr)">
        @foreach($pr['steps'] as $i => $step)
          <div class="trial-step"><span class="n">{{ $i + 1 }}</span><div><b>{{ $step['title'] }}</b><span>{{ $step['text'] }}</span></div></div>
        @endforeach
      </div>
    @endif

    @if($trial)
      <div class="trial-card reveal">
        <div>
          <h3>{{ __('Coba Gratis · :days hari', ['days' => $trialDays]) }}</h3>
          <div class="meta">
            <span><i class="bi bi-check-circle-fill"></i>{{ __('Semua fitur') }}</span>
            <span><i class="bi bi-check-circle-fill"></i>{{ $limit($trial->max_users, 'user') }}</span>
            <span><i class="bi bi-check-circle-fill"></i>{{ $limit($trial->max_assets, 'aset') }}</span>
            <span><i class="bi bi-check-circle-fill"></i>{{ __('Penyimpanan :size', ['size' => $storage($trial->max_storage_bytes)]) }}</span>
            <span><i class="bi bi-check-circle-fill"></i>{{ __('Tanpa kartu kredit') }}</span>
          </div>
        </div>
        <a class="btn btn-primary btn-lg" href="{{ $ctaUrl }}">{{ $ctaLabel }} <i class="bi bi-arrow-right"></i></a>
      </div>
    @endif

    @if($paidPlans->isNotEmpty())
      @php
        $maxSaved = $paidPlans->map(fn ($p) => (float) $p->price_monthly > 0 ? (int) round(((float) $p->price_monthly * 12 - (float) $p->price_yearly) / (float) $p->price_monthly) : 0)->max();
      @endphp
      <div class="toggle reveal">
        <div class="toggle-inner" role="group" aria-label="{{ __('Periode tagihan') }}">
          <button type="button" class="active" data-cycle="monthly">{{ __('Bulanan') }}</button>
          <button type="button" data-cycle="yearly">{{ __('Tahunan') }} @if($maxSaved > 0)<span class="save">{{ __('hemat s/d :n bulan', ['n' => $maxSaved]) }}</span>@endif</button>
        </div>
      </div>

      <div class="plans" style="--cols:{{ min(4, $paidPlans->count()) }}">
        @foreach($paidPlans as $plan)
          @php
            $isRec = $plan->plan_code === $recommendedCode;
            $monthly = (float) $plan->price_monthly;
            $yearly = (float) $plan->price_yearly;
            $savedMonths = $monthly > 0 ? (int) round(($monthly * 12 - $yearly) / $monthly) : 0;
          @endphp
          <div class="plan reveal {{ $isRec ? 'rec' : '' }}">
            @if($isRec)<span class="ribbon">{{ __('REKOMENDASI') }}</span>@endif
            <h3>{{ $plan->plan_name }}</h3>
            <p class="desc">{{ $isEn ? ($plan->description_en ?: $plan->description) : $plan->description }}</p>
            <div class="price">
              <b data-monthly="{{ $rupiah($monthly) }}" data-yearly="{{ $rupiah($yearly) }}">{{ $rupiah($monthly) }}</b>
              <span data-monthly="{{ __('/bulan') }}" data-yearly="{{ __('/tahun') }}">{{ __('/bulan') }}</span>
            </div>
            <div class="price-note" data-monthly="" data-yearly="{{ $savedMonths > 0 ? __('Hemat :n bulan dibanding bulanan', ['n' => $savedMonths]) : '' }}"></div>
            <ul>
              <li class="hl"><i class="bi bi-people-fill"></i>{{ $limit($plan->max_users, 'user') }}</li>
              <li class="hl"><i class="bi bi-box-seam-fill"></i>{{ $limit($plan->max_assets, 'aset') }}</li>
              <li class="hl"><i class="bi bi-hdd-fill"></i>{{ __('Penyimpanan :size', ['size' => $storage($plan->max_storage_bytes)]) }}</li>
              @foreach($pr['plan_features'] as $feature)<li><i class="bi bi-check2"></i>{{ $feature }}</li>@endforeach
            </ul>
            <a class="btn {{ $isRec ? 'btn-primary' : 'btn-ghost' }}" href="{{ $ctaUrl }}">{{ auth()->check() ? __('Buka Dashboard') : __('Mulai gratis') }}</a>
          </div>
        @endforeach
      </div>
    @endif

    @if(count($pr['notes']))
      <div class="pricing-foot reveal" style="grid-template-columns:repeat({{ count($pr['notes']) }},1fr)">
        @foreach($pr['notes'] as $note)
          <div><i class="bi {{ $note['icon'] }}"></i><span><b>{{ $note['title'] }}</b>{{ $note['text'] }}</span></div>
        @endforeach
      </div>
    @endif
  </div>
</section>

{{-- ================= FAQ ================= --}}
@if(count($c['faq']['items']))
<section id="faq" style="padding-top:24px">
  <div class="wrap">
    @include('landing._head', ['h' => $c['faq']])
    <div class="faq">
      @foreach($c['faq']['items'] as $item)
        <details class="reveal"><summary>{{ $item['q'] }} <i class="bi bi-plus-lg"></i></summary><p>{{ $item['a'] }}</p></details>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ================= FINAL CTA ================= --}}
<div class="final">
  <div class="wrap">
    <div class="final-card reveal">
      <h2>{{ $c['cta']['title'] }}</h2>
      <p>{{ $c['cta']['text'] }}</p>
      <div class="acts">
        <a class="btn btn-light btn-lg" href="{{ $ctaUrl }}">{{ $ctaLabel }} <i class="bi bi-arrow-right"></i></a>
        @guest<a class="btn btn-outline-light btn-lg" href="{{ route('login') }}">{{ __('Sudah punya akun? Masuk') }}</a>@endguest
      </div>
    </div>
  </div>
</div>

{{-- ================= FOOTER ================= --}}
@php
  $ft = $c['footer'];
  $wa = preg_replace('/\D+/', '', (string) $ft['whatsapp']);
  $wa = $wa && str_starts_with($wa, '0') ? '62' . substr($wa, 1) : $wa;
  // Tampilan nomor: +62 8121 1955 805
  $waLabel = $wa ? '+' . (str_starts_with($wa, '62') ? '62 ' . trim(chunk_split(substr($wa, 2), 4, ' ')) : $wa) : '';
  $waUrl = $wa ? 'https://wa.me/' . $wa . (trim((string) ($ft['whatsapp_text'] ?? '')) !== '' ? '?text=' . rawurlencode(trim($ft['whatsapp_text'])) : '') : '';
  $mapsUrl = preg_match('#^https://#i', trim((string) ($ft['maps_url'] ?? ''))) ? trim($ft['maps_url']) : '';
  $ftEmail = filter_var(trim((string) $ft['email']), FILTER_VALIDATE_EMAIL) ?: '';
@endphp
<footer>
  <div class="wrap">
    <div class="foot">
      <div>
        <img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra" width="195" height="40">
        <p style="max-width:340px">{{ $ft['tagline'] }}</p>
      </div>
      <div>
        <h4>{{ __('Produk') }}</h4>
        <ul><li><a href="#fitur">{{ __('Fitur') }}</a></li><li><a href="#tur">{{ __('Tur produk') }}</a></li><li><a href="#solusi">{{ __('Solusi') }}</a></li><li><a href="#harga">{{ __('Harga') }}</a></li></ul>
      </div>
      <div>
        <h4>{{ __('Akun') }}</h4>
        <ul><li><a href="{{ route('register') }}">{{ __('Coba gratis') }}</a></li><li><a href="{{ route('login') }}">{{ __('Masuk') }}</a></li><li><a href="#faq">FAQ</a></li></ul>
      </div>
      @if($ftEmail || $wa || $ft['address'])
      <div id="kontak">
        <h4>{{ __('Kontak') }}</h4>
        <ul class="foot-contact">
          @if($wa)<li><i class="bi bi-whatsapp"></i><a href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $waLabel }}</a></li>@endif
          @if($ftEmail)<li><i class="bi bi-envelope"></i><a href="mailto:{{ $ftEmail }}">{{ $ftEmail }}</a></li>@endif
          @if($ft['address'])
            <li><i class="bi bi-geo-alt"></i>
              @if($mapsUrl)<a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="addr" style="white-space:pre-line">{{ $ft['address'] }}</a>@else<span style="white-space:pre-line">{{ $ft['address'] }}</span>@endif
            </li>
          @endif
        </ul>
      </div>
      @endif
    </div>
    <div class="copy">
      <span>© {{ now()->year }} Vasetra{{ $ft['company'] ? ' · ' . $ft['company'] : '' }}</span>
      <span>Asset Lifecycle Platform</span>
    </div>
  </div>
</footer>

@php
  $strong = fn ($t) => '<strong>' . e($t) . '</strong>';
  $jsText = [
    'f1t' => __('Request perbaikan diambil'),
    'f1b' => e(__('Laptop Dell Latitude sedang dikerjakan oleh')) . ' ' . $strong(__('Budi (Teknisi)')),
    'f2t' => __('Aset diserahkan'),
    'f2b' => e(__('Proyektor Epson kini dipegang')) . ' ' . $strong('Sari') . ' · ' . e(__('Ruang Training')),
    'f3t' => __('Maintenance jatuh tempo'),
    'f3b' => e(__('Genset Perkins 50 kVA dijadwalkan')) . ' ' . $strong(__('hari ini')),
    'f4t' => __('Perbaikan selesai'),
    'f4b' => e(__('Printer Finance selesai · biaya')) . ' ' . $strong(__('Rp85.000')),
    'justNow' => __('baru saja'),
    'waiting' => __('Menunggu diambil'),
    'inProgress' => __('Sedang dikerjakan'),
    'done' => __('Selesai'),
    'numLocale' => $isEn ? 'en-US' : 'id-ID',
  ];
@endphp
<script>
// Teks dinamis untuk animasi (diterjemahkan dari server, aman dari XSS).
var T = @json($jsText);
(function () {
  var nav = document.getElementById('nav');
  var toggle = document.getElementById('navToggle');

  window.addEventListener('scroll', function () {
    nav.classList.toggle('scrolled', window.scrollY > 8);
  }, { passive: true });

  toggle.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.innerHTML = open ? '<i class="bi bi-x-lg"></i>' : '<i class="bi bi-list"></i>';
  });
  document.querySelectorAll('#navLinks a').forEach(function (a) {
    a.addEventListener('click', function () { nav.classList.remove('open'); toggle.innerHTML = '<i class="bi bi-list"></i>'; });
  });

  // Ganti bahasa tetap di bagian halaman yang sama (#harga, #fitur, ...).
  document.querySelectorAll('[data-lang-link]').forEach(function (a) {
    a.addEventListener('click', function () { if (location.hash) a.href = a.href.split('#')[0] + location.hash; });
  });

  // Bulanan / tahunan
  document.querySelectorAll('.toggle button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cycle = btn.getAttribute('data-cycle');
      document.querySelectorAll('.toggle button').forEach(function (b) { b.classList.toggle('active', b === btn); });
      document.querySelectorAll('[data-' + cycle + ']').forEach(function (el) {
        el.textContent = el.getAttribute('data-' + cycle);
      });
    });
  });

  // Sebelum / sesudah
  var compare = document.getElementById('compare');
  if (compare) {
    var range = compare.querySelector('.cmp-range');
    var switches = document.querySelectorAll('.compare-switch .tab');
    var setPos = function (v, animate) {
      compare.querySelectorAll('.cmp-before,.cmp-handle').forEach(function (el) {
        el.style.transition = animate ? 'clip-path .6s ease, left .6s ease' : 'none';
      });
      compare.style.setProperty('--pos', v + '%');
      range.value = v;
      switches.forEach(function (b) {
        b.classList.toggle('active', (b.getAttribute('data-pos') === '100') === (v >= 50));
      });
    };
    range.addEventListener('input', function () { setPos(range.value, false); });
    switches.forEach(function (b) {
      b.addEventListener('click', function () { setPos(parseInt(b.getAttribute('data-pos'), 10) === 100 ? 85 : 15, true); });
    });
    setPos(50, false);
  }

  // Tur produk
  var tourUrl = document.getElementById('tourUrl');
  document.querySelectorAll('[data-tour]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      var key = tab.getAttribute('data-tour');
      document.querySelectorAll('[data-tour]').forEach(function (t) { t.classList.toggle('active', t === tab); });
      document.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === key); });
      document.querySelectorAll('[data-rail]').forEach(function (r) { r.classList.toggle('on', r.getAttribute('data-rail').split(' ').indexOf(key) > -1); });
      tourUrl.innerHTML = '<i class="bi bi-lock-fill"></i> ' + tab.getAttribute('data-url');
    });
  });

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var onVisible = function (el, cb) {
    if (!el) return;
    if (!('IntersectionObserver' in window)) { cb(); return; }
    var o = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { o.disconnect(); cb(); } });
    }, { threshold: 0.35 });
    o.observe(el);
  };

  if (!reduceMotion) {
    // Hero: angka KPI menghitung naik.
    onVisible(document.querySelector('.stage'), function () {
      document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10), t0 = null;
        var step = function (t) {
          if (!t0) t0 = t;
          var p = Math.min(1, (t - t0) / 1200);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString(T.numLocale);
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      });
    });

    // Hero: notifikasi aktivitas berganti seperti aplikasi sungguhan.
    var feed = [
      ['ic-teal', 'bi-person-check-fill', T.f1t, T.f1b],
      ['ic-blue', 'bi-arrow-left-right', T.f2t, T.f2b],
      ['ic-amber', 'bi-bell-fill', T.f3t, T.f3b],
      ['ic-teal', 'bi-check2-circle', T.f4t, T.f4b],
    ];
    var live = document.getElementById('liveFeed'), li = 0;
    if (live) {
      setInterval(function () {
        var icon = live.querySelector('[data-live-icon]'), body = live.querySelector('[data-live-body]');
        icon.classList.add('out'); body.classList.add('out');
        setTimeout(function () {
          li = (li + 1) % feed.length;
          var f = feed[li];
          icon.className = 'ic live-msg out ' + f[0];
          icon.innerHTML = '<i class="bi ' + f[1] + '"></i>';
          body.innerHTML = '<b>' + f[2] + '</b><small>' + f[3] + '</small><div class="time"><span class="live-dot"></span>' + T.justNow + '</div>';
          requestAnimationFrame(function () { icon.classList.remove('out'); body.classList.remove('out'); });
        }, 350);
      }, 3800);
    }

    // Tiket request: langkah muncul berurutan, berulang.
    var tl = document.getElementById('ticketTimeline'), st = document.getElementById('ticketStatus');
    onVisible(tl, function () {
      var play = function () {
        tl.classList.remove('play'); void tl.offsetWidth; tl.classList.add('play');
        st.className = 'status b-amber'; st.textContent = T.waiting;
        setTimeout(function () { st.className = 'status b-blue'; st.textContent = T.inProgress; }, 1000);
        setTimeout(function () { st.className = 'status b-teal'; st.textContent = T.done; }, 2900);
      };
      play();
      setInterval(play, 8000);
    });

    // Sebelum/sesudah: geser otomatis sekali sebagai petunjuk.
    var cmp = document.getElementById('compare'), hint = document.getElementById('cmpHint');
    onVisible(cmp, function () {
      var r = cmp.querySelector('.cmp-range'), seq = [72, 28, 50], i = 0, touched = false;
      cmp.addEventListener('pointerdown', function () { touched = true; if (hint) hint.classList.add('hide'); });
      var go = function () {
        if (touched || i >= seq.length) { setTimeout(function () { hint && hint.classList.add('hide'); }, 1500); return; }
        cmp.querySelectorAll('.cmp-before,.cmp-handle').forEach(function (el) { el.style.transition = 'clip-path .9s ease, left .9s ease'; });
        cmp.style.setProperty('--pos', seq[i] + '%'); r.value = seq[i]; i++;
        setTimeout(go, 1100);
      };
      setTimeout(go, 400);
    });

    // Tur produk: tab berpindah otomatis seperti video, berhenti saat diklik.
    var tourTabs = Array.prototype.slice.call(document.querySelectorAll('[data-tour]'));
    var tourWrap = tourTabs.length ? tourTabs[0].parentNode : null, tourTimer = null, tourIdx = 0, auto = true;
    var nextTour = function () {
      if (!auto) return;
      tourIdx = (tourIdx + 1) % tourTabs.length;
      tourTabs[tourIdx].dispatchEvent(new CustomEvent('click', { detail: 'auto' }));
      restartBar();
    };
    var restartBar = function () {
      var bar = tourTabs[tourIdx].querySelector('.bar');
      if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
      clearTimeout(tourTimer); tourTimer = setTimeout(nextTour, 6000);
    };
    tourTabs.forEach(function (t, i) {
      t.addEventListener('click', function (e) {
        tourIdx = i;
        if (e.detail !== 'auto' && e.isTrusted) { auto = false; clearTimeout(tourTimer); tourWrap.classList.remove('autoplay'); }
      });
    });
    onVisible(document.querySelector('.tour-frame'), function () {
      if (!auto || !tourWrap || /^#tur-/.test(location.hash)) return;
      tourWrap.classList.add('autoplay');
      restartBar();
    });
  }

  // Tautan langsung ke tab tur, mis. /#tur-kalender
  var tourHash = (location.hash.match(/^#tur-(\w+)$/) || [])[1];
  var tourTab = tourHash && document.querySelector('[data-tour="' + tourHash + '"]');
  if (tourTab) { tourTab.click(); document.getElementById('tur').scrollIntoView(); }

  // Solusi
  document.querySelectorAll('[data-sol]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      var key = tab.getAttribute('data-sol');
      document.querySelectorAll('[data-sol]').forEach(function (t) { t.classList.toggle('active', t === tab); });
      document.querySelectorAll('[data-sol-panel]').forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-sol-panel') === key); });
    });
  });

  // Animasi muncul saat di-scroll
  var items = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('in'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { rootMargin: '0px 0px -60px 0px', threshold: 0.08 });
  items.forEach(function (el) { io.observe(el); });
})();
</script>
</body>
</html>
