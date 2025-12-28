<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// filters
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$supplier = intval($_GET['supplier_id'] ?? 0);
$where = [];
$params = [];
if ($from !== '') { $where[] = 'p.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'p.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
if ($supplier > 0) { $where[] = 'p.supplier_id = ?'; $params[] = $supplier; }
$qwhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT p.*, s.name AS supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id $qwhere ORDER BY p.created_at DESC");
$stmt->execute($params);
$purchases = $stmt->fetchAll();

// suppliers list for filter select
$sstmt = $pdo->query('SELECT id,name FROM suppliers ORDER BY name');
$suppliers = $sstmt->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2">
      <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
      <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
      <div class="col-auto">
        <select name="supplier_id" class="form-select">
          <option value="0">-- Todos proveedores --</option>
          <?php foreach($suppliers as $s): ?>
            <option value="<?=$s['id']?>" <?= $s['id']==$supplier ? 'selected' : '' ?>><?=htmlspecialchars($s['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-secondary" type="submit">Filtrar</button></div>
      <div class="col-auto"><a class="btn btn-outline-secondary" href="export_purchases.php?from=<?=$from?>&to=<?=$to?>&supplier_id=<?=$supplier?>">Exportar CSV</a></div>
      <div class="col-auto"><a class="btn btn-outline-secondary" href="purchase_report_print.php?from=<?=$from?>&to=<?=$to?>&supplier_id=<?=$supplier?>">Imprimir</a></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h3 class="card-title">Compras <a class="btn btn-sm btn-primary float-end" href="create_purchase.php">Nueva compra</a></h3>
    <table class="table table-sm">
      <thead><tr><th>Fecha</th><th>Proveedor</th><th>Total</th><th>Método</th><th></th></tr></thead>
      <tbody>
        <?php foreach($purchases as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['created_at'])?></td>
            <td><?=htmlspecialchars($p['supplier_name'] ?? '—')?></td>
            <td class="text-end"><?=number_format($p['total'],2)?></td>
            <td><?=htmlspecialchars($p['payment_method'])?></td>
            <td><a class="btn btn-sm btn-secondary" href="purchase_receipt.php?id=<?=$p['id']?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>