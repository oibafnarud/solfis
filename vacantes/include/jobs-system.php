<?php
/**
 * Sistema principal para el portal de empleos de SolFis
 */

// Incluir la clase de conexión a base de datos si no está incluida
if (!class_exists('Database')) {
    require_once __DIR__ . '/database.php';
}

/**
 * Clase VacantesManager - Gestiona las operaciones relacionadas con las vacantes
 */
class VacantesManager {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las vacantes activas con paginación y filtros
     */
    public function getVacantes($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        // Construir la consulta base
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.color as categoria_color 
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.estado = 'Publicada'
                AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())";
        
        // Aplicar filtros si los hay
        if (!empty($filters)) {
            // Filtro por palabra clave
            if (!empty($filters['keyword'])) {
                $keyword = $this->db->escape($filters['keyword']);
                $sql .= " AND (v.titulo LIKE '%$keyword%' OR v.descripcion LIKE '%$keyword%' OR v.requisitos LIKE '%$keyword%' OR v.departamento LIKE '%$keyword%')";
            }
            
            // Filtro por ubicación
            if (!empty($filters['ubicacion'])) {
                $ubicacion = $this->db->escape($filters['ubicacion']);
                $sql .= " AND v.ubicacion = '$ubicacion'";
            }
            
            // Filtro por categoría
            if (!empty($filters['categoria'])) {
                $categoria = (int)$filters['categoria'];
                $sql .= " AND v.categoria_id = $categoria";
            }
            
            // Filtro por modalidad
            if (!empty($filters['modalidad'])) {
                $modalidad = $this->db->escape($filters['modalidad']);
                $sql .= " AND v.modalidad = '$modalidad'";
            }
            
            // Filtro por jornada
            if (!empty($filters['jornada'])) {
                $jornada = $this->db->escape($filters['jornada']);
                $sql .= " AND v.jornada = '$jornada'";
            }
            
            // Filtro por nivel
            if (!empty($filters['nivel'])) {
                $nivel = $this->db->escape($filters['nivel']);
                $sql .= " AND v.nivel = '$nivel'";
            }
        }
        
        // Ordenar los resultados
        $sql .= " ORDER BY v.destacada DESC, v.fecha_publicacion DESC";
        
        // Aplicar límite y offset para paginación
        $sql .= " LIMIT $offset, $limit";
        
        // Ejecutar la consulta
        $result = $this->db->query($sql);
        $vacantes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vacantes[] = $row;
            }
        }
        
        // Consultar total de registros para paginación (sin limit)
        $sqlCount = "SELECT COUNT(*) as total FROM vacantes v 
                    WHERE v.estado = 'Publicada'
                    AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())";
        
        // Aplicar los mismos filtros
        if (!empty($filters)) {
            // Filtro por palabra clave
            if (!empty($filters['keyword'])) {
                $keyword = $this->db->escape($filters['keyword']);
                $sqlCount .= " AND (v.titulo LIKE '%$keyword%' OR v.descripcion LIKE '%$keyword%' OR v.requisitos LIKE '%$keyword%' OR v.departamento LIKE '%$keyword%')";
            }
            
            // Filtro por ubicación
            if (!empty($filters['ubicacion'])) {
                $ubicacion = $this->db->escape($filters['ubicacion']);
                $sqlCount .= " AND v.ubicacion = '$ubicacion'";
            }
            
            // Filtro por categoría
            if (!empty($filters['categoria'])) {
                $categoria = (int)$filters['categoria'];
                $sqlCount .= " AND v.categoria_id = $categoria";
            }
            
            // Filtro por modalidad
            if (!empty($filters['modalidad'])) {
                $modalidad = $this->db->escape($filters['modalidad']);
                $sqlCount .= " AND v.modalidad = '$modalidad'";
            }
            
            // Filtro por jornada
            if (!empty($filters['jornada'])) {
                $jornada = $this->db->escape($filters['jornada']);
                $sqlCount .= " AND v.jornada = '$jornada'";
            }
            
            // Filtro por nivel
            if (!empty($filters['nivel'])) {
                $nivel = $this->db->escape($filters['nivel']);
                $sqlCount .= " AND v.nivel = '$nivel'";
            }
        }
        
        $resultCount = $this->db->query($sqlCount);
        $totalVacantes = 0;
        
        if ($resultCount && $row = $resultCount->fetch_assoc()) {
            $totalVacantes = $row['total'];
        }
        
        return [
            'vacantes' => $vacantes,
            'total' => $totalVacantes,
            'paginas' => ceil($totalVacantes / $limit),
            'pagina_actual' => $page
        ];
    }
    
    /**
     * Obtener una vacante por su slug
     */
    public function getVacanteBySlug($slug) {
        $slug = $this->db->escape($slug);
        
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.color
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.slug = '$slug'";
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener una vacante por su ID
     */
    public function getVacanteById($id) {
        $id = (int)$id;
        
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.color
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.id = $id";
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener vacantes por categoría
     */
    public function getVacantesByCategoria($categoriaSlug, $page = 1, $limit = 10) {
        $categoriaSlug = $this->db->escape($categoriaSlug);
        $offset = ($page - 1) * $limit;
        
        // Primero, obtener la categoría por su slug
        $sqlCategoria = "SELECT * FROM vacantes_categorias WHERE slug = '$categoriaSlug'";
        $resultCategoria = $this->db->query($sqlCategoria);
        
        if (!$resultCategoria || $resultCategoria->num_rows === 0) {
            return [
                'vacantes' => [],
                'total' => 0,
                'paginas' => 0,
                'pagina_actual' => $page,
                'categoria' => null
            ];
        }
        
        $categoria = $resultCategoria->fetch_assoc();
        $categoriaId = $categoria['id'];
        
        // Obtener vacantes de esta categoría
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.color
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.categoria_id = $categoriaId
                AND v.estado = 'Publicada'
                AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())
                ORDER BY v.destacada DESC, v.fecha_publicacion DESC
                LIMIT $offset, $limit";
        
        $result = $this->db->query($sql);
        $vacantes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vacantes[] = $row;
            }
        }
        
        // Contar total para paginación
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM vacantes 
                     WHERE categoria_id = $categoriaId
                     AND estado = 'Publicada'
                     AND (fecha_cierre IS NULL OR fecha_cierre >= CURDATE())";
        
        $resultCount = $this->db->query($sqlCount);
        $totalVacantes = 0;
        
        if ($resultCount && $row = $resultCount->fetch_assoc()) {
            $totalVacantes = $row['total'];
        }
        
        return [
            'vacantes' => $vacantes,
            'total' => $totalVacantes,
            'paginas' => ceil($totalVacantes / $limit),
            'pagina_actual' => $page,
            'categoria' => $categoria
        ];
    }
    
    /**
     * Obtener vacantes destacadas
     */
    public function getVacantesDestacadas($limit = 6) {
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.color
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.estado = 'Publicada'
                AND v.destacada = 1
                AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())
                ORDER BY v.fecha_publicacion DESC
                LIMIT $limit";
        
        $result = $this->db->query($sql);
        $vacantes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vacantes[] = $row;
            }
        }
        
        return $vacantes;
    }
    
    /**
     * Incrementar contador de vistas
     */
    public function incrementarVistas($id) {
        $id = (int)$id;
        
        $sql = "UPDATE vacantes SET vistas = vistas + 1 WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener habilidades requeridas para una vacante
     */
    public function getVacanteHabilidades($vacanteId) {
        $vacanteId = (int)$vacanteId;
        
        $sql = "SELECT * FROM vacantes_habilidades 
                WHERE vacante_id = $vacanteId 
                ORDER BY nombre";
        
        $result = $this->db->query($sql);
        $habilidades = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $habilidades[] = $row;
            }
        }
        
        return $habilidades;
    }
    
    /**
     * Obtener vacantes relacionadas por categoría (excluyendo la actual)
     */
    public function getVacantesRelacionadas($idActual, $categoriaId, $limit = 3) {
        $idActual = (int)$idActual;
        $categoriaId = (int)$categoriaId;
        $limit = (int)$limit;
        
        $sql = "SELECT v.*, c.nombre as categoria_nombre, c.color
                FROM vacantes v
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE v.id != $idActual
                AND v.categoria_id = $categoriaId
                AND v.estado = 'Publicada'
                AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())
                ORDER BY v.fecha_publicacion DESC
                LIMIT $limit";
        
        $result = $this->db->query($sql);
        $vacantes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vacantes[] = $row;
            }
        }
        
        // Si no hay suficientes vacantes en la misma categoría, obtener otras vacantes
        if (count($vacantes) < $limit) {
            $faltantes = $limit - count($vacantes);
            $idsExcluir = [$idActual];
            
            foreach ($vacantes as $v) {
                $idsExcluir[] = $v['id'];
            }
            
            $idsExcluirStr = implode(',', $idsExcluir);
            
            $sqlOtras = "SELECT v.*, c.nombre as categoria_nombre, c.color
                        FROM vacantes v
                        LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                        WHERE v.id NOT IN ($idsExcluirStr)
                        AND v.estado = 'Publicada'
                        AND (v.fecha_cierre IS NULL OR v.fecha_cierre >= CURDATE())
                        ORDER BY v.destacada DESC, v.fecha_publicacion DESC
                        LIMIT $faltantes";
            
            $resultOtras = $this->db->query($sqlOtras);
            
            if ($resultOtras) {
                while ($row = $resultOtras->fetch_assoc()) {
                    $vacantes[] = $row;
                }
            }
        }
        
        return $vacantes;
    }
}

/**
 * Clase CandidatosManager - Gestiona operaciones relacionadas con candidatos
 */
class CandidatosManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar un nuevo candidato
     */
    public function registrarCandidato($data) {
        // Implementación básica - se expandirá según necesidades
        $nombre = $this->db->escape($data['nombre']);
        $apellidos = $this->db->escape($data['apellidos']);
        $email = $this->db->escape($data['email']);
        $telefono = $this->db->escape($data['telefono'] ?? '');
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Verificar si el email ya está registrado
        $checkSql = "SELECT id FROM candidatos WHERE email = '$email'";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            return ['success' => false, 'message' => 'Este email ya está registrado en nuestro sistema.'];
        }
        
        $sql = "INSERT INTO candidatos (
                    nombre, apellidos, email, telefono, password, 
                    estado, created_at, updated_at
                ) VALUES (
                    '$nombre', '$apellidos', '$email', '$telefono', '$password', 
                    'Activo', NOW(), NOW()
                )";
        
        if ($this->db->query($sql)) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Error al registrar el candidato.'];
    }
    
    /**
     * Autenticar un candidato
     */
    public function login($email, $password) {
        $email = $this->db->escape($email);
        
        $sql = "SELECT * FROM candidatos WHERE email = '$email' AND estado = 'Activo'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $candidato = $result->fetch_assoc();
            if (password_verify($password, $candidato['password'])) {
                // Actualizar último acceso
                $id = $candidato['id'];
                $this->db->query("UPDATE candidatos SET ultimo_acceso = NOW() WHERE id = $id");
                
                // No devolver la contraseña
                unset($candidato['password']);
                return ['success' => true, 'candidato' => $candidato];
            }
        }
        
        return ['success' => false, 'message' => 'Credenciales incorrectas.'];
    }
}

/**
 * Clase PostulacionesManager - Gestiona las postulaciones a vacantes
 */
class PostulacionesManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener postulaciones de un candidato
     */
    public function getPostulacionesCandidato($candidatoId, $page = 1, $limit = 10) {
        $candidatoId = (int)$candidatoId;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, v.titulo as vacante_titulo, v.slug as vacante_slug, 
                v.ubicacion, v.modalidad, v.jornada,
                c.nombre as categoria_nombre, c.color
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                LEFT JOIN vacantes_categorias c ON v.categoria_id = c.id
                WHERE p.candidato_id = $candidatoId
                ORDER BY p.fecha_postulacion DESC
                LIMIT $offset, $limit";
        
        $result = $this->db->query($sql);
        $postulaciones = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $postulaciones[] = $row;
            }
        }
        
        // Contar total para paginación
        $sqlCount = "SELECT COUNT(*) as total FROM postulaciones WHERE candidato_id = $candidatoId";
        $resultCount = $this->db->query($sqlCount);
        $totalPostulaciones = 0;
        
        if ($resultCount && $row = $resultCount->fetch_assoc()) {
            $totalPostulaciones = $row['total'];
        }
        
        return [
            'postulaciones' => $postulaciones,
            'total' => $totalPostulaciones,
            'paginas' => ceil($totalPostulaciones / $limit),
            'pagina_actual' => $page
        ];
    }
    
    /**
     * Verificar si un candidato ya aplicó a una vacante
     */
    public function checkPostulacionExistente($vacanteId, $candidatoId) {
        $vacanteId = (int)$vacanteId;
        $candidatoId = (int)$candidatoId;
        
        $sql = "SELECT id FROM postulaciones WHERE vacante_id = $vacanteId AND candidato_id = $candidatoId";
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0);
    }
}