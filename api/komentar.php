<?php
// ============================================================
//  API: Komentar (POST)
//  Endpoint: api/komentar.php
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $nama  = trim($body['nama'] ?? '');
    $email = trim($body['email'] ?? '');
    $hp    = trim($body['no_hp'] ?? '');
    $pesan = trim($body['pesan'] ?? '');

    if (!$nama || !$pesan) {
        echo json_encode(['success' => false, 'message' => 'Nama dan komentar wajib diisi.']);
        exit;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare("INSERT INTO komentar (nama, email, no_hp, pesan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $email ?: null, $hp ?: null, $pesan]);
        echo json_encode(['success' => true, 'message' => 'Komentar berhasil dikirim. Terima kasih!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan komentar.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
