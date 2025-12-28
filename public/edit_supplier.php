<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: suppliers.php'); exit; }
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '') { $error = 'El nombre es obligatorio'; }
    else {
        $stmt = $pdo->prepare('UPDATE suppliers SET name=?,contact=?,phone=?,email=?,address=? WHERE id=?');
        $stmt->execute([$name,$contact,$phone,$email,$address,$id]);
        header('Location: suppliers.php'); exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
$stmt->execute([$id]);
$supplier = $stmt->fetch();
if (!$supplier) { header('Location: suppliers.php'); exit; }

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Editar Proveedor</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" type="text" name="name" value="<?=htmlspecialchars($supplier['name'])?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contacto</label>
        <input class="form-control" type="text" name="contact" value="<?=htmlspecialchars($supplier['contact'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Teléfono</label>
        <input class="form-control" type="text" name="phone" value="<?=htmlspecialchars($supplier['phone'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?=htmlspecialchars($supplier['email'])?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Dirección</label>
        <input class="form-control" type="text" name="address" value="<?=htmlspecialchars($supplier['address'])?>">
      </div>
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-secondary" href="suppliers.php">Volver</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>