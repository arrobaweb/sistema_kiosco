<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// filters
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$where = [];
$params = [];
if ($from !== '') { $where[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'created_at <= ?'; $params[] = $to . ' 23:59:59'; }
$qwhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT e.*, u.username FROM expenses e LEFT JOIN users u ON e.user_id = u.id $qwhere ORDER BY e.created_at DESC");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Gastos <a class="btn btn-sm btn-primary float-end" href="create_expense.php">Nuevo gasto</a></h3>
    <form class="row g-2 mb-3">
      <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
      <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
      <div class="col-auto"><button class="btn btn-secondary" type="submit">Filtrar</button></div>
      <div class="col-auto"><a class="btn btn-outline-secondary" href="export_expenses.php?from=<?=$from?>&to=<?=$to?>">Exportar CSV</a></div>
    </form>
    <table class="table table-sm">
      <thead><tr><th>Fecha</th><th>Categoría</th><th>Descripción</th><th class="text-end">Monto</th><th></th></tr></thead>
      <tbody>
        <?php foreach($expenses as $e): ?>
          <tr>
            <td><?=htmlspecialchars($e['created_at'])?></td>
            <td><?=htmlspecialchars($e['category'])?></td>
            <td><?=htmlspecialchars($e['description'])?></td>
            <td class="text-end"><?=number_format($e['amount'],2)?></td>
            <td><a class="btn btn-sm btn-danger" href="delete_expense.php?id=<?=$e['id']?>" onclick="return confirm('Eliminar gasto?')">Eliminar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>