<?php
require_once __DIR__ . '/auth.php';
require_login(['admin']);
require_once('db_connection.php');
$currencySymbol=$pdo->query("SELECT COALESCE((SELECT setting_value FROM app_settings WHERE setting_key='currency_symbol'),'DH')")->fetchColumn();

if (isset($_POST['restock'])) {
    $id = (int)($_POST['id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    if ($id > 0 && $quantity > 0) {
        $stmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :id');
        $stmt->execute([':quantity' => $quantity, ':id' => $id]);
    }
    header('Location: stock.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$brandId = (int)($_GET['brand'] ?? 0);
$status = $_GET['status'] ?? '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(p.name LIKE :search OR p.barcode LIKE :search)';
    $params[':search'] = "%$search%";
}
if ($categoryId > 0) { $where[] = 'c.id = :category'; $params[':category'] = $categoryId; }
if ($brandId > 0) { $where[] = 'b.id = :brand'; $params[':brand'] = $brandId; }
$statusSql = ['high' => 'p.stock_quantity > 50', 'medium' => 'p.stock_quantity BETWEEN 21 AND 50', 'low' => 'p.stock_quantity BETWEEN 1 AND 20', 'out' => 'p.stock_quantity = 0'];
if (isset($statusSql[$status])) { $where[] = $statusSql[$status]; }

$sql = 'SELECT p.*, b.name AS brand_name, c.name AS category_name FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = b.category_id';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query('SELECT COUNT(*) AS total, COALESCE(SUM(stock_quantity * purchase_price), 0) AS value, SUM(stock_quantity BETWEEN 1 AND 20) AS low_stock, SUM(stock_quantity = 0) AS out_stock FROM products')->fetch(PDO::FETCH_ASSOC);

function stockState(int $quantity): array {
    if ($quantity === 0) return ['out', 'Rupture'];
    if ($quantity <= 20) return ['low', 'Stock bas'];
    if ($quantity <= 50) return ['medium', 'Stock moyen'];
    return ['high', 'En stock'];
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion de l'Inventaire</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/global.css"><link rel="stylesheet" href="../css/stock.css">
</head>
<body>
  <header class="header">
    <div class="header-left"><span><i class="fas fa-user"></i> Administrateur : <?=strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN'))?></span></div>
    <div class="header-center"><span id="current-time"></span></div>
    <div class="header-right"><button class="close-btn" onclick="location.href='menu.php'">✕</button></div>
  </header>
  <?php render_app_date_script($pdo); ?>
  <div class="container">
    <div class="page-title"><i class="fas fa-boxes"></i><h2>Gestion de l'Inventaire</h2></div>
    <div class="stats-section">
      <div class="stat-card stat-card-primary"><div class="stat-card-icon"><i class="fas fa-cube"></i></div><div class="stat-card-title">Total Produits</div><div class="stat-card-value"><?=$stats['total']?></div></div>
      <div class="stat-card stat-card-success"><div class="stat-card-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-card-title">Valeur du Stock</div><div class="stat-card-value"><?=number_format($stats['value'], 2, '.', ' ')?> <?=htmlspecialchars($currencySymbol)?></div></div>
      <div class="stat-card stat-card-warning"><div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-card-title">Stock Bas</div><div class="stat-card-value"><?=$stats['low_stock']?></div></div>
      <div class="stat-card stat-card-danger"><div class="stat-card-icon"><i class="fas fa-times-circle"></i></div><div class="stat-card-title">Rupture Stock</div><div class="stat-card-value"><?=$stats['out_stock']?></div></div>
    </div>

    <div class="filter-section">
      <div class="filter-header"><i class="fas fa-filter"></i><span>Filtrer l'Inventaire</span></div>
      <form class="filter-row" method="get">
        <div class="filter-group"><label><i class="fas fa-search"></i> Rechercher Produit</label><input type="text" name="search" id="search-input" placeholder="Nom, code-barres..." value="<?=htmlspecialchars($search)?>"></div>
        <div class="filter-group"><label><i class="fas fa-th-large"></i> Catégorie</label><select name="category" id="filter-category"><option value="">Toutes les catégories</option><?php foreach($categories as $category): ?><option value="<?=$category['id']?>" <?=$categoryId === (int)$category['id'] ? 'selected' : ''?>><?=htmlspecialchars($category['name'])?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label><i class="fas fa-layer-group"></i> État Stock</label><select name="status" id="filter-status"><option value="">Tous les états</option><option value="high" <?=$status==='high'?'selected':''?>>En stock</option><option value="medium" <?=$status==='medium'?'selected':''?>>Stock moyen</option><option value="low" <?=$status==='low'?'selected':''?>>Stock bas</option><option value="out" <?=$status==='out'?'selected':''?>>Rupture</option></select></div>
        <div class="filter-group"><label><i class="fas fa-bookmark"></i> Marque</label><select name="brand" id="filter-brand"><option value="">Toutes les marques</option><?php foreach($brands as $brand): ?><option value="<?=$brand['id']?>" <?=$brandId === (int)$brand['id'] ? 'selected' : ''?>><?=htmlspecialchars($brand['name'])?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><button class="filter-btn" type="submit"><i class="fas fa-search"></i></button></div>
      </form>
    </div>

    <div class="table-section">
      <div class="table-header"><div class="table-title"><i class="fas fa-list"></i><span>Liste des Produits en Stock</span></div><div class="table-actions"><button type="button" class="table-action-btn btn-export" onclick="exportStock()"><i class="fas fa-file-excel"></i> Exporter</button><button type="button" class="table-action-btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimer</button></div></div>
      <div class="table-container"><table id="stock-table">
        <thead><tr><th>ID</th><th>Produit</th><th>Code-Barres</th><th>Catégorie</th><th>Marque</th><th>Prix Achat</th><th>Prix Vente</th><th>Stock</th><th>État</th><th>Valeur</th><th>Actions</th></tr></thead>
        <tbody id="inventory-tbody">
          <?php foreach($products as $product): [$stateClass, $stateLabel] = stockState((int)$product['stock_quantity']); ?>
          <tr>
            <td><?=$product['id']?></td><td><?=htmlspecialchars($product['name'])?></td><td><?=htmlspecialchars($product['barcode'])?></td><td><?=htmlspecialchars($product['category_name'] ?? '')?></td><td><?=htmlspecialchars($product['brand_name'] ?? '')?></td>
            <td class="price-cell"><?=number_format($product['purchase_price'], 2)?> <?=htmlspecialchars($currencySymbol)?></td><td class="price-cell"><?=number_format($product['selling_price'], 2)?> <?=htmlspecialchars($currencySymbol)?></td><td><strong><?=$product['stock_quantity']?></strong></td><td><span class="stock-status status-<?=$stateClass?>"><?=$stateLabel?></span></td><td class="price-cell"><?=number_format($product['stock_quantity'] * $product['purchase_price'], 2)?> <?=htmlspecialchars($currencySymbol)?></td>
            <td><div class="action-btns"><button type="button" class="action-btn btn-view" data-info="<?=htmlspecialchars($product['name'].' — Stock: '.$product['stock_quantity'])?>"><i class="fas fa-eye"></i></button><button type="button" class="action-btn btn-edit" onclick="location.href='articles.php?edit=<?=$product['id']?>'"><i class="fas fa-edit"></i></button><button type="button" class="action-btn btn-restock" data-id="<?=$product['id']?>"><i class="fas fa-plus"></i></button></div></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$products): ?><tr><td colspan="11">Aucun produit trouvé.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
      <div class="pagination-section"><div class="pagination-info"><i class="fas fa-info-circle"></i> <?=$stats['total']?> produit(s), <?=$products ? count($products) : 0?> affiché(s)</div></div>
    </div>
  </div>
  <form id="restock-form" method="post" hidden><input name="id"><input name="quantity"><input name="restock" value="1"></form>
  <script src="../js/global-time.js"></script>
  <script>
    document.querySelectorAll('.btn-view').forEach(button => button.addEventListener('click', () => alert(button.dataset.info)));
    document.querySelectorAll('.btn-restock').forEach(button => button.addEventListener('click', () => {
      const quantity = Number(prompt('Quantité à ajouter :', '1'));
      if (Number.isInteger(quantity) && quantity > 0) {
        const form = document.getElementById('restock-form'); form.elements.id.value = button.dataset.id; form.elements.quantity.value = quantity; form.submit();
      }
    }));
    function exportStock() {
      const rows = Array.from(document.querySelectorAll('#stock-table tr')).map(row => Array.from(row.cells).slice(0, 10).map(cell => `"${cell.innerText.replaceAll('"','""')}"`).join(';')).join('\n');
      const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([rows], {type:'text/csv;charset=utf-8'})); link.download = 'stock.csv'; link.click(); URL.revokeObjectURL(link.href);
    }
  </script>
</body></html>
