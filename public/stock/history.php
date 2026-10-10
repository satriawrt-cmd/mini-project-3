<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_login('../login.php');

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$total   = (int) $pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page   = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT sm.id, sm.type, sm.quantity, sm.note, sm.created_at, p.sku, p.name, u.username
                       FROM stock_movements sm
                       JOIN products p ON p.id = sm.product_id
                       JOIN users u ON u.id = sm.user_id
                       ORDER BY sm.created_at DESC, sm.id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'Histori Stok'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Histori Barang Masuk / Keluar</h1>
<table><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Jumlah</th><th>User</th><th>Catatan</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['created_at']) ?></td><td><?= e($r['sku'] . ' - ' . $r['name']) ?></td>
<td><?= $r['type'] === 'IN' ? 'Masuk' : 'Keluar' ?></td><td><?= (int)$r['quantity'] ?></td>
<td><?= e($r['username']) ?></td><td><?= e($r['note']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6">Belum ada transaksi.</td></tr><?php endif; ?>
</table>
<p class="pager">Halaman:
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
  <a class="<?= $i === $page ? 'cur' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
<?php endfor; ?></p>
<?php require __DIR__ . '/../../views/footer.php';
