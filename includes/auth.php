<?php

declare(strict_types=1);

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('Log in to edit the content.', 'err');
        redirect('admin/login.php');
    }
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}

function attempt_login(string $password): ?string
{
    $hash = (string) (config()['password_hash'] ?? '');
    if ($hash === '') {
        return 'The password is not set yet. Enter password_hash in config.php.';
    }
    if (!password_verify($password, $hash)) {
        return 'Wrong password.';
    }
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    return null;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
