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
    document.getElementById('payOrderNum').textContent = data.nomor_pesanan;

    if (bayar === 'qris') {
      document.getElementById('qrisSection').style.display = '';
      document.getElementById('cashSection').style.display = 'none';
      document.getElementById('qrisAmount').textContent = data.total_fmt;
      // startQrisTimer ada di order-system.js
      if (typeof startQrisTimer === 'function') startQrisTimer(600);
    } else {
      document.getElementById('qrisSection').style.display = 'none';
      document.getElementById('cashSection').style.display = '';
      document.getElementById('cashAmount').textContent = data.total_fmt;
      document.getElementById('cashMeja').textContent = meja ? 'Meja ' + meja : 'Kasir';
    }
    openOverlay('paymentOverlay');

    // Kosongkan keranjang — renderOrderCart ada di order-system.js
    orderCart = [];
    if (typeof renderOrderCart === 'function') renderOrderCart();
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
      window.open(`https://wa.me/6281234567890?text=${waMsg}`, '_blank');
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
