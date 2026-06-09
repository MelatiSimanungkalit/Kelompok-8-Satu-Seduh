<?php
require_once __DIR__ . '/includes/config.php';

$orderNum = $_GET['order'] ?? '';
if (!$orderNum) {
    die("Pesanan tidak ditemukan.");
}

$db = getDB();

// Handle mock payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $stmt = $db->prepare("UPDATE pesanan SET status = 'lunas' WHERE nomor_pesanan = ?");
    $stmt->execute([$orderNum]);
    header("Location: struk.php?order=" . urlencode($orderNum));
    exit;
}

// Fetch order details
$stmt = $db->prepare("SELECT * FROM pesanan WHERE nomor_pesanan = ? LIMIT 1");
$stmt->execute([$orderNum]);
$order = $stmt->fetch();

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

if ($order['status'] === 'confirmed' || $order['status'] === 'lunas') {
    header("Location: struk.php?order=" . urlencode($orderNum));
    exit;
}

if ($order['status'] === 'cancelled') {
    die("Pesanan ini sudah dibatalkan atau kadaluarsa.");
}

$expiredAt = strtotime($order['expired_at']);
$now = time();
$remaining = $expiredAt - $now;

if ($remaining <= 0) {
    $db->prepare("UPDATE pesanan SET status = 'cancelled' WHERE nomor_pesanan = ?")->execute([$orderNum]);
    die("Waktu pembayaran telah habis. Pesanan dibatalkan otomatis.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Satu Seduh</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .pay-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .pay-header h2 { margin: 0 0 10px; color: #333; }
        .pay-total { font-size: 2rem; font-weight: bold; color: #2c3e50; margin: 15px 0; }
        .countdown {
            font-size: 1.5rem;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .qris-img {
            width: 200px;
            height: 200px;
            background: #eee;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ccc;
            border-radius: 8px;
        }
        .qris-img img { width: 100%; height: 100%; object-fit: contain; }
        .btn-pay {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }
        .btn-pay:hover { background: #219653; }
        .info { font-size: 0.85rem; color: #7f8c8d; margin-top: 15px; }
    </style>
</head>
<body>

<div class="pay-box">
    <div class="pay-header">
        <h2>Selesaikan Pembayaran</h2>
        <p>Order ID: <strong><?= htmlspecialchars($orderNum) ?></strong></p>
    </div>
    
    <div class="pay-total"><?= rupiah($order['total']) ?></div>

    <p>Selesaikan pembayaran dalam waktu:</p>
    <div class="countdown" id="timer">--:--</div>

    <div class="qris-img">
        <canvas id="realQrisCanvas" width="200" height="200"></canvas>
    </div>

    <!-- Tombol Simpan QRIS -->
    <a id="btnDownloadQris" href="#" download="QRIS_<?= htmlspecialchars($orderNum) ?>.png" style="display:inline-block; margin-bottom:20px; background:#0070ba; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:0.9rem; font-weight:600;">
        Unduh QRIS
    </a>

    <form method="POST">
        <button type="submit" name="pay" class="btn-pay">Simulasikan Bayar (Mock API)</button>
    </form>
    
    <p class="info">
        Tombol di atas hanyalah simulasi. Di sistem asli (Midtrans API), halaman ini otomatis berpindah saat pembeli selesai scan QRIS dari HP mereka.
    </p>
</div>

<script src="js/qrious.min.js"></script>
<script>
    const orderNum = "<?= addslashes($orderNum) ?>";
    const total = "<?= addslashes($order['total']) ?>";
    const qrText = "Satu Seduh Order: " + orderNum + " | Total: " + total;
    
    const qr = new QRious({
      element: document.getElementById('realQrisCanvas'),
      value: qrText,
      size: 300,
      level: 'H'
    });
    
    document.getElementById('btnDownloadQris').href = qr.toDataURL('image/png');
let remainingSeconds = <?= $remaining ?>;
const timerEl = document.getElementById('timer');

function tick() {
    if (remainingSeconds <= 0) {
        timerEl.textContent = 'KADALUARSA';
        timerEl.style.color = '#e74c3c';
        setTimeout(() => location.reload(), 2000);
        return;
    }
    const m = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
    const s = String(remainingSeconds % 60).padStart(2, '0');
    timerEl.textContent = m + ':' + s;
    remainingSeconds--;
    setTimeout(tick, 1000);
}
tick();
</script>

</body>
</html>
