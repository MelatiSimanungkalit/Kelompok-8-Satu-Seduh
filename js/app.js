/* ============================================================
   SATU SEDUH — app.js
   Integrasi frontend ke PHP API, dengan fallback offline
   ============================================================ */

/* ── Override submitCheckout ── */
window.submitCheckout = async function () {
  const nama  = document.getElementById("coNama")?.value.trim();
  const telp  = document.getElementById("coTelp")?.value.trim();
  const meja  = document.getElementById("coMeja")?.value.trim();
  const cat   = document.getElementById("coCatatan")?.value.trim();
  const payEl = document.querySelector(".pay-opt.sel");
  const bayar = payEl?.dataset.pay || "qris";

  /* ── Validasi ── */
  if (!nama) {
    showToast("Nama pemesan wajib diisi!");
    document.getElementById("coNama")?.focus();
    return;
  }
  if (!telp) {
    showToast("Nomor telepon wajib diisi!");
    document.getElementById("coTelp")?.focus();
    return;
  }
  if (!meja) {
    showToast("Nomor meja wajib diisi!");
    document.getElementById("coMeja")?.focus();
    return;
  }
  if (!orderCart.length) {
    showToast("Keranjang masih kosong!");
    return;
  }

  const telpNorm = telp.replace(/[\s\-().]/g, "");
  const items    = orderCart.map((item) => ({
    name  : item.name,
    price : item.price,
    qty   : item.qty || 1,
    type  : item.type || "menu",
    meta  : Array.isArray(item.meta) ? item.meta.join(", ") : item.meta || "",
  }));
  const total    = orderCart.reduce((s, i) => s + i.price * (i.qty || 1), 0);

  /* ── Coba kirim ke API ── */
  let orderNum = "SS-" + Date.now().toString().slice(-6);
  let apiOk    = false;

  try {
    const baseUrl = (function () {
      const parts = location.pathname.split("/");
      parts.pop();
      return location.origin + parts.join("/");
    })();

    const controller = new AbortController();
    const timeout    = setTimeout(() => controller.abort(), 4000); // 4 detik timeout

    const res = await fetch(baseUrl + "/api/pesanan.php", {
      method  : "POST",
      headers : { "Content-Type": "application/json" },
      signal  : controller.signal,
      body    : JSON.stringify({ nama, telepon: telpNorm, meja, catatan: cat, metode_bayar: bayar, items }),
    });
    clearTimeout(timeout);

    if (res.ok) {
      const data = await res.json();
      if (data.success) {
        orderNum = data.nomor_pesanan || orderNum;
        apiOk    = true;
      }
    }
  } catch (e) {
    // API tidak tersedia — lanjut mode offline (tidak error)
    console.warn("API tidak tersedia, mode offline:", e.message);
  }

  /* ── Lanjut ke Payment Modal (API berhasil atau fallback offline) ── */
  closeOverlay("checkoutOverlay");
  orderCart = [];
  if (typeof renderOrderCart === "function") renderOrderCart();

  // Isi payment modal
  document.getElementById("payOrderNum").textContent = orderNum;
  document.getElementById("qrisAmount").textContent  = "IDR " + Number(total).toLocaleString("id-ID");

  // Tampilkan section yang sesuai
  const qrisSection = document.getElementById("qrisSection");
  const cashSection = document.getElementById("cashSection");
  if (bayar === "cash") {
    if (qrisSection) qrisSection.style.display = "none";
    if (cashSection) cashSection.style.display  = "block";
  } else {
    if (qrisSection) qrisSection.style.display  = "block";
    if (cashSection) cashSection.style.display  = "none";
  }

  openOverlay("paymentOverlay");

  // Generate QR
  setTimeout(function () {
    try {
      const canvas = document.getElementById("realQrisCanvas");
      if (canvas && typeof QRious !== "undefined") {
        new QRious({
          element : canvas,
          value   : "SatuSeduh|" + bayar + "|" + orderNum + "|" + total,
          size    : 300,
          level   : "H",
        });
      }
    } catch (e) {
      console.warn("QR error:", e);
    }
  }, 100);

  if (typeof startQrisTimer === "function") startQrisTimer(600);
};

/* ── Override submitReservasi ── */
window.submitReservasi = async function (e) {
  e.preventDefault();

  const jam   = document.getElementById("resWaktuJam")?.value;
  const menit = document.getElementById("resWaktuMenit")?.value;
  const waktu = jam && menit ? `${jam}:${menit}` : "";

  const payload = {
    nama         : document.getElementById("resNama")?.value.trim(),
    whatsapp     : document.getElementById("resWa")?.value.trim(),
    ruangan      : document.querySelector(".room-opt.sel strong")?.textContent.trim() || "",
    tanggal      : document.getElementById("resTgl")?.value,
    waktu,
    durasi       : document.getElementById("resDurasi")?.value,
    jumlah_orang : parseInt(document.getElementById("resOrang")?.value) || 0,
    catatan      : document.getElementById("resCatatan")?.value.trim(),
  };

  if (!payload.nama || !payload.whatsapp || !payload.tanggal || !waktu) {
    showToast("Harap lengkapi semua field wajib!");
    return;
  }

  /* ── Coba kirim ke API, fallback ke tampil modal langsung ── */
  let apiData = null;
  try {
    const baseUrlRes   = (function () {
      const parts = location.pathname.split("/");
      parts.pop();
      return location.origin + parts.join("/");
    })();
    const controller   = new AbortController();
    const timeout      = setTimeout(() => controller.abort(), 4000);
    const res          = await fetch(baseUrlRes + "/api/reservasi.php", {
      method  : "POST",
      headers : { "Content-Type": "application/json" },
      signal  : controller.signal,
      body    : JSON.stringify(payload),
    });
    clearTimeout(timeout);
    if (res.ok) {
      const data = await res.json();
      if (data.success) apiData = data.data;
    }
  } catch (e) {
    console.warn("Reservasi API tidak tersedia, mode offline:", e.message);
  }

  /* ── Isi confirm modal ── */
  const d       = apiData || payload;
  const tglFmt  = formatTanggal(d.tanggal || payload.tanggal);
  const ruangan = d.ruangan || payload.ruangan || "-";
  const namaPes = d.nama || payload.nama;
  const waNum   = d.whatsapp || payload.whatsapp;
  const waktuSt = d.waktu || waktu;
  const durasi  = d.durasi || payload.durasi || "1 Jam";
  const orang   = d.jumlah_orang || payload.jumlah_orang || "-";
  const catatan = d.catatan || payload.catatan || "-";

  document.getElementById("rcRuangan").textContent = ruangan;
  document.getElementById("rcNama").textContent    = namaPes;
  document.getElementById("rcWa").textContent      = waNum;
  document.getElementById("rcTanggal").textContent = tglFmt;
  document.getElementById("rcWaktu").textContent   = `${waktuSt} · ${durasi}`;
  document.getElementById("rcOrang").textContent   = orang ? orang + " orang" : "-";
  document.getElementById("rcCatatan").textContent = catatan;

  const waMsg = encodeURIComponent(
    `Halo Satu Seduh! Saya ingin reservasi:\nRuangan: ${ruangan}\nNama: ${namaPes}\nTanggal: ${tglFmt}\nWaktu: ${waktuSt}\nDurasi: ${durasi}\nJumlah: ${orang} orang\nCatatan: ${catatan || "-"}`
  );
  document.getElementById("rcWaBtn").onclick = () => {
    window.open(`https://wa.me/628137113082?text=${waMsg}`, "_blank");
  };

  openOverlay("resConfirmOverlay");
  showToast("Reservasi berhasil dikirim!");
};

/* ── Override kirimKomentar ── */
window.kirimKomentar = async function (e) {
  e.preventDefault();

  const payload = {
    nama  : document.getElementById("komNama")?.value.trim(),
    email : document.getElementById("komEmail")?.value.trim(),
    no_hp : document.getElementById("komHp")?.value.trim(),
    pesan : document.getElementById("komPesan")?.value.trim(),
  };

  if (!payload.nama || !payload.pesan) {
    showToast("Nama dan komentar wajib diisi!");
    return;
  }

  try {
    const baseUrlKom = (function () {
      const parts = location.pathname.split("/");
      parts.pop();
      return location.origin + parts.join("/");
    })();
    const controller = new AbortController();
    const timeout    = setTimeout(() => controller.abort(), 4000);
    const res        = await fetch(baseUrlKom + "/api/komentar.php", {
      method  : "POST",
      headers : { "Content-Type": "application/json" },
      signal  : controller.signal,
      body    : JSON.stringify(payload),
    });
    clearTimeout(timeout);
    if (res.ok) {
      const data = await res.json();
      if (data.success) {
        showToast(data.message || "Komentar terkirim!");
        ["komNama", "komEmail", "komHp", "komPesan"].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.value = "";
        });
        return;
      }
    }
  } catch (e) {
    console.warn("Komentar API tidak tersedia:", e.message);
  }

  // Fallback: anggap berhasil
  showToast("Komentar terkirim! Terima kasih.");
  ["komNama", "komEmail", "komHp", "komPesan"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.value = "";
  });
};

/* ── Helper: format tanggal ── */
function formatTanggal(str) {
  if (!str) return "-";
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString("id-ID", { day: "2-digit", month: "long", year: "numeric" });
}

/* ── Helper: showToast fallback ── */
if (typeof showToast === "undefined") {
  window.showToast = function (msg) {
    const t = document.getElementById("toast");
    const m = document.getElementById("toastMsg");
    if (!t || !m) return;
    m.textContent = msg;
    t.classList.add("show");
    setTimeout(() => t.classList.remove("show"), 3000);
  };
}

/* ── Cetak Bukti Reservasi ── */
window.cetakBuktiReservasi = function () {
  const ruangan  = document.getElementById("rcRuangan")?.textContent || "-";
  const nama     = document.getElementById("rcNama")?.textContent || "-";
  const wa       = document.getElementById("rcWa")?.textContent || "-";
  const tanggal  = document.getElementById("rcTanggal")?.textContent || "-";
  const waktu    = document.getElementById("rcWaktu")?.textContent || "-";
  const orang    = document.getElementById("rcOrang")?.textContent || "-";
  const catatan  = document.getElementById("rcCatatan")?.textContent || "-";
  const noRef    = "SS-" + Date.now().toString(36).toUpperCase().slice(-6);
  const cetakWkt = new Date().toLocaleString("id-ID", { day:"2-digit", month:"long", year:"numeric", hour:"2-digit", minute:"2-digit" });

  const html = `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Bukti Reservasi</title>
  <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f0e8;display:flex;justify-content:center;padding:30px 16px}.card{background:#fff;width:100%;max-width:480px;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.13)}.header{background:#1a1209;color:#c9a84c;text-align:center;padding:28px 24px}.logo{font-size:1.6rem;font-weight:700;letter-spacing:2px}.tagline{font-size:.72rem;color:#a0825a;letter-spacing:3px;margin-top:3px}.status{background:#c9a84c;color:#1a1209;text-align:center;padding:10px;font-size:.8rem;font-weight:700;letter-spacing:1.5px}.body{padding:24px}.ref{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;padding-bottom:16px;border-bottom:1.5px dashed #e0d5c0}.ref h2{font-size:1.15rem;color:#1a1209;font-weight:700}.ref-num{font-size:.72rem;color:#888;text-align:right}.ref-num strong{display:block;font-size:.82rem;color:#c9a84c;font-family:monospace}table{width:100%;border-collapse:collapse}td{padding:9px 4px;font-size:.875rem;border-bottom:1px solid #f0ebe0;vertical-align:top}td:first-child{color:#8a7560;width:42%;font-weight:500}td:last-child{color:#1a1209;font-weight:600;text-align:right}.note{background:#fffbf0;border:1px solid #e8d89a;border-radius:8px;padding:12px 14px;margin-top:20px;font-size:.78rem;color:#7a6430;line-height:1.5}.note strong{display:block;margin-bottom:4px;color:#b8862e}.footer{background:#1a1209;color:#a0825a;text-align:center;padding:14px 20px;font-size:.7rem;line-height:1.7}.footer strong{color:#c9a84c}.meta{text-align:center;font-size:.68rem;color:#bbb;margin-top:16px;padding-top:14px;border-top:1px dashed #e0d5c0}</style>
  </head><body><div class="card"><div class="header"><div class="logo">SATU SEDUH</div><div class="tagline">BUKTI RESERVASI</div></div>
  <div class="status">⏳ Menunggu Konfirmasi</div><div class="body">
  <div class="ref"><h2>Bukti Reservasi</h2><div class="ref-num">No. Referensi<strong>${noRef}</strong></div></div>
  <table><tr><td>Ruangan</td><td>${ruangan}</td></tr><tr><td>Nama</td><td>${nama}</td></tr><tr><td>WhatsApp</td><td>${wa}</td></tr><tr><td>Tanggal</td><td>${tanggal}</td></tr><tr><td>Waktu &amp; Durasi</td><td>${waktu}</td></tr><tr><td>Jumlah Tamu</td><td>${orang}</td></tr><tr><td>Catatan</td><td>${catatan}</td></tr></table>
  <div class="note"><strong>⚠️ Informasi Penting</strong>Reservasi belum sah sampai mendapat konfirmasi dari tim Satu Seduh via WhatsApp.</div>
  <div class="meta">Dicetak: ${cetakWkt}</div></div>
  <div class="footer"><strong>Satu Seduh</strong><br>WA: 0813-7113-082</div></div></body></html>`;

  const blob   = new Blob([html], { type: "text/html" });
  const url    = URL.createObjectURL(blob);
  const old    = document.getElementById("_buktiFrame");
  if (old) old.remove();
  const iframe = document.createElement("iframe");
  iframe.id    = "_buktiFrame";
  iframe.style.cssText = "position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;";
  iframe.src   = url;
  document.body.appendChild(iframe);
  iframe.onload = function () {
    try { iframe.contentWindow.focus(); iframe.contentWindow.print(); }
    catch (e) { window.open(url, "_blank"); }
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  };
};
