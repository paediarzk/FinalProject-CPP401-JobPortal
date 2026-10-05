-- Run AFTER schema.sql. Creates the first administrator account.
-- Default login: admin@jobportal.local / Admin#12345   (CHANGE IT after first login!)
USE jobportal;
INSERT INTO users (email, password_hash, role)
VALUES ('admin@jobportal.local', '$2y$10$4nVUoS9y0Oh4iQ0PaUdituMS.iA4UE/ePXgSxbCy4TZiLZ/PEj8MK', 'admin');
