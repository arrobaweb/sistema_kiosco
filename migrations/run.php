<?php
// Simple migrations runner: executes all .sql files in this directory in alphabetical order.
// Usage: php migrations/run.php
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from CLI\n";
    exit(1);
}
require_once __DIR__ . '/../config/db.php';
$files = glob(__DIR__ . '/*.sql');
sort($files, SORT_STRING);
if (!$files) {
    echo "No .sql migrations found in migrations/\n";
    exit(0);
}
try {
    $pdo->beginTransaction();
    foreach ($files as $f) {
        $sql = file_get_contents($f);
        echo "Applying: " . basename($f) . "... ";
        $pdo->exec($sql);
        echo "OK\n";
    }
    $pdo->commit();
    echo "All migrations applied successfully.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
