<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/PurchaseProcessor.php';
Auth::requireLogin();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    if ($amount <= 0) { $error = 'El monto debe ser mayor que cero'; }
    else {
        $processor = new \App\PurchaseProcessor($pdo);
        $currentUserId = null;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!empty($_SESSION['user_id'])) $currentUserId = intval($_SESSION['user_id']);
        $processor->addExpense(['category'=>$category, 'amount'=>$amount, 'description'=>$description], $currentUserId);
        header('Location: expenses.php'); exit;
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Registrar Gasto</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <div id="formError" class="alert alert-danger d-none"></div>
    <form id="expenseForm" method="post">
      <div class="mb-3">
        <label class="form-label">Categoría</label>
        <input class="form-control" type="text" name="category">
      </div>
      <div class="mb-3">
        <label class="form-label">Monto</label>
        <input class="form-control" type="number" step="0.01" name="amount" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Descripción</label>
        <input class="form-control" type="text" name="description">
      </div>
      <button class="btn btn-primary" type="submit">Registrar</button>
      <a class="btn btn-secondary" href="expenses.php">Volver</a>
    </form>
  </div>
</div>
<script>
  const expenseForm = document.getElementById('expenseForm');
  const expFormError = document.getElementById('formError');
  expenseForm.addEventListener('submit', function(e){
    expFormError.classList.add('d-none'); expFormError.textContent = '';
    const amount = parseFloat(this.querySelector('input[name="amount"]').value) || 0;
    if (amount <= 0) { e.preventDefault(); expFormError.textContent = 'El monto debe ser mayor que cero'; expFormError.classList.remove('d-none'); return; }
  });
</script>
<?php require_once __DIR__ . '/_footer.php'; ?>