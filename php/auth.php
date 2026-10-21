<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/db_connection.php';

function require_login(array $allowedRoles = []): void
{
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_unset();
        session_destroy();
        header('Location: /index.php');
        exit();
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = ['owner' => 'admin', 'assistant' => 'server'][$user['role']] ?? $user['role'];

    if ($allowedRoles && !in_array($_SESSION['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('Accès refusé.');
    }
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function get_app_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $value = $stmt->fetchColumn();
    return $value !== false && $value !== null ? (string)$value : $default;
}

function render_app_date_script(PDO $pdo): void
{
    $dateFormat = get_app_setting($pdo, 'date_display_format', 'full');
    echo '<script>window.appDateFormat = ' . json_encode($dateFormat, JSON_UNESCAPED_UNICODE) . ';</script>';
}

function catalog_product_image(string $name, ?string $existingImage = null): ?string
{
    $normalizedName = strtolower(strtr($name, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
    ]));

    $images = [
        ['sidi\s*ali|sidi-?ali', '12_Sidi_Ali.jpg'],
        ['eau\s*gazeuse|gazeuse|sparkling', '09_Eau_Gazeuse.jpg'],
        ['\b(eau|water)\b', '11_Eau.jpg'],
        ['coca|sprite|soda', '08_Coca_Sprite.jpg'],
        ['limonade|lemonade', '08_Coca_Sprite.jpg'],
        ['jus.*orange|orange.*jus', '07_Jus_Orange.jpg'],
        ['panache', '10_Panache.jpg'],
        ['espresso|expresso', '01_Cafe_Expresso_Eau.jpg'],
        ['cappuccino|cappucino', '04_Cappuccino.jpg'],
        ['cafe.*(au lait|latte)|(au lait|latte).*cafe', '05_Cafe_Au_Lait_Eau.jpg'],
        ['cafe.*(grand|large)|(grand|large).*cafe', '06_Cafe_Creme_Grand.jpg'],
        ['cafe.*creme|creme.*cafe', '02_Cafe_Creme_Eau.jpg'],
        ['\b(the|tea)\b', '03_The.jpg'],
        ['khli3|khlii|khlia', '14_Petit_Dejeuner_Khli3.jpg'],
        ['fromage|cheese', '15_Petit_Dejeuner_Fromage.jpg'],
        ['petit.?dejeuner|breakfast', '13_Petit_Dejeuner_Simple.jpg'],
        ['viennoiserie|croissant|pain au chocolat', '16_Viennoiserie.jpg'],
        ['\bcafe\b|coffee', '01_Cafe_Expresso_Eau.jpg'],
    ];

    foreach ($images as [$pattern, $image]) {
        if (preg_match('/' . $pattern . '/', $normalizedName)) {
            return '../imgs/CLYO_photos_carres/' . $image;
        }
    }

    return $existingImage !== null && $existingImage !== '' ? $existingImage : null;
}
