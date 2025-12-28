<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: products.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo 'Producto no encontrado'; exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $cost = floatval($_POST['cost'] ?? 0);
    if ($name === '') {
        $error = 'El nombre es obligatorio';
    } else {
        $stmt = $pdo->prepare('UPDATE products SET code = ?, name = ?, price = ?, stock = ?, cost = ? WHERE id = ?');
        $stmt->execute([$code === '' ? null : $code, $name, $price, $stock, $cost, $id]);
        header('Location: products.php');
        exit;
    }
}
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Editar Producto</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Código</label>
        <input class="form-control" type="text" name="code" value="<?=htmlspecialchars($p['code'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" type="text" name="name" required value="<?=htmlspecialchars($p['name'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Precio</label>
        <input class="form-control" type="number" step="0.01" name="price" value="<?=htmlspecialchars($p['price'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Costo</label>
        <input class="form-control" type="number" step="0.01" name="cost" value="<?=htmlspecialchars($p['cost'] ?? 0)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Stock</label>
        <input class="form-control" type="number" name="stock" value="<?=htmlspecialchars($p['stock'])?>">
      </div>
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-secondary" href="products.php">Volver</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
