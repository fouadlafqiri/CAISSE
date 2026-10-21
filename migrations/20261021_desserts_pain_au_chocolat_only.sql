INSERT INTO categories (name, image)
SELECT 'Desserts', NULL
WHERE NOT EXISTS (
  SELECT 1 FROM categories WHERE name = 'Desserts'
);

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
