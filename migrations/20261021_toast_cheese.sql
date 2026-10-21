UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.name = 'Toast au fromage',
    p.image = '../imgs/CLYO_photos_carres/15_Petit_Dejeuner_Fromage.jpg',
    p.selling_price = 8.00,
    p.is_active = 1
WHERE c.name = 'Snacks'
  AND LOWER(p.name) IN ('toast', 'toast au fromage');
