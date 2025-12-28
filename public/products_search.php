<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
$q = trim($_GET['q'] ?? '');
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT id,code,name,price,stock FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    echo json_encode($p ?: new stdClass());
    exit;
}
if ($q === '') { echo json_encode([]); exit; }
// Buscar por código exacto primero
$stmt = $pdo->prepare('SELECT id,code,name,price,stock FROM products WHERE code = ? LIMIT 20');
$stmt->execute([$q]);
$rows = $stmt->fetchAll();
if (count($rows) === 0) {
    $qlike = '%' . str_replace(' ','%',$q) . '%';
    $stmt = $pdo->prepare('SELECT id,code,name,price,stock FROM products WHERE name LIKE ? OR code LIKE ? LIMIT 50');
    $stmt->execute([$qlike, $qlike]);
    $rows = $stmt->fetchAll();
}
echo json_encode($rows);
