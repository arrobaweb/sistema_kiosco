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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte por Producto - Imprimir</title>
  <style>body{font-family:Arial,Helvetica,sans-serif} table{width:100%;border-collapse:collapse} td,th{padding:6px;border:1px solid #ccc}</style>
</head>
<body>
  <h2>Reporte por Producto (<?=htmlspecialchars($from)?> — <?=htmlspecialchars($to)?>)</h2>
  <table>
    <thead><tr><th>Producto</th><th class="text-end">Cantidad vendida</th><th class="text-end">Total ventas</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['name'])?></td>
        <td class="text-end"><?=intval($r['qty'])?></td>
        <td class="text-end"><?=number_format($r['total_sales'],2,',','.')?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <script>window.print()</script>
</body>
</html>