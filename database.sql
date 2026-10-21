-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 10:26 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grocery`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `image`, `category_id`) VALUES
(15, 'Lio', '../imgs/brands-img/Lio.png', 49),
(16, 'Lesieur', '../imgs/brands-img/Lesieur.png', 49),
(17, 'Central', '../imgs/brands-img/Central.png', 47),
(18, 'Chergui', '../imgs/brands-img/Chergui.png', 47),
(19, 'Magix', '../imgs/brands-img/Magix.png', 48),
(20, 'Ace', '../imgs/brands-img/Ace.png', 48),
(21, 'Oni', '../imgs/brands-img/Oni.png', 48),
(23, 'Jaouda', '../imgs/brands-img/Jaouda.png', 47),
(24, 'Afia', '../imgs/brands-img/Afia.png', 49);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image`) VALUES
(47, 'Lait', '../imgs/categories-img/Lait.png'),
(48, 'Nettoyages', '../imgs/categories-img/Nettoyage.png'),
(49, 'Huiles', '../imgs/categories-img/huiles.png');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `selling_price` int(11) NOT NULL,
  `purchase_price` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `brand_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `image`, `selling_price`, `purchase_price`, `stock_quantity`, `barcode`, `brand_id`) VALUES
(5, 'Lait jaouda 500ml', '../imgs/products-img/Lait_jaouda_500ml.png', 4, 3, 24, '1234', 23),
(6, 'Lait jaouda UHT 1L', '../imgs/products-img/Lait_jaouda_UHT_1L.png', 11, 10, 36, '9876543214321', 23),
(7, 'Raibi', '../imgs/products-img/Raibi.png', 2, 2, 12, '1234567894321', 23),
(8, 'Lait central 500ml', '../imgs/products-img/Lait_central_500ml.png', 4, 3, 12, '1234567899876', 17),
(9, 'Lait central UHT 1L', '../imgs/products-img/Lait_central_UHT_1L.png', 11, 10, 24, '1234567890452', 17),
(10, 'Danone assil vanille', '../imgs/products-img/Danone_assil_vanille.png', 2, 2, 16, '1950453434000', 17),
(11, 'Javel ace 1L', '../imgs/products-img/Javel_ace_1L.png', 7, 6, 60, '1230000001235', 20),
(12, 'Javel ace 2L', '../imgs/products-img/Javel_ace_2L.png', 16, 14, 20, '1111', 20);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','server') NOT NULL DEFAULT 'server'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'mohamed', '$2y$10$N.1CnZ1GQDtJWF/O1nkWfu8/rV7QOxfj5LSvavynZUnr4MZbYgU5y', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `marque_id` (`brand_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE products
  ADD COLUMN category_id int(11) DEFAULT NULL,
  ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1,
  ADD KEY idx_products_category_active (category_id, is_active),
  MODIFY selling_price decimal(10,2) NOT NULL,
  MODIFY purchase_price decimal(10,2) NOT NULL;

UPDATE products p
LEFT JOIN brands b ON b.id = p.brand_id
LEFT JOIN categories c ON c.id = b.category_id
SET p.category_id = b.category_id,
    p.is_active = CASE WHEN c.name IN ('Lait', 'Nettoyages', 'Huiles') THEN 0 ELSE 1 END;

ALTER TABLE sale_items
  ADD COLUMN product_name varchar(100) DEFAULT NULL;

UPDATE sale_items si
JOIN products p ON p.id = si.product_id
SET si.product_name = p.name
WHERE si.product_name IS NULL;

INSERT INTO categories (name, image)
SELECT defaults.name, NULL
FROM (
  SELECT 'Cafés' AS name UNION ALL
  SELECT 'Thés' UNION ALL
  SELECT 'Boissons froides' UNION ALL
  SELECT 'Viennoiseries' UNION ALL
  SELECT 'Repas' UNION ALL
  SELECT 'Sandwichs' UNION ALL
  SELECT 'Desserts'
) AS defaults
WHERE NOT EXISTS (
  SELECT 1 FROM categories existing WHERE existing.name = defaults.name
);

UPDATE products p
JOIN categories old_category ON old_category.id = p.category_id
JOIN categories new_category ON new_category.name = 'Desserts'
SET p.category_id = new_category.id
WHERE old_category.name = 'Pâtisseries et Desserts' AND p.is_active = 1;

UPDATE products p
JOIN categories old_category ON old_category.id = p.category_id
JOIN categories new_category ON new_category.name = 'Thés'
SET p.category_id = new_category.id
WHERE old_category.name = 'Thés et Infusions' AND p.is_active = 1;

UPDATE categories SET name = 'Boissons froides'
WHERE BINARY name = 'Boissons Froides';

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT water.name, water.image, water.selling_price, 0, 0, water.barcode, NULL, c.id, 1
FROM categories c
CROSS JOIN (
  SELECT 'Eau 33 cl' AS name, '../imgs/CLYO_photos_carres/11_Eau.jpg' AS image, 3.00 AS selling_price, 'EAU-33CL' AS barcode
  UNION ALL
  SELECT 'Eau 1,5 L', '../imgs/CLYO_photos_carres/11_Eau.jpg', 9.00, 'EAU-15L'
) AS water
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products existing WHERE existing.name = water.name);

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
JOIN (
  SELECT 'Eau 33 cl' AS name, '../imgs/CLYO_photos_carres/11_Eau.jpg' AS image, 3.00 AS selling_price
  UNION ALL
  SELECT 'Eau 1,5 L', '../imgs/CLYO_photos_carres/11_Eau.jpg', 9.00
) AS water ON water.name = p.name
SET p.image = water.image,
    p.selling_price = water.selling_price,
    p.category_id = c.id,
    p.is_active = 1;

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Eau 33 cl avec café', '../imgs/CLYO_photos_carres/11_Eau.jpg', 2.00, 0, 0, 'EAU-33CL-CAFE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Cafés'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) = LOWER('Eau 33 cl avec café'));

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Jus d’orange', '../imgs/CLYO_photos_carres/07_Jus_Orange.jpg', 16.00, 0, 0, 'JUS-ORANGE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) LIKE '%orange%' AND LOWER(p.name) LIKE '%jus%');

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Panaché', '../imgs/CLYO_photos_carres/10_Panache.jpg', 16.00, 0, 0, 'JUS-PANACHE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) LIKE '%panach%');

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Limonade', '../imgs/CLYO_photos_carres/08_Coca_Sprite.jpg', 13.00, 0, 0, 'BOISSON-LIMONADE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) LIKE '%limonad%' OR LOWER(p.name) LIKE '%lemonade%');

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Café noir', '../imgs/CLYO_photos_carres/01_Cafe_Expresso_Eau.jpg', 9.00, 0, 0, 'CAFE-NOIR', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Cafés'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) LIKE '%noir%' AND (LOWER(p.name) LIKE '%cafe%' OR LOWER(p.name) LIKE '%coffee%'));

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
SET p.image = '../imgs/CLYO_photos_carres/07_Jus_Orange.jpg', p.selling_price = 16.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) LIKE '%orange%' AND LOWER(p.name) LIKE '%jus%';

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
SET p.image = '../imgs/CLYO_photos_carres/10_Panache.jpg', p.selling_price = 16.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) LIKE '%panach%';

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
SET p.image = '../imgs/CLYO_photos_carres/08_Coca_Sprite.jpg', p.selling_price = 13.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) LIKE '%limonad%' OR LOWER(p.name) LIKE '%lemonade%';

UPDATE products p
JOIN categories c ON c.name = 'Cafés'
SET p.image = '../imgs/CLYO_photos_carres/01_Cafe_Expresso_Eau.jpg', p.selling_price = 9.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) LIKE '%noir%' AND (LOWER(p.name) LIKE '%cafe%' OR LOWER(p.name) LIKE '%coffee%');

UPDATE products
SET is_active = 0
WHERE LOWER(name) IN ('frappuccino', 'iced coffee', 'smoothie');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.selling_price = 10.00,
    p.image = '../imgs/CLYO_photos_carres/04_Cappuccino.jpg'
WHERE c.name = 'Cafés'
  AND LOWER(p.name) LIKE '%capp%';

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.selling_price = 9.00,
    p.image = '../imgs/CLYO_photos_carres/05_Cafe_Au_Lait_Eau.jpg'
WHERE c.name = 'Cafés'
  AND LOWER(p.name) LIKE '%au lait%';

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.selling_price = 9.00,
    p.image = '../imgs/CLYO_photos_carres/01_Cafe_Expresso_Eau.jpg'
WHERE c.name = 'Cafés'
  AND (LOWER(p.name) LIKE '%espresso%' OR LOWER(p.name) LIKE '%expresso%');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.is_active = 0
WHERE c.name = 'Cafés'
  AND (LOWER(p.name) LIKE '%latte macchiato%'
       OR (LOWER(p.name) LIKE '%noir%' AND (LOWER(p.name) LIKE '%cafe%' OR LOWER(p.name) LIKE '%coffee%')));

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Pain au Chocolat', '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg', 4.00, 0, 0, 'DESSERT-PAIN-CHOCOLAT', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Desserts'
  AND NOT EXISTS (
    SELECT 1 FROM products p
    WHERE p.category_id = c.id AND LOWER(p.name) = LOWER('Pain au Chocolat')
  );

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.is_active = 0
WHERE c.name = 'Desserts'
  AND LOWER(p.name) <> LOWER('Pain au Chocolat');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Pain au Chocolat',
    p.image = '../imgs/CLYO_photos_carres/16_Viennoiserie.jpg',
    p.selling_price = 4.00,
    p.is_active = 1
WHERE c.name = 'Desserts'
  AND LOWER(p.name) = LOWER('Pain au Chocolat');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Petit-déjeuner',
    p.image = '../imgs/CLYO_photos_carres/13_Petit_Dejeuner_Simple.jpg',
    p.selling_price = 25.00
WHERE c.name = 'Snacks'
  AND LOWER(p.name) = 'sandwich';

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Khlea',
    p.image = '../imgs/CLYO_photos_carres/14_Petit_Dejeuner_Khli3.jpg',
    p.selling_price = 8.00
WHERE c.name = 'Snacks'
  AND LOWER(p.name) = 'panini';

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Toast au fromage',
    p.image = '../imgs/CLYO_photos_carres/15_Petit_Dejeuner_Fromage.jpg',
    p.selling_price = 8.00
WHERE c.name = 'Snacks'
  AND LOWER(p.name) = 'toast';

UPDATE categories current_category
LEFT JOIN categories existing ON existing.name = 'Croissantes'
SET current_category.name = 'Croissantes'
WHERE current_category.name = 'Desserts'
  AND existing.id IS NULL;

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Eau 33 cl', '../imgs/CLYO_photos_carres/11_Eau.jpg', 2.00, 0, 0, 'EAU-33CL-THE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Thés'
  AND NOT EXISTS (
    SELECT 1 FROM products p
    WHERE p.category_id = c.id AND LOWER(p.name) = LOWER('Eau 33 cl')
  );

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.image = '../imgs/CLYO_photos_carres/11_Eau.jpg',
    p.selling_price = 2.00,
    p.is_active = 1
WHERE c.name = 'Thés'
  AND LOWER(p.name) = LOWER('Eau 33 cl');

UPDATE brands b
SET b.category_id = NULL
WHERE b.category_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = b.category_id);

DELETE c FROM categories c
WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id);

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
  ('currency', 'MAD'),
  ('currency_symbol', 'DH'),
  ('store_name', 'Café'),
  ('invoice_header', 'Café - Ticket'),
  ('invoice_footer', 'Merci pour votre visite !'),
  ('wifi_ssid', ''),
  ('wifi_password', ''),
  ('date_display_format', 'full');

UPDATE app_settings SET setting_value = 'Café'
WHERE setting_key = 'store_name' AND setting_value = 'Épicerie Al Baraka';

UPDATE app_settings SET setting_value = 'Café - Ticket'
WHERE setting_key = 'invoice_header' AND setting_value = 'EPICERIE AL BARAKA - Facture';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
