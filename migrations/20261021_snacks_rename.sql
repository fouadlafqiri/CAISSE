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
  AND LOWER(p.name) IN ('toast', 'toast au fromage');
