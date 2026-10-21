INSERT IGNORE INTO categories (name, image)
SELECT 'Boissons froides', NULL
WHERE NOT EXISTS (
  SELECT 1 FROM categories WHERE name = 'Boissons froides'
);

INSERT INTO products (name, image, selling_price, purchase_price, stock_quantity, barcode, brand_id, category_id, is_active)
SELECT water.name, water.image, water.selling_price, 0, 0, water.barcode, NULL, c.id, 1
FROM categories c
CROSS JOIN (
  SELECT 'Eau 33 cl' AS name, '../imgs/CLYO_photos_carres/11_Eau.jpg' AS image, 2.00 AS selling_price, 'EAU-33CL' AS barcode
  UNION ALL
  SELECT 'Eau 1,5 L', '../imgs/CLYO_photos_carres/11_Eau.jpg', 9.00, 'EAU-15L'
) AS water
WHERE c.name = 'Boissons froides'
  AND NOT EXISTS (SELECT 1 FROM products existing WHERE existing.name = water.name);

UPDATE products p
JOIN categories c ON c.name = 'Boissons froides'
JOIN (
  SELECT 'Eau 33 cl' AS name, '../imgs/CLYO_photos_carres/11_Eau.jpg' AS image, 2.00 AS selling_price
  UNION ALL
  SELECT 'Eau 1,5 L', '../imgs/CLYO_photos_carres/11_Eau.jpg', 9.00
) AS water ON water.name = p.name
SET p.image = water.image,
    p.selling_price = water.selling_price,
    p.category_id = c.id,
    p.is_active = 1;

UPDATE brands b
SET b.category_id = NULL
WHERE b.category_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = b.category_id);

DELETE c FROM categories c
WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id);
