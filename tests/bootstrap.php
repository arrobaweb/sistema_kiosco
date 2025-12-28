<?php
// Try to load composer autoload if available
$vendor = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendor)) {
    require $vendor;
}

// Ensure db config and class files are available for tests
require_once __DIR__ . '/../config/db.php';
// If the config didn't initialize $pdo for some reason, try to create it here
if (!isset($pdo) || !$pdo instanceof PDO) {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        throw new Exception('DB connection ($pdo) not available and no DB constants defined.');
    }
}
// Export $pdo explicitly so tests can access it as a global
$GLOBALS['pdo'] = $pdo;

// Indicate we're running under PHPUnit to avoid scripts calling exit()/headers
if (!defined('PHPUNIT_RUNNING')) define('PHPUNIT_RUNNING', true);

require_once __DIR__ . '/../src/SaleProcessor.php';
require_once __DIR__ . '/../src/PurchaseProcessor.php';
