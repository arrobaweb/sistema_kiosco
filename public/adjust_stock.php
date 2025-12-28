<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock.php');
    exit;
}
$productId = intval($_POST['product_id'] ?? 0);
$delta = intval($_POST['delta'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($productId <= 0 || $delta === 0) {
    header('Location: stock.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    $stmt->execute([$delta, $productId]);

    $stmt2 = $pdo->prepare('INSERT INTO stock_movements (product_id, delta, reason, reference_id) VALUES (?,?,?,NULL)');
    $stmt2->execute([$productId, $delta, $reason]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

header('Location: stock.php');
exit;
?>