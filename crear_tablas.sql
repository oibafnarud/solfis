-- Crear base de datos
CREATE DATABASE IF NOT EXISTS solfis_blog;
USE solfis_blog;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'author') NOT NULL DEFAULT 'author',
    image VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- Tabla de posts
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    author_id INT NOT NULL,
    status ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'draft',
    image VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de comentarios
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    parent_id INT DEFAULT 0,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Tabla de multimedia
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    size INT NOT NULL,
    created_at DATETIME NOT NULL
);

-- Tabla de suscriptores
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL
);

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (hasheada)
INSERT INTO users (name, email, password, role, created_at, updated_at) 
VALUES ('Admin', 'admin@solfis.com', '$2y$10$Yg1LQjjToJH2P1AYrwQniu/CeI1vTK5TLQ3Ppny.JelAcJj0iX.c.', 'admin', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Insertar categorías predeterminadas
INSERT INTO categories (name, slug, description, created_at, updated_at)
VALUES 
('Contabilidad', 'contabilidad', 'Artículos sobre contabilidad general', NOW(), NOW()),
('Impuestos', 'impuestos', 'Información sobre impuestos y declaraciones fiscales', NOW(), NOW()),
('Finanzas', 'finanzas', 'Consejos y estrategias financieras', NOW(), NOW()),
('Negocios', 'negocios', 'Temas relacionados con la gestión empresarial', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Insertar post de ejemplo
INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status, published_at, created_at, updated_at)
SELECT 
    'Bienvenidos al Blog de SolFis', 
    'bienvenidos-al-blog-de-solfis',
    '<p>Bienvenidos al blog oficial de SolFis, donde compartiremos información relevante sobre contabilidad, finanzas, impuestos y gestión empresarial.</p><p>Nuestro objetivo es proporcionar contenido de valor que ayude a profesionales y empresarios a estar al día con las últimas novedades en el ámbito financiero y contable.</p><p>¡Esperamos que disfruten de nuestros artículos!</p>',
    'Bienvenidos al blog oficial de SolFis, donde compartiremos información relevante sobre contabilidad, finanzas, impuestos y gestión empresarial.',
    (SELECT id FROM categories WHERE slug = 'contabilidad' LIMIT 1),
    (SELECT id FROM users WHERE email = 'admin@solfis.com' LIMIT 1),
    'published',
    NOW(),
    NOW(),
    NOW()
FROM dual
WHERE NOT EXISTS (SELECT id FROM posts WHERE slug = 'bienvenidos-al-blog-de-solfis');