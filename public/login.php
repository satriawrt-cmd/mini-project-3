<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (current_user()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared statement: cegah SQL Injection
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Username atau password salah.';
}

$title = 'Login'; $root = '';
require __DIR__ . '/../views/header.php';
?>
<div class="card login">
  <h1>Login</h1>
  <?php if ($error): ?><div class="errors"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Username</label><input name="username" required autofocus>
    <label>Password</label><input type="password" name="password" required>
    <p><button>Masuk</button></p>
  </form>
</div>
<?php require __DIR__ . '/../views/footer.php';
