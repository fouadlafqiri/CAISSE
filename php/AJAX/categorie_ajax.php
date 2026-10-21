<?php
require_once __DIR__ . '/../auth.php';
require_login(['admin', 'server']);
require '../db_connection.php';
$categories = $pdo->query('SELECT c.id, c.name, c.image FROM categories c WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id AND p.is_active = 1) ORDER BY c.name')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($categories, JSON_UNESCAPED_UNICODE);
