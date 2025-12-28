<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: purchases.php'); exit; }

$stmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id=s.id WHERE p.id = ?');
$stmt->execute([$id]);
$pur = $stmt->fetch();
if (!$pur) { header('Location: purchases.php'); exit; }

$stmt = $pdo->prepare('SELECT pi.*, pr.name AS product_name FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Recibo Compra #<?=$pur['id']?></h3>
    <p>Proveedor: <?=htmlspecialchars($pur['supplier_name'] ?? '—')?></p>
    <p>Fecha: <?=htmlspecialchars($pur['created_at'])?></p>
    <table class="table table-sm">
      <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <tr>
            <td><?=htmlspecialchars($it['product_name'])?></td>
            <td class="text-end"><?= (int)$it['quantity'] ?></td>
            <td class="text-end"><?= number_format($it['price'],2) ?></td>
            <td class="text-end"><?= number_format($it['total'],2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="text-end">
      <div>Subtotal: <strong><?=number_format($pur['subtotal'],2)?></strong></div>
      <div>Impuesto: <strong><?=number_format($pur['tax_amount'],2)?></strong></div>
      <div>Total: <strong><?=number_format($pur['total'],2)?></strong></div>
      <a class="btn btn-primary" href="purchases.php">Volver</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>