<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Halaman internal wajib login
function require_login(string $loginPath = 'login.php'): void
{
    if (!current_user()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

// Authorization di sisi server, bukan hanya menyembunyikan menu
function require_role(string $role, string $loginPath = 'login.php'): void
{
    require_login($loginPath);
    if (current_user()['role'] !== $role) {
        http_response_code(403);
        exit('Forbidden: Anda tidak punya akses ke halaman ini.');
    }
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

// CSRF token sederhana untuk setiap form POST
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Token CSRF tidak valid.');
    }
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
