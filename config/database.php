<?php
// Koneksi PDO. Sesuaikan user/password dengan XAMPP/Laragon Anda.
$dsn  = 'mysql:host=127.0.0.1;dbname=inventory_db;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa config/database.php');
}
