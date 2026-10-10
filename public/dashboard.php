<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../config/database.php';
require_login();

// Widget dashboard (fungsi agregat)
$stat = $pdo->query("SELECT COUNT(id) AS total_products,
                            COALESCE(SUM(stock * price),0) AS inventory_value,
                            COALESCE(SUM(stock <= minimum_stock),0) AS low_count
                     FROM products")->fetch();
$mov = $pdo->query("SELECT
        COALESCE(SUM(CASE WHEN type='IN'  THEN quantity END),0) AS total_in,
        COALESCE(SUM(CASE WHEN type='OUT' THEN quantity END),0) AS total_out
     FROM stock_movements")->fetch();

// Produk stok rendah
$low = $pdo->query("SELECT sku, name, stock, minimum_stock FROM products
                    WHERE stock <= minimum_stock ORDER BY stock ASC")->fetchAll();

// Laporan per kategori: JOIN + GROUP BY + COUNT/SUM/AVG
$cat = $pdo->query("SELECT c.name, COUNT(p.id) AS total_products,
                           COALESCE(SUM(p.stock),0) AS total_stock,
                           COALESCE(AVG(p.price),0) AS average_price
                    FROM categories c LEFT JOIN products p ON p.category_id = c.id
                    GROUP BY c.id, c.name ORDER BY c.name")->fetchAll();

// Laporan inventory: total masuk/keluar per produk
$report = $pdo->query("SELECT p.sku, p.name, p.stock,
        SUM(CASE WHEN sm.type='IN'  THEN sm.quantity ELSE 0 END) AS total_in,
        SUM(CASE WHEN sm.type='OUT' THEN sm.quantity ELSE 0 END) AS total_out
     FROM products p LEFT JOIN stock_movements sm ON sm.product_id = p.id
     GROUP BY p.id, p.sku, p.name, p.stock ORDER BY p.name")->fetchAll();

$title = 'Dashboard'; $root = '';
require __DIR__ . '/../views/header.php';
?>
<h1>Dashboard</h1>
<div class="grid">
  <div class="card stat">Total produk<b><?= (int)$stat['total_products'] ?></b></div>
  <div class="card stat">Nilai inventory<b><?= e(rupiah($stat['inventory_value'])) ?></b></div>
  <div class="card stat">Stok rendah<b class="<?= $stat['low_count'] > 0 ? 'low' : '' ?>"><?= (int)$stat['low_count'] ?></b></div>
  <div class="card stat">Barang masuk<b><?= (int)$mov['total_in'] ?></b></div>
  <div class="card stat">Barang keluar<b><?= (int)$mov['total_out'] ?></b></div>
</div>

<h2>Produk Stok Rendah</h2>
<table><tr><th>SKU</th><th>Nama</th><th>Stok</th><th>Minimum</th></tr>
<?php foreach ($low as $r): ?>
<tr><td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td>
<td class="low"><?= (int)$r['stock'] ?></td><td><?= (int)$r['minimum_stock'] ?></td></tr>
<?php endforeach; if (!$low): ?><tr><td colspan="4">Tidak ada produk stok rendah.</td></tr><?php endif; ?>
</table>

<h2>Ringkasan per Kategori</h2>
<table><tr><th>Kategori</th><th>Jumlah Produk</th><th>Total Stok</th><th>Rata-rata Harga</th></tr>
<?php foreach ($cat as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= (int)$r['total_products'] ?></td>
<td><?= (int)$r['total_stock'] ?></td><td><?= e(rupiah($r['average_price'])) ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Laporan Inventory</h2>
<table><tr><th>SKU</th><th>Nama</th><th>Total Masuk</th><th>Total Keluar</th><th>Stok Saat Ini</th></tr>
<?php foreach ($report as $r): ?>
<tr><td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td>
<td><?= (int)$r['total_in'] ?></td><td><?= (int)$r['total_out'] ?></td><td><?= (int)$r['stock'] ?></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../views/footer.php';
