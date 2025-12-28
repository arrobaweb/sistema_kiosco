<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/PurchaseProcessor.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_purchase.php'); exit;
}

$currentUserId = null;
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['user_id'])) $currentUserId = intval($_SESSION['user_id']);

$processor = new \App\PurchaseProcessor($pdo);
try {
    $res = $processor->process($_POST, $currentUserId);
    header('Location: purchase_receipt.php?id=' . $res['purchase_id']); exit;
} catch (Exception $e) {
    echo 'Error al registrar compra: ' . $e->getMessage();
}