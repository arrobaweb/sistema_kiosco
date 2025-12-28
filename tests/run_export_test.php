<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/PurchaseExportTest.php';

$test = new PurchaseExportTest('testExportPurchasesCsvContainsRecentPurchase');
try {
    $rs = new ReflectionClass($test);
    $setUp = $rs->getMethod('setUp'); $setUp->setAccessible(true); $setUp->invoke($test);
    // Ensure globals and session are available to the included script
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['user_id'] = 1;
    global $pdo; $pdo = $GLOBALS['pdo'];

    ob_start();
    $test->testExportPurchasesCsvContainsRecentPurchase();
    $out = ob_get_clean();
    echo "TEST OK\n";
    echo "Output:\n" . $out . "\n";
    $tear = $rs->getMethod('tearDown'); $tear->setAccessible(true); $tear->invoke($test);
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
