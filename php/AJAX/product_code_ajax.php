<?php
require_once __DIR__ . '/../auth.php';
require_login(['admin', 'server']);
require '../db_connection.php';

if (isset($_POST['barcode'])) {
    $barcodeProduct = trim($_POST['barcode']);
    $sql = 'SELECT id, name, image, selling_price FROM products WHERE barcode = :barcode AND is_active = 1';
    $req = $pdo->prepare($sql);
    $req->execute([':barcode' => $barcodeProduct]);
    $product = $req->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($product, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Code manquant.']);
}