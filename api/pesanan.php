<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $db = getDB();
    $status = $_GET['status'] ?? '';
    if ($status) {
        $stmt = $db->prepare("SELECT * FROM pesanan WHERE status = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$status]);
    } else {
        $stmt = $db->query("SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 50");
    }
    $pesanan = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $pesanan]);
    exit;
}
// ── POST: Buat pesanan baru ───────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $nama        = trim($body['nama'] ?? '');
    $telepon     = trim($body['telepon'] ?? '');
    $meja        = trim($body['meja'] ?? '');
    $catatan     = trim($body['catatan'] ?? '');
    $bayar       = $body['metode_bayar'] ?? 'cash';
    $items       = $body['items'] ?? [];

    // Validasi
    if (!$nama) {
        echo json_encode(['success' => false, 'message' => 'Nama pemesan wajib diisi.']); exit;
    }
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Keranjang kosong.']); exit;
    }
    if (!in_array($bayar, ['qris','cash'])) {
        echo json_encode(['success' => false, 'message' => 'Metode bayar tidak valid.']); exit;
    }

    // Hitung total
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (int)($item['price'] ?? 0) * (int)($item['qty'] ?? 1);
    }
    $total = $subtotal; // bisa tambahkan pajak/service charge di sini

    $nomorPesanan = generateOrderNumber();

    try {
        $db = getDB();
        $db->beginTransaction();

        // Insert pesanan
        $stmt = $db->prepare("
            INSERT INTO pesanan (nomor_pesanan, nama_pemesan, no_telepon, nomor_meja, catatan, metode_bayar, subtotal, total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nomorPesanan, $nama, $telepon, $meja, $catatan, $bayar, $subtotal, $total]);
        $pesananId = $db->lastInsertId();

        // Insert detail
        $stmtDetail = $db->prepare("
            INSERT INTO detail_pesanan (pesanan_id, nama_item, jenis, harga, qty, keterangan)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $stmtDetail->execute([
                $pesananId,
                $item['name'] ?? '-',
                $item['type'] ?? 'menu',
                (int)($item['price'] ?? 0),
                max(1, (int)($item['qty'] ?? 1)),
                $item['meta'] ?? null,
            ]);
        }

        $db->commit();

        echo json_encode([
            'success'       => true,
            'nomor_pesanan' => $nomorPesanan,
            'total'         => $total,
            'total_fmt'     => rupiah($total),
            'message'       => 'Pesanan berhasil dibuat!',
        ]);

    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
