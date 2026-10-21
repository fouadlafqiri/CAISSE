<?php 
require_once __DIR__ . '/auth.php';
require_login(['admin']);
require_once('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        if (isset($_POST['add']) || isset($_POST['update'])) {
            if ($name === '') {
                throw new RuntimeException('Le nom de la catégorie est obligatoire.');
            }
            $id = (int)($_POST['id'] ?? 0);
            $duplicateStmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name AND id <> :id LIMIT 1');
            $duplicateStmt->execute([':name' => $name, ':id' => isset($_POST['update']) ? $id : 0]);
            if ($duplicateStmt->fetchColumn()) {
                throw new RuntimeException('Une catégorie porte déjà ce nom.');
            }
            $image = null;
            if (isset($_POST['update'])) {
                $stmt = $pdo->prepare('SELECT image FROM categories WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $image = $stmt->fetchColumn();
                if ($image === false) {
                    throw new RuntimeException('Catégorie introuvable.');
                }
            }
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Le téléversement de l’image a échoué.');
                }
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    throw new RuntimeException('Format d’image invalide (JPG, PNG ou WEBP).');
                }
                $fileName = bin2hex(random_bytes(12)) . '.' . $extension;
                $image = '../imgs/categories-img/' . $fileName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../imgs/categories-img/' . $fileName)) {
                    throw new RuntimeException('Impossible d’enregistrer l’image de la catégorie.');
                }
            }
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare('INSERT INTO categories (name, image) VALUES (:name, :image)');
            } else {
                $stmt = $pdo->prepare('UPDATE categories SET name = :name, image = :image WHERE id = :id');
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
            $_SESSION['category_message'] = 'Catégorie enregistrée.';
        } elseif (isset($_POST['delete'])) {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
            $stmt->execute([':id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Cette catégorie contient encore des produits. Réaffectez-les ou retirez-les avant de la supprimer.');
            }
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM brands WHERE category_id = :id');
            $stmt->execute([':id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Cette catégorie est encore liée aux anciennes données. Elle ne peut pas être supprimée.');
            }
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['category_message'] = 'Catégorie supprimée.';
        }
    } catch (Throwable $e) {
        $_SESSION['category_message'] = 'Opération impossible : ' . $e->getMessage();
    }
    header('Location: categories.php');
    exit();
}
$message = $_SESSION['category_message'] ?? '';
unset($_SESSION['category_message']);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/categories.css">
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>
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
            <i class="fas fa-th-large"></i>
            <h2>Gestion des Catégories</h2>
        </div>

        <div class="content-wrapper">
            <!-- Form Section -->
            <div class="form-section">
                <div class="form-header">
                    <i class="fas fa-plus-circle"></i>
                    <span>Ajouter / Modifier Catégorie</span>
                </div>
                <?php if ($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data" >
                    <input type="hidden" name="id" id="category-id">

                    <div class="form-group">
                        <label for="category-name">
                            <i class="fas fa-tag"></i> Nom de la Catégorie
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="category-name" 
                            placeholder="Entrez le nom de la catégorie..." 
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="category-image">
                            <i class="fas fa-image"></i> Image (facultative)
                        </label>
                        <input 
                            type="file" 
                            name="image" 
                            id="category-image"
                        >
                        <div class="image-preview" id="imagePreview">
                            <div class="placeholder">
                                <i class="fas fa-image"></i>
                                <p>Aperçu de l'image</p>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="add" id="addCategory" class="btn btn-add">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                        <button type="submit" name="update" id="updateCategory" class="btn btn-update" disabled>
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i>
                        <span>Liste des Catégories</span>
                    </div>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search-input" 
                            placeholder="Rechercher une catégorie..."
                        >
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">ID</th>
                                <th style="width: 45%;">Nom Catégorie</th>
                                <th style="width: 15%;">Image</th>
                                <th style="width: 30%;">Actions</th>
                            </tr>
                        </thead>

                        <?php
                        $sql2 = 'SELECT c.* FROM categories c ORDER BY c.name';
                        $stmt = $pdo->prepare($sql2);
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <tbody id="categories-tbody">                            
                            <?php foreach($categories as $category):?>
                                <tr class="category">
                                    <td><?=$category['id']?></td>
                                    <td><?=htmlspecialchars($category['name'])?></td>
                                    <td>
                                        <?php if ($category['image']): ?><img src="<?=htmlspecialchars($category['image'])?>" alt="<?=htmlspecialchars($category['name'])?>" style="width:140px;" class="category-image"><?php else: ?><span>—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="action-btn btn-edit" >
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id" value="<?=$category['id'];?>">
                                            <button name="delete" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد من الحذف؟');" >
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                                
    <script src="../js/categories.js"></script>
    <?php render_app_date_script($pdo); ?>
    <script src="../js/global-time.js"></script>
</body>
</html>