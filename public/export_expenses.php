<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$where = [];
$params = [];
if ($from !== '') { $where[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'created_at <= ?'; $params[] = $to . ' 23:59:59'; }
$qwhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM expenses $qwhere ORDER BY created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="expenses_export.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['id','created_at','category','description','amount']);
foreach($rows as $r) fputcsv($out, [$r['id'],$r['created_at'],$r['category'],$r['description'],$r['amount']]);
fclose($out);
exit;