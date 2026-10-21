<?php
require_once __DIR__ . '/auth.php';
require_login(['admin', 'server']);
require_once('db_connection.php');

$saleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$saleId || $saleId < 1) {
    http_response_code(400);
    exit('Numéro de ticket invalide.');
}

$settings = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$saleStmt = $pdo->prepare(
    'SELECT s.id, s.user_id, s.date, s.total_amount, COALESCE(pay.method, s.payment_type) AS payment_method
     FROM sales s
     LEFT JOIN payments pay ON pay.sale_id = s.id
     WHERE s.id = :id
     LIMIT 1'
);
$saleStmt->execute([':id' => $saleId]);
$sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
if (!$sale) {
    http_response_code(404);
    exit('Ticket introuvable.');
}
if (!is_admin() && (int)$sale['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    exit('Ce ticket ne vous appartient pas.');
}

$itemsStmt = $pdo->prepare(
    "SELECT COALESCE(si.product_name, p.name, 'Produit') AS product_name,
            si.quantity, si.unit_price, si.total_price
     FROM sale_items si
     LEFT JOIN products p ON p.id = si.product_id
     WHERE si.sale_id = :sale_id
     ORDER BY si.id"
);
$itemsStmt->execute([':sale_id' => $saleId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
$currency = $settings['currency_symbol'] ?? 'DH';
$header = $settings['invoice_header'] ?? 'Café - Ticket';
$footer = $settings['invoice_footer'] ?? 'Merci pour votre visite !';
$paymentLabels = ['cash' => 'Espèces', 'card' => 'Carte bancaire', 'transfer' => 'Virement'];
$paymentMethod = $paymentLabels[$sale['payment_method']] ?? $sale['payment_method'];
$shouldPrint = ($_GET['print'] ?? '') === '1';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=htmlspecialchars($header)?></title>
    <style>
        body { margin: 0; padding: 24px; color: #222; font: 14px Arial, sans-serif; }
        .ticket { width: min(100%, 360px); margin: 0 auto; }
        .center { text-align: center; }
        h1 { margin: 0 0 14px; font-size: 20px; }
        .meta { margin: 4px 0; }
        table { width: 100%; margin: 18px 0 10px; border-collapse: collapse; }
        th, td { padding: 6px 2px; border-bottom: 1px dashed #aaa; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .total { padding-top: 10px; border-top: 2px solid #222; font-size: 18px; font-weight: bold; }
        .wifi { margin-top: 18px; padding-top: 10px; border-top: 1px dashed #777; }
        .actions { display: flex; justify-content: center; gap: 10px; margin: 20px auto; }
        .actions a, .actions button { padding: 10px 14px; border: 0; border-radius: 5px; background: #198754; color: white; text-decoration: none; cursor: pointer; }
        .actions a { background: #6c757d; }
        @media print {
            body { padding: 0; }
            .ticket { width: 72mm; }
            .actions { display: none; }
            @page { margin: 4mm; }
        }
    </style>
</head>
<body>
    <main class="ticket">
        <header class="center">
            <h1><?=htmlspecialchars($header)?></h1>
            <div class="meta"><strong>Date :</strong> <?=htmlspecialchars(date('d/m/Y H:i', strtotime($sale['date'])))?></div>
        </header>
        <table>
            <thead><tr><th>Article</th><th>Qté</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?=htmlspecialchars($item['product_name'])?><br><small><?=number_format((float)$item['unit_price'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?> / unité</small></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?=number_format((float)$item['total_price'], 2, ',', ' ')?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total">TOTAL : <?=number_format((float)$sale['total_amount'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></div>
        <div class="meta">Paiement : <?=htmlspecialchars($paymentMethod)?></div>
        <footer class="center">
            <p><?=htmlspecialchars($footer)?></p>
            <div class="wifi">
                <strong>Wi-Fi</strong><br>
                Réseau : <?=htmlspecialchars($settings['wifi_ssid'] ?: 'À configurer')?> <br>
                Mot de passe : <?=htmlspecialchars($settings['wifi_password'] ?: 'À configurer')?>
            </div>
        </footer>
    </main>
    <nav class="actions">
        <button type="button" onclick="window.print()">Imprimer le ticket</button>
        <a href="tickets.php">Historique</a>
        <a href="caisse.php">Retour à la caisse</a>
    </nav>
    <?php if ($shouldPrint): ?><script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script><?php endif; ?>
</body>
</html>
