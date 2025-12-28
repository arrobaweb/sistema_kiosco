<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$id]);
header('Location: products.php');
exit;
?>
