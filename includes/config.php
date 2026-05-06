<?php
// ============================================================
//  SATU SEDUH — Konfigurasi Database
//  Edit file ini sesuai dengan pengaturan MySQL Anda
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Username MySQL Anda
define('DB_PASS', '');            // Password MySQL Anda
define('DB_NAME', 'satu_seduh');
define('DB_CHARSET', 'utf8mb4');

// Fungsi koneksi PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Helper format rupiah
function rupiah(int $n): string {
    return 'IDR ' . number_format($n, 0, ',', '.');
}

// Helper generate nomor pesanan
function generateOrderNumber(): string {
    return 'SS-' . strtoupper(substr(uniqid(), -6));
}

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
