<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_login('../login.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $productId = valid_int($_POST['product_id'] ?? '', 1);
    $quantity  = valid_int($_POST['quantity'] ?? '', 1);   // harus > 0
    $note      = clean_str($_POST['note'] ?? '', 255);

    if ($productId === false) $errors[] = 'Produk tidak valid.';
    if ($quantity === false)  $errors[] = 'Jumlah harus lebih dari nol.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('SELECT id FROM products WHERE id = :id FOR UPDATE');
            $s->execute(['id' => $productId]);
            if (!$s->fetch()) { throw new RuntimeException('Produk tidak ditemukan.'); }

            // 1) tambah stok  2) catat histori + user  -> satu transaksi
            $pdo->prepare('UPDATE products SET stock = stock + :q WHERE id = :id')
                ->execute(['q' => $quantity, 'id' => $productId]);
            $pdo->prepare("INSERT INTO stock_movements (product_id, user_id, type, quantity, note)
                           VALUES (:p, :u, 'IN', :q, :n)")
                ->execute(['p' => $productId, 'u' => current_user()['id'], 'q' => $quantity, 'n' => $note ?: null]);
            $pdo->commit();
            flash('Barang masuk berhasil dicatat.');
            header('Location: in.php'); exit;
        } catch (RuntimeException $ex) {
            $pdo->rollBack(); $errors[] = $ex->getMessage();
        } catch (Throwable $ex) {
            $pdo->rollBack(); $errors[] = 'Terjadi kesalahan, transaksi dibatalkan.';
        }
    }
}

$products = $pdo->query('SELECT id, sku, name, stock FROM products ORDER BY name')->fetchAll();
$title = 'Barang Masuk'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Barang Masuk</h1>
<?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Produk</label>
  <select name="product_id" required>
    <option value="">-- pilih produk --</option>
    <?php foreach ($products as $p): ?>
      <option value="<?= (int)$p['id'] ?>"><?= e($p['sku'] . ' - ' . $p['name'] . ' (stok ' . $p['stock'] . ')') ?></option>
    <?php endforeach; ?>
  </select>
  <label>Jumlah</label><input type="number" name="quantity" min="1" required>
  <label>Catatan</label><input name="note" maxlength="255">
  <p><button>Simpan</button></p>
</form>
<?php require __DIR__ . '/../../views/footer.php';
