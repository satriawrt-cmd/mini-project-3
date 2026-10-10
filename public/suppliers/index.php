<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_role('admin', '../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = valid_int($_POST['id'] ?? '', 1);
    $name   = clean_str($_POST['name'] ?? '', 150);
    $phone  = clean_str($_POST['phone'] ?? '', 30);
    try {
        if ($action === 'save') {
            if ($name === '') { flash('Nama supplier wajib diisi.', 'err'); }
            elseif ($id === false) {
                $pdo->prepare('INSERT INTO suppliers (name, phone) VALUES (:n, :p)')->execute(['n' => $name, 'p' => $phone ?: null]);
                flash('Supplier ditambahkan.');
            } else {
                $pdo->prepare('UPDATE suppliers SET name=:n, phone=:p WHERE id=:id')->execute(['n' => $name, 'p' => $phone ?: null, 'id' => $id]);
                flash('Supplier diperbarui.');
            }
        } elseif ($action === 'delete' && $id !== false) {
            $pdo->prepare('DELETE FROM suppliers WHERE id = :id')->execute(['id' => $id]);
            flash('Supplier dihapus.');
        }
    } catch (PDOException $ex) {
        flash('Gagal: supplier masih terhubung dengan produk.', 'err');
    }
    header('Location: index.php'); exit;
}

$edit = null;
if (($eid = valid_int($_GET['edit'] ?? '', 1)) !== false) {
    $s = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id'); $s->execute(['id' => $eid]); $edit = $s->fetch() ?: null;
}
$rows = $pdo->query('SELECT s.id, s.name, s.phone, COUNT(ps.product_id) AS total FROM suppliers s
                     LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id GROUP BY s.id, s.name, s.phone ORDER BY s.name')->fetchAll();

$title = 'Supplier'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Supplier</h1>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
  <label>Nama</label><input name="name" value="<?= e($edit['name'] ?? '') ?>" required>
  <label>Telepon</label><input name="phone" value="<?= e($edit['phone'] ?? '') ?>">
  <p><button>Simpan</button> <?php if ($edit): ?><a class="btn gray" href="index.php">Batal</a><?php endif; ?></p>
</form>
<table><tr><th>Nama</th><th>Telepon</th><th>Jumlah Produk</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= (int)$r['total'] ?></td><td>
  <a href="?edit=<?= (int)$r['id'] ?>">Edit</a>
  <form class="inline" method="post" onsubmit="return confirm('Hapus supplier?')">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <button class="btn danger">Hapus</button></form></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../views/footer.php';
