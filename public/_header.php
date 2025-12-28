<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Sistema Kiosco</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="" crossorigin="anonymous">
<style>
    body {
        background-color: #f5d9d6ff;
    }

    .card {
        box-shadow: 3px 3px 5px #313131ff;
        background-color: #f5e5cdff;
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Kiosco</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="pos.php">POS</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">Productos</a></li>
        <li class="nav-item"><a class="nav-link" href="stock.php">Stock</a></li>
        <li class="nav-item"><a class="nav-link" href="suppliers.php">Proveedores</a></li>
        <li class="nav-item"><a class="nav-link" href="purchases.php">Compras</a></li>
        <li class="nav-item"><a class="nav-link" href="expenses.php">Gastos</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reportes</a></li>
        <li class="nav-item"><a class="nav-link" href="balance.php">Balance</a></li>
      </ul>
      <div class="d-flex">
        <?php if (Auth::currentUser()): ?>
          <span class="navbar-text me-2"><?=htmlspecialchars(Auth::currentUser()['username'])?></span>
          <a class="btn btn-outline-light btn-sm" href="logout.php">Salir</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="login.php">Entrar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<div class="container">