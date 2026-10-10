<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_role('admin', '../login.php'); // staff -> 403 Forbidden

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = clean_str($_POST['username'] ?? '', 50);
    $fullName = clean_str($_POST['full_name'] ?? '', 100);
    $password = (string) ($_POST['password'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) $errors[] = 'Username 3-50 karakter (huruf, angka, underscore).';
    if ($fullName === '') $errors[] = 'Nama lengkap wajib diisi.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:u, :h, :r)')
                ->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT), 'r' => $role]);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO user_profiles (user_id, full_name) VALUES (:id, :n)')
                ->execute(['id' => $uid, 'n' => $fullName]);
            $pdo->commit();
            flash('User ditambahkan.');
            header('Location: index.php'); exit;
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $errors[] = 'Username sudah dipakai.';
        }
    }
}

$users = $pdo->query('SELECT u.id, u.username, u.role, up.full_name FROM users u
                      LEFT JOIN user_profiles up ON up.user_id = u.id ORDER BY u.id')->fetchAll();

$title = 'User'; $root = '../';
require __DIR__ . '/../../views/header.php';
?>
<h1>Manajemen User</h1>
<?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Username</label><input name="username" required>
  <label>Nama lengkap</label><input name="full_name" required>
  <label>Password</label><input type="password" name="password" required>
  <label>Role</label><select name="role"><option value="staff">staff</option><option value="admin">admin</option></select>
  <p><button>Tambah user</button></p>
</form>
<table><tr><th>Username</th><th>Nama</th><th>Role</th></tr>
<?php foreach ($users as $u): ?>
<tr><td><?= e($u['username']) ?></td><td><?= e($u['full_name']) ?></td><td><?= e($u['role']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../views/footer.php';
