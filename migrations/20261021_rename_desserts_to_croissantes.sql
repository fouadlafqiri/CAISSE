UPDATE categories current_category
LEFT JOIN categories existing ON existing.name = 'Croissantes'
SET current_category.name = 'Croissantes'
WHERE current_category.name = 'Desserts'
  AND existing.id IS NULL;
