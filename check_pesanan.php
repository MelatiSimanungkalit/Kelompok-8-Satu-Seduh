<?php
require 'includes/config.php';
$db = getDB();
$rows = $db->query('SELECT id, nama_pemesan, status FROM pesanan ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
