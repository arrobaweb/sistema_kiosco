<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$products = $pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
$movements = $pdo->query('SELECT sm.*, p.name FROM stock_movements sm JOIN products p ON p.id = sm.product_id ORDER BY sm.created_at DESC LIMIT 50')->fetchAll();
require_once __DIR__ . '/_header.php';
?>
<div class="row">
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Registrar movimiento</h5>
        <form method="post" action="adjust_stock.php">
          <div class="mb-3">
            <label class="form-label">Producto</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- seleccionar --</option>
              <?php foreach($products as $p): ?>
                <option value="<?=intval($p['id'])?>"><?=htmlspecialchars($p['name'])?> (Stock: <?=intval($p['stock'])?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Delta (usar negativo para salida)</label>
            <input class="form-control" type="number" name="delta" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <input class="form-control" type="text" name="reason">
          </div>
          <button class="btn btn-primary" type="submit">Aplicar</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <h5>Últimos movimientos</h5>
    <table class="table table-sm table-bordered">
      <thead><tr><th>ID</th><th>Producto</th><th>Delta</th><th>Motivo</th><th>Referencia</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($movements as $m): ?>
        <tr>
          <td><?=intval($m['id'])?></td>
          <td><?=htmlspecialchars($m['name'])?></td>
          <td><?=intval($m['delta'])?></td>
          <td><?=htmlspecialchars($m['reason'])?></td>
          <td><?=htmlspecialchars($m['reference_id'])?></td>
          <td><?=htmlspecialchars($m['created_at'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
