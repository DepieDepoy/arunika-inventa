<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vasetra — Asset Lifecycle Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0B2A46;
    --deep:#0F3D82;
    --deep-2:#071C33;
    --teal:#18C7C6;
    --teal-light:#E5FBFA;
    --navy-soft:#DEE7F7;
    --paper:#F5F8FB;
    --white:#FFFFFF;
    --line:#DCE6F0;
    --danger:#C0483F;
    --radius-sm:6px;
    --radius-md:12px;
    --radius-lg:22px;
    --max:1180px;
  }
  *{box-sizing:border-box;}
  html{scroll-behavior:smooth;}
  body{
    margin:0;
    background:var(--paper);
    color:var(--ink);
    font-family:'Manrope',sans-serif;
    -webkit-font-smoothing:antialiased;
    overflow-x:hidden;
  }
  img,svg{max-width:100%;display:block;}
  h1,h2,h3{
    font-family:'Space Grotesk',sans-serif;
    margin:0;
    color:var(--deep-2);
    letter-spacing:-0.01em;
  }
  p{margin:0;}
  a{text-decoration:none;color:inherit;}
  .wrap{max-width:var(--max);margin:0 auto;padding:0 32px;}
  @media(max-width:640px){.wrap{padding:0 20px;}}

  /* ---------- NAV ---------- */
  .nav{
    position:sticky;top:0;z-index:50;
    background:rgba(245,248,251,0.88);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
  }
  .nav .wrap{display:flex;align-items:center;justify-content:space-between;height:76px;}
  .brand{display:flex;align-items:center;gap:10px;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.25rem;color:var(--deep-2);}
  .brand .mark{width:32px;height:32px;flex:none;}
  .nav-links{display:flex;gap:34px;font-size:0.95rem;font-weight:600;color:var(--deep);}
  .nav-cta{display:flex;gap:12px;align-items:center;}
  @media(max-width:820px){.nav-links{display:none;}}

  .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:13px 26px;border-radius:999px;font-weight:700;font-size:0.95rem;
    cursor:pointer;border:1.5px solid transparent;white-space:nowrap;
    transition:transform .15s ease, box-shadow .15s ease, background .15s ease;
  }
  .btn:hover{transform:translateY(-1px);}
  .btn-primary{background:var(--deep);color:var(--white);box-shadow:0 8px 20px -8px rgba(15,61,130,0.55);}
  .btn-primary:hover{background:#0C3269;}
  .btn-ghost{background:transparent;color:var(--deep-2);border-color:var(--line);}
  .btn-ghost:hover{border-color:var(--deep-2);}
  .btn-dark{background:var(--deep-2);color:var(--white);}
  .btn-dark:hover{background:#051224;}
  .btn-gold{background:var(--white);color:var(--deep-2);}
  .btn-gold:hover{background:var(--teal-light);}
  .btn-sm{padding:10px 18px;font-size:0.88rem;}

  section{position:relative;}

  /* ---------- HERO ---------- */
  .hero{padding:76px 0 90px;overflow:hidden;}
  .hero .wrap{display:grid;grid-template-columns:1.05fr 0.95fr;gap:56px;align-items:center;}
  @media(max-width:960px){.hero .wrap{grid-template-columns:1fr;gap:48px;}}
  .hero-eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    font-size:0.85rem;font-weight:700;color:var(--deep);
    background:var(--teal-light);padding:7px 14px;border-radius:999px;margin-bottom:22px;
  }
  .hero-eyebrow .dot{width:7px;height:7px;border-radius:50%;background:var(--teal);}
  .hero h1{
    font-size:clamp(2.1rem,4vw,3.35rem);
    line-height:1.1;
    font-weight:700;
    max-width:13ch;
  }
  .hero .sub{
    margin-top:22px;font-size:1.13rem;line-height:1.6;color:#46587A;max-width:46ch;
  }
  .hero .cta-row{display:flex;gap:14px;margin-top:34px;flex-wrap:wrap;}
  .hero .microtrust{margin-top:26px;font-size:0.85rem;color:#5A6B85;display:flex;gap:8px;align-items:center;}

  .hero-art{position:relative;}
  .hero-art .ring{
    position:absolute;width:150px;height:150px;border-radius:50%;
    border:22px solid var(--navy-soft);z-index:-1;
  }
  .hero-art .ring.r1{top:-40px;right:-40px;opacity:.7;}
  .hero-art .ring.r2{bottom:-50px;left:-36px;width:110px;height:110px;border-width:16px;border-color:var(--teal-light);opacity:.9;}
  .hero-art img{border-radius:var(--radius-lg);box-shadow:0 30px 60px -20px rgba(7,26,46,0.45);}

  /* ---------- PROBLEM STRIP ---------- */
  .problem{padding:70px 0 84px;background:var(--white);border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
  .section-head{max-width:640px;margin-bottom:44px;}
  .section-kicker{font-size:0.9rem;font-weight:700;color:var(--danger);margin-bottom:10px;}
  .section-head h2{font-size:clamp(1.6rem,2.6vw,2.15rem);line-height:1.22;}
  .problem-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;}
  @media(max-width:860px){.problem-grid{grid-template-columns:1fr;}}
  .problem-card{
    background:var(--paper);
    border:1px solid var(--line);
    border-radius:var(--radius-md);
    overflow:hidden;
  }
  .problem-card .thumb{background:var(--white);display:flex;align-items:center;justify-content:center;padding:22px;}
  .problem-card .thumb img{width:auto;max-height:170px;object-fit:contain;}
  .problem-card .body{padding:22px 24px 24px;border-left:4px solid var(--danger);}
  .problem-card .pnum{font-family:'Space Grotesk';font-weight:700;font-size:0.8rem;color:var(--danger);letter-spacing:.04em;margin-bottom:14px;display:block;}
  .problem-card h3{font-size:1.15rem;margin-bottom:10px;}
  .problem-card p{font-size:0.98rem;line-height:1.55;color:#4A5A72;}

  /* ---------- PRODUCT INTRO ---------- */
  .intro{padding:96px 0;}
  .intro .wrap{display:grid;grid-template-columns:0.85fr 1.15fr;gap:60px;align-items:center;}
  @media(max-width:960px){.intro .wrap{grid-template-columns:1fr;}.intro .mock{order:2;}}
  .intro h2{font-size:clamp(1.7rem,2.8vw,2.3rem);line-height:1.2;margin-bottom:18px;}
  .intro .lead{font-size:1.06rem;line-height:1.65;color:#46587A;max-width:42ch;}
  .intro .tag{display:inline-block;font-size:0.85rem;font-weight:700;color:var(--deep);background:var(--teal-light);padding:6px 13px;border-radius:999px;margin-bottom:18px;}

  .mock img{
    border-radius:18px;
    box-shadow:0 40px 70px -30px rgba(7,26,46,0.5);
    width:100%;
    height:auto;
  }

  /* ---------- FEATURES ---------- */
  .features{padding:90px 0;background:var(--white);border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
  .feat-list{display:flex;flex-direction:column;border-top:1px solid var(--line);}
  .feat-row{
    display:grid;grid-template-columns:64px 1fr;gap:26px;
    padding:30px 0;border-bottom:1px solid var(--line);
    align-items:flex-start;
  }
  .feat-row .ic{
    width:64px;height:64px;border-radius:14px;
    background:var(--teal-light);
    display:flex;align-items:center;justify-content:center;
    border:1.5px solid var(--navy-soft);
    padding:6px;
  }
  .feat-row .ic img{width:100%;height:100%;object-fit:contain;}
  .feat-row h3{font-size:1.18rem;margin-bottom:6px;}
  .feat-row p{color:#4A5A72;font-size:0.99rem;line-height:1.58;max-width:56ch;}
  .feat-row .fmeta{font-family:'Space Grotesk';font-size:0.78rem;font-weight:600;color:#7C8FAD;letter-spacing:.03em;margin-bottom:8px;text-transform:uppercase;}

  /* ---------- WHY ---------- */
  .why{padding:96px 0;background:var(--deep-2);color:var(--white);}
  .why .wrap{display:grid;grid-template-columns:1fr;gap:48px;}
  .why .section-head h2{color:var(--white);}
  .why .section-kicker{color:var(--teal);}
  .why .lead{color:#A9BEDD;max-width:60ch;font-size:1.03rem;line-height:1.6;margin-top:14px;}
  .why-body{display:grid;grid-template-columns:0.9fr 1.1fr;gap:48px;align-items:center;}
  @media(max-width:960px){.why-body{grid-template-columns:1fr;}}
  .why-visual img{border-radius:var(--radius-lg);}
  .why-grid{display:grid;grid-template-columns:repeat(1,1fr);gap:1px;background:rgba(255,255,255,0.12);border-radius:var(--radius-md);overflow:hidden;}
  .why-card{background:var(--deep-2);padding:28px 26px;}
  .why-card .wk{font-family:'Space Grotesk';font-weight:700;color:var(--teal);font-size:0.85rem;margin-bottom:16px;}
  .why-card h3{color:var(--white);font-size:1.2rem;margin-bottom:12px;line-height:1.3;}
  .why-card p{color:#9FB2D1;font-size:0.95rem;line-height:1.6;}

  /* ---------- FINAL CTA ---------- */
  .final{padding:100px 0;}
  .final-card{
    background:linear-gradient(135deg,var(--deep) 0%, var(--teal) 100%);
    border-radius:28px;
    padding:64px 56px;
    display:flex;align-items:center;justify-content:space-between;gap:40px;
    position:relative;overflow:hidden;
  }
  .final-card::before{
    content:"";position:absolute;right:-60px;top:-60px;width:220px;height:220px;
    border-radius:50%;border:30px solid rgba(255,255,255,0.12);
  }
  @media(max-width:820px){.final-card{flex-direction:column;text-align:center;padding:48px 30px;}}
  .final-copy{flex:1;min-width:0;}
  .final-card h2{color:var(--white);font-size:clamp(1.6rem,3vw,2.3rem);line-height:1.2;max-width:24ch;}
  .final-card p{color:#E1E9F9;margin-top:14px;font-size:1.02rem;max-width:44ch;}
  .final-cta-btns{display:flex;gap:14px;flex-wrap:wrap;flex:none;}
  @media(max-width:820px){.final-cta-btns{justify-content:center;}}
  .final-visual{flex:none;width:150px;}
  .final-visual img{border-radius:var(--radius-md);}
  @media(max-width:820px){.final-visual{width:120px;}}

  /* ---------- FOOTER ---------- */
  footer{padding:36px 0;}
  footer .wrap{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
  footer .brand{font-size:1rem;}
  footer .tag{color:#5A6B85;font-size:0.88rem;}
</style>
</head>
<body>

<nav class="nav">
  <div class="wrap">
    <div class="brand">
      <svg class="mark" viewBox="0 0 32 32" fill="none">
        <rect width="32" height="32" rx="9" fill="#071C33"/>
        <path d="M8 10 L16 22 L24 10" stroke="#18C7C6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Vasetra
    </div>
    <div class="nav-links">
      <a href="#fitur">Fitur</a>
      <a href="#kenapa">Kenapa Vasetra</a>
      <a href="#cta">Demo</a>
    </div>
    <div class="nav-cta">
      <a class="btn btn-ghost btn-sm" href="#cta">Request Demo</a>
      <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Coba Gratis</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <span class="hero-eyebrow"><span class="dot"></span>Asset Lifecycle Platform</span>
      <h1>Satu Platform untuk Seluruh Siklus Hidup Aset Perusahaan Anda</h1>
      <p class="sub">Dari pencatatan, maintenance, sampai audit — kelola semua aset perusahaan tanpa spreadsheet yang berantakan.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="{{ route('register') }}">Coba Gratis</a>
        <a class="btn btn-ghost" href="#cta">Request Demo</a>
      </div>
      <div class="microtrust">Dipakai tim aset &amp; operasional di berbagai perusahaan multi-cabang</div>
    </div>

    <div class="hero-art">
      <div class="ring r1"></div>
      <div class="ring r2"></div>
      <img src="{{ asset('images/landing/hero-illustration.webp') }}" alt="Ilustrasi Vasetra Asset Lifecycle Platform" width="490" height="385">
    </div>
  </div>
</section>

<!-- PROBLEM STRIP -->
<section class="problem">
  <div class="wrap">
    <div class="section-head">
      <div class="section-kicker">Masalah yang sering dialami tim aset dan operasional</div>
      <h2>Mengelola aset secara manual selalu berakhir sama</h2>
    </div>
    <div class="problem-grid">
      <div class="problem-card">
        <div class="thumb"><img src="{{ asset('images/landing/problem-messy-spreadsheets.webp') }}" alt="Data aset tercecer di banyak spreadsheet"></div>
        <div class="body">
          <span class="pnum">Data Tercecer</span>
          <h3>Aset dicatat di file yang berbeda-beda</h3>
          <p>Setiap tim punya spreadsheet sendiri, sulit dilacak siapa yang paling update, dan riwayatnya gampang hilang.</p>
        </div>
      </div>
      <div class="problem-card">
        <div class="thumb"><img src="{{ asset('images/landing/problem-delayed-maintenance.webp') }}" alt="Maintenance aset yang telat dilakukan"></div>
        <div class="body">
          <span class="pnum">Maintenance Telat</span>
          <h3>Tidak ada pengingat servis</h3>
          <p>Aset baru diperhatikan setelah rusak, karena tidak ada sistem yang mengingatkan jadwal perawatan.</p>
        </div>
      </div>
      <div class="problem-card">
        <div class="thumb"><img src="{{ asset('images/landing/problem-audit-chaos.webp') }}" alt="Proses audit aset yang kacau"></div>
        <div class="body">
          <span class="pnum">Audit Jadi Drama</span>
          <h3>Laporan tahunan disusun manual</h3>
          <p>Butuh waktu berhari-hari mengumpulkan data dari berbagai sumber setiap kali audit tiba.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRODUCT INTRO -->
<section class="intro">
  <div class="wrap">
    <div class="intro-copy">
      <span class="tag">Kenalkan Vasetra</span>
      <h2>Asset Lifecycle Platform untuk perusahaan Anda</h2>
      <p class="lead">Vasetra membantu perusahaan mencatat, memantau, dan mengelola seluruh aset dalam satu sistem terpusat — mulai dari pembelian hingga pemeliharaan, dengan akses yang bisa diatur sesuai peran tim Anda.</p>
    </div>
    <div class="mock">
      <img src="{{ asset('images/landing/dashboard-mockup.webp') }}" alt="Tampilan dashboard Vasetra" width="300" height="170">
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features" id="fitur">
  <div class="wrap">
    <div class="section-head">
      <div class="section-kicker" style="color:var(--deep);">Fitur</div>
      <h2>Semua yang dibutuhkan tim aset, dalam satu tempat</h2>
    </div>
    <div class="feat-list">
      <div class="feat-row">
        <div class="ic"><img src="{{ asset('images/landing/feature-asset-management.webp') }}" alt="Manajemen Aset"></div>
        <div>
          <div class="fmeta">Manajemen Aset</div>
          <h3>Catat aset lengkap, tanpa file terpisah-pisah</h3>
          <p>Simpan seluruh aset lengkap dengan kategori, sub-kategori, foto, dan dokumen pendukung dalam satu tempat.</p>
        </div>
      </div>
      <div class="feat-row">
        <div class="ic"><img src="{{ asset('images/landing/feature-vendor-management.webp') }}" alt="Manajemen Vendor"></div>
        <div>
          <div class="fmeta">Manajemen Vendor</div>
          <h3>Data vendor rapi, tanpa buka email lama</h3>
          <p>Kelola data vendor dan riwayat kerja sama dengan terstruktur, langsung dari satu dashboard.</p>
        </div>
      </div>
      <div class="feat-row">
        <div class="ic"><img src="{{ asset('images/landing/feature-maintenance-service-history.webp') }}" alt="Maintenance dan Riwayat Servis"></div>
        <div>
          <div class="fmeta">Maintenance &amp; Riwayat Servis</div>
          <h3>Pantau perbaikan dari pengajuan sampai selesai</h3>
          <p>Ajukan permintaan perbaikan, pantau statusnya, dan lihat seluruh riwayat maintenance tiap aset.</p>
        </div>
      </div>
      <div class="feat-row">
        <div class="ic"><img src="{{ asset('images/landing/feature-import-data.webp') }}" alt="Import Data"></div>
        <div>
          <div class="fmeta">Import Data</div>
          <h3>Migrasi dari Excel hanya sekali upload</h3>
          <p>Pindahkan data aset lama dari spreadsheet ke Vasetra dengan satu kali proses import.</p>
        </div>
      </div>
      <div class="feat-row" style="border-bottom:1px solid var(--line);">
        <div class="ic"><img src="{{ asset('images/landing/feature-user-role-management.webp') }}" alt="Manajemen User dan Role"></div>
        <div>
          <div class="fmeta">Manajemen User &amp; Role</div>
          <h3>Akses yang jelas untuk tim besar maupun multi-cabang</h3>
          <p>Atur siapa yang boleh mengakses data apa, sehingga aman digunakan di seluruh organisasi.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHY VASETRA -->
<section class="why" id="kenapa">
  <div class="wrap">
    <div class="section-head">
      <div class="section-kicker">Kenapa Vasetra</div>
      <h2>Bukan sekadar daftar aset — ini kontrol penuh</h2>
      <p class="lead">Vasetra dibangun untuk perusahaan yang serius mengelola aset, bukan sekadar mencatatnya.</p>
    </div>
    <div class="why-body">
      <div class="why-visual">
        <img src="{{ asset('images/landing/why-vasetra-visual.webp') }}" alt="Kenapa memilih Vasetra">
      </div>
      <div class="why-grid">
        <div class="why-card">
          <div class="wk">Multi-Perusahaan / Cabang</div>
          <h3>Satu akun, banyak entitas bisnis</h3>
          <p>Kelola beberapa perusahaan atau cabang sekaligus tanpa harus berpindah sistem.</p>
        </div>
        <div class="why-card">
          <div class="wk">Siap Audit Kapan Saja</div>
          <h3>Laporan real-time, bukan rekap manual</h3>
          <p>Data selalu siap ditarik saat dibutuhkan, tanpa proses pengumpulan berhari-hari.</p>
        </div>
        <div class="why-card">
          <div class="wk">Akses Berjenjang</div>
          <h3>Role &amp; permission yang jelas</h3>
          <p>Setiap tim hanya melihat dan mengubah data yang relevan dengan tanggung jawabnya.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final" id="cta">
  <div class="wrap">
    <div class="final-card">
      <div class="final-copy">
        <h2>Kelola Aset Perusahaan Lebih Rapi, Mulai Hari Ini</h2>
        <p>Coba Vasetra sekarang dan rasakan bedanya mengelola aset tanpa ribet.</p>
        <div class="final-cta-btns" style="margin-top:26px;">
          <a class="btn btn-gold" href="{{ route('register') }}">Coba Gratis Sekarang</a>
          <a class="btn" style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.3);" href="#">Request Demo</a>
        </div>
      </div>
      <div class="final-visual">
        <img src="{{ asset('images/landing/final-cta-try-now.webp') }}" alt="Coba Vasetra sekarang">
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="brand" style="font-size:0.95rem;">
      <svg class="mark" viewBox="0 0 32 32" fill="none" style="width:24px;height:24px;">
        <rect width="32" height="32" rx="9" fill="#071C33"/>
        <path d="M8 10 L16 22 L24 10" stroke="#18C7C6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Vasetra
    </div>
    <div class="tag">Vasetra — Asset Lifecycle Platform</div>
  </div>
</footer>

</body>
</html>
