<?php 
require_once __DIR__ . '/auth.php';
require_login(['admin']);
require_once('db_connection.php');

function redirectWithMessage(string $message): void {
    $_SESSION['product_message'] = $message;
    header('Location: articles.php');
    exit();
}

function saveProductImage(?string $currentImage): ?string {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentImage;
    }
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement de l’image a échoué.');
    }
    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Format d’image invalide (JPG, PNG ou WEBP).');
    }
    $fileName = bin2hex(random_bytes(12)) . '.' . $extension;
    $relativePath = '../imgs/products-img/' . $fileName;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../imgs/products-img/' . $fileName)) {
        throw new RuntimeException('Impossible d’enregistrer l’image du produit.');
    }
    return $relativePath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add']) || isset($_POST['update'])) {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $categoryId = (int)($_POST['category'] ?? 0);
            $price = filter_var($_POST['selling-price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($name === '' || $categoryId < 1 || $price === false || $price <= 0) {
                throw new RuntimeException('Renseignez le nom, la catégorie et un prix supérieur à zéro.');
            }
            $categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
            $categoryStmt->execute([':id' => $categoryId]);
            if (!$categoryStmt->fetchColumn()) {
                throw new RuntimeException('La catégorie sélectionnée est introuvable.');
            }

            $barcode = trim($_POST['barcode'] ?? '');
            if ($barcode === '') {
                $barcode = 'CAFE-' . strtoupper(bin2hex(random_bytes(6)));
            }
            $currentImage = null;
            if (isset($_POST['update'])) {
                $currentStmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
                $currentStmt->execute([':id' => $id]);
                $currentImage = $currentStmt->fetchColumn();
                if ($currentImage === false) {
                    throw new RuntimeException('Le produit à modifier est introuvable.');
                }
            }
            $image = saveProductImage($currentImage);
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare('INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active) VALUES (:name, :image, :price, 0, 0, :barcode, NULL, :category, 1)');
            } else {
                $stmt = $pdo->prepare('UPDATE products SET name = :name, image = :image, selling_price = :price, barcode = :barcode, category_id = :category, is_active = :active WHERE id = :id');
                $stmt->bindValue(':active', isset($_POST['is_active']) ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':price', $price);
            $stmt->bindValue(':barcode', $barcode, PDO::PARAM_STR);
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            redirectWithMessage(isset($_POST['add']) ? 'Produit ajouté.' : 'Produit modifié.');
        }

        if (isset($_POST['delete'])) {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = :id');
            $stmt->execute([':id' => $id]);
            redirectWithMessage('Produit retiré de la carte. Son historique de ventes est conservé.');
        }
    } catch (Throwable $e) {
        redirectWithMessage('Opération impossible : ' . $e->getMessage());
    }
}

$categories = $pdo->query("SELECT c.id, c.name FROM categories c WHERE c.name IN ('Cafés', 'Thés', 'Boissons froides', 'Viennoiseries', 'Repas', 'Sandwichs', 'Desserts') OR EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id AND p.is_active = 1) ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT p.id, p.name, p.image, p.selling_price, p.barcode, p.category_id, p.is_active, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.name')->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as &$product) {
    $product['image'] = catalog_product_image($product['name'], $product['image']);
}
unset($product);
$message = $_SESSION['product_message'] ?? '';
unset($_SESSION['product_message']);
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/articles.css">
    <link rel="stylesheet" href="../css/global.css">

</head>
<body data-edit-id="<?=(int)($_GET['edit'] ?? 0)?>">
    <header class="header">
        <div class="header-left">
            <span><i class="fas fa-user"></i> Administrateur : <?php echo strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN')); ?></span>
        </div>
        <div class="header-center">
            <span id="current-time"></span>
        </div>
        <div class="header-right">
            <button class="close-btn" onclick="location.href='menu.php'">✕</button>
        </div>
    </header>

    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <i class="fas fa-box-open"></i>
            <h2>Gestion des Produits</h2>
        </div>

        <div class="content-wrapper">
            <!-- Form Section -->
            <div class="form-section">
                <div class="form-header">
                    <i class="fas fa-plus-circle"></i>
                    <span>Ajouter / Modifier Produit</span>
                </div>

                <?php if ($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="product-id">
                    
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-cube"></i> Nom du Produit
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="product-name" 
                            placeholder="Ex: Café espresso"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="selling-price"><i class="fas fa-tag"></i> Prix de vente (DH)</label>
                        <input type="number" name="selling-price" id="selling-price" min="0.01" step="0.01" placeholder="15.00" required>
                    </div>
                    <div class="form-group">
                        <label for="barcode"><i class="fas fa-barcode"></i> Code (facultatif)</label>
                        <input type="text" name="barcode" id="barcode" placeholder="Généré automatiquement si vide">
                    </div>
                    <div class="form-group">
                        <label for="product-category">
                            <i class="fas fa-th-large"></i> Catégorie
                        </label>
                        <select name="category" id="product-category" required>
                            <option value="">Choisir une catégorie...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?=$category['id']?>"><?=htmlspecialchars($category['name'])?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_active" id="product-active" checked> Produit disponible à la vente</label>
                    </div>

                    <div class="form-group">
                        <label for="product-image">
                            <i class="fas fa-image"></i> Image (facultative)
                        </label>
                        <input 
                        type="file" 
                        name="image" 
                        id="product-image"
                        >
                        <div class="image-preview" id="imagePreview">
                            <div class="placeholder">
                                <i class="fas fa-image"></i>
                                <p>Aperçu de l'image</p>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" id="addProduct" name="add" class="btn btn-add">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                        <button type="submit" id="updateProduct" name="update" class="btn btn-update" disabled>
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button type="button" id="cancelEdit" class="btn btn-secondary" hidden>Annuler</button>
                    </div>
                </form>
            </div>

            


            <!-- Table Section -->
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i>
                        <span>Liste des Produits</span>
                    </div>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search-input" 
                            placeholder="Rechercher un produit..."
                        >
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%;">Nom</th>
                                <th>Catégorie</th>
                                <th>Prix (DH)</th>
                                <th>Code</th>
                                <th>État</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="products-tbody">
                            <?php foreach ($products as $product):?>
                                <tr class="product">
                                    <td><?=$product['id']?></td>
                                    <td><?=htmlspecialchars($product['name'])?></td>
                                    <td><?=htmlspecialchars($product['category_name'] ?? 'Sans catégorie')?></td>
                                    <td class="price-cell"><?=number_format((float)$product['selling_price'], 2, '.', ' ')?></td>
                                    <td><?=htmlspecialchars($product['barcode'])?></td>
                                    <td><?=$product['is_active'] ? 'Actif' : 'Archivé'?></td>
                                    <td>
                                        <button class="action-btn btn-edit" 
                                                data-id="<?=$product['id']?>"
                                                data-name="<?=htmlspecialchars($product['name'], ENT_QUOTES)?>"
                                                data-image="<?=htmlspecialchars($product['image'] ?? '', ENT_QUOTES)?>"
                                                data-sell-price="<?=$product['selling_price']?>"
                                                data-barcode="<?=htmlspecialchars($product['barcode'], ENT_QUOTES)?>"
                                                data-category="<?=$product['category_id']?>"
                                                data-active="<?=$product['is_active']?>"
                                                >
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id" value="<?=$product['id']?>">
                                            <?php if ($product['is_active']): ?><button name="delete" class="action-btn btn-delete" onclick="return confirm('Retirer ce produit de la carte ? L’historique sera conservé.');">
                                                <i class="fas fa-eye-slash"></i> Retirer
                                            </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/articles.js"></script>
    <?php render_app_date_script($pdo); ?>
    <script src="../js/global-time.js"></script>
</body>
</html>
