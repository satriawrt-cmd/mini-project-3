<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_role('admin', '../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = valid_int($_POST['id'] ?? '', 1);
    $name   = clean_str($_POST['name'] ?? '', 100);
    try {
        if ($action === 'save') {
            if ($name === '') { flash('Nama kategori wajib diisi.', 'err'); }
            elseif ($id === false) {
                $pdo->prepare('INSERT INTO categories (name) VALUES (:n)')->execute(['n' => $name]);
                flash('Kategori ditambahkan.');
            } else {
                $pdo->prepare('UPDATE categories SET name = :n WHERE id = :id')->execute(['n' => $name, 'id' => $id]);
                flash('Kategori diperbarui.');
            }
        } elseif ($action === 'delete' && $id !== false) {
            $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]); // FK RESTRICT
            flash('Kategori dihapus.');
        }
    } catch (PDOException $ex) {
        // 23000 = pelanggaran constraint (nama duplikat / masih dipakai produk)
        flash('Gagal: nama sudah ada atau kategori masih dipakai produk.', 'err');
    }
    header('Location: index.php'); exit;
}

$edit = null;
if (($eid = valid_int($_GET['edit'] ?? '', 1)) !== false) {
    $s = $pdo->prepare('SELECT * FROM categories WHERE id = :id'); $s->execute(['id' => $eid]); $edit = $s->fetch() ?: null;
}
$rows = $pdo->query('SELECT c.id, c.name, COUNT(p.id) AS total FROM categories c
                     LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.name ORDER BY c.name')->fetchAll();

$title = 'Kategori'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Kategori</h1>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
  <label><?= $edit ? 'Edit kategori' : 'Tambah kategori' ?></label>
  <input name="name" value="<?= e($edit['name'] ?? '') ?>" required>
  <p><button>Simpan</button> <?php if ($edit): ?><a class="btn gray" href="index.php">Batal</a><?php endif; ?></p>
</form>
<table><tr><th>Nama</th><th>Jumlah Produk</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= (int)$r['total'] ?></td><td>
  <a href="?edit=<?= (int)$r['id'] ?>">Edit</a>
  <form class="inline" method="post" onsubmit="return confirm('Hapus kategori?')">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <button class="btn danger">Hapus</button></form></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../views/footer.php';
