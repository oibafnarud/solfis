<?php
/**
 * Clase Contact independiente
 * Este archivo debe guardarse como includes/contact-class.php
 */

/**
 * Clase Contact - Maneja los mensajes de contacto
 */
class Contact {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Crear tabla si no existe
        $this->createContactTable();
    }
    
    /**
     * Crear la tabla de contactos si no existe
     */
    private function createContactTable() {
        $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied', 'archived') NOT NULL DEFAULT 'new',
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )";
        
        $this->db->query($sql);
    }
    
    /**
     * Guardar un nuevo mensaje de contacto
     */
    public function saveMessage($data) {
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $phone = isset($data['phone']) ? $this->db->escape($data['phone']) : '';
        $subject = $this->db->escape($data['subject']);
        $message = $this->db->escape($data['message']);
        $ipAddress = $this->db->escape($_SERVER['REMOTE_ADDR'] ?? '');
        
        $sql = "INSERT INTO contact_messages (name, email, phone, subject, message, status, ip_address, created_at, updated_at) 
                VALUES ('$name', '$email', '$phone', '$subject', '$message', 'new', '$ipAddress', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener mensajes para el dashboard
     */
    public function getMessages($page = 1, $per_page = 10, $status = null) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM contact_messages";
                
        if ($status) {
            $status = $this->db->escape($status);
            $sql .= " WHERE status = '$status'";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $messages = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM contact_messages";
        
        if ($status) {
            $countSql .= " WHERE status = '$status'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalMessages = 0;
        
        if ($countResult && $countResult->num_rows > 0) {
            $totalMessages = $countResult->fetch_assoc()['total'];
        }
        
        return [
            'messages' => $messages,
            'total' => $totalMessages,
            'pages' => ceil($totalMessages / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener un mensaje por su ID
     */
    public function getMessageById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM contact_messages WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Actualizar el estado de un mensaje
     */
    public function updateStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->escape($status);
        
        $sql = "UPDATE contact_messages SET 
                status = '$status', 
                updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar un mensaje
     */
    public function deleteMessage($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM contact_messages WHERE id = $id";
        return $this->db->query($sql);
    }
	
	/**
 * Guardar una respuesta a un mensaje
 */
public function saveReply($contactId, $replyMessage) {
    $contactId = (int)$contactId;
    $replyMessage = $this->db->escape($replyMessage);
    
    // Verificar si existe la tabla de respuestas
    $this->createRepliesTable();
    
    // Guardar la respuesta
    $sql = "INSERT INTO contact_replies (contact_id, reply_content, created_at) 
            VALUES ($contactId, '$replyMessage', NOW())";
            
    return $this->db->query($sql);
}

/**
 * Obtener las respuestas a un mensaje
 */
public function getMessageReplies($contactId) {
    $contactId = (int)$contactId;
    
    // Verificar si existe la tabla de respuestas
    $this->createRepliesTable();
    
    $sql = "SELECT * FROM contact_replies 
            WHERE contact_id = $contactId 
            ORDER BY created_at DESC";
            
    $result = $this->db->query($sql);
    $replies = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $replies[] = $row;
        }
    }
    
    return $replies;
}

	/**
	 * Crear la tabla de respuestas si no existe
	 */
	private function createRepliesTable() {
		$sql = "CREATE TABLE IF NOT EXISTS contact_replies (
			id INT AUTO_INCREMENT PRIMARY KEY,
			contact_id INT NOT NULL,
			reply_content TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			FOREIGN KEY (contact_id) REFERENCES contact_messages(id) ON DELETE CASCADE
		)";
		
		$this->db->query($sql);
	}

	/**
	 * Desarchivar un mensaje
	 */
	public function unarchiveMessage($id) {
		$id = (int)$id;
		
		$sql = "UPDATE contact_messages SET 
				status = 'read', 
				updated_at = NOW() 
				WHERE id = $id";
				
		return $this->db->query($sql);
	}
}