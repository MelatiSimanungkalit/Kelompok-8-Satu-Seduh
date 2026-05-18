<?php
// ============================================================
//  API: Menu & Produk (GET)
//  Endpoint: api/menu.php?kategori=kopi
//            api/menu.php?type=produk
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$db   = getDB();
$type = $_GET['type'] ?? 'menu';

if ($type === 'produk') {
    $stmt = $db->query("SELECT * FROM produk WHERE aktif = 1 ORDER BY id ASC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// Default: menu
$kategori = $_GET['kategori'] ?? '';
if ($kategori && in_array($kategori, ['kopi','nonkopi','makanberat','makanringan'])) {
    $stmt = $db->prepare("SELECT * FROM menu WHERE aktif = 1 AND kategori = ? ORDER BY id ASC");
    $stmt->execute([$kategori]);
} else {
    $stmt = $db->query("SELECT * FROM menu WHERE aktif = 1 ORDER BY kategori, id ASC");
}

echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
