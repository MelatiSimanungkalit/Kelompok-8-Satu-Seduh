<?php
// ============================================================
//  API: Reservasi (POST)
//  Endpoint: api/reservasi.php
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Daftar reservasi ────────────────────────────────────
if ($method === 'GET') {
    $db   = getDB();
    $stmt = $db->query("SELECT * FROM reservasi ORDER BY tanggal DESC, waktu DESC LIMIT 100");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── POST: Buat reservasi baru ────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $nama    = trim($body['nama'] ?? '');
    $wa      = trim($body['whatsapp'] ?? '');
    $ruangan = trim($body['ruangan'] ?? '');
    $tgl     = trim($body['tanggal'] ?? '');
    $waktu   = trim($body['waktu'] ?? '');
    $durasi  = trim($body['durasi'] ?? '');
    $orang   = (int)($body['jumlah_orang'] ?? 0);
    $catatan = trim($body['catatan'] ?? '');

    // Validasi wajib
    $errors = [];
    if (!$nama)    $errors[] = 'Nama wajib diisi.';
    if (!$wa)      $errors[] = 'No. WhatsApp wajib diisi.';
    if (!$ruangan) $errors[] = 'Ruangan wajib dipilih.';
    if (!$tgl)     $errors[] = 'Tanggal wajib diisi.';
    if (!$waktu)   $errors[] = 'Waktu mulai wajib diisi.';

    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // Validasi format tanggal
    $tglObj = DateTime::createFromFormat('Y-m-d', $tgl);
    if (!$tglObj || $tglObj->format('Y-m-d') !== $tgl) {
        echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']);
        exit;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO reservasi (nama, whatsapp, ruangan, tanggal, waktu, durasi, jumlah_orang, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nama, $wa, $ruangan, $tgl, $waktu, $durasi, $orang ?: null, $catatan ?: null]);

        echo json_encode([
            'success' => true,
            'message' => 'Reservasi berhasil dikirim! Tim kami akan menghubungi Anda dalam 30 menit.',
            'data' => [
                'nama'        => $nama,
                'whatsapp'    => $wa,
                'ruangan'     => $ruangan,
                'tanggal'     => $tgl,
                'waktu'       => $waktu,
                'durasi'      => $durasi,
                'jumlah_orang'=> $orang,
                'catatan'     => $catatan,
            ],
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan reservasi: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
