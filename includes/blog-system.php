<?php
/**
 * Sistema Principal del Blog SolFis
 * 
 * Este archivo contiene todas las clases necesarias para el funcionamiento
 * del sistema de blog, incluyendo modelos para todas las entidades.
 */

// Clase Database - Maneja las conexiones a la base de datos
class Database {
    private $connection;
    private static $instance;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die('Error de conexión: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
}

// Clase BlogPost - Maneja la lógica de los artículos del blog
class BlogPost {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todos los posts con paginación y filtrado opcional por categoría
     */
    public function getPosts($page = 1, $per_page = 6, $category = null) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT p.*, c.name as category_name, u.name as author_name, u.image as author_image 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE p.status = 'published'";
        
        if ($category) {
            $category = $this->db->escape($category);
            $sql .= " AND c.slug = '$category'";
        }
        
        $sql .= " ORDER BY p.published_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $posts = [];
        
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        // Contar total de posts para paginación
        $countSql = "SELECT COUNT(*) as total FROM posts p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.status = 'published'";
                     
        if ($category) {
            $countSql .= " AND c.slug = '$category'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalPosts = $countResult->fetch_assoc()['total'];
        
        return [
            'posts' => $posts,
            'total' => $totalPosts,
            'pages' => ceil($totalPosts / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener un post por su slug
     */
    public function getPostBySlug($slug) {
        $slug = $this->db->escape($slug);
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as author_name, u.image as author_image, u.bio as author_bio
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE p.slug = '$slug' AND p.status = 'published'";
                
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener posts relacionados según categoría
     */
    public function getRelatedPosts($postId, $categoryId, $limit = 3) {
        $postId = (int)$postId;
        $categoryId = (int)$categoryId;
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id != $postId AND p.category_id = $categoryId AND p.status = 'published'
                ORDER BY p.published_at DESC 
                LIMIT $limit";
                
        $result = $this->db->query($sql);
        $posts = [];
        
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        return $posts;
    }
    
    /**
     * Crear un nuevo post
     */
    public function createPost($data) {
        $title = $this->db->escape($data['title']);
        $slug = $this->db->escape($data['slug']);
        $content = $this->db->escape($data['content']);
        $excerpt = $this->db->escape($data['excerpt']);
        $categoryId = (int)$data['category_id'];
        $authorId = (int)$data['author_id'];
        $status = $this->db->escape($data['status']);
        $image = $this->db->escape($data['image']);
        $published_at = $data['published_at'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status, image, published_at, created_at, updated_at) 
                VALUES ('$title', '$slug', '$content', '$excerpt', $categoryId, $authorId, '$status', '$image', '$published_at', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar un post existente
     */
    public function updatePost($id, $data) {
        $id = (int)$id;
        $title = $this->db->escape($data['title']);
        $slug = $this->db->escape($data['slug']);
        $content = $this->db->escape($data['content']);
        $excerpt = $this->db->escape($data['excerpt']);
        $categoryId = (int)$data['category_id'];
        $status = $this->db->escape($data['status']);
        $image = $this->db->escape($data['image']);
        
        $sql = "UPDATE posts SET 
                title = '$title', 
                slug = '$slug', 
                content = '$content', 
                excerpt = '$excerpt', 
                category_id = $categoryId, 
                status = '$status',";
                
        if (!empty($image)) {
            $sql .= " image = '$image',";
        }
        
        $sql .= " updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar un post
     */
    public function deletePost($id) {
        $id = (int)$id;
        $sql = "DELETE FROM posts WHERE id = $id";
        
        return $this->db->query($sql);
    }
    
    /**
     * Obtener posts para dashboard (admin)
     */
    public function getAdminPosts($page = 1, $per_page = 10, $status = null) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT p.*, c.name as category_name, u.name as author_name 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id";
                
        if ($status) {
            $status = $this->db->escape($status);
            $sql .= " WHERE p.status = '$status'";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $posts = [];
        
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM posts";
        
        if ($status) {
            $countSql .= " WHERE status = '$status'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalPosts = $countResult->fetch_assoc()['total'];
        
        return [
            'posts' => $posts,
            'total' => $totalPosts,
            'pages' => ceil($totalPosts / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener un post por su ID (admin)
     */
    public function getPostById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM posts WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}

// Clase Category - Maneja las categorías del blog
class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las categorías
     */
    public function getCategories() {
        $sql = "SELECT c.*, COUNT(p.id) as post_count 
                FROM categories c 
                LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                GROUP BY c.id 
                ORDER BY c.name ASC";
                
        $result = $this->db->query($sql);
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Crear una nueva categoría
     */
    public function createCategory($data) {
        $name = $this->db->escape($data['name']);
        $slug = $this->db->escape($data['slug']);
        $description = $this->db->escape($data['description'] ?? '');
        
        $sql = "INSERT INTO categories (name, slug, description, created_at, updated_at) 
                VALUES ('$name', '$slug', '$description', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar una categoría
     */
    public function updateCategory($id, $data) {
        $id = (int)$id;
        $name = $this->db->escape($data['name']);
        $slug = $this->db->escape($data['slug']);
        $description = $this->db->escape($data['description'] ?? '');
        
        $sql = "UPDATE categories SET 
                name = '$name', 
                slug = '$slug', 
                description = '$description', 
                updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una categoría
     */
    public function deleteCategory($id) {
        $id = (int)$id;
        
        // Verificar que no tenga posts asociados
        $checkSql = "SELECT COUNT(*) as count FROM posts WHERE category_id = $id";
        $result = $this->db->query($checkSql);
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            return false; // No se puede eliminar si tiene posts
        }
        
        $sql = "DELETE FROM categories WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener una categoría por su ID
     */
    public function getCategoryById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM categories WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener una categoría por su slug
     */
    public function getCategoryBySlug($slug) {
        $slug = $this->db->escape($slug);
        
        $sql = "SELECT * FROM categories WHERE slug = '$slug'";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}

// Clase User - Maneja los usuarios del sistema de blog
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Verificar credenciales de usuario para login
     */
    public function login($email, $password) {
        $email = $this->db->escape($email);
        
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Eliminar la contraseña antes de devolver los datos
                unset($user['password']);
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getUsers() {
        $sql = "SELECT id, name, email, role, image, created_at FROM users ORDER BY name ASC";
        $result = $this->db->query($sql);
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Crear un nuevo usuario
     */
    public function createUser($data) {
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $this->db->escape($data['role']);
        $image = $this->db->escape($data['image'] ?? '');
        $bio = $this->db->escape($data['bio'] ?? '');
        
        $sql = "INSERT INTO users (name, email, password, role, image, bio, created_at, updated_at) 
                VALUES ('$name', '$email', '$password', '$role', '$image', '$bio', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar un usuario
     */
    public function updateUser($id, $data) {
        $id = (int)$id;
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $role = $this->db->escape($data['role']);
        $image = $this->db->escape($data['image'] ?? '');
        $bio = $this->db->escape($data['bio'] ?? '');
        
        $sql = "UPDATE users SET 
                name = '$name', 
                email = '$email', 
                role = '$role',";
                
        if (!empty($image)) {
            $sql .= " image = '$image',";
        }
        
        $sql .= " bio = '$bio', 
                updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Cambiar contraseña de usuario
     */
    public function changePassword($id, $password) {
        $id = (int)$id;
        $password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET 
                password = '$password', 
                updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar un usuario
     */
    public function deleteUser($id) {
        $id = (int)$id;
        
        // Verificar que no tenga posts asociados
        $checkSql = "SELECT COUNT(*) as count FROM posts WHERE author_id = $id";
        $result = $this->db->query($checkSql);
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            return false; // No se puede eliminar si tiene posts
        }
        
        $sql = "DELETE FROM users WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener un usuario por su ID
     */
    public function getUserById($id) {
        $id = (int)$id;
        
        $sql = "SELECT id, name, email, role, image, bio, created_at FROM users WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}

// Clase Media - Maneja los archivos multimedia (imágenes)
class Media {
    private $db;
    private $uploadDir = 'img/blog/uploads/';
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Crear directorio si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Subir una imagen
     */
    public function uploadImage($file, $customFileName = null) {
        $fileName = $customFileName ?? uniqid() . '_' . basename($file['name']);
        $targetFile = $this->uploadDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Verificar si es una imagen real
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return [
                'success' => false,
                'message' => 'El archivo no es una imagen válida.'
            ];
        }
        
        // Verificar extensión
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Solo se permiten archivos JPG, JPEG, PNG y GIF.'
            ];
        }
        
        // Verificar tamaño (5MB máximo)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande. Máximo 5MB.'
            ];
        }
        
        // Subir archivo
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Registrar en base de datos
            $name = $this->db->escape(basename($file['name']));
            $path = $this->db->escape($targetFile);
            $type = $this->db->escape($file['type']);
            $size = (int)$file['size'];
            
            $sql = "INSERT INTO media (name, file_name, path, type, size, created_at) 
                    VALUES ('$name', '$fileName', '$path', '$type', $size, NOW())";
                    
            $this->db->query($sql);
            
            return [
                'success' => true,
                'file' => $targetFile,
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Hubo un error al subir el archivo.'
            ];
        }
    }
    
    /**
     * Obtener todas las imágenes
     */
    public function getImages($page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM media ORDER BY created_at DESC LIMIT $offset, $per_page";
        $result = $this->db->query($sql);
        $images = [];
        
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM media";
        $countResult = $this->db->query($countSql);
        $totalImages = $countResult->fetch_assoc()['total'];
        
        return [
            'images' => $images,
            'total' => $totalImages,
            'pages' => ceil($totalImages / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Eliminar una imagen
     */
    public function deleteImage($id) {
        $id = (int)$id;
        
        // Obtener información de la imagen
        $sql = "SELECT path FROM media WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result->num_rows > 0) {
            $image = $result->fetch_assoc();
            
            // Eliminar archivo
            if (file_exists($image['path'])) {
                unlink($image['path']);
            }
            
            // Eliminar registro
            $deleteSql = "DELETE FROM media WHERE id = $id";
            return $this->db->query($deleteSql);
        }
        
        return false;
    }
}

// Clase Comment - Maneja los comentarios del blog
class Comment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener comentarios de un post
     */
    public function getPostComments($postId, $approved = true) {
        $postId = (int)$postId;
        
        $sql = "SELECT * FROM comments 
                WHERE post_id = $postId";
                
        if ($approved) {
            $sql .= " AND status = 'approved'";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $result = $this->db->query($sql);
        $comments = [];
        
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        return $comments;
    }
    
    /**
     * Crear un nuevo comentario
     */
    public function createComment($data) {
        $postId = (int)$data['post_id'];
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $content = $this->db->escape($data['content']);
        $status = $this->db->escape($data['status'] ?? 'pending');
        
        $sql = "INSERT INTO comments (post_id, parent_id, name, email, content, status, created_at) 
                VALUES ($postId, $parentId, '$name', '$email', '$content', '$status', NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Aprobar un comentario
     */
    public function approveComment($id) {
        $id = (int)$id;
        
        $sql = "UPDATE comments SET status = 'approved' WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Rechazar un comentario
     */
    public function rejectComment($id) {
        $id = (int)$id;
        
        $sql = "UPDATE comments SET status = 'rejected' WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar un comentario
     */
    public function deleteComment($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM comments WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener comentarios para dashboard (admin)
     */
    public function getAdminComments($page = 1, $per_page = 10, $status = null) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT c.*, p.title as post_title 
                FROM comments c 
                LEFT JOIN posts p ON c.post_id = p.id";
                
        if ($status) {
            $status = $this->db->escape($status);
            $sql .= " WHERE c.status = '$status'";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $comments = [];
        
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM comments";
        
        if ($status) {
            $countSql .= " WHERE status = '$status'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalComments = $countResult->fetch_assoc()['total'];
        
        return [
            'comments' => $comments,
            'total' => $totalComments,
            'pages' => ceil($totalComments / $per_page),
            'current_page' => $page
        ];
    }
}

// Clase Subscriber - Maneja los suscriptores del newsletter
class Subscriber {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Suscribir un nuevo email
     */
    public function subscribe($email, $name = null) {
        $email = $this->db->escape($email);
        $name = $name ? $this->db->escape($name) : '';
        
        // Verificar si ya existe
        $checkSql = "SELECT id FROM subscribers WHERE email = '$email'";
        $result = $this->db->query($checkSql);
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Este email ya está suscrito.'
            ];
        }
        
        $sql = "INSERT INTO subscribers (email, name, status, created_at) 
                VALUES ('$email', '$name', 'active', NOW())";
                
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Suscripción exitosa.'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al procesar la suscripción.'
        ];
    }
    
    /**
     * Obtener todos los suscriptores
     */
    public function getSubscribers($page = 1, $per_page = 20, $status = 'active') {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM subscribers";
        
        if ($status) {
            $status = $this->db->escape($status);
            $sql .= " WHERE status = '$status'";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $subscribers = [];
        
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM subscribers";
        
        if ($status) {
            $countSql .= " WHERE status = '$status'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalSubscribers = $countResult->fetch_assoc()['total'];
        
        return [
            'subscribers' => $subscribers,
            'total' => $totalSubscribers,
            'pages' => ceil($totalSubscribers / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Cambiar estado de un suscriptor
     */
    public function changeStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->escape($status);
        
        $sql = "UPDATE subscribers SET status = '$status' WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar un suscriptor
     */
    public function deleteSubscriber($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM subscribers WHERE id = $id";
        return $this->db->query($sql);
    }
}

/**
 * Clase Auth - Maneja la autenticación y autorización
 */
class Auth {
    private static $instance;
    private $user = null;
    
    private function __construct() {
        $this->checkSession();
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function checkSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user'])) {
            $this->user = $_SESSION['user'];
        }
    }
    
    public function login($email, $password) {
        $userModel = new User();
        $user = $userModel->login($email, $password);
        
        if ($user) {
            $_SESSION['user'] = $user;
            $this->user = $user;
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        $this->user = null;
        
        return true;
    }
    
    public function isLoggedIn() {
        return $this->user !== null;
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $this->user['role'] === 'admin';
    }
    
    public function getUser() {
        return $this->user;
    }
    
    public function getUserId() {
        return $this->isLoggedIn() ? $this->user['id'] : null;
    }
}

/**
 * Clase Helpers - Funciones útiles para el sistema
 */
class Helpers {
    /**
     * Generar slug a partir de un texto
     */
    public static function slugify($text) {
        // Reemplazar espacios y caracteres especiales
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Transliterar
        $text = iconv('utf-8', 'ascii//TRANSLIT', $text);
        
        // Reemplazar caracteres no alfanuméricos
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Eliminar guiones duplicados
        $text = preg_replace('~-+~', '-', $text);
        
        // Convertir a minúsculas
        $text = strtolower($text);
        
        // Eliminar guiones al inicio y final
        $text = trim($text, '-');
        
        return empty($text) ? 'n-a' : $text;
    }
    
    /**
     * Formato de fecha
     */
    public static function formatDate($dateString, $format = 'd M Y') {
        $date = new DateTime($dateString);
        return $date->format($format);
    }
    
    /**
     * Truncar texto 
     */
    public static function truncate($text, $length = 100, $append = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
    
    /**
     * Generar token aleatorio
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Obtener URL actual
     */
    public static function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Redirigir a otra página
     */
    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Sanitizar entrada
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
            return $input;
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}