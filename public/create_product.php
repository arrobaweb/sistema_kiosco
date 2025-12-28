<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

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
        $stmt = $pdo->prepare('INSERT INTO products (code,name,price,stock,cost) VALUES (?,?,?,?,?)');
        $stmt->execute([$code === '' ? null : $code, $name, $price, $stock, $cost]);
        header('Location: products.php');
        exit;
    }
}
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Crear Producto</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Código</label>
        <input class="form-control" type="text" name="code">
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" type="text" name="name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Precio</label>
        <input class="form-control" type="number" step="0.01" name="price" value="0.00">
      </div>
      <div class="mb-3">
        <label class="form-label">Costo</label>
        <input class="form-control" type="number" step="0.01" name="cost" value="0.00">
      </div>
      <div class="mb-3">
        <label class="form-label">Stock</label>
        <input class="form-control" type="number" name="stock" value="0">
      </div>
      <button class="btn btn-primary" type="submit">Crear</button>
      <a class="btn btn-secondary" href="products.php">Volver</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
