<?php
try {
    require __DIR__ . '/../config/db.php';
    echo isset($pdo) ? 'pdo_ok' : 'pdo_missing';
} catch (Exception $e) {
    echo 'pdo_error: ' . $e->getMessage();
}
