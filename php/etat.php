<?php
require_once __DIR__ . '/auth.php';
require_login(['admin', 'server']);
require_once('db_connection.php');

$periodType = $_GET['period'] ?? 'daily';
$isAdmin = is_admin();
if (!in_array($periodType, $isAdmin ? ['daily', 'weekly', 'custom'] : ['daily', 'weekly'], true)) {
    $periodType = 'daily';
}
$today = new DateTimeImmutable('today');
if ($periodType === 'weekly') {
    $defaultStart = $today->modify('monday this week')->format('Y-m-d');
    $defaultEnd = $today->modify('sunday this week')->format('Y-m-d');
} elseif ($periodType === 'custom') {
    $defaultStart = $today->format('Y-m-d');
    $defaultEnd = $defaultStart;
} else {
    $defaultStart = $today->format('Y-m-d');
    $defaultEnd = $defaultStart;
}
$start = $_GET['start'] ?? $defaultStart;
$end = $_GET['end'] ?? $defaultEnd;
$reportType = $_GET['type'] ?? 'sales';
if (!$isAdmin) {
    $start = $defaultStart;
    $end = $defaultEnd;
    $reportType = 'sales';
}
$validDate = static function (string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};
if (!$validDate($start) || !$validDate($end)) {
    $start = $defaultStart;
    $end = $defaultEnd;
}
if ($start > $end) {
    [$start, $end] = [$end, $start];
}
$params = [':start' => $start, ':end' => $end];
if ($isAdmin && !in_array($reportType, ['sales', 'products', 'payments'], true)) {
    $reportType = 'sales';
}
$salesScope = $isAdmin ? '' : ' AND s.user_id = :user_id';
if (!$isAdmin) {
    $params[':user_id'] = (int)$_SESSION['user_id'];
}
$settings = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$currency = $settings['currency_symbol'] ?? 'DH';

$stmt = $pdo->prepare('SELECT COALESCE(SUM(s.total_amount), 0) AS revenue, COUNT(*) AS sales_count FROM sales s WHERE DATE(s.date) BETWEEN :start AND :end' . $salesScope);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT DATE(s.date) AS sale_day, COUNT(*) AS sales_count, COALESCE(SUM(s.total_amount), 0) AS revenue
     FROM sales s WHERE DATE(s.date) BETWEEN :start AND :end' . $salesScope . '
     GROUP BY DATE(s.date) ORDER BY sale_day'
);
$stmt->execute($params);
$dailyTotals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT DATE(s.date) AS sale_day,
            COALESCE(si.product_name, p.name, 'Produit') AS product_name,
            si.unit_price, SUM(si.quantity) AS quantity,
            SUM(si.total_price) AS revenue,
            COUNT(DISTINCT s.id) AS orders_count
     FROM sale_items si
     JOIN sales s ON s.id = si.sale_id
     LEFT JOIN products p ON p.id = si.product_id
     WHERE DATE(s.date) BETWEEN :start AND :end" . $salesScope . "
     GROUP BY DATE(s.date), COALESCE(si.product_name, p.name, 'Produit'), si.unit_price
     ORDER BY sale_day DESC, product_name"
);
$stmt->execute($params);
$purchaseDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT COALESCE(si.product_name, p.name, 'Produit') AS product_name,
            c.name AS category_name, SUM(si.quantity) AS quantity,
            SUM(si.total_price) AS revenue
     FROM sale_items si
     JOIN sales s ON s.id = si.sale_id
     LEFT JOIN products p ON p.id = si.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE DATE(s.date) BETWEEN :start AND :end" . $salesScope . "
     GROUP BY COALESCE(si.product_name, p.name, 'Produit'), c.name
     ORDER BY quantity DESC LIMIT 20"
);
$stmt->execute($params);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT pay.method, COUNT(DISTINCT pay.id) AS transactions, SUM(pay.amount) AS total
     FROM payments pay JOIN sales s ON s.id = pay.sale_id
     WHERE DATE(s.date) BETWEEN :start AND :end' . $salesScope . '
     GROUP BY pay.method ORDER BY total DESC'
);
$stmt->execute($params);
$paymentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$methodLabels = ['cash' => 'Espèces', 'card' => 'Carte bancaire', 'transfer' => 'Virement'];
$periodLabel = $periodType === 'daily' ? 'Journalier' : ($periodType === 'weekly' ? 'Hebdomadaire' : 'Personnalisé');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapports des ventes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/etat.css?v=2">
</head>
<body>
    <header class="header">
        <div class="header-left"><span><i class="fas fa-user"></i> <?= $isAdmin ? 'Administrateur' : 'Serveur' ?> : <?=strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN'))?></span></div>
        <div class="header-center"><span id="current-time"></span></div>
        <div class="header-right"><button class="close-btn" onclick="location.href='menu.php'">✕</button></div>
    </header>
    <section class="print-report">
        <header class="print-report-header">
            <h1>Rapport des ventes</h1>
            <p>Période : <?=htmlspecialchars($start)?> au <?=htmlspecialchars($end)?></p>
        </header>
        <div class="print-report-revenue">
            <span>Chiffre d’affaires total</span>
            <strong><?=number_format((float)$stats['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></strong>
        </div>
        <table class="print-report-table">
            <thead><tr><th>Date</th><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Total vendu</th></tr></thead>
            <tbody>
                <?php foreach ($purchaseDetails as $item): ?>
                    <tr>
                        <td><?=htmlspecialchars($item['sale_day'])?></td>
                        <td><?=htmlspecialchars($item['product_name'])?></td>
                        <td><?=(int)$item['quantity']?></td>
                        <td><?=number_format((float)$item['unit_price'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td>
                        <td><?=number_format((float)$item['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$purchaseDetails): ?><tr><td colspan="5">Aucun produit vendu sur cette période.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <div class="container">
        <div class="page-title"><h2><i class="fas fa-chart-line"></i> Rapports des ventes</h2></div>
        <div class="d-flex flex-wrap gap-2 mb-3 period-actions">
            <a class="btn <?=$periodType === 'daily' ? 'btn-success' : 'btn-outline-success'?>" href="?period=daily">Aujourd’hui</a>
            <a class="btn <?=$periodType === 'weekly' ? 'btn-success' : 'btn-outline-success'?>" href="?period=weekly">Cette semaine</a>
            <?php if ($isAdmin): ?><a class="btn <?=$periodType === 'custom' ? 'btn-success' : 'btn-outline-success'?>" href="?period=custom">Période personnalisée</a><?php endif; ?>
        </div>

        <section class="stats-section">
            <div class="row g-3">
                <div class="col-md-6"><div class="stat-card stat-card-primary"><div class="stat-card-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-card-title">Chiffre d’affaires · <?=htmlspecialchars($periodLabel)?></div><div class="stat-card-value"><?=number_format((float)$stats['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></div><div class="stat-card-subtitle"><?=htmlspecialchars($start)?> au <?=htmlspecialchars($end)?></div></div></div>
                <div class="col-md-6"><div class="stat-card stat-card-info"><div class="stat-card-icon"><i class="fas fa-receipt"></i></div><div class="stat-card-title">Commandes validées</div><div class="stat-card-value"><?=(int)$stats['sales_count']?></div><div class="stat-card-subtitle">Sur la période</div></div></div>
            </div>
        </section>

        <?php if ($isAdmin): ?><section class="filter-section">
            <div class="filter-header"><i class="fas fa-calendar-alt"></i><span>Filtrer le rapport</span></div>
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="period" value="<?=$periodType?>">
                <div class="col-md-4"><label class="form-label">Du</label><input type="date" name="start" class="form-control" value="<?=htmlspecialchars($start)?>" required></div>
                <div class="col-md-4"><label class="form-label">Au</label><input type="date" name="end" class="form-control" value="<?=htmlspecialchars($end)?>" required></div>
                <div class="col-md-4"><label class="form-label">Détail</label><select name="type" class="form-select">
                    <option value="sales" <?=$reportType === 'sales' ? 'selected' : ''?>>Ventes par jour</option>
                    <option value="products" <?=$reportType === 'products' ? 'selected' : ''?>>Produits vendus</option>
                    <option value="payments" <?=$reportType === 'payments' ? 'selected' : ''?>>Méthodes de paiement</option>
                </select></div>
                <div class="col-12 text-end"><button class="btn btn-generate"><i class="fas fa-file-alt"></i> Générer</button></div>
            </form>
        </section><?php endif; ?>

        <section class="table-card report-table" data-report="sales">
            <div class="table-header"><div class="table-title"><i class="fas fa-calendar-day"></i><span>Ventes par jour</span></div><div class="table-actions"><button class="btn-export"><i class="fas fa-file-csv"></i> Exporter CSV</button><button class="btn-print"><i class="fas fa-print"></i> Imprimer</button></div></div>
            <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Date</th><th>Commandes</th><th>Chiffre d’affaires</th></tr></thead><tbody>
                <?php foreach ($dailyTotals as $row): ?><tr><td><?=htmlspecialchars($row['sale_day'])?></td><td><?=(int)$row['sales_count']?></td><td><?=number_format((float)$row['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td></tr><?php endforeach; ?>
                <?php if (!$dailyTotals): ?><tr><td colspan="3">Aucune vente sur cette période.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="table-card report-table" data-report="sales">
            <div class="table-header"><div class="table-title"><i class="fas fa-mug-hot"></i><span>Articles achetés sur la période (<?=htmlspecialchars($start)?> au <?=htmlspecialchars($end)?>)</span></div><div class="table-actions"><button class="btn-export"><i class="fas fa-file-csv"></i> Exporter CSV</button><button class="btn-print"><i class="fas fa-print"></i> Imprimer</button></div></div>
            <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Date</th><th>Produit</th><th>Commandes</th><th>Quantité vendue</th><th>Prix unitaire historique</th><th>Total vendu</th></tr></thead><tbody>
                <?php foreach ($purchaseDetails as $item): ?>
                    <tr>
                        <td><?=htmlspecialchars($item['sale_day'])?></td>
                        <td><?=htmlspecialchars($item['product_name'])?></td>
                        <td><?=(int)$item['orders_count']?></td>
                        <td><?=(int)$item['quantity']?></td>
                        <td><?=number_format((float)$item['unit_price'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td>
                        <td><?=number_format((float)$item['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$purchaseDetails): ?><tr><td colspan="6">Aucun produit acheté sur cette période.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="table-card report-table" data-report="products">
            <div class="table-header"><div class="table-title"><i class="fas fa-mug-hot"></i><span>Produits vendus</span></div><div class="table-actions"><button class="btn-export"><i class="fas fa-file-csv"></i> Exporter CSV</button><button class="btn-print"><i class="fas fa-print"></i> Imprimer</button></div></div>
            <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Produit</th><th>Catégorie</th><th>Quantité vendue</th><th>Chiffre d’affaires historique</th></tr></thead><tbody>
                <?php foreach ($topProducts as $product): ?><tr><td><?=htmlspecialchars($product['product_name'])?></td><td><?=htmlspecialchars($product['category_name'] ?? '—')?></td><td><?=(int)$product['quantity']?></td><td><?=number_format((float)$product['revenue'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td></tr><?php endforeach; ?>
                <?php if (!$topProducts): ?><tr><td colspan="4">Aucun produit vendu sur cette période.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <section class="table-card report-table" data-report="payments">
            <div class="table-header"><div class="table-title"><i class="fas fa-credit-card"></i><span>Ventes par moyen de paiement</span></div><div class="table-actions"><button class="btn-export"><i class="fas fa-file-csv"></i> Exporter CSV</button><button class="btn-print"><i class="fas fa-print"></i> Imprimer</button></div></div>
            <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Moyen de paiement</th><th>Transactions</th><th>Montant</th></tr></thead><tbody>
                <?php foreach ($paymentStats as $payment): ?><tr><td><?=htmlspecialchars($methodLabels[$payment['method']] ?? $payment['method'])?></td><td><?=(int)$payment['transactions']?></td><td><?=number_format((float)$payment['total'], 2, ',', ' ')?> <?=htmlspecialchars($currency)?></td></tr><?php endforeach; ?>
                <?php if (!$paymentStats): ?><tr><td colspan="3">Aucun paiement sur cette période.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
    </div>
    <script>
        const selectedReportType = <?=json_encode($reportType)?>;
        document.querySelectorAll('.report-table').forEach(section => {
            section.hidden = selectedReportType !== section.dataset.report;
        });
    </script>
    <script src="../js/etat.js"></script>
    <?php render_app_date_script($pdo); ?>
    <script src="../js/global-time.js"></script>
</body>
</html>
