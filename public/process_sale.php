<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/SaleProcessor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$currentUserId = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

$processor = new \App\SaleProcessor($pdo);
try {
    $result = $processor->process($_POST, $currentUserId);
    header('Location: receipt.php?id=' . $result['sale_id']);
    exit;
} catch (Exception $e) {
    echo "Error al registrar la venta: " . $e->getMessage();
}

