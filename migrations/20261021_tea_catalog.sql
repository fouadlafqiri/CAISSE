UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Thé',
    p.selling_price = 10.00,
    p.image = '../imgs/CLYO_photos_carres/03_The.jpg',
    p.is_active = 1
WHERE c.name = 'Thés'
  AND LOWER(p.name) = LOWER('Thé Noir');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Thé moyenne',
    p.selling_price = 12.00,
    p.image = '../imgs/CLYO_photos_carres/03_The.jpg',
    p.is_active = 1
WHERE c.name = 'Thés'
  AND LOWER(p.name) = LOWER('Infusion');

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.is_active = 0
WHERE c.name = 'Thés'
  AND LOWER(p.name) = LOWER('Thé à la Menthe');

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
