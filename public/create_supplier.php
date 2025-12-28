<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '') {
        $error = 'El nombre es obligatorio';
    } else {
        $stmt = $pdo->prepare('INSERT INTO suppliers (name,contact,phone,email,address) VALUES (?,?,?,?,?)');
        $stmt->execute([$name,$contact,$phone,$email,$address]);
        header('Location: suppliers.php'); exit;
    }
}
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Crear Proveedor</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" type="text" name="name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contacto</label>
        <input class="form-control" type="text" name="contact">
      </div>
      <div class="mb-3">
        <label class="form-label">Teléfono</label>
        <input class="form-control" type="text" name="phone">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email">
      </div>
      <div class="mb-3">
        <label class="form-label">Dirección</label>
        <input class="form-control" type="text" name="address">
      </div>
      <button class="btn btn-primary" type="submit">Crear</button>
      <a class="btn btn-secondary" href="suppliers.php">Volver</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>