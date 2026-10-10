<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_login('../login.php');

// --- Search, filter, sorting (allowlist) ---
$q        = clean_str($_GET['q'] ?? '', 100);
$category = valid_int($_GET['category'] ?? '', 1);
$allowedSort = ['name' => 'p.name', 'price' => 'p.price', 'stock' => 'p.stock', 'sku' => 'p.sku'];
$sortKey  = array_key_exists($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'name';
$sortCol  = $allowedSort[$sortKey];                       // nama kolom dari allowlist, bukan input mentah
$dir      = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

$where = []; $params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q1 OR p.sku LIKE :q2)';
    $params['q1'] = $params['q2'] = '%' . $q . '%';       // nilai LIKE tetap di-bind
}
if ($category !== false) {
    $where[] = 'p.category_id = :cat';
    $params['cat'] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- Pagination ---
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$cnt->execute($params);
$total      = (int) $cnt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$sql = "SELECT p.*, c.name AS category_name
        FROM products p JOIN categories c ON c.id = p.category_id
        $whereSql ORDER BY $sortCol $dir LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Pertahankan search/filter/sort saat pindah halaman
function page_url(int $p): string {
    $qs = $_GET; $qs['page'] = $p;
    return '?' . http_build_query($qs);
}
function sort_url(string $key): string {
    $qs = $_GET;
    $qs['dir']  = (($qs['sort'] ?? '') === $key && ($qs['dir'] ?? 'asc') === 'asc') ? 'desc' : 'asc';
    $qs['sort'] = $key; $qs['page'] = 1;
    return '?' . http_build_query($qs);
}

$title = 'Produk'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Produk</h1>
<form class="filters" method="get">
  <input name="q" placeholder="Cari nama / SKU" value="<?= e($q) ?>">
  <select name="category">
    <option value="">Semua kategori</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sort">
    <?php foreach (['name'=>'Nama','sku'=>'SKU','price'=>'Harga','stock'=>'Stok'] as $k => $lbl): ?>
      <option value="<?= e($k) ?>" <?= $sortKey === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="dir">
    <option value="asc"  <?= $dir === 'ASC'  ? 'selected' : '' ?>>A → Z / Kecil → Besar</option>
    <option value="desc" <?= $dir === 'DESC' ? 'selected' : '' ?>>Z → A / Besar → Kecil</option>
  </select>
  <button>Terapkan</button>
  <?php if (is_admin()): ?><a class="btn gray" href="form.php">+ Produk</a><?php endif; ?>
</form>

<table>
<tr><th><a href="<?= e(sort_url('sku')) ?>">SKU</a></th><th><a href="<?= e(sort_url('name')) ?>">Nama</a></th>
<th>Kategori</th><th><a href="<?= e(sort_url('price')) ?>">Harga</a></th>
<th><a href="<?= e(sort_url('stock')) ?>">Stok</a></th><th>Min</th><?php if (is_admin()): ?><th>Aksi</th><?php endif; ?></tr>
<?php foreach ($products as $p): ?>
<tr>
  <td><?= e($p['sku']) ?></td><td><?= e($p['name']) ?></td><td><?= e($p['category_name']) ?></td>
  <td><?= e(rupiah($p['price'])) ?></td>
  <td class="<?= $p['stock'] <= $p['minimum_stock'] ? 'low' : '' ?>"><?= (int)$p['stock'] ?></td>
  <td><?= (int)$p['minimum_stock'] ?></td>
  <?php if (is_admin()): ?>
  <td><a href="form.php?id=<?= (int)$p['id'] ?>">Edit</a>
    <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Hapus produk ini?')">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <button class="btn danger">Hapus</button>
    </form></td>
  <?php endif; ?>
</tr>
<?php endforeach; if (!$products): ?><tr><td colspan="7">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>

<p class="pager">Halaman:
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
  <a class="<?= $i === $page ? 'cur' : '' ?>" href="<?= e(page_url($i)) ?>"><?= $i ?></a>
<?php endfor; ?>
 (<?= $total ?> produk)</p>
<?php require __DIR__ . '/../../views/footer.php';
