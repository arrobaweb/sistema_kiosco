<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if (Auth::login($user, $pass)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña inválidos';
    }
}
require_once __DIR__ . '/_header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card mt-5">
      <div class="card-body">
        <h3 class="card-title">Iniciar sesión</h3>
        <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input class="form-control" type="text" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <button class="btn btn-primary" type="submit">Entrar</button>
        </form>
        <p class="mt-3">Si aún no tenés un administrador, <a href="register_admin.php">crear admin</a> (solo si no existen usuarios).</p>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
