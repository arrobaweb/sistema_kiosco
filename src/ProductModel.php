<?php
class ProductModel {
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function all() { return $this->pdo->query('SELECT * FROM products')->fetchAll(); }
    public function find($id) { $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([$id]); return $stmt->fetch(); }
    public function create($code, $name, $price, $stock) { $stmt = $this->pdo->prepare('INSERT INTO products (code,name,price,stock) VALUES (?,?,?,?)'); return $stmt->execute([$code,$name,$price,$stock]); }
}
