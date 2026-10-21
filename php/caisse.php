<?php
require_once __DIR__ . '/auth.php';
require_login(['admin', 'server']);
require_once('db_connection.php');

$appSettings = [];
try {
    $appSettings = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $appSettings = [];
}
$paymentMethods = [
    'cash' => ($appSettings['payment_cash'] ?? '1') === '1',
    'card' => ($appSettings['payment_card'] ?? '1') === '1',
    'transfer' => ($appSettings['payment_transfer'] ?? '0') === '1',
];
$allowedPaymentMethods = array_keys(array_filter($paymentMethods));
if (!$allowedPaymentMethods) {
    $paymentMethods['cash'] = true;
    $allowedPaymentMethods = ['cash'];
}
$currencySymbol = $appSettings['currency_symbol'] ?? 'DH';
$invoiceHeader = $appSettings['invoice_header'] ?? 'Facture de Vente';
$invoiceFooter = $appSettings['invoice_footer'] ?? 'Merci pour votre achat !';
$autoPrint = ($appSettings['auto_print'] ?? '0') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);
    $items = is_array($data) ? ($data['items'] ?? []) : [];
    $paymentMethod = is_array($data) ? ($data['payment_method'] ?? '') : '';

    if (!is_array($items) || !$items || !in_array($paymentMethod, $allowedPaymentMethods, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Données de vente invalides.']);
        exit();
    }

    try {
        $pdo->beginTransaction();
        $total = 0;
        $saleItems = [];

        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                throw new RuntimeException('Produit ou quantité invalide.');
            }

            $stmt = $pdo->prepare('SELECT id, name, selling_price FROM products WHERE id = :id AND is_active = 1 FOR UPDATE');
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new RuntimeException('Produit indisponible ou introuvable.');
            }

            $unitPrice = round((float)$product['selling_price'], 2);
            $lineTotal = round($unitPrice * $quantity, 2);
            $total += $lineTotal;
            $saleItems[] = [
                'productId' => $productId,
                'productName' => $product['name'],
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
            ];
        }
        $total = round($total, 2);

        $stmt = $pdo->prepare('INSERT INTO sales (total_amount, payment_type, user_id) VALUES (:total, :payment_type, :user_id)');
        $stmt->execute([':total' => $total, ':payment_type' => $paymentMethod, ':user_id' => $_SESSION['user_id'] ?? null]);
        $saleId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price) VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_price, :total_price)');
        foreach ($saleItems as $saleItem) {
            $itemStmt->execute([':sale_id' => $saleId, ':product_id' => $saleItem['productId'], ':product_name' => $saleItem['productName'], ':quantity' => $saleItem['quantity'], ':unit_price' => $saleItem['unitPrice'], ':total_price' => $saleItem['lineTotal']]);
        }

        $stmt = $pdo->prepare('INSERT INTO payments (sale_id, amount, method) VALUES (:sale_id, :amount, :method)');
        $stmt->execute([':sale_id' => $saleId, ':amount' => $total, ':method' => $paymentMethod]);
        $pdo->commit();
        echo json_encode(['success' => true, 'sale_id' => $saleId, 'total' => $total]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Point de Vente - Caisse</title>
    <script src="../js/frameWork/jquery-3.7.1.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/caisse.css">
    <link rel="stylesheet" href="../css/global.css">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <header class="header">
        <div class="header-left">
            <span><i class="fas fa-user"></i> Serveur : <?php echo strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN'))  ?></span>
        </div>
        <div class="header-center">
            <span id="current-time"></span>
        </div>
        <div class="header-right">
            <button class="close-btn" onclick="location.href='menu.php'">✕</button>
        </div>
    </header>

    <div class="main-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <!-- Invoice Section -->
            <div class="invoice-section">
                <div class="invoice-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix</th>
                                <th>Qt.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="factureTbody">

                        </tbody>
                    </table>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <span class="total-label">
                        <i class="fas fa-calculator"></i> Total Facture :
                    </span>
                    <span class="total-amount">0.00 <?=htmlspecialchars($currencySymbol)?></span>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn btn-delete" id="deleteBtn">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                    <button class="action-btn btn-clear" id="viderBtn">
                        <i class="fas fa-broom"></i> Vider
                    </button>
                    <button class="action-btn btn-complete">
                        <i class="fas fa-check-circle"></i> Finaliser Vente
                    </button>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <!-- Header de navigation -->
            <div class="navigation-header">
                <div class="nav-breadcrumb">
                    <span class="nav-breadcrumb-item" id="navHome">
                        <i class="fas fa-home"></i> Accueil
                    </span>
                    <span class="nav-breadcrumb-separator" id="navSep1" style="display: none;">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                    <span class="nav-breadcrumb-item" id="navCategory" style="display: none;">
                        <i class="fas fa-list"></i> <span id="navCategoryName"></span>
                    </span>
                </div>
                <button type="button" class="nav-back-btn" id="navBackCategories" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Autres catégories
                </button>
            </div>

            <!-- Grille dynamique -->
            <div class="products-grid" id="dynamicGrid">
                <!-- Le contenu sera généré par JavaScript -->
            </div>
        </div>

    </div>

    <!-- Keypad Popup -->
    <div class="keypad-overlay" id="keypadOverlay">
        <div class="keypad-popup">
            <div class="keypad-header">
                <h3><i class="fas fa-calculator"></i> Entrer la Quantité</h3>
                <p>Saisissez la quantité désirée</p>
            </div>

            <div class="quantity-display" id="quantityDisplay">1</div>

            <div class="keypad-grid">
                <button class="keypad-btn" data-number="1">1</button>
                <button class="keypad-btn" data-number="2">2</button>
                <button class="keypad-btn" data-number="3">3</button>
                <button class="keypad-btn" data-number="4">4</button>
                <button class="keypad-btn" data-number="5">5</button>
                <button class="keypad-btn" data-number="6">6</button>
                <button class="keypad-btn" data-number="7">7</button>
                <button class="keypad-btn" data-number="8">8</button>
                <button class="keypad-btn" data-number="9">9</button>
                <button class="keypad-btn" data-number="0">0</button>
                <button class="keypad-btn keypad-special" data-action="clear">
                    <i class="fas fa-backspace"></i> Effacer
                </button>
            </div>

            <div class="keypad-actions">
                <button class="keypad-action-btn btn-cancel" id="cancelBtn">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="keypad-action-btn btn-confirm" id="confirmBtn">
                    <i class="fas fa-check"></i> Confirmer
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">

                <!-- Header -->
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice"></i> <?=htmlspecialchars($invoiceHeader)?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">✕</button>
                </div>

                <!-- Body -->
                <div class="modal-body bg-white">
                    <div class="d-flex justify-content-between mb-3">
                        <p><strong><i class="fas fa-calendar text-info"></i> Date:</strong>
                            <span id="invoiceDate" class="text-primary fw-bold"></span>
                        </p>
                    </div>

                    <table class="table table-bordered">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Produit</th>
                                <th>Prix Unit.</th>
                                <th>Qté</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceProductsTable"></tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                                <td colspan="2" class="text-end fw-bold">TOTAL :</td>
                                <td class="text-center fw-bold"><span id="invoiceTotalAmount"></span> <?=htmlspecialchars($currencySymbol)?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Payment Method Selection -->
                    <div class="payment-method-section">
                        <div class="payment-method-title">
                            <i class="fas fa-credit-card"></i>
                            Sélectionnez la méthode de paiement
                        </div>

                        <div class="payment-methods-grid">
                            <!-- Espèces -->
                            <?php if ($paymentMethods['cash']): ?>
                            <div class="payment-method-option">
                                <input type="radio" id="payment-cash" name="payment-method" value="cash" <?=$allowedPaymentMethods[0]==='cash'?'checked':''?>>
                                <label for="payment-cash" class="payment-method-label">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                    </div>
                                    <div class="payment-method-name">Espèces</div>
                                </label>
                                <div class="payment-method-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($paymentMethods['card']): ?>
                            <!-- Carte -->
                            <div class="payment-method-option">
                                <input type="radio" id="payment-card" name="payment-method" value="card" <?=$allowedPaymentMethods[0]==='card'?'checked':''?>>
                                <label for="payment-card" class="payment-method-label">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-credit-card text-primary"></i>
                                    </div>
                                    <div class="payment-method-name">Carte bancaire</div>
                                </label>
                                <div class="payment-method-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($paymentMethods['transfer']): ?>
                            <!-- Virement -->
                            <div class="payment-method-option">
                                <input type="radio" id="payment-transfer" name="payment-method" value="transfer" <?=$allowedPaymentMethods[0]==='transfer'?'checked':''?>>
                                <label for="payment-transfer" class="payment-method-label">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-exchange-alt text-warning"></i>
                                    </div>
                                    <div class="payment-method-name">Virement</div>
                                </label>
                                <div class="payment-method-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-center text-muted mt-3 border-top pt-3">
                        <i class="fas fa-heart text-danger"></i> <?=htmlspecialchars($invoiceFooter)?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" id="finishSaleBtn" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Terminer
                    </button>
                </div>
            </div>
        </div>
    </div>



    <?php render_app_date_script($pdo); ?>
    <script src="../js/global-time.js"></script>
    <script src="../js/frameWork/jquery-3.7.1.js"></script>


    <script>
        const currencySymbol = <?=json_encode($currencySymbol, JSON_UNESCAPED_UNICODE)?>;
        const autoPrint = <?=$autoPrint ? 'true' : 'false'?>;
        //==========================
        //Decliration of all IDs
        //======================

        // Keypad quantite
        const keypadOverlay = document.getElementById('keypadOverlay');
        const quantityDisplay = document.getElementById('quantityDisplay');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');

        // Boutons action
        const deleteBtn = document.getElementById('deleteBtn');
        const viderBtn = document.getElementById('viderBtn');

        // Facture
        const factureTbody = document.getElementById('factureTbody');
        const factureTotal = document.querySelector('.total-amount');

        // Navigation
        const navHome = document.getElementById('navHome');
        const navSep1 = document.getElementById('navSep1');
        const navCategory = document.getElementById('navCategory');
        const navCategoryName = document.getElementById('navCategoryName');
        const navBackCategories = document.getElementById('navBackCategories');

        // Grid
        const dynamicGrid = document.getElementById('dynamicGrid');

        // Modal
        const invoiceModal = document.getElementById('invoiceModal');
        const invoiceDate = document.getElementById('invoiceDate');
        const invoiceProductsTable = document.getElementById('invoiceProductsTable');
        const invoiceTotalAmount = document.getElementById('invoiceTotalAmount');
        const finishSaleBtn = document.getElementById('finishSaleBtn');


        //=============================
        //Declaration for all variables
        //=============================

        let currentQuantity = '0';
        let currentAction = null;
        let currentRow = null;

        let selectedProduct = null;

        let totalGlobal = 0;

        let currentView = 'categories';
        let selectedCategory = null;
        let categoriesData = [];

        //===================
        // The start of code 
        //===================

        function showQuantityKeypad(quantity, action, row) {
            currentQuantity = quantity;
            currentAction = action;
            currentRow = row;
            quantityDisplay.textContent = currentQuantity;
            keypadOverlay.classList.add("show");
        }

        function hideQuantityKeypad() {
            keypadOverlay.classList.remove("show");
            currentQuantity = '0';
            currentAction = null;
            currentRow = null;
        }

        function updateTotalDisplay() {
            factureTotal.textContent = totalGlobal.toFixed(2) + " " + currencySymbol;
        }

        function findProductInFacture(productId) {
            let foundRow = null;
            factureTbody.querySelectorAll("tr").forEach(tr => {
                if (parseInt(productId) === parseInt(tr.children[0].textContent)) {
                    foundRow = tr;
                }
            })
            return foundRow;
        }

        function addProductToFacture(product, quantity) {
            let productFouder = findProductInFacture(product.id);
            if (productFouder !== null) {
                updateProductQuantity(productFouder, quantity)
            } else {
                let newProduct = document.createElement("tr");
                let productTotal = parseInt(quantity) * parseFloat(product.selling_price);
                newProduct.innerHTML = `
                    <td style="display:none;">${product.id}</td>
                    <td>${product.name}</td>
                    <td>${product.selling_price.toFixed(2)}</td>
                    <td>${quantity}</td>
                    <td>${productTotal.toFixed(2)}</td>
                `
                totalGlobal += productTotal;
                factureTbody.appendChild(newProduct);
                updateTotalDisplay();
            }
        }

        function updateProductQuantity(row, newQuantity) {
            let productPrice = row.children[2].textContent;
            let oldQuantity = row.children[3];
            let oldProductTotal = row.children[4];

            oldQuantity.textContent = newQuantity;
            let NewProductTotal = parseInt(newQuantity) * parseFloat(productPrice);

            totalGlobal = (totalGlobal - parseFloat(oldProductTotal.textContent)) + parseFloat(NewProductTotal);
            updateTotalDisplay();

            oldProductTotal.textContent = NewProductTotal.toFixed(2);
        }

        function showCategories() {
            currentView = 'categories';
            updateBreadcrumb();
            dynamicGrid.innerHTML = '';

            $.ajax({
                method: "POST",
                dataType: "json",
                url: "./AJAX/categorie_ajax.php",
                success: function(categories, textStatus, XHR) {
                    // To put all categories in one table 
                    categoriesData = categories;
                    categories.forEach((category) => {
                        dynamicGrid.appendChild(createCategoryCard(category));
                    })
                }
            })
        }

        function showProducts(categoryId) {
            selectedCategory = categoriesData.find(category => Number(category.id) === Number(categoryId));
            currentView = 'products';
            updateBreadcrumb();
            dynamicGrid.innerHTML = '';

            $.ajax({
                method: "POST",
                dataType: "json",
                url: "./AJAX/products_ajax.php",
                data: {
                    category_id: categoryId,
                },
                success: function(products, textStatus, XHR) {
                    products.forEach((product) => {
                        dynamicGrid.appendChild(createProductCard(product));
                    })
                    if (!products.length) {
                        dynamicGrid.textContent = 'Aucun produit actif dans cette catégorie. Ajoutez-en depuis Gestion des produits.';
                    }
                },
                error: function() {
                    dynamicGrid.textContent = 'Impossible de charger les produits.';
                }
            })
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[char]);
        }

        function createCategoryCard(category) {
            let card = document.createElement("div");
            card.className = 'product-card category-card';
            const categoryPhotos = {
                'cafés': '../imgs/CLYO_photos_carres/04_Cappuccino.jpg',
                'cafes': '../imgs/CLYO_photos_carres/04_Cappuccino.jpg',
                'thés': '../imgs/CLYO_photos_carres/03_The.jpg',
                'thes': '../imgs/CLYO_photos_carres/03_The.jpg',
                'thés et infusions': '../imgs/CLYO_photos_carres/03_The.jpg',
                'boissons froides': '../imgs/CLYO_photos_carres/07_Jus_Orange.jpg',
                'croissantes': '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
                'viennoiseries': '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
                'desserts': '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
                'pâtisseries et desserts': '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
                'patisseries et desserts': '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
                'repas': '../imgs/CLYO_photos_carres/13_Petit_Dejeuner_Simple.jpg',
                'sandwichs': '../imgs/CLYO_photos_carres/15_Petit_Dejeuner_Fromage.jpg',
                'snacks': '../imgs/CLYO_photos_carres/14_Petit_Dejeuner_Khli3.jpg'
            };
            const categoryImage = category.image || categoryPhotos[String(category.name).toLocaleLowerCase()] || '';
            card.innerHTML = `
                <div class="product-image${categoryImage ? ' has-image' : ''}">
                    ${categoryImage ? `<img src="${escapeHtml(categoryImage)}" alt="${escapeHtml(category.name)}" onerror="this.parentElement.classList.remove('has-image'); this.remove()">` : '<i class="fas fa-mug-hot"></i>'}
                </div>
                <div class="product-name">${escapeHtml(category.name)}</div>
            `;
            card.addEventListener('click', () => showProducts(category.id))
            return card;
        }

        function createProductCard(product) {
            product.id = Number(product.id);
            product.selling_price = Number(product.selling_price);
            let card = document.createElement("div");
            card.className = 'product-card';
            card.innerHTML = `
                <div class="product-image${product.image ? ' has-image' : ''}">
                    ${product.image ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" onerror="this.parentElement.classList.remove('has-image'); this.remove()">` : '<i class="fas fa-mug-hot"></i>'}
                </div>
                <div class="product-name">${escapeHtml(product.name)}</div>
                <div class="product-price">${product.selling_price.toFixed(2)} ${currencySymbol}</div>
            `;

            card.addEventListener('click', () => {
                selectedProduct = product;
                let productFouder = findProductInFacture(product.id);
                if (productFouder !== null) {
                    let ProductFouderQuantity = productFouder.children[3].textContent;
                    showQuantityKeypad(ProductFouderQuantity, 'edit', productFouder);
                } else {
                    showQuantityKeypad('1', 'add');
                }
            })
            return card;
        }


        function updateBreadcrumb() {
            navSep1.style.display = 'none';
            navCategory.style.display = 'none';
            navBackCategories.style.display = currentView === 'products' ? 'inline-flex' : 'none';
            if (currentView === 'products') {
                navSep1.style.display = 'inline';
                navCategory.style.display = 'inline';
                navCategoryName.textContent = selectedCategory?.name ?? '';
            }
        }


        //==================================
        // Buttons and EventListener
        //==================================

        confirmBtn.addEventListener('click', () => {
            let qte = parseInt(currentQuantity);

            if (isNaN(qte) || qte <= 0) {
                alert("❗ Impossible d'entrer une quantité inférieure ou égale à 0");
                return;
            }

            if (currentAction == 'add') {
                addProductToFacture(selectedProduct, qte);
            } else if (currentAction == 'edit') {
                updateProductQuantity(currentRow, qte)
            }

            hideQuantityKeypad();
        })

        cancelBtn.addEventListener('click', hideQuantityKeypad);

        keypadOverlay.addEventListener('click', (e) => {
            if (e.target === keypadOverlay) {
                hideQuantityKeypad();
            }
        });

        document.querySelectorAll('.keypad-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.number) {
                    if (currentQuantity === '0') {
                        currentQuantity = btn.dataset.number;
                    } else if (currentQuantity.length < 4) {
                        currentQuantity += btn.dataset.number;
                    }
                    quantityDisplay.textContent = currentQuantity;
                } else if (btn.dataset.action === 'clear') {
                    if (currentQuantity.length > 1) {
                        currentQuantity = currentQuantity.slice(0, -1);
                    } else {
                        currentQuantity = '0';
                    }
                    quantityDisplay.textContent = currentQuantity;
                }
            });
        });

        // Selection for a product in facture (one click)
        factureTbody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (row) {
                factureTbody.querySelectorAll('tr').forEach(tr => tr.classList.remove('selected'));
                row.classList.add('selected');
            }
        });

        // Désélectionner une ligne (double-clic)
        factureTbody.addEventListener('dblclick', (e) => {
            const row = e.target.closest('tr');
            if (row) {
                row.classList.remove('selected');
            }
        });


        deleteBtn.addEventListener('click', () => {
            const selectedRow = factureTbody.querySelector('tr.selected');
            if (!selectedRow) {
                alert("❗ Veuillez sélectionner une ligne à supprimer");
                return;
            }

            const totalCell = selectedRow.children[4];
            const rowTotal = parseFloat(totalCell.textContent);

            selectedRow.remove();

            totalGlobal -= rowTotal;
            updateTotalDisplay();

        });

        viderBtn.addEventListener('click', () => {
            if (factureTbody.children.length === 0) {
                alert("❗ La facture est déjà vide");
                return;
            }

            const confirmation = confirm("🗑️ Êtes-vous sûr de vouloir vider toute la facture ?");
            if (!confirmation) return;

            factureTbody.innerHTML = '';
            totalGlobal = 0;
            updateTotalDisplay();

        });


        navHome.addEventListener('click', showCategories);
        navCategory.addEventListener('click', showCategories);
        navBackCategories.addEventListener('click', showCategories);




        // ✅ فتح الـ modal مع أنيميشن
        function openInvoiceModal() {
            invoiceModal.style.display = 'flex';
            invoiceModal.classList.add('show');
        }

        // ✅ إغلاق الـ modal مع أنيميشن
        function closeInvoiceModal() {
            invoiceModal.classList.remove('show');
            invoiceModal.style.display = 'none';
        }

        // ✅ زر إتمام البيع
        const btnComplete = document.querySelector('.btn-complete');
        if (btnComplete) {
            btnComplete.addEventListener('click', () => {
                if (factureTbody.children.length === 0) {
                    alert("❗ La facture est vide ! Veuillez ajouter des produits.");
                    return;
                }

                // تحديث التاريخ
                const now = new Date();
                invoiceDate.textContent =
                    now.toLocaleDateString('fr-FR') + ' ' + now.toLocaleTimeString('fr-FR');

                // ملء جدول الفاتورة
                const invoiceTable = document.getElementById('invoiceProductsTable');
                invoiceTable.innerHTML = '';
                factureTbody.querySelectorAll('tr').forEach(row => {
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = row.innerHTML;
                    newRow.classList.remove('selected');
                    invoiceTable.appendChild(newRow);
                });

                document.getElementById('invoiceTotalAmount').textContent = totalGlobal.toFixed(2);

                openInvoiceModal();
            });
        }

        // ✅ إغلاق بزر X
        const closeModalBtn = invoiceModal?.querySelector('.btn-close');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeInvoiceModal);
        }

        // ✅ إغلاق بالضغط خارج الـ modal
        if (invoiceModal) {
            invoiceModal.addEventListener('click', (e) => {
                if (e.target === invoiceModal) closeInvoiceModal();
            });
        }

        const finishSaleBtnEl = document.getElementById('finishSaleBtn');
        if (finishSaleBtnEl) {
            finishSaleBtnEl.addEventListener('click', async () => {
                const items = Array.from(factureTbody.querySelectorAll('tr')).map(row => ({
                    product_id: Number(row.children[0].textContent),
                    quantity: Number(row.children[3].textContent)
                }));
                const paymentMethod = document.querySelector('input[name="payment-method"]:checked')?.value;
                finishSaleBtnEl.disabled = true;
                const ticketWindow = window.open('about:blank', '_blank');

                try {
                    const response = await fetch('caisse.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({items, payment_method: paymentMethod})
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'La vente n’a pas pu être enregistrée.');
                    }

                    const ticketUrl = `ticket.php?id=${encodeURIComponent(result.sale_id)}${autoPrint ? '&print=1' : ''}`;
                    if (ticketWindow) ticketWindow.location.href = ticketUrl;
                    factureTbody.innerHTML = '';
                    totalGlobal = 0;
                    updateTotalDisplay();
                    closeInvoiceModal();
                    if (!ticketWindow) window.location.href = ticketUrl;
                } catch (error) {
                    if (ticketWindow) ticketWindow.close();
                    alert(error.message);
                } finally {
                    finishSaleBtnEl.disabled = false;
                }
            });
        }


        document.addEventListener('DOMContentLoaded', () => {
            showCategories();
        });
    </script>

</body>

</html>
