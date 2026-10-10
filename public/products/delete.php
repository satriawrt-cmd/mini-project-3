<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../config/database.php';
require_role('admin', '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();
$id = valid_int($_POST['id'] ?? '', 1);
if ($id === false) { header('Location: index.php'); exit; }

// Business rule: produk yang punya histori tidak boleh dihapus
$h = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id = :id');
$h->execute(['id' => $id]);
if ($h->fetchColumn() > 0) {
    flash('Produk tidak dapat dihapus karena sudah memiliki histori transaksi.', 'err');
} else {
    $pdo->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $id]);
    flash('Produk dihapus.');
}
header('Location: index.php');
exit;
