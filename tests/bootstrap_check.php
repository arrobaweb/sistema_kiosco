<?php
try {
    require __DIR__ . '/bootstrap.php';
    echo isset($pdo) && $pdo instanceof PDO ? 'bootstrap_ok' : 'bootstrap_no_pdo';
} catch (Throwable $e) {
    echo 'bootstrap_error: ' . $e->getMessage();
}
