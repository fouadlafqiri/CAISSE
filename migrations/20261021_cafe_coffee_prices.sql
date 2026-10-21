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
