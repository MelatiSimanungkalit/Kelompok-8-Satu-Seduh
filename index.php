<?php
// ============================================================
//  SATU SEDUH — Halaman Utama (PHP)
// ============================================================
require_once __DIR__ . '/includes/config.php';

// Ambil data menu dari database
$db = getDB();
$menuData = [];
$stmt = $db->query("SELECT * FROM menu WHERE aktif = 1 ORDER BY kategori, id ASC");
foreach ($stmt->fetchAll() as $row) {
    $menuData[$row['kategori']][] = $row;
}

// Ambil produk
$produkData = $db->query("SELECT * FROM produk WHERE aktif = 1 ORDER BY id ASC")->fetchAll();

// Helper render badge
function renderBadge(?string $badge): string {
    if (!$badge) return '';
    return '<span class="fnb-badge">' . htmlspecialchars($badge) . '</span>';
}

// Helper harga
function hargaFmt(int $n): string {
    return 'IDR ' . number_format($n / 1000, 0) . 'K';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Satu Seduh — Kedai Kopi Premium Medan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <script src="https://unpkg.com/feather-icons"></script>
  <link rel="stylesheet" href="css/style.css"/>
  <link rel="stylesheet" href="css/order-system.css"/>
</head>
<body>

<div class="cursor" id="cur"></div>
<div class="cursor-ring" id="curR"></div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <a href="#home" class="logo">satu<span>seduh</span>.</a>
  <div class="nav-links">
    <a href="#about">Tentang</a>
    <a href="#facilities">Fasilitas</a>
    <a href="#space">Space</a>
    <a href="#fnb">Menu</a>
    <a href="#products">Produk</a>
    <a href="#review">Review</a>
    <a href="#kontak">Kontak</a>
    <a href="#reservasi" class="nav-book">Reservasi</a>
  </div>
  <div class="nav-extra">
    <a id="cartBtn" title="Keranjang">
      <i data-feather="shopping-bag"></i>
      <sup id="cartCount" style="color:var(--gold);font-size:0.7rem;font-weight:700;">0</sup>
    </a>
    <a id="ham"><i data-feather="menu"></i></a>
  </div>
</nav>

<div class="mob-nav" id="mobNav">
  <a href="#about"      onclick="mobNav.classList.remove('open')">Tentang</a>
  <a href="#facilities" onclick="mobNav.classList.remove('open')">Fasilitas</a>
  <a href="#space"      onclick="mobNav.classList.remove('open')">Space</a>
  <a href="#fnb"        onclick="mobNav.classList.remove('open')">Menu</a>
  <a href="#products"   onclick="mobNav.classList.remove('open')">Produk</a>
  <a href="#review"     onclick="mobNav.classList.remove('open')">Review</a>
  <a href="#kontak"     onclick="mobNav.classList.remove('open')">Kontak</a>
  <a href="#reservasi"  onclick="mobNav.classList.remove('open')" style="color:var(--gold)">Reservasi</a>
</div>

<!-- HERO -->
<section id="home">
  <div class="hero-video">
    <video autoplay loop muted playsinline>
      <source src="vid.mp4" type="video/mp4">
    </video>
  </div>
  <div class="bean"></div><div class="bean"></div>
  <div class="bean"></div><div class="bean"></div>
  <div class="hero-inner">
    <div class="hero-tag"><span></span> Kedai Kopi Premium — Medan, Sumatera Utara</div>
    <h1><em>Satu Seduh,</em><strong>Seribu Rasa</strong></h1>
    <p class="hero-sub">Dari biji kopi pilihan Nusantara hingga ruang meeting nyaman — semua hadir dalam satu tempat yang hangat dan berkesan.</p>
    <div class="hero-actions">
      <a href="#fnb" class="btn-gold">Lihat Menu</a>
      <a href="#reservasi" class="btn-ghost">Booking Sekarang</a>
    </div>
  </div>
  <div class="hero-stats">
    <div class="h-stat"><strong>7+</strong><span>Tahun Berdiri</span></div>
    <div class="h-stat"><strong>50+</strong><span>Menu Pilihan</span></div>
    <div class="h-stat"><strong>12K+</strong><span>Pelanggan</span></div>
  </div>
  <div class="scroll-hint"><div class="scroll-line"></div>Scroll untuk eksplorasi</div>
</section>

<!-- TENTANG KAMI -->
<section id="about" class="section">
  <div class="about-img reveal-left">
    <div class="about-frame"></div>
    <img class="about-img-main" src="tentang-kami.jpg" alt="Tentang Satu Seduh"
      onerror="this.src='https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600&q=80'">
    <div class="about-badge"><strong>2018</strong><span>Berdiri Sejak</span></div>
  </div>
  <div class="about-text reveal-right">
    <span class="sec-label">Kisah Kami</span>
    <h2 class="sec-title">Lebih dari Sekadar <em>Secangkir Kopi</em></h2>
    <div class="gold-line"></div>
    <p class="sec-sub">Di Satu Seduh, setiap cangkir kopi menyimpan kisah dan kehangatan. Lahir dari semangat menghadirkan pengalaman ngopi otentik dengan biji kopi terbaik Nusantara — dari Aceh, Toraja, hingga Flores.</p>
    <p class="sec-sub" style="margin-top:1rem;">Kami tidak hanya menyajikan kopi, tapi juga ruang untuk bercerita, berkreasi, dan berkolaborasi. Dengan fasilitas meeting, co-working, dan acara privat — Satu Seduh adalah rumah kedua Anda.</p>
    <div class="about-chips">
      <span class="chip">Arabica Specialty</span><span class="chip">Single Origin</span>
      <span class="chip">Petani Lokal</span><span class="chip">Halal Certified</span>
      <span class="chip">Free Wi-Fi</span>
    </div>
    <div class="about-stats">
      <div class="astat"><strong>50+</strong><span>Menu Pilihan</span></div>
      <div class="astat"><strong>12K+</strong><span>Pelanggan Puas</span></div>
      <div class="astat"><strong>4</strong><span>Ruang Meeting</span></div>
    </div>
  </div>
</section>

<!-- FASILITAS -->
<section id="facilities" class="section">
  <div class="fac-header reveal">
    <span class="sec-label">Apa yang Kami Tawarkan</span>
    <h2 class="sec-title">Fasilitas <em>Lengkap</em></h2>
    <div class="gold-line"></div>
    <p class="sec-sub">Nikmati berbagai fasilitas premium yang dirancang untuk kenyamanan Anda, baik untuk bersantai maupun bekerja.</p>
  </div>
  <div class="fac-grid">
    <?php
    $fasilitas = [
      ['wifi',    'Wi-Fi Kencang',   'Koneksi internet 100 Mbps gratis untuk semua pengunjung tanpa batas waktu.'],
      ['monitor', 'Ruang Meeting',   '4 ruang meeting privat dengan proyektor, AC, dan whiteboard. Kapasitas 4–20 orang.'],
      ['headphones','Live Music',    'Live akustik setiap Jumat & Sabtu malam mulai pukul 19.00 WIB.'],
      ['zap',     'Colokan & USB',   'Setiap meja dilengkapi stop kontak dan port USB untuk kemudahan Anda.'],
      ['camera',  'Photo Corner',    'Spot foto estetis dengan pencahayaan sempurna untuk konten media sosial.'],
      ['map-pin', 'Parkir Luas',     'Area parkir mobil dan motor yang luas, aman, dan mudah diakses.'],
      ['users',   'Area Co-Working', 'Zona kerja tenang dengan kursi ergonomis dan meja panjang untuk tim kecil.'],
      ['gift',    'Venue Acara',     'Tersedia untuk gathering, ulang tahun, peluncuran produk, dan acara korporat.'],
    ];
    foreach ($fasilitas as [$icon, $judul, $desc]):
    ?>
    <div class="fac-card reveal">
      <div class="fac-icon"><i data-feather="<?= $icon ?>"></i></div>
      <h3><?= $judul ?></h3>
      <p><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- SPACE & AREA -->
<section id="space" class="section">
  <div class="space-header reveal">
    <span class="sec-label">Ruang & Area</span>
    <h2 class="sec-title">Pilih <em>Space</em> Idealmu</h2>
    <div class="gold-line"></div>
    <p class="sec-sub">Dari sudut santai hingga ruang meeting formal — semua tersedia di Satu Seduh untuk kebutuhan berbeda Anda.</p>
  </div>
  <div class="space-grid">
    <?php
    $spaces = [
      ['Indoor',      'Cozy Lounge',    'Suasana hangat dengan pencahayaan ambient. Cocok untuk nongkrong santai dan kerja.', '35 orang',       'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=600&q=80'],
      ['Private Room','Meeting Room',   'Ruang meeting ber-AC dengan proyektor Full HD, whiteboard, dan koneksi internet cepat.', '4–20 orang', 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600&q=80'],
      ['Outdoor',     'Teras Hijau',    'Area outdoor dengan tanaman tropis, cocok untuk bersantai di pagi dan sore hari.', '30 orang',        'https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=600&q=80'],
      ['Co-Working',  'Work Zone',      'Area kerja tenang dengan meja panjang, kursi ergonomis, dan akses listrik di setiap meja.', '25 orang', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80'],
      ['Event',       'Venue Privat',   'Area luas untuk gathering, launching produk, seminar, atau ulang tahun spesial Anda.', '100 orang',  'https://images.unsplash.com/photo-1528698827591-e19ccd7bc23d?w=600&q=80'],
      ['Bar Area',    'Coffee Bar',     'Duduk di depan barista dan saksikan seni meracik kopi terbaik secara langsung.', '10 orang',        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=600&q=80'],
    ];
    foreach ($spaces as [$cap, $judul, $desc, $kapasitas, $img]):
    ?>
    <div class="space-card reveal">
      <img src="<?= $img ?>" alt="<?= $judul ?>">
      <div class="space-overlay">
        <span class="space-cap"><?= $cap ?></span>
        <h3><?= $judul ?></h3>
        <p><?= $desc ?></p>
        <span class="tag"><i data-feather="users" style="width:12px;height:12px;"></i>&nbsp; Kapasitas: <?= $kapasitas ?></span>
      </div>
      <a href="#reservasi" class="space-book-btn">Booking</a>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- FOOD & BEVERAGES (dari database) -->
<section id="fnb" class="section">
  <div class="fnb-header">
    <div class="reveal">
      <span class="sec-label">Food & Beverages</span>
      <h2 class="sec-title">Menu <em>Pilihan</em> Kami</h2>
      <div class="gold-line"></div>
    </div>
    <div class="fnb-tabs reveal">
      <button class="tab-btn active" onclick="showTab('kopi',this)">☕ Kopi Premium</button>
      <button class="tab-btn" onclick="showTab('nonkopi',this)">🥤 Non-Kopi</button>
      <button class="tab-btn" onclick="showTab('makanberat',this)">🍽️ Makanan Berat</button>
      <button class="tab-btn" onclick="showTab('makanringan',this)">🥪 Makanan Ringan</button>
    </div>
  </div>

  <?php
  $tabKategori = ['kopi','nonkopi','makanberat','makanringan'];
  $isMinuman   = ['kopi','nonkopi'];

  foreach ($tabKategori as $idx => $kat):
    $items = $menuData[$kat] ?? [];
    $showClass = $idx === 0 ? 'fnb-section show' : 'fnb-section';
  ?>
  <div class="<?= $showClass ?>" id="tab-<?= $kat ?>">
    <?php foreach ($items as $item):
      $imgSrc  = $item['gambar'] ? htmlspecialchars($item['gambar']) : 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&q=80';
      $harga   = hargaFmt((int)$item['harga']);
      $hargaRaw= (int)$item['harga'];
      $namaEsc = htmlspecialchars($item['nama'], ENT_QUOTES);
      $isMin   = in_array($kat, $isMinuman);
      if ($isMin):
        $onclick = "openCustModal({name:'$namaEsc',price:$hargaRaw,img:this.closest('.fnb-card').querySelector('img').src})";
      else:
        $onclick = "addFoodToCart('$namaEsc',$hargaRaw,this.closest('.fnb-card').querySelector('img').src)";
      endif;
    ?>
    <div class="fnb-card reveal">
      <div class="fnb-img-wrap">
        <img src="<?= $imgSrc ?>" alt="<?= $namaEsc ?>">
        <?= renderBadge($item['badge']) ?>
      </div>
      <div class="fnb-body">
        <h3><?= htmlspecialchars($item['nama']) ?></h3>
        <p><?= htmlspecialchars($item['deskripsi'] ?? '') ?></p>
        <div class="fnb-footer">
          <span class="fnb-price"><?= $harga ?></span>
          <div class="fnb-add" onclick="<?= $onclick ?>"><i data-feather="plus"></i></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</section>

<!-- PRODUK UNGGULAN (dari database) -->
<section id="products" class="section">
  <div class="prod-header reveal">
    <div>
      <span class="sec-label">Biji Kopi Pilihan</span>
      <h2 class="sec-title">Produk <em>Unggulan</em></h2>
      <div class="gold-line"></div>
    </div>
  </div>
  <div class="prod-grid">
    <?php foreach ($produkData as $p):
      $imgSrc   = $p['gambar'] ?: 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=400&q=80';
      $harga    = hargaFmt((int)$p['harga']);
      $hargaOld = $p['harga_lama'] ? hargaFmt((int)$p['harga_lama']) : '';
      $namaEsc  = htmlspecialchars($p['nama'], ENT_QUOTES);
      $chipsArr = array_map('trim', explode(',', $p['chips'] ?? ''));
    ?>
    <div class="prod-card reveal"
         data-name="<?= $namaEsc ?>"
         data-price="<?= (int)$p['harga'] ?>"
         data-badge="<?= htmlspecialchars($p['badge'] ?? '') ?>"
         data-stars="<?= htmlspecialchars($p['stars'] ?? '&#9733;&#9733;&#9733;&#9733;&#9733;') ?>"
         data-desc="<?= htmlspecialchars($p['deskripsi'] ?? '', ENT_QUOTES) ?>"
         data-chips="<?= htmlspecialchars($p['chips'] ?? '') ?>"
         data-old="<?= $hargaOld ?>">
      <div class="prod-img-wrap">
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= $namaEsc ?>">
        <?php if ($p['diskon']): ?><span class="prod-discount"><?= htmlspecialchars($p['diskon']) ?></span><?php endif; ?>
        <div class="prod-overlay">
          <a onclick="openProdModal(this)" title="Detail"><i data-feather="eye"></i></a>
          <a onclick="addProdToCart(this)" title="Tambah"><i data-feather="shopping-bag"></i></a>
        </div>
      </div>
      <div class="prod-body">
        <span class="prod-badge"><?= htmlspecialchars($p['badge'] ?? '') ?></span>
        <div class="prod-stars"><?= !empty($p['stars']) ? htmlspecialchars($p['stars']) : '&#9733;&#9733;&#9733;&#9733;&#9733;' ?></div>
        <h3><?= htmlspecialchars($p['nama']) ?></h3>
        <p><?= htmlspecialchars($p['deskripsi'] ?? '') ?></p>
        <div class="prod-footer">
          <div class="prod-price"><?= $harga ?><?= $hargaOld ? " <s>$hargaOld</s>" : '' ?></div>
          <div class="fnb-add" onclick="addProdToCart(this)"><i data-feather="plus"></i></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- RESERVASI -->
<section id="reservasi" class="section">
  <div class="res-wrap">
    <div class="res-left reveal-left">
      <span class="sec-label">Booking</span>
      <h2>Reservasi <em>Space</em> Impianmu</h2>
      <div class="gold-line"></div>
      <p>Rencanakan pertemuan, gathering, atau sesi kerja produktifmu di Satu Seduh. Isi formulir dan tim kami akan menghubungi Anda dalam 30 menit.</p>
      <div class="res-perks">
        <div class="res-perk"><div class="res-perk-icon"><i data-feather="check-circle"></i></div><div class="res-perk-text"><strong>Konfirmasi Cepat</strong><span>Tim kami merespons dalam 30 menit di jam operasional</span></div></div>
        <div class="res-perk"><div class="res-perk-icon"><i data-feather="monitor"></i></div><div class="res-perk-text"><strong>Peralatan Lengkap</strong><span>Proyektor, whiteboard, dan sound system tersedia</span></div></div>
        <div class="res-perk"><div class="res-perk-icon"><i data-feather="coffee"></i></div><div class="res-perk-text"><strong>Welcome Drinks</strong><span>Setiap booking meeting mendapat welcome drink gratis</span></div></div>
        <div class="res-perk"><div class="res-perk-icon"><i data-feather="shield"></i></div><div class="res-perk-text"><strong>Pembatalan Fleksibel</strong><span>Batalkan hingga 24 jam sebelumnya tanpa biaya</span></div></div>
      </div>
    </div>
    <div class="res-form reveal-right">
      <h3>Form Reservasi</h3>
      <p class="form-sub">Isi semua data dengan lengkap untuk proses booking yang lebih cepat.</p>
      <div class="form-group">
        <label>Pilih Ruangan</label>
        <div class="room-select">
          <div class="room-opt sel" onclick="selRoom(this)"><strong>Meeting</strong>4–20 org</div>
          <div class="room-opt" onclick="selRoom(this)"><strong>Co-Work</strong>1–25 org</div>
          <div class="room-opt" onclick="selRoom(this)"><strong>Venue</strong>hingga 100</div>
          <div class="room-opt" onclick="selRoom(this)"><strong>Outdoor</strong>1–30 org</div>
          <div class="room-opt" onclick="selRoom(this)"><strong>Bar Area</strong>1–10 org</div>
          <div class="room-opt" onclick="selRoom(this)"><strong>Lounge</strong>1–35 org</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Nama Lengkap</label><input type="text" id="resNama" placeholder="Nama Anda"></div>
        <div class="form-group"><label>No. WhatsApp</label><input type="tel" id="resWa" placeholder="+62 8xx xxxx xxxx"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Tanggal</label><input type="date" id="resTgl"></div>
        <div class="form-group">
          <label>Waktu Mulai</label>
          <div style="display:flex;gap:6px;align-items:center;">
            <select id="resWaktuJam" style="flex:1;padding:10px 8px;background:#1a1209;color:#f5e6c8;border:1px solid #5a3e1b;border-radius:8px;font-size:1rem;">
              <option value="">Jam</option>
              <?php for ($h=6; $h<=22; $h++): ?><option><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?>
            </select>
            <span style="color:#c9a84c;font-weight:bold;">:</span>
            <select id="resWaktuMenit" style="flex:1;padding:10px 8px;background:#1a1209;color:#f5e6c8;border:1px solid #5a3e1b;border-radius:8px;font-size:1rem;">
              <option value="">Menit</option>
              <option>00</option><option>15</option><option>30</option><option>45</option>
            </select>
          </div>
          <input type="hidden" id="resWaktu">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Durasi</label>
          <select id="resDurasi">
            <option>1 Jam</option><option>2 Jam</option><option>3 Jam</option>
            <option>4 Jam</option><option>Seharian (8 Jam)</option>
          </select>
        </div>
        <div class="form-group"><label>Jumlah Orang</label><input type="number" id="resOrang" placeholder="contoh: 10" min="1"></div>
      </div>
      <div class="form-group"><label>Keperluan / Catatan</label><textarea rows="3" id="resCatatan" placeholder="Contoh: Butuh proyektor, minta layout U-shape..."></textarea></div>
      <button class="btn-res" onclick="submitReservasi(event)">Kirim Reservasi →</button>
    </div>
  </div>
</section>

<!-- REVIEW -->
<section id="review" class="section">
  <div class="review-header reveal">
    <span class="sec-label">Kata Mereka</span>
    <h2 class="sec-title">Customer <em>Review</em></h2>
    <div class="gold-line"></div>
    <p class="sec-sub">Ribuan pelanggan telah merasakan kehangatan Satu Seduh. Ini kata mereka.</p>
  </div>
  <div style="overflow:hidden;">
    <div class="review-track" id="revTrack">
      <?php
      $reviews = [
        ['AR','Andi Rizky','Product Manager · Medan','★★★★★','Satu Seduh bukan hanya kafe biasa. Kopinya benar-benar premium, dan suasananya bikin betah berlama-lama. Meeting room-nya sangat profesional!'],
        ['SN','Sarah Nadia','Freelance Designer · Medan','★★★★★','Cold Brew Signature-nya luar biasa! Diseduh 16 jam dan rasanya memang beda. Sering ke sini buat kerja karena tempatnya tenang dan kopinya enak.'],
        ['DP','Dian Pratiwi','Event Organizer · Medan','★★★★☆','Venue untuk launching produk kami kemarin keren banget. Tim Satu Seduh sangat helpful dari layout sampai sound system. Tamu kami sangat berkesan!'],
        ['MF','Muhammad Faiz','Food Blogger · Medan','★★★★★','Nasi Goreng nya harus dicoba! Rasanya unik banget — ada yang subtle tapi bikin nasi goreng ini beda dari yang lain. Wajib balik!'],
        ['LH','Lisa Handayani','Content Creator · Medan','★★★★★','Tempat favoritku buat kerja dari pagi sampai sore. Pour Overnya konsisten enak, dan baristanya sudah hafal pesananku. Pelayanan sangat personal!'],
        ['RW','Ryan Wicaksono','Startup Founder · Medan','★★★★★','Booking meeting room di sini sangat mudah. Langsung direspons dalam 15 menit, proses konfirmasinya simpel. Highly recommended untuk bisnis!'],
      ];
      foreach ($reviews as [$inisial, $nama, $jabatan, $stars, $teks]):
      ?>
      <div class="rev-card">
        <div class="rev-stars"><?= $stars ?></div>
        <p class="rev-text">"<?= htmlspecialchars($teks) ?>"</p>
        <div class="rev-author">
          <div class="rev-avatar"><?= $inisial ?></div>
          <div class="rev-author-info"><strong><?= htmlspecialchars($nama) ?></strong><span><?= htmlspecialchars($jabatan) ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="rev-nav">
    <button class="rev-btn" id="revPrev"><i data-feather="chevron-left"></i></button>
    <div class="rev-dots" id="revDots"></div>
    <button class="rev-btn" id="revNext"><i data-feather="chevron-right"></i></button>
  </div>
</section>

<!-- KONTAK & LOKASI -->
<section id="kontak" class="section">
  <div class="kontak-grid">
    <div class="reveal-left">
      <span class="sec-label">Temukan Kami</span>
      <h2 class="sec-title">Kontak & <em>Lokasi</em></h2>
      <div class="gold-line"></div>
      <p class="sec-sub">Ada pertanyaan atau ingin reservasi langsung? Kami siap membantu Anda setiap saat.</p>
      <div class="kontak-info">
        <div class="k-item"><div class="k-icon"><i data-feather="map-pin"></i></div><div class="k-text"><strong>Alamat</strong><span>Jl. Dr. Mansyur No.119, Padang Bulan, Medan Selayang, Kota Medan, Sumatera Utara 20143</span></div></div>
        <div class="k-item"><div class="k-icon"><i data-feather="phone"></i></div><div class="k-text"><strong>WhatsApp</strong><span>+628137113082</span></div></div>
        <div class="k-item"><div class="k-icon"><i data-feather="mail"></i></div><div class="k-text"><strong>Email</strong><span>hello@satuseduh.id</span></div></div>
        <div class="k-item"><div class="k-icon"><i data-feather="clock"></i></div><div class="k-text"><strong>Jam Operasional</strong><span>Senin – Minggu: 07.00 – 22.00 WIB</span></div></div>
      </div>
      <iframe class="map-frame"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.091063655249!2d98.65581857496845!3d3.567679550332678!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x302e68dce3e1f7b7%3A0x9b84d0cbef799d4b!2sJl.%20Dr.%20Mansyur%20No.119!5e0!3m2!1sid!2sid!4v1731145670000!5m2!1sid!2sid"
        allowfullscreen loading="lazy"></iframe>
    </div>
    <div class="kontak-form reveal-right">
      <h3>Kirim Komentar</h3>
      <p class="form-sub">Isi form dan kami akan segera menghubungi Anda.</p>
      <div class="form-group"><label>Nama</label><input type="text" id="komNama" placeholder="Nama lengkap Anda"></div>
      <div class="form-group"><label>Email</label><input type="email" id="komEmail" placeholder="email@anda.com"></div>
      <div class="form-group"><label>No. HP</label><input type="tel" id="komHp" placeholder="+62 8xx xxxx xxxx"></div>
      <div class="form-group"><label>Komentar</label><textarea rows="4" id="komPesan" placeholder="Tuliskan komentar Anda..."></textarea></div>
      <button class="btn-res" onclick="kirimKomentar(event)">Kirim Komentar →</button>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="foot-top">
    <div class="foot-brand">
      <a href="#home" class="logo">satu<span>seduh</span>.</a>
      <p>Kedai kopi premium Medan yang menghadirkan cita rasa Nusantara terbaik dengan fasilitas lengkap dan suasana yang hangat.</p>
      <div class="foot-socials">
        <a href="#"><i data-feather="instagram"></i></a>
        <a href="#"><i data-feather="twitter"></i></a>
        <a href="#"><i data-feather="facebook"></i></a>
        <a href="#"><i data-feather="youtube"></i></a>
      </div>
    </div>
    <div class="foot-col">
      <h4>Navigasi</h4>
      <a href="#home">Home</a><a href="#about">Tentang Kami</a><a href="#facilities">Fasilitas</a>
      <a href="#space">Space & Area</a><a href="#fnb">Food & Beverages</a>
      <a href="#products">Produk</a><a href="#reservasi">Reservasi</a><a href="#kontak">Kontak</a>
    </div>
    <div class="foot-col">
      <h4>Menu Unggulan</h4>
      <a href="#fnb">Espresso</a><a href="#fnb">Cold Brew Signature</a><a href="#fnb">Pour Over</a>
      <a href="#fnb">Nasi Goreng</a><a href="#fnb">Truffle Fries</a><a href="#fnb">Banana Coffee Cake</a>
    </div>
    <div class="foot-col">
      <h4>Jam Buka</h4>
      <a>Senin – Jumat</a>
      <a style="color:rgba(255,255,255,0.4);font-size:0.78rem;">07.00 – 22.00 WIB</a>
      <a>Sabtu – Minggu</a>
      <a style="color:rgba(255,255,255,0.4);font-size:0.78rem;">08.00 – 23.00 WIB</a>
      <a href="tel:+628137113082" style="margin-top:1rem;color:var(--gold);">+628137113082</a>
    </div>
  </div>
  <div class="foot-bot">
    <span>Satu Seduh. Dibuat dengan ☕ oleh <strong>satuseduh||team</strong></span>
    <span>Medan, Sumatera Utara · Indonesia</span>
  </div>
</footer>

<!-- CART SIDEBAR -->
<div class="cart-sb" id="cartSb">
  <div class="cart-hd">
    <h3>Keranjang Pesanan</h3>
    <span class="cart-hd-close" id="cartClose"><i data-feather="x"></i></span>
  </div>
  <div class="cart-body" id="cartBody">
    <p class="cart-empty-msg">Keranjang masih kosong ☕</p>
  </div>
  <div class="cart-ft">
    <div class="cart-tot"><span>Total</span><span id="cartTotal">IDR 0</span></div>
    <button class="cart-checkout-btn" onclick="openCheckout()">Checkout Sekarang →</button>
  </div>
</div>

<!-- PRODUCT MODAL -->
<div class="prod-modal-overlay" id="prodModal">
  <div class="prod-modal">
    <span class="prod-modal-close" onclick="closeModal()"><i data-feather="x"></i></span>
    <div class="prod-modal-img"><img id="modalImg" src="" alt=""></div>
    <div class="prod-modal-body">
      <span class="prod-modal-badge" id="modalBadge"></span>
      <div class="prod-modal-stars" id="modalStars"></div>
      <h2 id="modalName"></h2>
      <p class="prod-modal-desc" id="modalDesc"></p>
      <div class="prod-modal-origin" id="modalChips"></div>
      <div class="prod-modal-price-row">
        <div class="prod-modal-price" id="modalPrice"></div>
        <button class="prod-modal-add" id="modalAddBtn">Tambah ke Keranjang</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST & BACKDROP -->
<div class="toast" id="toast"><i data-feather="check-circle"></i><span id="toastMsg"></span></div>
<div class="backdrop" id="backdrop"></div>

<!-- CUSTOMIZE MODAL (Minuman) -->
<div class="ss-overlay" id="custOverlay">
  <div class="ss-modal">
    <div class="cust-hero">
      <img id="custHeroImg" src="" alt="">
      <span class="ss-close" onclick="closeOverlay('custOverlay')"><i data-feather="x"></i></span>
      <div class="cust-hero-info"><h3 id="custItemName"></h3><span class="base-price" id="custBasePrice"></span></div>
    </div>
    <div class="ss-body">
      <div class="cust-sec"><div class="cust-sec-label">🌡️ Temperature</div><div class="cust-opts">
        <button class="cust-opt" data-group="temp" data-val="Hot" onclick="pickOpt(this,'temp','Hot')">☕ Hot</button>
        <button class="cust-opt sel" data-group="temp" data-val="Iced" onclick="pickOpt(this,'temp','Iced')">🧊 Iced</button>
      </div></div>
      <div class="cust-sec" id="iceSection"><div class="cust-sec-label cust-ice-label">🧊 Ice Level</div><div class="cust-opts">
        <button class="cust-opt sel" data-group="ice" data-val="Normal Ice" onclick="pickOpt(this,'ice','Normal Ice')">Normal</button>
        <button class="cust-opt" data-group="ice" data-val="Less Ice" onclick="pickOpt(this,'ice','Less Ice')">Less Ice</button>
        <button class="cust-opt" data-group="ice" data-val="No Ice" onclick="pickOpt(this,'ice','No Ice')">No Ice</button>
      </div></div>
      <div class="cust-sec"><div class="cust-sec-label">🍬 Sugar Level</div><div class="cust-opts">
        <button class="cust-opt sel" data-group="sugar" data-val="Normal Sugar" onclick="pickOpt(this,'sugar','Normal Sugar')">Normal</button>
        <button class="cust-opt" data-group="sugar" data-val="Less Sugar" onclick="pickOpt(this,'sugar','Less Sugar')">Less Sugar</button>
        <button class="cust-opt" data-group="sugar" data-val="No Sugar" onclick="pickOpt(this,'sugar','No Sugar')">No Sugar</button>
      </div></div>
      <div class="cust-sec"><div class="cust-sec-label">📏 Size</div><div class="cust-opts">
        <button class="cust-opt sel" data-group="size" data-val="Regular" onclick="pickOpt(this,'size','Regular')">Regular (12oz)</button>
        <button class="cust-opt" data-group="size" data-val="Large" onclick="pickOpt(this,'size','Large')">Large (16oz) +5K</button>
      </div></div>
      <div class="cust-sec"><div class="cust-sec-label">✨ Add-ons (Opsional)</div>
        <div class="addon-grid">
          <button class="addon-card" onclick="toggleAddon(this,'Caramel Drizzle',3000)"><strong>Caramel Drizzle</strong><em>+IDR 3K</em></button>
          <button class="addon-card" onclick="toggleAddon(this,'Whipped Cream',5000)"><strong>Whipped Cream</strong><em>+IDR 5K</em></button>
          <button class="addon-card" onclick="toggleAddon(this,'Ice Cream',8000)"><strong>Ice Cream</strong><em>+IDR 8K</em></button>
          <button class="addon-card" onclick="toggleAddon(this,'Extra Shot',5000)"><strong>Extra Shot</strong><em>+IDR 5K</em></button>
          <button class="addon-card" onclick="toggleAddon(this,'Oat Milk',7000)"><strong>Oat Milk</strong><em>+IDR 7K</em></button>
          <button class="addon-card" onclick="toggleAddon(this,'Vanilla Syrup',3000)"><strong>Vanilla Syrup</strong><em>+IDR 3K</em></button>
        </div>
      </div>
    </div>
    <div class="ss-footer">
      <div class="cust-price-row"><span>Total Harga</span><span class="cust-price-val" id="custTotalPrice">IDR 0</span></div>
      <button class="cust-add-btn" onclick="addCustToCart()"><i data-feather="shopping-bag"></i> Masukkan ke Keranjang</button>
    </div>
  </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="ss-overlay" id="checkoutOverlay">
  <div class="ss-modal">
    <div class="ss-hd"><h3>Detail Pesanan</h3><span class="ss-close" onclick="closeOverlay('checkoutOverlay')"><i data-feather="x"></i></span></div>
    <div class="ss-body">
      <div class="co-sec-label">📋 Ringkasan Pesanan</div>
      <div class="co-items" id="coItemList"></div>
      <div class="co-subtotal"><span>Subtotal</span><span id="coSubtotal">IDR 0</span></div>
      <div class="co-total"><span>Total Pembayaran</span><span id="coTotal">IDR 0</span></div>
      <div class="co-sec-label">👤 Informasi Pemesan</div>
      <div class="co-field"><label>Nama Pemesan</label><input type="text" id="coNama" placeholder="Nama lengkap Anda"></div>
      <div class="co-row">
        <div class="co-field"><label>Nomor Telepon</label><input type="tel" id="coTelp" placeholder="+62 8xx xxxx xxxx"></div>
        <div class="co-field"><label>Nomor Meja</label><input type="text" id="coMeja" placeholder="contoh: 7"></div>
      </div>
      <div class="co-field"><label>Catatan (Opsional)</label><textarea id="coCatatan" rows="2" placeholder="Misal: jangan terlalu manis, alergi kacang..."></textarea></div>
      <div class="co-sec-label">💳 Metode Pembayaran</div>
      <div class="pay-opts">
        <div class="pay-opt sel" data-pay="qris" onclick="selectPayMethod(this,'qris')"><div class="pay-icon">📱</div><strong>QRIS</strong><span>GoPay, OVO, Dana, dll</span></div>
        <div class="pay-opt" data-pay="cash" onclick="selectPayMethod(this,'cash')"><div class="pay-icon">💵</div><strong>Tunai (Cash)</strong><span>Bayar ke kasir</span></div>
      </div>
    </div>
    <div class="ss-footer"><button class="checkout-btn" onclick="submitCheckout()">Checkout Sekarang →</button></div>
  </div>
</div>

<!-- PAYMENT MODAL -->
<div class="ss-overlay" id="paymentOverlay">
  <div class="ss-modal payment-modal">
    <span class="ss-close" onclick="closeOverlay('paymentOverlay')"><i data-feather="x"></i></span>
    <div class="pay-status">
      <div class="pay-status-badge"><div class="pay-status-dot"></div> Menunggu Pembayaran</div>
      <h3>Konfirmasi Pembayaran</h3>
      <p>Selesaikan pembayaran untuk memproses pesanan Anda.</p>
    </div>
    <div id="qrisSection">
      <div class="qris-box">
        <div class="qris-logo"><span>QRIS <small>by</small></span><span style="color:#0070ba;font-weight:800;">GoPay</span></div>
        <div class="qris-amount" id="qrisAmount">IDR 0</div>
        <div class="qris-img">
          <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="200" fill="white"/>
            <rect x="10" y="10" width="60" height="60" rx="4" fill="#111"/><rect x="16" y="16" width="48" height="48" rx="2" fill="white"/><rect x="22" y="22" width="36" height="36" rx="1" fill="#111"/>
            <rect x="130" y="10" width="60" height="60" rx="4" fill="#111"/><rect x="136" y="16" width="48" height="48" rx="2" fill="white"/><rect x="142" y="22" width="36" height="36" rx="1" fill="#111"/>
            <rect x="10" y="130" width="60" height="60" rx="4" fill="#111"/><rect x="16" y="136" width="48" height="48" rx="2" fill="white"/><rect x="22" y="142" width="36" height="36" rx="1" fill="#111"/>
            <rect x="80" y="10" width="8" height="8" fill="#111"/><rect x="90" y="10" width="8" height="8" fill="#111"/><rect x="110" y="10" width="8" height="8" fill="#111"/>
            <rect x="80" y="80" width="8" height="8" fill="#111"/><rect x="100" y="80" width="8" height="8" fill="#111"/><rect x="120" y="80" width="8" height="8" fill="#111"/><rect x="140" y="80" width="8" height="8" fill="#111"/><rect x="160" y="80" width="8" height="8" fill="#111"/>
            <rect x="80" y="130" width="8" height="8" fill="#111"/><rect x="100" y="130" width="8" height="8" fill="#111"/><rect x="120" y="130" width="8" height="8" fill="#111"/>
            <rect x="80" y="150" width="8" height="8" fill="#111"/><rect x="110" y="150" width="8" height="8" fill="#111"/><rect x="80" y="170" width="8" height="8" fill="#111"/>
          </svg>
        </div>
        <p>Scan QR di atas menggunakan aplikasi dompet digital Anda</p>
        <p style="font-size:0.7rem;color:#999;margin-top:0.3rem;">GoPay · OVO · Dana · LinkAja · ShopeePay</p>
      </div>
      <div class="qris-timer"><i data-feather="clock" style="width:14px;height:14px;"></i> Kadaluarsa dalam <strong id="qrisCountdown">10:00</strong></div>
    </div>
    <div id="cashSection" style="display:none;">
      <div class="cash-box">
        <div class="cash-info"><div class="cash-icon">💵</div><h4>Bayar di Kasir</h4><p>Tunjukkan nomor pesanan kepada kasir</p></div>
        <div class="cash-amount" id="cashAmount">IDR 0</div>
        <div class="cash-note">📍 Silakan menuju kasir dan tunjukkan nomor pesanan Anda.<br>🪑 Pesanan akan diantar ke <strong id="cashMeja">Meja -</strong> setelah pembayaran dikonfirmasi.</div>
      </div>
    </div>
    <div class="pay-order-num">No. Pesanan: <strong id="payOrderNum">-</strong></div>
    <div class="pay-waiting-bar"><div class="pay-waiting-spinner"></div><span>Menunggu Pembayaran...</span></div>
  </div>
</div>

<!-- RESERVASI CONFIRM MODAL -->
<div class="ss-overlay" id="resConfirmOverlay">
  <div class="ss-modal res-confirm-modal">
    <span class="ss-close" onclick="closeOverlay('resConfirmOverlay')"><i data-feather="x"></i></span>
    <div class="res-confirm-status">
      <div class="res-confirm-icon">📅</div>
      <div class="res-confirm-badge"><div class="dot"></div> Menunggu Konfirmasi</div>
      <h3>Reservasi Terkirim!</h3>
      <p>Permintaan reservasi Anda telah diterima. Tim Satu Seduh akan menghubungi Anda dalam <strong style="color:var(--gold);">30 menit</strong> untuk konfirmasi.</p>
    </div>
    <div class="res-detail-box">
      <div class="res-detail-row"><span>Ruangan</span><span id="rcRuangan">-</span></div>
      <div class="res-detail-row"><span>Nama</span><span id="rcNama">-</span></div>
      <div class="res-detail-row"><span>WhatsApp</span><span id="rcWa">-</span></div>
      <div class="res-detail-row"><span>Tanggal</span><span id="rcTanggal">-</span></div>
      <div class="res-detail-row"><span>Waktu & Durasi</span><span id="rcWaktu">-</span></div>
      <div class="res-detail-row"><span>Jumlah Tamu</span><span id="rcOrang">-</span></div>
      <div class="res-detail-row"><span>Catatan</span><span id="rcCatatan">-</span></div>
    </div>
    <div class="res-note-box"><strong>⚠️ Informasi Penting</strong> Reservasi belum terkonfirmasi sampai Anda mendapat balasan dari tim kami.</div>
    <div class="res-action-btns">
      <button class="res-waba-btn" id="rcWaBtn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Kirim via WhatsApp
      </button>
      <button class="res-print-btn" id="rcPrintBtn" onclick="cetakBuktiReservasi()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Cetak / Simpan Bukti
      </button>
    </div>
    <button class="res-close-btn" onclick="closeOverlay('resConfirmOverlay')">Tutup</button>
  </div>
</div>

<script src="js/main.js"></script>
<script src="js/order-system.js"></script>
<script src="js/app.js"></script>
</body>
</html>
