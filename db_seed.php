<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDB();

    // Create table
    $db->exec("
        CREATE TABLE IF NOT EXISTS ruangan_reservasi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kategori VARCHAR(50) NOT NULL,
            nama_ruangan VARCHAR(100) NOT NULL,
            deskripsi TEXT,
            kapasitas VARCHAR(50),
            gambar VARCHAR(255),
            aktif TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Check if empty to seed
    $count = $db->query("SELECT COUNT(*) FROM ruangan_reservasi")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO ruangan_reservasi (kategori, nama_ruangan, deskripsi, kapasitas, gambar) VALUES (?, ?, ?, ?, ?)");
        
        $spaces = [
            ['Indoor',      'Cozy Lounge',    'Suasana hangat dengan pencahayaan ambient. Cocok untuk nongkrong santai dan kerja.', '35 orang',       'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=600&q=80'],
            ['Private Room','Meeting Room',   'Ruang meeting ber-AC dengan proyektor Full HD, whiteboard, dan koneksi internet cepat.', '4–20 orang', 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600&q=80'],
            ['Outdoor',     'Teras Hijau',    'Area outdoor dengan tanaman tropis, cocok untuk bersantai di pagi dan sore hari.', '30 orang',        'https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=600&q=80'],
            ['Co-Working',  'Work Zone',      'Area kerja tenang dengan meja panjang, kursi ergonomis, dan akses listrik di setiap meja.', '25 orang', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80'],
            ['Event',       'Venue Privat',   'Area luas untuk gathering, launching produk, seminar, atau ulang tahun spesial Anda.', '100 orang',  'https://images.unsplash.com/photo-1528698827591-e19ccd7bc23d?w=600&q=80'],
            ['Bar Area',    'Coffee Bar',     'Duduk di depan barista dan saksikan seni meracik kopi terbaik secara langsung.', '10 orang',        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=600&q=80'],
        ];

        foreach ($spaces as $s) {
            $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4]]);
        }
        echo "Table created and seeded.\n";
    } else {
        echo "Table already exists and has data.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
