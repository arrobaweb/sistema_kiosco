<?php
namespace App;

class PurchaseProcessor
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Process a purchase.
     * Expects POST-like data with:
     *  - 'qty' => [productId => qty]
     *  - 'purchase_price' => [productId => price]
     *  - 'supplier_id' (optional), 'tax_percent', 'payment_method', 'invoice_number'
     * @return array ['purchase_id'=>int, 'subtotal'=>float, 'tax_amount'=>float, 'total'=>float]
     */
    public function process(array $data, ?int $userId = null, bool $manageTransaction = true): array
    {
        $qtys = $data['qty'] ?? [];
        $prices = $data['purchase_price'] ?? [];
        $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : null;
        $tax_percent = floatval($data['tax_percent'] ?? 0);
        $payment = $data['payment_method'] ?? 'efectivo';

        $items = [];
        $subtotal = 0.0;
        foreach ($qtys as $productId => $q) {
            $q = intval($q);
            if ($q <= 0) continue;
            $stmt = $this->pdo->prepare('SELECT id, stock, cost FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $p = $stmt->fetch();
            if (!$p) continue;
            $price = floatval($prices[$productId] ?? 0.0);
            if ($price <= 0) {
                throw new \Exception("Precio de compra inválido para el producto ID {$productId}.");
            }
            $items[] = ['product_id' => $productId, 'quantity' => $q, 'price' => $price, 'old_stock' => (int)$p['stock'], 'old_cost' => (float)$p['cost']];
            $subtotal += $price * $q;
        }

        if (count($items) === 0) {
            throw new \Exception('No se proporcionaron items de compra.');
        }

        $tax_amount = round(($subtotal * ($tax_percent/100)), 2);
        $total = round($subtotal + $tax_amount, 2);

        try {
            if ($manageTransaction) $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('INSERT INTO purchases (supplier_id, user_id, invoice_number, subtotal, tax_amount, total, payment_method) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$supplier_id, $userId, $data['invoice_number'] ?? null, $subtotal, $tax_amount, $total, $payment]);
            $purchaseId = (int)$this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, quantity, price, total) VALUES (?,?,?,?,?)');
            $stmtStock = $this->pdo->prepare('UPDATE products SET stock = stock + ?, cost = ? WHERE id = ?');
            $stmtMovement = $this->pdo->prepare('INSERT INTO stock_movements (product_id, delta, reason, reference_id) VALUES (?,?,?,?)');

            foreach ($items as $it) {
                $lineTotal = round($it['price'] * $it['quantity'], 2);
                $stmtItem->execute([$purchaseId, $it['product_id'], $it['quantity'], $it['price'], $lineTotal]);

                // compute new cost as weighted average (promedio ponderado)
                $oldStock = $it['old_stock'];
                $oldCost = $it['old_cost'];
                $qty = $it['quantity'];
                $price = $it['price'];
                $newCost = $price; // default
                $totalUnits = $oldStock + $qty;
                if ($totalUnits > 0) {
                    $newCost = round((($oldStock * $oldCost) + ($qty * $price)) / $totalUnits, 2);
                }

                // update stock and cost
                $stmtStock->execute([$it['quantity'], $newCost, $it['product_id']]);
                $stmtMovement->execute([$it['product_id'], $it['quantity'], 'compra', $purchaseId]);
            }

            // Registrar en cuentas como egreso si es pago al contado (si es 'efectivo' u otro)
            $stmtAcc = $this->pdo->prepare('INSERT INTO accounts (type, amount, description) VALUES (?,?,?)');
            $stmtAcc->execute(['egreso', $total, 'Compra ID ' . $purchaseId]);

            if ($manageTransaction) $this->pdo->commit();

            return ['purchase_id' => $purchaseId, 'subtotal' => (float)$subtotal, 'tax_amount' => (float)$tax_amount, 'total' => (float)$total];
        } catch (\Exception $e) {
            if ($manageTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Register an expense and optionally create an accounts egreso.
     */
    public function addExpense(array $data, ?int $userId = null, bool $manageTransaction = true): int
    {
        $amount = round(floatval($data['amount']), 2);
        $category = $data['category'] ?? null;
        $description = $data['description'] ?? null;

        try {
            if ($manageTransaction) $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('INSERT INTO expenses (user_id, category, amount, description) VALUES (?,?,?,?)');
            $stmt->execute([$userId, $category, $amount, $description]);
            $expenseId = (int)$this->pdo->lastInsertId();

            $stmtAcc = $this->pdo->prepare('INSERT INTO accounts (type, amount, description) VALUES (?,?,?)');
            $stmtAcc->execute(['egreso', $amount, 'Gasto ID ' . $expenseId]);

            if ($manageTransaction) $this->pdo->commit();
            return $expenseId;
        } catch (\Exception $e) {
            if ($manageTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
