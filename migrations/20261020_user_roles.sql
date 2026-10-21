ALTER TABLE users
  MODIFY role ENUM('owner', 'assistant', 'admin', 'server') NOT NULL;

UPDATE users
SET role = CASE role
  WHEN 'owner' THEN 'admin'
  WHEN 'assistant' THEN 'server'
  ELSE role
END;

ALTER TABLE users
  MODIFY role ENUM('admin', 'server') NOT NULL DEFAULT 'server';
