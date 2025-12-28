<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/_header.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo 'ID de venta inválido'; exit; }

$stmt = $pdo->prepare('SELECT s.*, u.name as user_name FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) { echo 'Venta no encontrada'; exit; }

$stmt = $pdo->prepare('SELECT si.*, p.name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <h3 class="card-title">Recibo - Venta #<?=htmlspecialchars($sale['id'])?></h3>
        <p class="small">Fecha: <?=htmlspecialchars($sale['created_at'])?><br>
        Cajero: <?=htmlspecialchars($sale['user_name'] ?? 'N/A')?><br>
        Método: <?=htmlspecialchars($sale['payment_method'])?></p>

        <table class="table table-sm">
          <tbody>
            <?php foreach($items as $it): ?>
              <tr>
                <td><?=htmlspecialchars($it['name'])?> x <?=intval($it['quantity'])?></td>
                <td class="text-end"><?=number_format($it['price'] * $it['quantity'],2,',','.')?></td>
              </tr>
              <?php if (!empty($it['discount_amount']) && $it['discount_amount'] > 0): ?>
              <tr>
                <td class="small">Descuento ítem</td>
                <td class="text-end">- <?=number_format($it['discount_amount'],2,',','.')?></td>
              </tr>
              <?php endif; ?>
            <?php endforeach; ?>
            <tr><td>Subtotal</td><td class="text-end"><?=number_format($sale['subtotal'] ?? 0,2,',','.')?></td></tr>
            <tr><td>Descuento</td><td class="text-end">- <?=number_format($sale['discount_amount'] ?? 0,2,',','.')?></td></tr>
            <tr><td>Impuesto</td><td class="text-end">+ <?=number_format($sale['tax_amount'] ?? 0,2,',','.')?></td></tr>
            <tr><th>Total</th><th class="text-end"><?=number_format($sale['total'],2,',','.')?></th></tr>
          </tbody>
        </table>

        <div class="text-end">
          <button id="printBtn" class="btn btn-sm btn-outline-primary" onclick="window.print()">Imprimir</button>
          <a class="btn btn-sm btn-secondary" href="pos.php">Volver al POS</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
