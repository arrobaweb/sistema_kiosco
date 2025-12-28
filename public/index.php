<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

require_once __DIR__ . '/_header.php';
?>
<div class="py-4 text-center">
  <h1>Sistema Kiosco</h1>
  <p class="lead">Bienvenido, <?=htmlspecialchars(Auth::currentUser()['username'] ?? '')?></p>
  <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
    <a class="btn btn-primary btn-lg" href="pos.php">Ir al POS</a>
    <a class="btn btn-secondary btn-lg" href="products.php">Gestionar Productos</a>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
