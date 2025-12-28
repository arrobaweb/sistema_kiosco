<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();
$products = $pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
require_once __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3">Productos</h1>
  <div>
    <a class="btn btn-success" href="create_product.php">Crear producto</a>
  </div>
</div>
<table class="table table-striped table-bordered">
  <thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th class="text-end">Precio</th><th class="text-end">Stock</th><th>Acciones</th></tr></thead>
  <tbody>
    <?php foreach($products as $p): ?>
      <tr>
        <td><?=htmlspecialchars($p['id'])?></td>
        <td><?=htmlspecialchars($p['code'])?></td>
        <td><?=htmlspecialchars($p['name'])?></td>
        <td class="text-end"><?=number_format($p['price'],2,',','.')?></td>
        <td class="text-end"><?=htmlspecialchars($p['stock'])?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="edit_product.php?id=<?=intval($p['id'])?>">Editar</a>
          <form action="delete_product.php" method="post" style="display:inline" onsubmit="return confirm('Confirmar eliminación?');">
            <input type="hidden" name="id" value="<?=intval($p['id'])?>">
            <button class="btn btn-sm btn-danger" type="submit">Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/_footer.php'; ?>
