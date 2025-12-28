<?php
namespace App;

class SaleProcessor
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Process a sale.
     * @param array $data POST-like data (expects 'qty' => [id => qty], optional 'item_discount' => [id => percent], 'tax_percent', 'payment_method')
     * @param int|null $userId
     * @param bool $manageTransaction whether to begin/commit/rollback inside (useful to disable from tests)
     * @return array ['sale_id' => int, 'subtotal' => float, 'discount_amount' => float, 'tax_amount' => float, 'total' => float]
     * @throws \Exception on validation or DB error
     */
    public function process(array $data, ?int $userId = null, bool $manageTransaction = true): array
    {
        $qtys = $data['qty'] ?? [];
        $payment = $data['payment_method'] ?? 'efectivo';
        $tax_percent = floatval($data['tax_percent'] ?? 0);

        $items = [];
        $subtotal = 0.0;

        foreach ($qtys as $productId => $q) {
            $q = intval($q);
            if ($q <= 0) continue;
            $stmt = $this->pdo->prepare('SELECT id, price, stock FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $p = $stmt->fetch();
            if (!$p) continue;
            if ($p['stock'] < $q) {
                throw new \Exception("Stock insuficiente para el producto ID {$productId}.");
            }
            $items[] = ['product_id' => $productId, 'quantity' => $q, 'price' => (float)$p['price']];
            $subtotal += $p['price'] * $q;
        }

        if (count($items) === 0) {
            throw new \Exception("No se seleccionaron productos.");
        }

        $item_discounts = $data['item_discount'] ?? [];
        $discount_amount_total = 0.0;
        foreach ($items as &$it) {
            $pid = $it['product_id'];
            $discPercent = floatval($item_discounts[$pid] ?? 0);
            $line = $it['price'] * $it['quantity'];
            $discAmount = round($line * ($discPercent/100), 2);
            $it['discount_amount'] = $discAmount;
            $discount_amount_total += $discAmount;
        }
        unset($it);

        $taxable = $subtotal - $discount_amount_total;
        $tax_amount = round($taxable * ($tax_percent/100), 2);
        $total = round($taxable + $tax_amount, 2);

        try {
            if ($manageTransaction) $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('INSERT INTO sales (user_id, subtotal, discount_amount, tax_amount, total, payment_method) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$userId, $subtotal, $discount_amount_total, $tax_amount, $total, $payment]);
            $saleId = (int)$this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, price, discount_amount) VALUES (?,?,?,?,?)');
            $stmtStock = $this->pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            $stmtMovement = $this->pdo->prepare('INSERT INTO stock_movements (product_id, delta, reason, reference_id) VALUES (?,?,?,?)');

            foreach ($items as $it) {
                $stmtItem->execute([$saleId, $it['product_id'], $it['quantity'], $it['price'], $it['discount_amount']]);
                $stmtStock->execute([$it['quantity'], $it['product_id']]);
                $stmtMovement->execute([$it['product_id'], -$it['quantity'], 'venta', $saleId]);
            }

            $stmtAcc = $this->pdo->prepare('INSERT INTO accounts (type, amount, description) VALUES (?,?,?)');
            $stmtAcc->execute(['ingreso', $total, 'Venta ID ' . $saleId]);

            if ($manageTransaction) $this->pdo->commit();

            return ['sale_id' => $saleId, 'subtotal' => (float)$subtotal, 'discount_amount' => (float)$discount_amount_total, 'tax_amount' => (float)$tax_amount, 'total' => (float)$total];
        } catch (\Exception $e) {
            if ($manageTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
