<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT s.*, u.name as user_name FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE DATE(s.created_at) BETWEEN ? AND ? ORDER BY s.created_at DESC");
$stmt->execute([$from, $to]);
$sales = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_'.$from.'_to_'.$to.'.csv"');
$out = fopen('php://output', 'w');
// headers
fputcsv($out, ['id','created_at','user','subtotal','discount','tax','total','payment_method']);
foreach($sales as $s){
    fputcsv($out, [ $s['id'], $s['created_at'], $s['user_name'], $s['subtotal'], $s['discount_amount'], $s['tax_amount'], $s['total'], $s['payment_method'] ]);
}
fclose($out);
exit;