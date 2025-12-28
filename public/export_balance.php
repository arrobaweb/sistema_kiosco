<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare('SELECT SUM(total) as revenue FROM sales WHERE DATE(created_at) BETWEEN ? AND ?');
$stmt->execute([$from,$to]); $revenue = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare('SELECT SUM(si.quantity * p.cost) as cogs FROM sale_items si JOIN products p ON p.id = si.product_id JOIN sales s ON s.id = si.sale_id WHERE DATE(s.created_at) BETWEEN ? AND ?');
$stmt->execute([$from,$to]); $cogs = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare("SELECT SUM(amount) as inc FROM accounts WHERE type='ingreso' AND DATE(created_at) BETWEEN ? AND ? AND description NOT LIKE 'Venta ID %'");
$stmt->execute([$from,$to]); $otherIncome = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare("SELECT SUM(amount) as eg FROM accounts WHERE type='egreso' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from,$to]); $expenses = floatval($stmt->fetchColumn() ?? 0);

$gross = $revenue - $cogs; $net = $gross + $otherIncome - $expenses;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="balance_'.$from.'_to_'.$to.'.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['metric','amount']);
fputcsv($out,['revenue',$revenue]);
fputcsv($out,['cogs',$cogs]);
fputcsv($out,['other_income',$otherIncome]);
fputcsv($out,['expenses',$expenses]);
fputcsv($out,['gross_profit',$gross]);
fputcsv($out,['net_profit',$net]);
fclose($out);
exit;