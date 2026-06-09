<?php
require_once __DIR__ . '/includes/config.php';

$orderNum = $_GET['order'] ?? '';
if (!$orderNum) {
    die("Pesanan tidak ditemukan.");
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM pesanan WHERE nomor_pesanan = ? LIMIT 1");
$stmt->execute([$orderNum]);
$order = $stmt->fetch();

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

if (!in_array($order['status'], ['lunas', 'confirmed', 'preparing', 'ready', 'done'])) {
    die("Pesanan belum dibayar atau dibatalkan.");
}

// Fetch items
$stmtDetail = $db->prepare("SELECT * FROM detail_pesanan WHERE pesanan_id = ?");
$stmtDetail->execute([$order['id']]);
$items = $stmtDetail->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Receipt - <?= htmlspecialchars($orderNum) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #e9ecef;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
            margin: 0;
        }
        .receipt {
            background: #fff;
            width: 100%;
            max-width: 350px;
            padding: 2rem 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .receipt::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: radial-gradient(circle, transparent 4px, #fff 4px) repeat-x;
            background-size: 10px 10px;
        }
        .header { text-align: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0; font-size: 0.9rem; color: #555; }
        .divider { border-bottom: 1px dashed #333; margin: 15px 0; }
        .info-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px; }
        .item-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; }
        .item-name { flex: 1; padding-right: 10px; }
        .item-qty { width: 30px; text-align: left; }
        .item-price { text-align: right; }
        .totals { margin-top: 15px; }
        .totals .info-row.bold { font-weight: bold; font-size: 1rem; margin-top: 5px; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.8rem; color: #555; }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background: #333;
            color: #fff;
            border: none;
            cursor: pointer;
            text-align: center;
            text-transform: uppercase;
            font-family: inherit;
        }
        .btn-print:hover { background: #555; }
        .btn-back {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ccc;
            cursor: pointer;
            text-align: center;
            text-transform: uppercase;
            font-family: inherit;
            text-decoration: none;
            box-sizing: border-box;
        }
        .btn-back:hover { background: #e2e6ea; }
        
        .stamp {
            position: absolute;
            top: 20px;
            right: 10px;
            border: 3px solid #27ae60;
            color: #27ae60;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 5px 15px;
            transform: rotate(15deg);
            border-radius: 5px;
            opacity: 0.8;
            pointer-events: none;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; max-width: 100%; }
            .btn-print, .btn-back { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt">
    <?php if ($order['status'] === 'lunas'): ?>
        <div class="stamp" style="border-color:#f39c12; color:#f39c12; font-size:1rem;">MENUNGGU ACC</div>
    <?php elseif ($order['status'] === 'done'): ?>
        <div class="stamp">SELESAI</div>
    <?php else: ?>
        <div class="stamp">DIPROSES</div>
    <?php endif; ?>

    <div class="header">
        <h1>Satu Seduh</h1>
        <p>Jl. Contoh No. 123, Kota</p>
        <p>Telp: 0812-3456-7890</p>
    </div>

    <div class="info-row">
        <span>Order ID:</span>
        <span><?= htmlspecialchars($orderNum) ?></span>
    </div>
    <div class="info-row">
        <span>Tanggal:</span>
        <span><?= date('d M Y H:i', strtotime($order['created_at'])) ?></span>
    </div>
    <div class="info-row">
        <span>Kasir:</span>
        <span>Sistem (Online)</span>
    </div>
    <div class="info-row">
        <span>Pelanggan:</span>
        <span><?= htmlspecialchars($order['nama_pemesan']) ?> (Meja <?= htmlspecialchars($order['nomor_meja']) ?>)</span>
    </div>

    <div class="divider"></div>

    <?php foreach ($items as $item): ?>
    <div class="item-row">
        <div class="item-qty"><?= $item['qty'] ?>x</div>
        <div class="item-name">
            <?= htmlspecialchars($item['nama_item']) ?>
            <?php if ($item['keterangan']): ?>
            <br><small style="color: #666;">- <?= htmlspecialchars($item['keterangan']) ?></small>
            <?php endif; ?>
        </div>
        <div class="item-price"><?= number_format($item['harga'] * $item['qty'], 0, ',', '.') ?></div>
    </div>
    <?php endforeach; ?>

    <div class="divider"></div>

    <div class="totals">
        <div class="info-row">
            <span>Subtotal</span>
            <span><?= number_format($order['subtotal'], 0, ',', '.') ?></span>
        </div>
        <div class="info-row bold">
            <span>TOTAL</span>
            <span>Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
        </div>
        <div class="info-row" style="margin-top: 10px;">
            <span>Metode Bayar:</span>
            <span style="text-transform: uppercase;"><?= htmlspecialchars($order['metode_bayar']) ?></span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Terima kasih atas pesanan Anda.</p>
        <p>Silakan tunjukkan struk ini kepada pelayan.</p>
    </div>

    <button class="btn-print" onclick="window.print()">Cetak Struk</button>
    <a href="index.php" class="btn-back">Kembali ke Beranda</a>
</div>

</body>
</html>
