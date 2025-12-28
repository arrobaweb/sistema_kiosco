<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';

// Si ya hay usuarios, no permitir registro público
$existing = $pdo->query('SELECT COUNT(*) as c FROM users')->fetch();
if ($existing && $existing['c'] > 0) {
    echo "Ya existe al menos un usuario. Registro de admin deshabilitado.";
    echo '<p><a href="login.php">Ir a login</a></p>';
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || $username === '' || $password === '') {
        $error = 'Completar todos los campos';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name,username,password,role) VALUES (?,?,?,?)');
        $stmt->execute([$name,$username,$hash,'admin']);
        echo "Administrador creado. <a href=\"login.php\">Ir a login</a>";
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Crear Administrador</title>
</head>
<body>
  <h1>Crear Administrador</h1>
  <?php if ($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
  <form method="post">
    <label>Nombre: <input type="text" name="name" required></label><br>
    <label>Usuario: <input type="text" name="username" required></label><br>
    <label>Contraseña: <input type="password" name="password" required></label><br>
    <button type="submit">Crear</button>
  </form>
</body>
</html>
