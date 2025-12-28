<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/_header.php';

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$threshold = intval($_GET['threshold'] ?? 5);
$payment_filter = $_GET['payment_method'] ?? 'all';
$user_filter = intval($_GET['user_id'] ?? 0);

// cargar usuarios para filtro
$users = $pdo->query('SELECT id,name FROM users ORDER BY name')->fetchAll();

// ventas por rango con filtros
$sql = "SELECT s.*, u.name as user_name FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE DATE(s.created_at) BETWEEN ? AND ?";
$params = [$from, $to];
if ($payment_filter !== 'all') { $sql .= " AND s.payment_method = ?"; $params[] = $payment_filter; }
if ($user_filter > 0) { $sql .= " AND s.user_id = ?"; $params[] = $user_filter; }
$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// reporte por producto
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
$productsReport = $stmtp->fetchAll();

$totals = ['subtotal'=>0,'discount'=>0,'tax'=>0,'total'=>0];
foreach($sales as $s){
    $totals['subtotal'] += $s['subtotal'];
    $totals['discount'] += $s['discount_amount'];
    $totals['tax'] += $s['tax_amount'];
    $totals['total'] += $s['total'];
}

// productos stock bajo
$stmt2 = $pdo->prepare('SELECT * FROM products WHERE stock <= ? ORDER BY stock ASC');
$stmt2->execute([$threshold]);
$lowStock = $stmt2->fetchAll();
?>
<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Reporte de Ventas</h5>
        <form class="row g-2 mb-3">
          <div class="col-auto">
            <label class="form-label">Desde</label>
            <input class="form-control" type="date" name="from" value="<?=htmlspecialchars($from)?>">
          </div>
          <div class="col-auto">
            <label class="form-label">Hasta</label>
            <input class="form-control" type="date" name="to" value="<?=htmlspecialchars($to)?>">
          </div>
          <div class="col-auto">
            <label class="form-label">Método de pago</label>
            <select name="payment_method" class="form-select">
              <option value="all" <?= $payment_filter==='all' ? 'selected' : '' ?>>Todos</option>
              <option value="efectivo" <?= $payment_filter==='efectivo' ? 'selected' : '' ?>>Efectivo</option>
              <option value="tarjeta" <?= $payment_filter==='tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label">Usuario</label>
            <select name="user_id" class="form-select">
              <option value="0">Todos</option>
              <?php foreach($users as $u): ?>
                <option value="<?=intval($u['id'])?>" <?= intval($user_filter) === intval($u['id']) ? 'selected' : '' ?>><?=htmlspecialchars($u['name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto align-self-end">
            <button class="btn btn-primary" type="submit">Generar</button>
            <a class="btn btn-outline-secondary" href="export_sales.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment_method=<?=urlencode($payment_filter)?>&user_id=<?=intval($user_filter)?>">Exportar CSV</a>
            <a class="btn btn-outline-secondary" href="reports_print.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment_method=<?=urlencode($payment_filter)?>&user_id=<?=intval($user_filter)?>" target="_blank">Imprimir/Exportar PDF</a>
          </div>
        </form>

        <table class="table table-sm table-bordered">
          <thead><tr><th>ID</th><th>Fecha</th><th>Cajero</th><th class="text-end">Subtotal</th><th class="text-end">Descuento</th><th class="text-end">Impuesto</th><th class="text-end">Total</th><th>Pago</th></tr></thead>
          <tbody>
            <?php foreach($sales as $s): ?>
            <tr>
              <td><?=intval($s['id'])?></td>
              <td><?=htmlspecialchars($s['created_at'])?></td>
              <td><?=htmlspecialchars($s['user_name'] ?? 'N/A')?></td>
              <td class="text-end"><?=number_format($s['subtotal'],2,',','.')?></td>
              <td class="text-end"><?=number_format($s['discount_amount'],2,',','.')?></td>
              <td class="text-end"><?=number_format($s['tax_amount'],2,',','.')?></td>
              <td class="text-end"><?=number_format($s['total'],2,',','.')?></td>
              <td><?=htmlspecialchars($s['payment_method'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3">Totales</th>
              <th class="text-end"><?=number_format($totals['subtotal'],2,',','.')?></th>
              <th class="text-end">-<?=number_format($totals['discount'],2,',','.')?></th>
              <th class="text-end">+<?=number_format($totals['tax'],2,',','.')?></th>
              <th class="text-end"><?=number_format($totals['total'],2,',','.')?></th>
              <th></th>
            </tr>
          </tfoot>
        </table>

        <hr>
        <h5>Reporte por Producto</h5>
        <div class="mb-2">
          <a class="btn btn-outline-secondary btn-sm" href="export_products.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment_method=<?=urlencode($payment_filter)?>&user_id=<?=intval($user_filter)?>">Exportar producto CSV</a>
          <a class="btn btn-outline-secondary btn-sm" href="product_report_print.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment_method=<?=urlencode($payment_filter)?>&user_id=<?=intval($user_filter)?>" target="_blank">Imprimir/Exportar PDF</a>
        </div>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Producto</th><th class="text-end">Cantidad vendida</th><th class="text-end">Total ventas</th></tr></thead>
          <tbody>
            <?php foreach($productsReport as $p): ?>
            <tr>
              <td><?=htmlspecialchars($p['name'])?></td>
              <td class="text-end"><?=intval($p['qty'])?></td>
              <td class="text-end"><?=number_format($p['total_sales'],2,',','.')?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Stock bajo (<= <?=intval($threshold)?>)</h5>
        <form class="mb-2">
          <div class="input-group">
            <input type="number" name="threshold" value="<?=intval($threshold)?>" class="form-control">
            <button class="btn btn-sm btn-primary" type="submit">Actualizar</button>
          </div>
        </form>
        <table class="table table-sm">
          <thead><tr><th>Producto</th><th class="text-end">Stock</th></tr></thead>
          <tbody>
            <?php foreach($lowStock as $p): ?>
            <tr>
              <td><?=htmlspecialchars($p['name'])?></td>
              <td class="text-end"><?=intval($p['stock'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>