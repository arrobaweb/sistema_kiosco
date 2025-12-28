<?php
use PHPUnit\Framework\TestCase;

class SaleProcessorTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // db config loaded by tests/bootstrap.php
        global $pdo;
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new \Exception('DB connection not available in setUp');
        }
        $this->pdo = $pdo;
        // start a transaction so we can rollback after each test
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testMultipleProductsSaleUpdatesStockAndMovements()
    {
        // create two products
        $stmt = $this->pdo->prepare('INSERT INTO products (code, name, price, stock) VALUES (?,?,?,?)');
        $stmt->execute(['TST1', 'Producto Test 1', 10.00, 10]);
        $p1 = (int)$this->pdo->lastInsertId();
        $stmt->execute(['TST2', 'Producto Test 2', 5.50, 20]);
        $p2 = (int)$this->pdo->lastInsertId();

        $postData = [
            'qty' => [ $p1 => 2, $p2 => 3 ],
            'item_discount' => [ $p1 => 10, $p2 => 0 ],
            'tax_percent' => 21,
            'payment_method' => 'efectivo'
        ];

        $processor = new \App\SaleProcessor($this->pdo);
        // We're already in a transaction; disable internal transaction management
        $res = $processor->process($postData, null, false);

        $this->assertArrayHasKey('sale_id', $res);
        $saleId = $res['sale_id'];

        // Verify sale exists
        $stmt = $this->pdo->prepare('SELECT * FROM sales WHERE id = ?');
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        $this->assertNotEmpty($sale);

        // Verify sale items
        $stmt = $this->pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY product_id');
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll();
        $this->assertCount(2, $items);

        // Check discounts applied
        $this->assertEquals(2, $items[0]['quantity']);
        $this->assertEquals(10.00, (float)$items[0]['price']);
        $this->assertEquals(2.00, (float)$items[0]['discount_amount']); // 10% of 20.00

        // Verify stock decreased
        $stmt = $this->pdo->prepare('SELECT stock FROM products WHERE id = ?');
        $stmt->execute([$p1]); $s1 = $stmt->fetchColumn();
        $stmt->execute([$p2]); $s2 = $stmt->fetchColumn();
        $this->assertEquals(8, (int)$s1);
        $this->assertEquals(17, (int)$s2);

        // Verify stock movements
        $stmt = $this->pdo->prepare('SELECT * FROM stock_movements WHERE product_id = ? AND reference_id = ?');
        $stmt->execute([$p1, $saleId]);
        $mov1 = $stmt->fetch();
        $this->assertNotEmpty($mov1);
        $this->assertEquals(-2, (int)$mov1['delta']);

        $stmt->execute([$p2, $saleId]);
        $mov2 = $stmt->fetch();
        $this->assertNotEmpty($mov2);
        $this->assertEquals(-3, (int)$mov2['delta']);

        // Verify accounts entry
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE description = ?');
        $stmt->execute(['Venta ID ' . $saleId]);
        $acc = $stmt->fetch();
        $this->assertNotEmpty($acc);
    }

    public function testSaleWithInsufficientStockThrows()
    {
        // create product with low stock
        $stmt = $this->pdo->prepare('INSERT INTO products (code, name, price, stock) VALUES (?,?,?,?)');
        $stmt->execute(['TST3', 'Producto Test 3', 12.00, 1]);
        $p = (int)$this->pdo->lastInsertId();

        $postData = [ 'qty' => [ $p => 2 ], 'tax_percent' => 0 ];
        $processor = new \App\SaleProcessor($this->pdo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $processor->process($postData, null, false);
    }
}
