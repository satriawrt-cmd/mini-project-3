<?php
// Variabel: $title, $root (path relatif ke folder public/, mis. '' atau '../')
require_once __DIR__ . '/../helpers/escape.php';
$root = $root ?? '';
$user = current_user();
$f = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Inventory') ?> - Inventory System</title>
<link rel="stylesheet" href="<?= $root ?>assets/style.css">
</head>
<body>
<?php if ($user): ?>
<nav>
  <strong>📦 Inventory</strong>
  <a href="<?= $root ?>dashboard.php">Dashboard</a>
  <a href="<?= $root ?>products/index.php">Produk</a>
  <a href="<?= $root ?>stock/in.php">Barang Masuk</a>
  <a href="<?= $root ?>stock/out.php">Barang Keluar</a>
  <a href="<?= $root ?>stock/history.php">Histori</a>
  <?php if (is_admin()): ?>
    <a href="<?= $root ?>categories/index.php">Kategori</a>
    <a href="<?= $root ?>suppliers/index.php">Supplier</a>
    <a href="<?= $root ?>users/index.php">User</a>
  <?php endif; ?>
  <span class="right"><?= e($user['username']) ?> (<?= e($user['role']) ?>)
    · <a href="<?= $root ?>logout.php">Logout</a></span>
</nav>
<?php endif; ?>
<main>
<?php if ($f): ?><div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endif; ?>
