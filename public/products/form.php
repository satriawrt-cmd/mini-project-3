<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_role('admin', '../login.php');

$id = valid_int($_GET['id'] ?? '', 1);
$product = ['category_id' => '', 'sku' => '', 'name' => '', 'price' => '', 'minimum_stock' => 5, 'stock' => 0];
$selectedSuppliers = []; // supplier_id => supplier_price

if ($id !== false) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $s->execute(['id' => $id]);
    $product = $s->fetch();
    if (!$product) { http_response_code(404); exit('Produk tidak ditemukan.'); }
    $s = $pdo->prepare('SELECT supplier_id, supplier_price FROM product_suppliers WHERE product_id = :id');
    $s->execute(['id' => $id]);
    foreach ($s->fetchAll() as $r) { $selectedSuppliers[$r['supplier_id']] = $r['supplier_price']; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sku   = clean_str($_POST['sku'] ?? '', 50);
    $name  = clean_str($_POST['name'] ?? '', 150);
    $cat   = valid_int($_POST['category_id'] ?? '', 1);
    $price = valid_decimal($_POST['price'] ?? '');
    $min   = valid_int($_POST['minimum_stock'] ?? '', 0);
    $sup   = $_POST['suppliers'] ?? [];          // array supplier_id
    $supPrice = $_POST['supplier_price'] ?? [];  // array [supplier_id => harga]

    if ($sku === '')  $errors[] = 'SKU wajib diisi.';
    if ($name === '') $errors[] = 'Nama wajib diisi.';
    if ($cat === false)   $errors[] = 'Kategori tidak valid.';
    if ($price === false) $errors[] = 'Harga tidak boleh negatif.';
    if ($min === false)   $errors[] = 'Minimum stok tidak valid.';

    if ($cat !== false) { // semantic: kategori harus ada
        $c = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
        $c->execute(['id' => $cat]);
        if (!$c->fetchColumn()) $errors[] = 'Kategori tidak ditemukan.';
    }
    if (!$errors) { // semantic: SKU harus unik
        $u = $pdo->prepare('SELECT id FROM products WHERE sku = :sku AND id <> :id');
        $u->execute(['sku' => $sku, 'id' => $id === false ? 0 : $id]);
        if ($u->fetch()) $errors[] = 'SKU sudah dipakai produk lain.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            if ($id === false) {
                $ins = $pdo->prepare('INSERT INTO products (category_id, sku, name, price, minimum_stock)
                                      VALUES (:c, :sku, :n, :p, :m)');
                $ins->execute(['c' => $cat, 'sku' => $sku, 'n' => $name, 'p' => $price, 'm' => $min]);
                $pid = (int) $pdo->lastInsertId();
            } else {
                // stok TIDAK diubah dari form ini; stok hanya berubah lewat transaksi masuk/keluar
                $upd = $pdo->prepare('UPDATE products SET category_id=:c, sku=:sku, name=:n, price=:p, minimum_stock=:m WHERE id=:id');
                $upd->execute(['c' => $cat, 'sku' => $sku, 'n' => $name, 'p' => $price, 'm' => $min, 'id' => $id]);
                $pid = $id;
                $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = :id')->execute(['id' => $pid]);
            }
            $link = $pdo->prepare('INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES (:p, :s, :sp)');
            $chk  = $pdo->prepare('SELECT COUNT(*) FROM suppliers WHERE id = :id');
            foreach ((array) $sup as $sid) {
                $sid = valid_int($sid, 1);
                if ($sid === false) continue;
                $chk->execute(['id' => $sid]);
                if (!$chk->fetchColumn()) continue;
                $sp = valid_decimal($supPrice[$sid] ?? '');
                $link->execute(['p' => $pid, 's' => $sid, 'sp' => $sp === false ? null : $sp]);
            }
            $pdo->commit();
            flash('Produk berhasil disimpan.');
            header('Location: index.php');
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan produk.';
        }
    }
    // isi ulang form saat error
    $product = ['category_id' => $cat, 'sku' => $sku, 'name' => $name, 'price' => $_POST['price'] ?? '',
                'minimum_stock' => $_POST['minimum_stock'] ?? 5, 'stock' => $product['stock']];
    $selectedSuppliers = [];
    foreach ((array) $sup as $sid) { $selectedSuppliers[(int)$sid] = $supPrice[$sid] ?? ''; }
}

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$sups = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$title = $id === false ? 'Tambah Produk' : 'Edit Produk'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($title) ?></h1>
<?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>SKU</label><input name="sku" value="<?= e($product['sku']) ?>" required>
  <label>Nama</label><input name="name" value="<?= e($product['name']) ?>" required>
  <label>Kategori</label>
  <select name="category_id" required>
    <option value="">-- pilih --</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <label>Harga</label><input type="number" step="0.01" min="0" name="price" value="<?= e($product['price']) ?>" required>
  <label>Minimum stok</label><input type="number" min="0" name="minimum_stock" value="<?= e($product['minimum_stock']) ?>" required>
  <label>Supplier (many-to-many)</label>
  <?php foreach ($sups as $s): $on = array_key_exists($s['id'], $selectedSuppliers); ?>
    <div>
      <label style="font-weight:400"><input type="checkbox" style="width:auto" name="suppliers[]" value="<?= (int)$s['id'] ?>" <?= $on ? 'checked' : '' ?>> <?= e($s['name']) ?></label>
      <input type="number" step="0.01" min="0" name="supplier_price[<?= (int)$s['id'] ?>]" placeholder="Harga dari supplier" value="<?= $on ? e($selectedSuppliers[$s['id']]) : '' ?>">
    </div>
  <?php endforeach; ?>
  <p><button>Simpan</button> <a class="btn gray" href="index.php">Batal</a></p>
</form>
<?php require __DIR__ . '/../../views/footer.php';
