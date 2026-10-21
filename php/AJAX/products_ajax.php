<?php
require_once __DIR__ . '/../auth.php';
require_login(['admin', 'server']);
require '../db_connection.php';

if (isset($_POST['category_id'])) {
    $categoryId = (int)$_POST['category_id'];
    $sql = "SELECT id, name, image, selling_price FROM products WHERE category_id = :category_id AND is_active = 1 ORDER BY CASE WHEN LOWER(name) IN ('espresso', 'expresso') THEN 0 ELSE 1 END, name";
    $req = $pdo->prepare($sql);
    $req->execute([':category_id' => $categoryId]);
    $products = $req->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as &$product) {
        $product['image'] = catalog_product_image($product['name'], $product['image']);
    }
    unset($product);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Catégorie manquante.']);
}