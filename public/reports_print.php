<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$payment_filter = $_GET['payment_method'] ?? 'all';
$user_filter = intval($_GET['user_id'] ?? 0);

$sql = "SELECT s.*, u.name as user_name FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE DATE(s.created_at) BETWEEN ? AND ?";
$params = [$from, $to];
if ($payment_filter !== 'all') { $sql .= " AND s.payment_method = ?"; $params[] = $payment_filter; }
if ($user_filter > 0) { $sql .= " AND s.user_id = ?"; $params[] = $user_filter; }
$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte de Ventas - Imprimir</title>
  <style>body{font-family:Arial,Helvetica,sans-serif} table{width:100%;border-collapse:collapse} td,th{padding:6px;border:1px solid #ccc}</style>
</head>
<body>
  <h2>Reporte de Ventas (<?=htmlspecialchars($from)?> — <?=htmlspecialchars($to)?>)</h2>
  <table>
    <thead><tr><th>ID</th><th>Fecha</th><th>Cajero</th><th>Subtotal</th><th>Descuento</th><th>Impuesto</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach($sales as $s): ?>
      <tr>
        <td><?=intval($s['id'])?></td>
        <td><?=htmlspecialchars($s['created_at'])?></td>
        <td><?=htmlspecialchars($s['user_name'] ?? 'N/A')?></td>
        <td><?=number_format($s['subtotal'],2,',','.')?></td>
        <td><?=number_format($s['discount_amount'],2,',','.')?></td>
        <td><?=number_format($s['tax_amount'],2,',','.')?></td>
        <td><?=number_format($s['total'],2,',','.')?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <script>window.print()</script>
</body>
</html>