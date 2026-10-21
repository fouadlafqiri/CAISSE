<?php
function request_uses_https(): bool
{
    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    return ($https !== '' && $https !== 'off')
        || (string)($_SERVER['SERVER_PORT'] ?? '') === '443'
        || getenv('APP_HTTPS') === '1';
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $usesHttps = request_uses_https();
    if (getenv('APP_ENV') === 'production' && !$usesHttps) {
        http_response_code(503);
        exit('HTTPS doit être activé avant de mettre l’application en production.');
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $usesHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

start_secure_session();
