/* ============================================================
   SATU SEDUH — app.js
   Integrasi frontend ke PHP API (menggantikan alert simulasi)
   ============================================================ */

/* ── Override submitCheckout: kirim ke API pesanan.php ── */
window.submitCheckout = async function () {
  const nama  = document.getElementById('coNama')?.value.trim();
  const telp  = document.getElementById('coTelp')?.value.trim();
  const meja  = document.getElementById('coMeja')?.value.trim();
  const cat   = document.getElementById('coCatatan')?.value.trim();
  const payEl = document.querySelector('.pay-opt.sel');
  const bayar = payEl?.dataset.pay || 'cash';

  // Validasi wajib: hanya nama yang harus ada
  if (!nama) { showToast('Nama pemesan wajib diisi!'); return; }
  if (!orderCart.length) { showToast('Keranjang masih kosong!'); return; }

  // Normalisasi nomor telepon: izinkan 08xxx dan +628xxx dll
  const telpNorm = telp.replace(/[\s\-().]/g, '');

  // Siapkan payload
  const items = orderCart.map(item => ({
    name:  item.name,
    price: item.price,
    qty:   item.qty || 1,
    type:  item.type || 'menu',
    meta:  Array.isArray(item.meta) ? item.meta.join(', ') : (item.meta || ''),
  }));

  // Tentukan base URL agar fetch tidak salah path
  const baseUrl = (function() {
    const scripts = document.querySelectorAll('script[src]');
    for (const s of scripts) {
      const m = s.src.match(/^(https?:\/\/[^/]+\/[^/]+)\//);
      if (m) return m[1];
    }
    // fallback: ambil dari pathname
    const parts = location.pathname.split('/');
    parts.pop(); // hapus file terakhir (index.php)
    return location.origin + parts.join('/');
  })();

  try {
    const res = await fetch(baseUrl + '/api/pesanan.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ nama, telepon: telpNorm, meja, catatan: cat, metode_bayar: bayar, items }),
    });

    if (!res.ok) {
      showToast('Server error: ' + res.status + '. Coba lagi.');
      return;
    }

    const data = await res.json();

    if (!data.success) {
      showToast(data.message || 'Gagal membuat pesanan.');
      return;
    }

    // ── Pesanan berhasil ──
    closeOverlay('checkoutOverlay');
    orderCart = [];
    if (typeof renderOrderCart === 'function') renderOrderCart();

    if (data.redirect_url) {
        window.location.href = data.redirect_url;
        return;
    }
    showToast('✓ Pesanan berhasil! No: ' + data.nomor_pesanan);

  } catch (err) {
    console.error('Checkout error:', err);
    showToast('Koneksi ke server gagal. Pastikan server PHP berjalan.');
  }
};

/* ── Override submitReservasi: kirim ke API reservasi.php ── */
window.submitReservasi = async function (e) {
  e.preventDefault();

  const jam    = document.getElementById('resWaktuJam')?.value;
  const menit  = document.getElementById('resWaktuMenit')?.value;
  const waktu  = (jam && menit) ? `${jam}:${menit}` : '';

  const payload = {
    nama:         document.getElementById('resNama')?.value.trim(),
    whatsapp:     document.getElementById('resWa')?.value.trim(),
    ruangan:      document.querySelector('.room-opt.sel strong')?.textContent.trim() || '',
    tanggal:      document.getElementById('resTgl')?.value,
    waktu,
    durasi:       document.getElementById('resDurasi')?.value,
    jumlah_orang: parseInt(document.getElementById('resOrang')?.value) || 0,
    catatan:      document.getElementById('resCatatan')?.value.trim(),
  };

  if (!payload.nama || !payload.whatsapp || !payload.tanggal || !waktu) {
    showToast('Harap lengkapi semua field wajib!');
    return;
  }

  // Tentukan base URL
  const baseUrlRes = (function() {
    const parts = location.pathname.split('/');
    parts.pop();
    return location.origin + parts.join('/');
  })();

  try {
    const res  = await fetch(baseUrlRes + '/api/reservasi.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();

    if (!data.success) {
      showToast(data.message || 'Gagal mengirim reservasi.');
      return;
    }

    // Tampilkan confirm modal
    const d = data.data;
    document.getElementById('rcRuangan').textContent = d.ruangan || '-';
    document.getElementById('rcNama').textContent    = d.nama || '-';
    document.getElementById('rcWa').textContent      = d.whatsapp || '-';
    document.getElementById('rcTanggal').textContent = formatTanggal(d.tanggal);
    document.getElementById('rcWaktu').textContent   = `${d.waktu} · ${d.durasi || '-'}`;
    document.getElementById('rcOrang').textContent   = d.jumlah_orang ? d.jumlah_orang + ' orang' : '-';
    document.getElementById('rcCatatan').textContent = d.catatan || '-';

    // Tombol WA
    const waMsg = encodeURIComponent(
      `Halo Satu Seduh! Saya ingin reservasi:\n` +
      `Ruangan: ${d.ruangan}\nNama: ${d.nama}\nTanggal: ${formatTanggal(d.tanggal)}\nWaktu: ${d.waktu}\nDurasi: ${d.durasi}\nJumlah: ${d.jumlah_orang} orang\nCatatan: ${d.catatan || '-'}`
    );
    document.getElementById('rcWaBtn').onclick = () => {
      window.open(`https://wa.me/628137113082?text=${waMsg}`, '_blank');
    };

    openOverlay('resConfirmOverlay');
    showToast('Reservasi berhasil dikirim!');

  } catch (err) {
    console.error('Reservasi error:', err);
    showToast('Koneksi ke server gagal. Pastikan server PHP berjalan.');
  }
};

/* ── Override kirimKomentar: kirim ke API komentar.php ── */
window.kirimKomentar = async function (e) {
  e.preventDefault();

  const payload = {
    nama:  document.getElementById('komNama')?.value.trim(),
    email: document.getElementById('komEmail')?.value.trim(),
    no_hp: document.getElementById('komHp')?.value.trim(),
    pesan: document.getElementById('komPesan')?.value.trim(),
  };

  if (!payload.nama || !payload.pesan) {
    showToast('Nama dan komentar wajib diisi!');
    return;
  }

  // Tentukan base URL
  const baseUrlKom = (function() {
    const parts = location.pathname.split('/');
    parts.pop();
    return location.origin + parts.join('/');
  })();

  try {
    const res  = await fetch(baseUrlKom + '/api/komentar.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Komentar terkirim!');
      // Reset form
      ['komNama','komEmail','komHp','komPesan'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
    } else {
      showToast(data.message || 'Gagal mengirim komentar.');
    }
  } catch (err) {
    console.error('Komentar error:', err);
    showToast('Koneksi ke server gagal. Pastikan server PHP berjalan.');
  }
};

/* ── Helper: format tanggal ── */
function formatTanggal(str) {
  if (!str) return '-';
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

/* ── Helper: showToast (jika belum ada di main.js) ── */
if (typeof showToast === 'undefined') {
  window.showToast = function(msg) {
    const t = document.getElementById('toast');
    const m = document.getElementById('toastMsg');
    if (!t || !m) return;
    m.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  };
}

/* ── Cetak / Simpan Bukti Reservasi ── */
window.cetakBuktiReservasi = function () {
  const ruangan  = document.getElementById('rcRuangan')?.textContent || '-';
  const nama     = document.getElementById('rcNama')?.textContent    || '-';
  const wa       = document.getElementById('rcWa')?.textContent      || '-';
  const tanggal  = document.getElementById('rcTanggal')?.textContent || '-';
  const waktu    = document.getElementById('rcWaktu')?.textContent   || '-';
  const orang    = document.getElementById('rcOrang')?.textContent   || '-';
  const catatan  = document.getElementById('rcCatatan')?.textContent || '-';

  const noRef = 'SS-' + Date.now().toString(36).toUpperCase().slice(-6);
  const cetakWaktu = new Date().toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });

  const html = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Reservasi - Satu Seduh</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f5f0e8;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      padding: 30px 16px;
    }
    .card {
      background: #fff;
      width: 100%;
      max-width: 480px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    }
    .header {
      background: #1a1209;
      color: #c9a84c;
      text-align: center;
      padding: 28px 24px 22px;
    }
    .header .logo-text {
      font-size: 1.6rem;
      font-weight: 700;
      letter-spacing: 2px;
      color: #c9a84c;
      text-transform: uppercase;
    }
    .header .tagline {
      font-size: 0.72rem;
      color: #a0825a;
      letter-spacing: 3px;
      text-transform: uppercase;
      margin-top: 3px;
    }
    .status-bar {
      background: #c9a84c;
      color: #1a1209;
      text-align: center;
      padding: 10px;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
    }
    .body {
      padding: 24px;
    }
    .title-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 18px;
      padding-bottom: 16px;
      border-bottom: 1.5px dashed #e0d5c0;
    }
    .title-row h2 {
      font-size: 1.15rem;
      color: #1a1209;
      font-weight: 700;
    }
    .title-row .no-ref {
      font-size: 0.72rem;
      color: #888;
      text-align: right;
    }
    .title-row .no-ref strong {
      display: block;
      font-size: 0.82rem;
      color: #c9a84c;
      font-family: monospace;
      letter-spacing: 1px;
    }
    table.detail {
      width: 100%;
      border-collapse: collapse;
    }
    table.detail tr td {
      padding: 9px 4px;
      font-size: 0.875rem;
      border-bottom: 1px solid #f0ebe0;
      vertical-align: top;
    }
    table.detail tr:last-child td {
      border-bottom: none;
    }
    table.detail td:first-child {
      color: #8a7560;
      width: 42%;
      font-weight: 500;
    }
    table.detail td:last-child {
      color: #1a1209;
      font-weight: 600;
      text-align: right;
    }
    .note-box {
      background: #fffbf0;
      border: 1px solid #e8d89a;
      border-radius: 8px;
      padding: 12px 14px;
      margin-top: 20px;
      font-size: 0.78rem;
      color: #7a6430;
      line-height: 1.5;
    }
    .note-box strong {
      display: block;
      margin-bottom: 4px;
      color: #b8862e;
    }
    .footer {
      background: #1a1209;
      color: #a0825a;
      text-align: center;
      padding: 14px 20px;
      font-size: 0.7rem;
      line-height: 1.7;
    }
    .footer strong {
      color: #c9a84c;
    }
    .print-meta {
      text-align: center;
      font-size: 0.68rem;
      color: #bbb;
      margin-top: 16px;
      padding-top: 14px;
      border-top: 1px dashed #e0d5c0;
    }
    @media print {
      body { background: white; padding: 0; }
      .card { box-shadow: none; border-radius: 0; max-width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <div class="logo-text">Satu Seduh</div>
      <div class="tagline">Coffee &amp; Co-Working Space</div>
    </div>
    <div class="status-bar">⏳ Menunggu Konfirmasi</div>
    <div class="body">
      <div class="title-row">
        <h2>Bukti Reservasi</h2>
        <div class="no-ref">
          No. Referensi<strong>${noRef}</strong>
        </div>
      </div>
      <table class="detail">
        <tr><td>Ruangan</td><td>${ruangan}</td></tr>
        <tr><td>Nama</td><td>${nama}</td></tr>
        <tr><td>WhatsApp</td><td>${wa}</td></tr>
        <tr><td>Tanggal</td><td>${tanggal}</td></tr>
        <tr><td>Waktu &amp; Durasi</td><td>${waktu}</td></tr>
        <tr><td>Jumlah Tamu</td><td>${orang}</td></tr>
        <tr><td>Catatan</td><td>${catatan}</td></tr>
      </table>
      <div class="note-box">
        <strong>⚠️ Informasi Penting</strong>
        Reservasi ini <em>belum terkonfirmasi</em>. Tim Satu Seduh akan menghubungi Anda melalui WhatsApp dalam <strong>30 menit</strong> untuk konfirmasi.
      </div>
      <div class="print-meta">
        Dicetak: ${cetakWaktu}
      </div>
    </div>
    <div class="footer">
      <strong>Satu Seduh</strong><br>
      Jl. Contoh No. 1, Medan · WA: 0813-7113-082<br>
      Dokumen ini sebagai tanda terima permintaan reservasi.
    </div>
  </div>

</body>
</html>`;

  // Gunakan Blob + iframe tersembunyi — tidak perlu popup, tidak diblokir browser
  const blob = new Blob([html], { type: 'text/html' });
  const url  = URL.createObjectURL(blob);

  // Hapus iframe lama kalau ada
  const old = document.getElementById('_buktiFrame');
  if (old) old.remove();

  const iframe = document.createElement('iframe');
  iframe.id    = '_buktiFrame';
  iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;';
  iframe.src   = url;
  document.body.appendChild(iframe);

  iframe.onload = function () {
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      // Fallback: buka di tab baru jika iframe diblokir (misal file:// protocol)
      window.open(url, '_blank');
    }
    setTimeout(() => { URL.revokeObjectURL(url); }, 60000);
  };
};
