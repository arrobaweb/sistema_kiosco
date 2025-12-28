<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$payment_filter = $_GET['payment_method'] ?? 'all';
$user_filter = intval($_GET['user_id'] ?? 0);

$sqlp = "SELECT p.id,p.name, SUM(si.quantity) as qty, SUM((si.price * si.quantity) - si.discount_amount) as total_sales
         FROM sale_items si
         JOIN products p ON p.id = si.product_id
         JOIN sales s ON s.id = si.sale_id
         WHERE DATE(s.created_at) BETWEEN ? AND ?";
$paramsP = [$from, $to];
if ($payment_filter !== 'all') { $sqlp .= " AND s.payment_method = ?"; $paramsP[] = $payment_filter; }
if ($user_filter > 0) { $sqlp .= " AND s.user_id = ?"; $paramsP[] = $user_filter; }
$sqlp .= " GROUP BY p.id ORDER BY qty DESC";
$stmtp = $pdo->prepare($sqlp);
$stmtp->execute($paramsP);
$rows = $stmtp->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products_report_'.$from.'_to_'.$to.'.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['product_id','name','quantity_sold','total_sales']);
foreach($rows as $r){ fputcsv($out, [$r['id'],$r['name'],$r['qty'],$r['total_sales']]); }
fclose($out);
exit;