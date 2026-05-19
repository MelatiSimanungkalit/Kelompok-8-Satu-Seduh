<?php
require_once __DIR__ . '/../includes/config.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$db = getDB();

$mode     = $_GET['mode']     ?? '7d';       // 7d | 14d | 30d | 6m | 1y | custom
$dateFrom = $_GET['from']     ?? null;
$dateTo   = $_GET['to']       ?? null;

// Tentukan range berdasarkan mode
switch ($mode) {
    case '14d':
        $from = date('Y-m-d', strtotime('-13 days'));
        $to   = date('Y-m-d');
        $groupBy = 'day';
        break;
    case '30d':
        $from = date('Y-m-d', strtotime('-29 days'));
        $to   = date('Y-m-d');
        $groupBy = 'day';
        break;
    case '6m':
        $from = date('Y-m-01', strtotime('-5 months'));
        $to   = date('Y-m-d');
        $groupBy = 'month';
        break;
    case '1y':
        $from = date('Y-m-01', strtotime('-11 months'));
        $to   = date('Y-m-d');
        $groupBy = 'month';
        break;
    case 'custom':
        $from    = $dateFrom ?? date('Y-m-d', strtotime('-6 days'));
        $to      = $dateTo   ?? date('Y-m-d');
        // Hitung selisih hari, kalau > 60 hari pakai monthly
        $diff    = (strtotime($to) - strtotime($from)) / 86400;
        $groupBy = $diff > 60 ? 'month' : 'day';
        break;
    default: // 7d
        $from = date('Y-m-d', strtotime('-6 days'));
        $to   = date('Y-m-d');
        $groupBy = 'day';
        break;
}

// Query berdasarkan groupBy
if ($groupBy === 'month') {
    $rows = $db->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as period,
               COALESCE(SUM(total), 0)           as total
        FROM pesanan
        WHERE DATE(created_at) BETWEEN :from AND :to
          AND status NOT IN ('cancelled')
        GROUP BY period
        ORDER BY period ASC
    ");
} else {
    $rows = $db->prepare("
        SELECT DATE(created_at)           as period,
               COALESCE(SUM(total), 0)    as total
        FROM pesanan
        WHERE DATE(created_at) BETWEEN :from AND :to
          AND status NOT IN ('cancelled')
        GROUP BY period
        ORDER BY period ASC
    ");
}
$rows->execute([':from' => $from, ':to' => $to]);
$rows = $rows->fetchAll();

// Bangun map dari hasil query
$map = [];
foreach ($rows as $r) {
    $map[$r['period']] = (int)$r['total'];
}

// Isi semua slot dengan 0 supaya tidak ada gap
$labels = [];
$data   = [];

if ($groupBy === 'month') {
    $cur = strtotime(date('Y-m-01', strtotime($from)));
    $end = strtotime(date('Y-m-01', strtotime($to)));
    while ($cur <= $end) {
        $key      = date('Y-m', $cur);
        $labels[] = date('M Y', $cur);
        $data[]   = $map[$key] ?? 0;
        $cur      = strtotime('+1 month', $cur);
    }
} else {
    $cur = strtotime($from);
    $end = strtotime($to);
    while ($cur <= $end) {
        $key      = date('Y-m-d', $cur);
        $labels[] = date('d M', $cur);
        $data[]   = $map[$key] ?? 0;
        $cur      = strtotime('+1 day', $cur);
    }
}

// Summary stats
$totalRevenue = array_sum($data);
$maxRevenue   = max(array_merge($data, [0]));
$avgRevenue   = count($data) > 0 ? round($totalRevenue / count($data)) : 0;

echo json_encode([
    'labels'  => $labels,
    'data'    => $data,
    'summary' => [
        'total' => $totalRevenue,
        'max'   => $maxRevenue,
        'avg'   => $avgRevenue,
        'from'  => $from,
        'to'    => $to,
    ]
]);
