<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE pesanan MODIFY status ENUM('pending', 'lunas', 'confirmed', 'preparing', 'ready', 'done', 'cancelled') DEFAULT 'pending'");
    echo "ENUM status updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating ENUM: " . $e->getMessage() . "\n";
}
