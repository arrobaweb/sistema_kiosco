<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

// fetch suppliers
$$stmt = $pdo->query('SELECT * FROM suppliers ORDER BY name');
$suppliers = $stmt->fetchAll();
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Proveedores <a class="btn btn-sm btn-primary float-end" href="create_supplier.php">Nuevo proveedor</a></h3>
    <table class="table table-striped">
      <thead><tr><th>Nombre</th><th>Contacto</th><th>Teléfono</th><th>Email</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach($suppliers as $s): ?>
          <tr>
            <td><?=htmlspecialchars($s['name'])?></td>
            <td><?=htmlspecialchars($s['contact'])?></td>
            <td><?=htmlspecialchars($s['phone'])?></td>
            <td><?=htmlspecialchars($s['email'])?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="edit_supplier.php?id=<?=$s['id']?>">Editar</a>
              <a class="btn btn-sm btn-danger" href="delete_supplier.php?id=<?=$s['id']?>" onclick="return confirm('Eliminar proveedor?')">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>