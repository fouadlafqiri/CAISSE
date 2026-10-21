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

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
  ('currency', 'MAD'),
  ('currency_symbol', 'DH'),
  ('store_name', 'Café'),
  ('invoice_header', 'Café - Ticket'),
  ('invoice_footer', 'Merci pour votre visite !'),
  ('wifi_ssid', ''),
  ('wifi_password', '');

UPDATE app_settings SET setting_value = 'Café'
WHERE setting_key = 'store_name' AND setting_value = 'Épicerie Al Baraka';

UPDATE app_settings SET setting_value = 'Café - Ticket'
WHERE setting_key = 'invoice_header' AND setting_value = 'EPICERIE AL BARAKA - Facture';
