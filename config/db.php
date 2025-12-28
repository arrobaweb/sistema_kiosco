<?php
// Configuración de conexión PDO para MySQL (XAMPP)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sistema_kiosco');
define('DB_USER', 'root');
define('DB_PASS', 'luis');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Error de conexión a la base de datos: " . $e->getMessage();
    exit;
}
