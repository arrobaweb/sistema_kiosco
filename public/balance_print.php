<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$stmt = $pdo->prepare('SELECT SUM(total) as revenue FROM sales WHERE DATE(created_at) BETWEEN ? AND ?'); $stmt->execute([$from,$to]); $revenue = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare('SELECT SUM(si.quantity * p.cost) as cogs FROM sale_items si JOIN products p ON p.id = si.product_id JOIN sales s ON s.id = si.sale_id WHERE DATE(s.created_at) BETWEEN ? AND ?'); $stmt->execute([$from,$to]); $cogs = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare("SELECT SUM(amount) as inc FROM accounts WHERE type='ingreso' AND DATE(created_at) BETWEEN ? AND ? AND description NOT LIKE 'Venta ID %'"); $stmt->execute([$from,$to]); $otherIncome = floatval($stmt->fetchColumn() ?? 0);
$stmt = $pdo->prepare("SELECT SUM(amount) as eg FROM accounts WHERE type='egreso' AND DATE(created_at) BETWEEN ? AND ?"); $stmt->execute([$from,$to]); $expenses = floatval($stmt->fetchColumn() ?? 0);
$gross = $revenue - $cogs; $net = $gross + $otherIncome - $expenses;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Balance (Imprimir)</title>
  <style>body{font-family:Arial,Helvetica,sans-serif} table{width:100%;border-collapse:collapse} td,th{padding:6px;border:1px solid #ccc}</style>
</head>
<body>
  <h2>Balance (<?=htmlspecialchars($from)?> — <?=htmlspecialchars($to)?>)</h2>
  <table>
    <tbody>
      <tr><td>Ventas (Ingresos)</td><td class="text-end"><?=number_format($revenue,2,',','.')?></td></tr>
      <tr><td>COGS</td><td class="text-end"><?=number_format($cogs,2,',','.')?></td></tr>
      <tr><td>Otros ingresos</td><td class="text-end"><?=number_format($otherIncome,2,',','.')?></td></tr>
      <tr><td>Gastos</td><td class="text-end"><?=number_format($expenses,2,',','.')?></td></tr>
      <tr><th>Utilidad bruta</th><th class="text-end"><?=number_format($gross,2,',','.')?></th></tr>
      <tr><th>Utilidad neta</th><th class="text-end"><?=number_format($net,2,',','.')?></th></tr>
    </tbody>
  </table>
  <script>window.print()</script>
</body>
</html>