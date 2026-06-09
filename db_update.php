<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDB();
    
    // Add status column if it doesn't exist
    $db->exec("ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS status ENUM('pending', 'lunas', 'batal') DEFAULT 'pending'");
    
    // Add expired_at column if it doesn't exist
    $db->exec("ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS expired_at DATETIME NULL");

    // Attempt to add status to detail_pesanan if needed, but probably just pesanan is enough.
    
    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
