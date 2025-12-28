<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$supplier = intval($_GET['supplier_id'] ?? 0);
$where = [];
$params = [];
if ($from !== '') { $where[] = 'p.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'p.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
if ($supplier > 0) { $where[] = 'p.supplier_id = ?'; $params[] = $supplier; }
$qwhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT p.*, s.name AS supplier FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id $qwhere ORDER BY p.created_at DESC");
$stmt->execute($params);
$purchases = $stmt->fetchAll();
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Reporte de Compras</h3>
    <table class="table table-sm">
      <thead><tr><th>Fecha</th><th>Proveedor</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach($purchases as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['created_at'])?></td>
            <td><?=htmlspecialchars($p['supplier'] ?? '—')?></td>
            <td class="text-end"><?=number_format($p['total'],2)?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="text-end"><button class="btn btn-primary" onclick="window.print()">Imprimir</button></div>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>