<?php
use PHPUnit\Framework\TestCase;

class PurchaseProcessorTest extends TestCase
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
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testMultipleProductsPurchaseUpdatesStockCostAndMovements()
    {
        // insert two products with initial stock and cost
        $stmt = $this->pdo->prepare('INSERT INTO products (code, name, price, stock, cost) VALUES (?,?,?,?,?)');
        $stmt->execute(['CP1', 'Producto Compra 1', 0.00, 5, 8.00]);
        $p1 = (int)$this->pdo->lastInsertId();
        $stmt->execute(['CP2', 'Producto Compra 2', 0.00, 0, 0.00]);
        $p2 = (int)$this->pdo->lastInsertId();

        $postData = [
            'qty' => [ $p1 => 3, $p2 => 2 ],
            'purchase_price' => [ $p1 => 9.00, $p2 => 4.50 ],
            'tax_percent' => 0,
            'payment_method' => 'efectivo'
        ];

        $processor = new \App\PurchaseProcessor($this->pdo);
        $res = $processor->process($postData, null, false);

        $this->assertArrayHasKey('purchase_id', $res);
        $purchaseId = $res['purchase_id'];

        // verify purchase exists
        $stmt = $this->pdo->prepare('SELECT * FROM purchases WHERE id = ?');
        $stmt->execute([$purchaseId]);
        $purchase = $stmt->fetch();
        $this->assertNotEmpty($purchase);

        // verify items
        $stmt = $this->pdo->prepare('SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY product_id');
        $stmt->execute([$purchaseId]);
        $items = $stmt->fetchAll();
        $this->assertCount(2, $items);

        // check stocks
        $stmt = $this->pdo->prepare('SELECT stock, cost FROM products WHERE id = ?');
        $stmt->execute([$p1]); $row1 = $stmt->fetch();
        $stmt->execute([$p2]); $row2 = $stmt->fetch();
        $this->assertEquals(8, (int)$row1['stock']); // 5 + 3
        $this->assertEquals(2, (int)$row2['stock']); // 0 + 2

        // check weighted average cost for p1: (5*8 + 3*9) / 8 = 8.375 -> 8.38
        $this->assertEquals(8.38, (float)$row1['cost']);

        // verify stock movements
        $stmt = $this->pdo->prepare('SELECT * FROM stock_movements WHERE product_id = ? AND reference_id = ?');
        $stmt->execute([$p1, $purchaseId]); $mov1 = $stmt->fetch();
        $this->assertNotEmpty($mov1);
        $this->assertEquals(3, (int)$mov1['delta']);

        $stmt->execute([$p2, $purchaseId]); $mov2 = $stmt->fetch();
        $this->assertNotEmpty($mov2);
        $this->assertEquals(2, (int)$mov2['delta']);

        // verify accounts egreso
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE description = ?');
        $stmt->execute(['Compra ID ' . $purchaseId]);
        $acc = $stmt->fetch();
        $this->assertNotEmpty($acc);
    }

    public function testAddExpenseCreatesRecordAndAccount()
    {
        $processor = new \App\PurchaseProcessor($this->pdo);
        $expenseData = ['category' => 'Servicios', 'amount' => 123.45, 'description' => 'Luz'];
        $expenseId = $processor->addExpense($expenseData, null, false);

        $stmt = $this->pdo->prepare('SELECT * FROM expenses WHERE id = ?');
        $stmt->execute([$expenseId]);
        $exp = $stmt->fetch();
        $this->assertNotEmpty($exp);
        $this->assertEquals(123.45, (float)$exp['amount']);

        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE description = ?');
        $stmt->execute(['Gasto ID ' . $expenseId]);
        $acc = $stmt->fetch();
        $this->assertNotEmpty($acc);
    }
}
