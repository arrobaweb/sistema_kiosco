<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
require_once __DIR__ . '/_header.php';

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

// Ventas en periodo
$stmt = $pdo->prepare('SELECT SUM(total) as revenue FROM sales WHERE DATE(created_at) BETWEEN ? AND ?');
$stmt->execute([$from,$to]);
$revRow = $stmt->fetch();
$revenue = floatval($revRow['revenue'] ?? 0);

// COGS: sum(quantity * cost)
$stmt = $pdo->prepare('SELECT SUM(si.quantity * p.cost) as cogs FROM sale_items si JOIN products p ON p.id = si.product_id JOIN sales s ON s.id = si.sale_id WHERE DATE(s.created_at) BETWEEN ? AND ?');
$stmt->execute([$from,$to]);
$cogsRow = $stmt->fetch();
$cogs = floatval($cogsRow['cogs'] ?? 0);

// Ingresos externos (no ventas)
$stmt = $pdo->prepare("SELECT SUM(amount) as inc FROM accounts WHERE type='ingreso' AND DATE(created_at) BETWEEN ? AND ? AND description NOT LIKE 'Venta ID %'");
$stmt->execute([$from,$to]);
$incRow = $stmt->fetch();
$otherIncome = floatval($incRow['inc'] ?? 0);

// Egresos
$stmt = $pdo->prepare("SELECT SUM(amount) as eg FROM accounts WHERE type='egreso' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from,$to]);
$egRow = $stmt->fetch();
$expenses = floatval($egRow['eg'] ?? 0);

$grossProfit = $revenue - $cogs;
$netProfit = $grossProfit + $otherIncome - $expenses;

// listar movimientos accounts
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC');
$stmt->execute([$from,$to]);
$accounts = $stmt->fetchAll();

?>
<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Balance (<?=htmlspecialchars($from)?> — <?=htmlspecialchars($to)?>)</h5>
        <form class="row g-2 mb-3">
          <div class="col-auto">
            <label class="form-label">Desde</label>
            <input class="form-control" type="date" name="from" value="<?=htmlspecialchars($from)?>">
          </div>
          <div class="col-auto">
            <label class="form-label">Hasta</label>
            <input class="form-control" type="date" name="to" value="<?=htmlspecialchars($to)?>">
          </div>
          <div class="col-auto align-self-end">
            <button class="btn btn-primary" type="submit">Generar</button>
            <a class="btn btn-outline-secondary" href="export_balance.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>">Exportar CSV</a>
            <a class="btn btn-outline-secondary" href="balance_print.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>" target="_blank">Imprimir</a>
          </div>
        </form>

        <table class="table table-sm">
          <tbody>
            <tr><td>Ventas (Ingresos)</td><td class="text-end"><?=number_format($revenue,2,',','.')?></td></tr>
            <tr><td>COGS (Costo de ventas)</td><td class="text-end"><?=number_format($cogs,2,',','.')?></td></tr>
            <tr><td>Otros ingresos</td><td class="text-end"><?=number_format($otherIncome,2,',','.')?></td></tr>
            <tr><td>Gastos</td><td class="text-end">- <?=number_format($expenses,2,',','.')?></td></tr>
            <tr class="table-active"><th>Utilidad bruta</th><th class="text-end"><?=number_format($grossProfit,2,',','.')?></th></tr>
            <tr class="table-success"><th>Utilidad neta</th><th class="text-end"><?=number_format($netProfit,2,',','.')?></th></tr>
          </tbody>
        </table>

        <h6>Movimientos (cuentas)</h6>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Tipo</th><th>Importe</th><th>Descripción</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach($accounts as $a): ?>
            <tr>
              <td><?=htmlspecialchars($a['type'])?></td>
              <td class="text-end"><?=number_format($a['amount'],2,',','.')?></td>
              <td><?=htmlspecialchars($a['description'])?></td>
              <td><?=htmlspecialchars($a['created_at'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>