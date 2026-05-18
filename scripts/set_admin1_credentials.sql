INSERT INTO users (full_name, national_id, email, role, password, status)
VALUES (
  'Admin One',
  'ADMIN0001',
  'admin1@gmail.com',
  'admin',
  '$2y$10$1ycTFpUbep0LEullkGqUTOG81vJeKp29zprJXK0P61NQ4s.jqBb16',
  'approved'
)
ON DUPLICATE KEY UPDATE
  role = 'admin',
  password = VALUES(password),
  status = 'approved';
