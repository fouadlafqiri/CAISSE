INSERT INTO categories (name, image)
SELECT requested.name, NULL
FROM (
  SELECT 'Cafés' AS name UNION ALL
  SELECT 'Boissons froides'
) AS requested
WHERE NOT EXISTS (
  SELECT 1 FROM categories existing WHERE existing.name = requested.name
);

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Eau 33 cl', '../imgs/CLYO_photos_carres/11_Eau.jpg', 3.00, 0, 0, 'EAU-33CL', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) = LOWER('Eau 33 cl'));

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Eau 1,5 L', '../imgs/CLYO_photos_carres/11_Eau.jpg', 9.00, 0, 0, 'EAU-15L', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) = LOWER('Eau 1,5 L'));

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT 'Eau 33 cl avec café', '../imgs/CLYO_photos_carres/11_Eau.jpg', 2.00, 0, 0, 'EAU-33CL-CAFE', NULL, c.id, 1
FROM categories c
WHERE c.name = 'Cafés'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE LOWER(p.name) LIKE '%eau 33 cl%avec cafe%');

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
SET p.image = '../imgs/CLYO_photos_carres/11_Eau.jpg', p.selling_price = 3.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) = LOWER('Eau 33 cl');

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
SET p.image = '../imgs/CLYO_photos_carres/11_Eau.jpg', p.selling_price = 9.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) = LOWER('Eau 1,5 L');

UPDATE products p
JOIN categories c ON c.name = 'Cafés'
SET p.image = '../imgs/CLYO_photos_carres/11_Eau.jpg', p.selling_price = 2.00, p.category_id = c.id, p.is_active = 1
WHERE LOWER(p.name) LIKE '%eau 33 cl%avec cafe%';

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
