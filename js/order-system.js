/* ════════════════════════════════════════════════
   ORDER SYSTEM — Satu Seduh
   1. Customize Modal (minuman kopi & non-kopi)
   2. Simple Add (makanan)
   3. Checkout Modal
   4. Payment Modal (QRIS / Cash)
   5. Reservasi Confirm Modal
   ════════════════════════════════════════════════ */

/* ─── STATE ─── */
let orderCart = [];  // { name, price, basePrice, meta, img, type }
let currentItem = null;

/* ─── HELPERS ─── */
function rupiah(n) {
  return 'IDR ' + parseInt(n).toLocaleString('id-ID');
}
function genOrderNum() {
  return 'SS-' + Date.now().toString().slice(-6);
}

/* ════════════════════════════════════════════════
   1. CUSTOMIZE MODAL (minuman)
   ════════════════════════════════════════════════ */
window.openCustModal = function(data) {
  currentItem = {
    name: data.name,
    basePrice: data.price,
    img: data.img,
    type: 'minuman',
    // defaults
    temp: 'Iced',
    ice: 'Normal Ice',
    sugar: 'Normal Sugar',
    size: 'Regular',
    addons: [],
    addonPrice: 0,
  };

  document.getElementById('custHeroImg').src = data.img;
  document.getElementById('custItemName').textContent = data.name;
  document.getElementById('custBasePrice').textContent = rupiah(data.price);
  updateCustTotal();

  // reset selections
  document.querySelectorAll('.cust-opt').forEach(el => el.classList.remove('sel'));
  document.querySelectorAll('.addon-card').forEach(el => el.classList.remove('sel'));

  // set defaults
  selectOpt('temp', 'Iced');
  selectOpt('ice', 'Normal Ice');
  selectOpt('sugar', 'Normal Sugar');
  selectOpt('size', 'Regular');

  openOverlay('custOverlay');
};

function selectOpt(group, val) {
  document.querySelectorAll(`[data-group="${group}"]`).forEach(el => {
    el.classList.toggle('sel', el.dataset.val === val);
  });
}

window.pickOpt = function(el, group, val) {
  document.querySelectorAll(`[data-group="${group}"]`).forEach(e => e.classList.remove('sel'));
  el.classList.add('sel');
  if (currentItem) currentItem[group] = val;

  // Kalau pilih Hot -> disable semua opsi ice
  if (group === 'temp') {
    const iceButtons = document.querySelectorAll('[data-group="ice"]');
    const iceLabel   = document.querySelector('.cust-ice-label');
    if (val === 'Hot') {
      iceButtons.forEach(btn => {
        btn.classList.remove('sel');
        btn.setAttribute('disabled', 'disabled');
        btn.style.opacity = '0.3';
        btn.style.pointerEvents = 'none';
        btn.style.cursor = 'not-allowed';
      });
      if (iceLabel) iceLabel.style.opacity = '0.35';
      if (currentItem) currentItem.ice = 'No Ice';
    } else {
      // Iced -> aktifkan kembali
      iceButtons.forEach(btn => {
        btn.removeAttribute('disabled');
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
        btn.style.cursor = '';
      });
      if (iceLabel) iceLabel.style.opacity = '';
      selectOpt('ice', 'Normal Ice');
      if (currentItem) currentItem.ice = 'Normal Ice';
    }
  }

  updateCustTotal();
};

window.toggleAddon = function(el, name, price) {
  el.classList.toggle('sel');
  if (!currentItem) return;
  if (el.classList.contains('sel')) {
    currentItem.addons.push(name);
    currentItem.addonPrice += price;
  } else {
    currentItem.addons = currentItem.addons.filter(a => a !== name);
    currentItem.addonPrice -= price;
  }
  updateCustTotal();
};

function updateCustTotal() {
  if (!currentItem) return;
  let total = currentItem.basePrice;
  // size surcharge
  if (currentItem.size === 'Large') total += 5000;
  total += currentItem.addonPrice;
  document.getElementById('custTotalPrice').textContent = rupiah(total);
  currentItem.finalPrice = total;
}

window.addCustToCart = function() {
  if (!currentItem) return;
  const meta = [
    currentItem.temp,
    currentItem.ice,
    currentItem.sugar,
    currentItem.size,
    ...currentItem.addons
  ].join(' · ');

  orderCart.push({
    name: currentItem.name,
    price: currentItem.finalPrice,
    meta,
    img: currentItem.img,
    type: 'minuman'
  });

  closeOverlay('custOverlay');
  renderOrderCart();
  showToast('✓ ' + currentItem.name + ' ditambahkan!');
  // Auto buka cart
  setTimeout(() => {
    document.getElementById('cartSb').classList.add('open');
    document.getElementById('backdrop').classList.add('on');
  }, 400);
};


/* ════════════════════════════════════════════════
   2. SIMPLE ADD (makanan ringan / berat)
   ════════════════════════════════════════════════ */
window.addFoodToCart = function(name, price, img) {
  orderCart.push({ name, price: parseInt(price), meta: '', img, type: 'makanan' });
  renderOrderCart();
  showToast('✓ ' + name + ' ditambahkan!');
  setTimeout(() => {
    document.getElementById('cartSb').classList.add('open');
    document.getElementById('backdrop').classList.add('on');
  }, 400);
};


/* ════════════════════════════════════════════════
   CART RENDER
   ════════════════════════════════════════════════ */
function renderOrderCart() {
  const cartBody  = document.getElementById('cartBody');
  const cartTotal = document.getElementById('cartTotal');
  const cartCount = document.getElementById('cartCount');

  cartCount.textContent = orderCart.length;

  if (orderCart.length === 0) {
    cartBody.innerHTML = '<p class="cart-empty-msg">Keranjang masih kosong ☕</p>';
    cartTotal.textContent = 'IDR 0';
    return;
  }

  let total = 0;
  cartBody.innerHTML = orderCart.map((item, i) => {
    total += item.price;
    return `
      <div class="c-item">
        <img src="${item.img}" alt="${item.name}"
          onerror="this.src='https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=100&q=60'">
        <div class="c-item-info">
          <h4>${item.name}</h4>
          ${item.meta ? `<div class="c-item-meta">${item.meta}</div>` : ''}
          <span style="color:var(--gold);font-size:0.8rem;font-weight:600;">${rupiah(item.price)}</span>
        </div>
        <span class="c-remove" onclick="removeOrderItem(${i})"><i data-feather="x"></i></span>
      </div>`;
  }).join('');

  cartTotal.textContent = rupiah(total);
  feather.replace();
}

window.removeOrderItem = function(i) {
  orderCart.splice(i, 1);
  renderOrderCart();
};


/* ════════════════════════════════════════════════
   3. CHECKOUT MODAL
   ════════════════════════════════════════════════ */
window.openCheckout = function() {
  if (orderCart.length === 0) {
    showToast('Keranjang masih kosong!');
    return;
  }

  // tutup cart sidebar
  document.getElementById('cartSb').classList.remove('open');
  document.getElementById('backdrop').classList.remove('on');

  // build order list
  let total = 0;
  const itemsHTML = orderCart.map(item => {
    total += item.price;
    return `
      <div class="co-item">
        <div class="co-item-name">
          ${item.name}
          ${item.meta ? `<small>${item.meta}</small>` : ''}
        </div>
        <div class="co-item-price">${rupiah(item.price)}</div>
      </div>`;
  }).join('');

  document.getElementById('coItemList').innerHTML = itemsHTML;
  document.getElementById('coSubtotal').textContent = rupiah(total);
  document.getElementById('coTotal').textContent = rupiah(total);

  // reset form
  document.getElementById('coNama').value = '';
  document.getElementById('coTelp').value = '';
  document.getElementById('coMeja').value = '';
  document.getElementById('coCatatan').value = '';
  document.querySelectorAll('.pay-opt').forEach(el => el.classList.remove('sel'));
  document.querySelector('.pay-opt[data-pay="qris"]').classList.add('sel');

  openOverlay('checkoutOverlay');
};

window.selectPayMethod = function(el, method) {
  document.querySelectorAll('.pay-opt').forEach(e => e.classList.remove('sel'));
  el.classList.add('sel');
};

window.submitCheckout = function() {
  const nama  = document.getElementById('coNama').value.trim();
  const telp  = document.getElementById('coTelp').value.trim();
  const meja  = document.getElementById('coMeja').value.trim();
  const catatan = document.getElementById('coCatatan').value.trim();
  const payEl = document.querySelector('.pay-opt.sel');

  if (!nama || !telp || !meja) {
    showToast('Lengkapi nama, telepon & nomor meja dulu!');
    return;
  }
  if (!payEl) {
    showToast('Pilih metode pembayaran dulu!');
    return;
  }

  const payMethod = payEl.dataset.pay;
  let total = orderCart.reduce((s, i) => s + i.price, 0);
  const orderNum = genOrderNum();

  closeOverlay('checkoutOverlay');

  // simpan info untuk payment modal
  window._lastOrder = { nama, telp, meja, catatan, payMethod, total, orderNum };

  openPaymentModal(payMethod, total, orderNum, nama, meja);
};


/* ════════════════════════════════════════════════
   4. PAYMENT MODAL
   ════════════════════════════════════════════════ */
function openPaymentModal(method, total, orderNum, nama, meja) {
  // Tampilkan no pesanan (hanya sekali)
  document.getElementById('payOrderNum').textContent = orderNum;

  if (method === 'qris') {
    document.getElementById('qrisSection').style.display = 'block';
    document.getElementById('cashSection').style.display = 'none';
    document.getElementById('qrisAmount').textContent = rupiah(total);
    startQrisTimer(600);
  } else {
    document.getElementById('qrisSection').style.display = 'none';
    document.getElementById('cashSection').style.display = 'block';
    document.getElementById('cashAmount').textContent = rupiah(total);
    document.getElementById('cashMeja').textContent = 'Meja ' + meja;
    clearInterval(window._qrisTimer);
  }

  openOverlay('paymentOverlay');
}

window.startQrisTimer = function startQrisTimer(seconds) {
  clearInterval(window._qrisTimer);
  const el = document.getElementById('qrisCountdown');
  const bar = document.querySelector('.pay-waiting-bar span');

  function tick() {
    const m = String(Math.floor(seconds / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    if (el) el.textContent = m + ':' + s;
    if (seconds <= 0) {
      clearInterval(window._qrisTimer);
      if (el) el.textContent = 'KADALUARSA';
      if (el) el.style.color = '#e74c3c';
      if (bar) bar.textContent = 'QR Code Kadaluarsa';
    }
    seconds--;
  }
  tick();
  window._qrisTimer = setInterval(tick, 1000);
}


/* ════════════════════════════════════════════════
   5. RESERVASI CONFIRM MODAL
   ════════════════════════════════════════════════ */
window.submitReservasi = function(e) {
  const btn = e.target;

  // Ambil nilai form
  const ruangan = document.querySelector('.room-opt.sel strong')?.textContent || '-';
  const nama    = document.getElementById('resNama')?.value.trim();
  const wa      = document.getElementById('resWa')?.value.trim();
  const tgl     = document.getElementById('resTgl')?.value;
  // Gabungkan jam & menit dari select dropdown
  const jam     = document.getElementById('resWaktuJam')?.value;
  const menit   = document.getElementById('resWaktuMenit')?.value;
  const waktuHidden = document.getElementById('resWaktu');
  if (jam && menit && waktuHidden) waktuHidden.value = jam + ':' + menit;
  const waktu   = waktuHidden?.value;
  const durasi  = document.getElementById('resDurasi')?.value;
  const orang   = document.getElementById('resOrang')?.value;
  const catatan = document.getElementById('resCatatan')?.value.trim();

  // Validasi dengan highlight & scroll ke field kosong
  const fields = [
    { val: nama,  id: 'resNama',  label: 'Nama Lengkap' },
    { val: wa,    id: 'resWa',    label: 'No. WhatsApp' },
    { val: tgl,   id: 'resTgl',   label: 'Tanggal' },
  ];
  fields.forEach(f => { const el = document.getElementById(f.id); if (el) el.style.borderColor = ''; });
  // Validasi khusus waktu (dari dua select)
  const jamEl    = document.getElementById('resWaktuJam');
  const menitEl  = document.getElementById('resWaktuMenit');
  if (jamEl) jamEl.style.borderColor = '';
  if (menitEl) menitEl.style.borderColor = '';
  const emptyField = fields.find(f => !f.val);
  if (emptyField) {
    const el = document.getElementById(emptyField.id);
    if (el) {
      el.style.borderColor = '#e74c3c';
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.focus();
      el.addEventListener('input', () => { el.style.borderColor = ''; }, { once: true });
    }
    showToast('Lengkapi ' + emptyField.label + ' terlebih dahulu!');
  } else if (!jam || !menit) {
    if (jamEl) { jamEl.style.borderColor = '#e74c3c'; jamEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    if (menitEl) menitEl.style.borderColor = '#e74c3c';
    showToast('Lengkapi Waktu Mulai terlebih dahulu!');
    return;
  }
  const tglFmt = new Date(tgl).toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

  // Isi detail modal konfirmasi
  document.getElementById('rcRuangan').textContent = ruangan;
  document.getElementById('rcNama').textContent    = nama;
  document.getElementById('rcWa').textContent      = wa;
  document.getElementById('rcTanggal').textContent = tglFmt;
  document.getElementById('rcWaktu').textContent   = waktu + ' WIB · ' + (durasi || '1 Jam');
  document.getElementById('rcOrang').textContent   = (orang || '-') + ' orang';
  document.getElementById('rcCatatan').textContent = catatan || 'Tidak ada catatan';

  // WA link
  const nomorWA = '628137113082';
  const pesan =
    `Halo Satu Seduh! 👋\n\n` +
    `Saya ingin mengajukan *Reservasi*:\n\n` +
    `📍 Ruangan : ${ruangan}\n` +
    `👤 Nama    : ${nama}\n` +
    `📅 Tanggal : ${tglFmt}\n` +
    `🕐 Waktu   : ${waktu} WIB · ${durasi || '1 Jam'}\n` +
    `👥 Jumlah  : ${orang || '-'} orang\n` +
    `📝 Catatan : ${catatan || '-'}\n\n` +
    `Mohon konfirmasinya. Terima kasih!`;

  const waUrl = `https://wa.me/${nomorWA}?text=${encodeURIComponent(pesan)}`;

  document.getElementById('rcWaBtn').onclick = () => {
    window.open(waUrl, '_blank');
  };

  // Simpan data untuk cetak bukti
  window._lastReservasi = { ruangan, nama, wa, tglFmt, waktu, durasi, orang, catatan };

  openOverlay('resConfirmOverlay');
};

/* ── Cetak / Simpan Bukti Reservasi ── */
window.cetakBuktiReservasi = function() {
  const d = window._lastReservasi;
  if (!d) return;

  const noRef = 'SS-RES-' + Date.now().toString().slice(-6);
  const tsCetak = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });

  let area = document.getElementById('buktiPrintArea');
  if (!area) {
    area = document.createElement('div');
    area.id = 'buktiPrintArea';
    document.body.appendChild(area);
  }

  area.innerHTML = `
    <div style="max-width:480px;margin:0 auto;font-family:'Georgia',serif;color:#1a1209;border:2px solid #c9a84c;border-radius:12px;overflow:hidden;">
      <div style="background:#1a1209;color:#c9a84c;text-align:center;padding:1.5rem 1rem;">
        <div style="font-size:1.8rem;font-weight:bold;letter-spacing:0.05em;">SATU SEDUH</div>
        <div style="font-size:0.75rem;letter-spacing:0.15em;margin-top:0.25rem;opacity:0.8;">BUKTI RESERVASI</div>
      </div>
      <div style="padding:1.5rem 2rem;">
        <div style="text-align:center;margin-bottom:1.2rem;">
          <div style="font-size:0.7rem;color:#888;letter-spacing:0.1em;">NO. REFERENSI</div>
          <div style="font-size:1.1rem;font-weight:bold;color:#c9a84c;letter-spacing:0.05em;">${noRef}</div>
        </div>
        <hr style="border:none;border-top:1px dashed #ddd;margin:1rem 0;">
        ${[
          ['Ruangan', d.ruangan],
          ['Nama Pemesan', d.nama],
          ['WhatsApp', d.wa],
          ['Tanggal', d.tglFmt],
          ['Waktu & Durasi', d.waktu + ' WIB · ' + (d.durasi || '1 Jam')],
          ['Jumlah Tamu', (d.orang || '-') + ' orang'],
          ['Catatan', d.catatan || 'Tidak ada catatan'],
        ].map(([label, val]) => `
          <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:0.5rem 0;border-bottom:1px solid #f0ece0;gap:1rem;">
            <span style="color:#888;font-size:0.82rem;min-width:120px;">${label}</span>
            <span style="font-weight:600;font-size:0.88rem;text-align:right;">${val}</span>
          </div>`).join('')}
        <hr style="border:none;border-top:1px dashed #ddd;margin:1rem 0;">
        <div style="background:#fffbf0;border:1px solid #e8d99a;border-radius:8px;padding:0.8rem 1rem;font-size:0.78rem;color:#7a6430;line-height:1.6;">
          ⚠️ <strong>Status: Menunggu Konfirmasi.</strong> Reservasi belum sah sampai mendapat balasan dari tim Satu Seduh via WhatsApp.
        </div>
        <div style="text-align:center;margin-top:1.2rem;font-size:0.72rem;color:#aaa;">
          Dicetak: ${tsCetak}<br>
          📍 Satu Seduh · wa.me/628137113082
        </div>
      </div>
    </div>`;

  area.style.display = 'block';
  window.print();
  setTimeout(() => { area.style.display = 'none'; }, 1000);
};


/* ─── OVERLAY HELPERS ─── */
function openOverlay(id) {
  document.getElementById(id).classList.add('open');
  // Pastikan custom cursor tetap aktif di atas overlay
  const cur  = document.getElementById('cur');
  const curR = document.getElementById('curR');
  if (cur)  cur.style.zIndex  = '2147483647';
  if (curR) curR.style.zIndex = '2147483646';
}
function closeOverlay(id) {
  document.getElementById(id).classList.remove('open');
}
window.closeOverlay = closeOverlay;

// Klik luar modal = tutup
['custOverlay','checkoutOverlay','paymentOverlay','resConfirmOverlay'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('click', e => { if (e.target === el) closeOverlay(id); });
});

/* ── CART BUTTON INIT ── */
document.addEventListener('DOMContentLoaded', function(){
  const cartBtn   = document.getElementById('cartBtn');
  const cartClose = document.getElementById('cartClose');
  const cartSb    = document.getElementById('cartSb');
  const backdrop  = document.getElementById('backdrop');

  if(cartBtn) cartBtn.addEventListener('click', ()=>{
    cartSb.classList.add('open');
    backdrop.classList.add('on');
  });
  if(cartClose) cartClose.addEventListener('click', ()=>{
    cartSb.classList.remove('open');
    backdrop.classList.remove('on');
  });

  // blok context menu di cart
  if(cartSb) cartSb.addEventListener('contextmenu', e=>e.preventDefault());

  // render kosong awal
  renderOrderCart();
});
