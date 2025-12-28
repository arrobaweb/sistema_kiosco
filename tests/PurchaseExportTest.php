<?php
use PHPUnit\Framework\TestCase;

/**
 * Nota: durante las pruebas PHPUnit, algunos scripts (p. ej. `public/export_purchases.php`)
 * llaman a `header()` y `exit()` y asumen un entorno HTTP. Para permitir que el test incluya
 * ese script y capture su salida sin terminar el proceso, definimos `PHPUNIT_RUNNING` en
 * `tests/bootstrap.php` y `export_purchases.php` emite una línea de cabecera adicional y evita `exit`
 * cuando esa constante está presente.
 */

class PurchaseExportTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        global $pdo;
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new \Exception('DB connection not available in setUp');
        }
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testExportPurchasesCsvContainsRecentPurchase()
    {
        // create supplier and purchase
        $stmt = $this->pdo->prepare('INSERT INTO suppliers (name) VALUES (?)');
        $stmt->execute(['Prov CSV']); $sid = (int)$this->pdo->lastInsertId();

        // create product
        $stmt = $this->pdo->prepare('INSERT INTO products (code,name,price,stock,cost) VALUES (?,?,?,?,?)');
        $stmt->execute(['CSV1','P CSV', 1.00, 10, 1.00]); $pid = (int)$this->pdo->lastInsertId();

        // insert a purchase using PurchaseProcessor
        $processor = new \App\PurchaseProcessor($this->pdo);
        $res = $processor->process([
            'qty' => [$pid => 1],
            'purchase_price' => [$pid => 2.50],
            'supplier_id' => $sid,
            'tax_percent' => 0,
            'payment_method' => 'efectivo'
        ], null, false);

        // capture output of export_purchases.php
        $_GET['from'] = date('Y-m-d', strtotime('-1 day'));
        $_GET['to'] = date('Y-m-d', strtotime('+1 day'));
        $_GET['supplier_id'] = $sid;

        ob_start();
        include __DIR__ . '/../public/export_purchases.php';
        $csv = ob_get_clean();

        $this->assertStringContainsString('purchases_export.csv', $csv);
        $this->assertStringContainsString((string)$res['purchase_id'], $csv);
    }
}
