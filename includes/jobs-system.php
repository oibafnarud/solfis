<?php

// Incluir TestManager y EmailSender si existen
if (file_exists(__DIR__ . '/TestManager.php')) {
    require_once __DIR__ . '/TestManager.php';
}

if (file_exists(__DIR__ . '/EmailSender.php')) {
    require_once __DIR__ . '/EmailSender.php';
}
/**
 * Sistema Principal de Vacantes SolFis
 * 
 * Este archivo contiene todas las clases necesarias para el funcionamiento
 * del sistema de vacantes, incluyendo modelos para todas las entidades.
 */
	// Clase para gestionar la base de datos (singleton)
	if (!class_exists('Database')) {
		class Database {
			private $connection;
			private static $instance;
			
			private function __construct() {
				// Incluir archivo de configuración si no están definidas las constantes
				if (!defined('DB_HOST')) {
					// Incluir el archivo config.php que contiene las constantes de la base de datos
					require_once __DIR__ . '/../config.php';
				}
				
				// Establecer conexión
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
			
			public function escape($string) {
				return $this->connection->real_escape_string($string);
			}
			
			public function lastInsertId() {
				return $this->connection->insert_id;
			}
		}
	}

// Clase para gestionar la base de datos (usando la misma conexión del sistema)
// Si ya existe una clase Database en blog-system.php, puedes comentar esta
// Clase para gestionar la base de datos (usando la misma conexión del sistema)

/**class VacanciesDatabase {
    private $connection;
    private static $instance;
    
    private function __construct() {
        // Incluir archivo de configuración si no están definidas las constantes
        if (!defined('DB_HOST')) {
            // Incluir el archivo config.php que contiene las constantes de la base de datos
            require_once __DIR__ . '/../config.php';
        }
        
        // Establecer conexión
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
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
}*/

/**
 * Clase para gestionar vacantes
 */
class VacancyManager {
    public $db; // Cambiado a público para facilitar el acceso
    
    public function __construct() {
        // Usar siempre la clase Database
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las vacantes con paginación y filtros opcionales
     */
    public function getVacancies($page = 1, $per_page = 10, $filters = []) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT v.*, c.nombre as categoria_nombre 
                FROM vacantes v
                LEFT JOIN categorias_vacantes c ON v.categoria_id = c.id
                WHERE 1=1";
        
        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $estado = $this->db->escape($filters['estado']);
            $sql .= " AND v.estado = '$estado'";
        }
        
        if (!empty($filters['categoria'])) {
            $categoria = (int)$filters['categoria'];
            $sql .= " AND v.categoria_id = $categoria";
        }
        
        if (!empty($filters['busqueda'])) {
            $busqueda = $this->db->escape($filters['busqueda']);
            $sql .= " AND (v.titulo LIKE '%$busqueda%' OR v.descripcion LIKE '%$busqueda%')";
        }
        
        if (!empty($filters['destacada'])) {
            $sql .= " AND v.destacada = 1";
        }
        
        if (!empty($filters['ubicacion'])) {
            $ubicacion = $this->db->escape($filters['ubicacion']);
            $sql .= " AND v.ubicacion = '$ubicacion'";
        }
        
        if (!empty($filters['modalidad'])) {
            $modalidad = $this->db->escape($filters['modalidad']);
            $sql .= " AND v.modalidad = '$modalidad'";
        }
        
        if (!empty($filters['excluir_id'])) {
            $excluir_id = (int)$filters['excluir_id'];
            $sql .= " AND v.id != $excluir_id";
        }
        
        // Ordenar
        if (!empty($filters['orden'])) {
            switch ($filters['orden']) {
                case 'fecha_asc':
                    $sql .= " ORDER BY v.fecha_publicacion ASC";
                    break;
                case 'titulo_asc':
                    $sql .= " ORDER BY v.titulo ASC";
                    break;
                case 'titulo_desc':
                    $sql .= " ORDER BY v.titulo DESC";
                    break;
                default:
                    $sql .= " ORDER BY v.fecha_publicacion DESC";
                    break;
            }
        } else {
            $sql .= " ORDER BY v.fecha_publicacion DESC";
        }
        
        $sql .= " LIMIT $offset, $per_page";
        
        // Ejecutar consulta
        $result = $this->db->query($sql);
        $vacancies = [];
        
        // Si hay resultados, procesarlos
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vacancies[] = $row;
            }
            
            // Contar total para paginación
            $countSql = "SELECT COUNT(*) as total FROM vacantes v WHERE 1=1";
            
            if (!empty($filters['estado'])) {
                $estado = $this->db->escape($filters['estado']);
                $countSql .= " AND v.estado = '$estado'";
            }
            
            if (!empty($filters['categoria'])) {
                $categoria = (int)$filters['categoria'];
                $countSql .= " AND v.categoria_id = $categoria";
            }
            
            if (!empty($filters['busqueda'])) {
                $busqueda = $this->db->escape($filters['busqueda']);
                $countSql .= " AND (v.titulo LIKE '%$busqueda%' OR v.descripcion LIKE '%$busqueda%')";
            }
            
            if (!empty($filters['destacada'])) {
                $countSql .= " AND v.destacada = 1";
            }
            
            if (!empty($filters['ubicacion'])) {
                $ubicacion = $this->db->escape($filters['ubicacion']);
                $countSql .= " AND v.ubicacion = '$ubicacion'";
            }
            
            if (!empty($filters['modalidad'])) {
                $modalidad = $this->db->escape($filters['modalidad']);
                $countSql .= " AND v.modalidad = '$modalidad'";
            }
            
            if (!empty($filters['excluir_id'])) {
                $excluir_id = (int)$filters['excluir_id'];
                $countSql .= " AND v.id != $excluir_id";
            }
            
            $countResult = $this->db->query($countSql);
            $total = ($countResult) ? $countResult->fetch_assoc()['total'] : 0;
            
            return [
                'vacancies' => $vacancies,
                'total' => $total,
                'pages' => ceil($total / $per_page),
                'current_page' => $page
            ];
        }
        
        // Si no hay resultados, devolver array vacío
        return [
            'vacancies' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
    
    /**
     * Crear una nueva vacante
     */
    public function createVacancy($data) {
        // Validar datos requeridos
        if (empty($data['titulo']) || empty($data['categoria']) || empty($data['descripcion'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $titulo = $this->db->escape($data['titulo']);
        $slug = $this->generateSlug($titulo);
        $descripcion = $this->db->escape($data['descripcion']);
        $requisitos = $this->db->escape($data['requisitos'] ?? '');
        $responsabilidades = $this->db->escape($data['responsabilidades'] ?? '');
        $beneficios = $this->db->escape($data['beneficios'] ?? '');
        $categoria_id = (int)$data['categoria'];
        $ubicacion = $this->db->escape($data['ubicacion'] ?? '');
        $modalidad = $this->db->escape($data['modalidad'] ?? 'presencial');
        $tipo_contrato = $this->db->escape($data['tipo_contrato'] ?? 'tiempo_completo');
        $experiencia = $this->db->escape($data['experiencia'] ?? '');
        $salario_min = !empty($data['salario_min']) ? (float)$data['salario_min'] : 0;
        $salario_max = !empty($data['salario_max']) ? (float)$data['salario_max'] : 0;
        $mostrar_salario = !empty($data['mostrar_salario']) ? 1 : 0;
        $estado = $this->db->escape($data['estado'] ?? 'borrador');
        $destacada = !empty($data['destacada']) ? 1 : 0;
		$empresa_contratante = $this->db->escape($data['empresa_contratante'] ?? '');
		$mostrar_empresa = !empty($data['mostrar_empresa']) ? 1 : 0;
        
        // Fechas
        $fecha_publicacion = !empty($data['fecha_publicacion']) ? "'" . $this->db->escape($data['fecha_publicacion']) . "'" : 'NULL';
        $fecha_cierre = !empty($data['fecha_cierre']) ? "'" . $this->db->escape($data['fecha_cierre']) . "'" : 'NULL';
        
        // Consulta SQL
		$sql = "INSERT INTO vacantes (
					titulo, slug, descripcion, requisitos, responsabilidades, beneficios, 
					categoria_id, ubicacion, modalidad, tipo_contrato, experiencia,
					salario_min, salario_max, mostrar_salario, estado, destacada,
					empresa_contratante, mostrar_empresa,
					fecha_publicacion, fecha_cierre, created_at, updated_at
				) VALUES (
					'$titulo', '$slug', '$descripcion', '$requisitos', '$responsabilidades', '$beneficios',
					$categoria_id, '$ubicacion', '$modalidad', '$tipo_contrato', '$experiencia',
					$salario_min, $salario_max, $mostrar_salario, '$estado', $destacada,
					'$empresa_contratante', $mostrar_empresa,
					$fecha_publicacion, $fecha_cierre, NOW(), NOW()
				)";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Vacante creada con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la vacante: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Generar un slug a partir de un título
     */
    private function generateSlug($text) {
        // Convertir a minúsculas
        $text = strtolower($text);
        
        // Reemplazar caracteres especiales
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Reemplazar espacios con guiones
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Eliminar guiones al principio y al final
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Obtener una vacante por su ID
     */
	public function getVacancyById($id) {
		$id = (int)$id;
		
		$sql = "SELECT v.*, c.nombre as categoria_nombre 
				FROM vacantes v
				LEFT JOIN categorias_vacantes c ON v.categoria_id = c.id
				WHERE v.id = $id";
				
		$result = $this->db->query($sql);
		
		return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
	}
    
    /**
     * Actualizar una vacante existente
     */
    public function updateVacancy($id, $data) {
        // Validar datos requeridos
        if (empty($data['titulo']) || empty($data['categoria']) || empty($data['descripcion'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $id = (int)$id;
        $titulo = $this->db->escape($data['titulo']);
        $descripcion = $this->db->escape($data['descripcion']);
        $requisitos = $this->db->escape($data['requisitos'] ?? '');
        $responsabilidades = $this->db->escape($data['responsabilidades'] ?? '');
        $beneficios = $this->db->escape($data['beneficios'] ?? '');
        $categoria_id = (int)$data['categoria'];
        $ubicacion = $this->db->escape($data['ubicacion'] ?? '');
        $modalidad = $this->db->escape($data['modalidad'] ?? 'presencial');
        $tipo_contrato = $this->db->escape($data['tipo_contrato'] ?? 'tiempo_completo');
        $experiencia = $this->db->escape($data['experiencia'] ?? '');
        $salario_min = !empty($data['salario_min']) ? (float)$data['salario_min'] : 0;
        $salario_max = !empty($data['salario_max']) ? (float)$data['salario_max'] : 0;
        $mostrar_salario = !empty($data['mostrar_salario']) ? 1 : 0;
        $estado = $this->db->escape($data['estado'] ?? 'borrador');
        $destacada = !empty($data['destacada']) ? 1 : 0;
		$empresa_contratante = $this->db->escape($data['empresa_contratante'] ?? '');
		$mostrar_empresa = !empty($data['mostrar_empresa']) ? 1 : 0;
        
        // Fechas
        $fecha_publicacion = !empty($data['fecha_publicacion']) ? "'" . $this->db->escape($data['fecha_publicacion']) . "'" : 'NULL';
        $fecha_cierre = !empty($data['fecha_cierre']) ? "'" . $this->db->escape($data['fecha_cierre']) . "'" : 'NULL';
        
        // Consulta SQL
		$sql = "UPDATE vacantes SET 
					titulo = '$titulo',
					descripcion = '$descripcion',
					requisitos = '$requisitos',
					responsabilidades = '$responsabilidades',
					beneficios = '$beneficios',
					categoria_id = $categoria_id,
					ubicacion = '$ubicacion',
					modalidad = '$modalidad',
					tipo_contrato = '$tipo_contrato',
					experiencia = '$experiencia',
					salario_min = $salario_min,
					salario_max = $salario_max,
					mostrar_salario = $mostrar_salario,
					estado = '$estado',
					destacada = $destacada,
					empresa_contratante = '$empresa_contratante',
					mostrar_empresa = $mostrar_empresa,
					fecha_publicacion = $fecha_publicacion,
					fecha_cierre = $fecha_cierre,
					updated_at = NOW()
				WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Vacante actualizada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la vacante: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Cambiar el estado de una vacante
     */
    public function changeVacancyStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->escape($status);
        
        $sql = "UPDATE vacantes SET estado = '$status', updated_at = NOW() WHERE id = $id";
        
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una vacante
     */
    public function deleteVacancy($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM vacantes WHERE id = $id";
        
        return $this->db->query($sql);
    }
	

/**
 * Obtiene vacantes recomendadas para un candidato
 * basado en su perfil y preferencias
 */
public function getRecommendedVacancies($candidato_id, $limit = 5) {
    $candidato_id = (int)$candidato_id;
    $limit = (int)$limit;
    
    // Obtener información del candidato
    $candidateManager = new CandidateManager();
    $candidato = $candidateManager->getCandidateById($candidato_id);
    
    if (!$candidato) {
        return [];
    }
    
    // Construir consulta base - SIN usar la función inexistente
    $sql = "SELECT v.*, c.nombre as categoria_nombre ";
    
    // Ya no intentamos usar getProfileMatchPercentage aquí
    // Simplemente agregamos un valor simulado para match_percentage
    $sql .= ", 80 as match_percentage "; // Valor fijo por ahora
    
    $sql .= "FROM vacantes v
            LEFT JOIN categorias_vacantes c ON v.categoria_id = c.id
            WHERE v.estado = 'publicada' ";
    
    // Filtrar por áreas de interés si están definidas
    if (!empty($candidato['areas_interes'])) {
        $areas = explode(',', $candidato['areas_interes']);
        $areas_escaped = array_map(function($area) {
            return (int)$area;
        }, $areas);
        
        if (!empty($areas_escaped)) {
            $areas_str = implode(',', $areas_escaped);
            $sql .= "AND v.categoria_id IN ($areas_str) ";
        }
    }
    
    // Filtrar por modalidad preferida si está definida
    if (!empty($candidato['modalidad_preferida'])) {
        $modalidad = $this->db->escape($candidato['modalidad_preferida']);
        $sql .= "AND (v.modalidad = '$modalidad' OR v.modalidad = 'hibrido') ";
    }
    
    // Filtrar por tipo de contrato preferido si está definido
    if (!empty($candidato['tipo_contrato_preferido'])) {
        $tipo_contrato = $this->db->escape($candidato['tipo_contrato_preferido']);
        $sql .= "AND v.tipo_contrato = '$tipo_contrato' ";
    }
    
    // Ordenar por fecha de publicación
    $sql .= "ORDER BY v.fecha_publicacion DESC ";
    
    // Limitar resultados
    $sql .= "LIMIT $limit";
    
    // Ejecutar consulta
    $result = $this->db->query($sql);
    $vacancies = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vacancies[] = $row;
        }
    }
    
    return $vacancies;
}
}



/**
 * Clase para gestionar categorías de vacantes
 */
class CategoryManager {
    public $db; // Cambiado a público para facilitar el acceso
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }
    
    /**
     * Obtener todas las categorías
     */
    public function getCategories() {
        $sql = "SELECT c.*, COUNT(v.id) as vacantes_count 
                FROM categorias_vacantes c
                LEFT JOIN vacantes v ON c.id = v.categoria_id AND v.estado = 'publicada'
                GROUP BY c.id
                ORDER BY c.nombre ASC";
                
        $result = $this->db->query($sql);
        $categories = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    /**
     * Obtener categoría por ID
     */
    public function getCategoryById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM categorias_vacantes WHERE id = $id";
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Crear nueva categoría
     */
    public function createCategory($data) {
        // Validar datos requeridos
        if (empty($data['nombre'])) {
            return [
                'success' => false,
                'message' => 'El nombre de la categoría es obligatorio'
            ];
        }
        
        $nombre = $this->db->escape($data['nombre']);
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $icono = $this->db->escape($data['icono'] ?? 'fas fa-briefcase');
        
        $sql = "INSERT INTO categorias_vacantes (nombre, descripcion, icono, created_at, updated_at)
                VALUES ('$nombre', '$descripcion', '$icono', NOW(), NOW())";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Categoría creada con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la categoría: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Actualizar categoría
     */
    public function updateCategory($id, $data) {
        // Validar datos requeridos
        if (empty($data['nombre'])) {
            return [
                'success' => false,
                'message' => 'El nombre de la categoría es obligatorio'
            ];
        }
        
        $id = (int)$id;
        $nombre = $this->db->escape($data['nombre']);
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $icono = $this->db->escape($data['icono'] ?? 'fas fa-briefcase');
        
        $sql = "UPDATE categorias_vacantes
                SET nombre = '$nombre', descripcion = '$descripcion', icono = '$icono', updated_at = NOW()
                WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Categoría actualizada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la categoría: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Eliminar categoría
     */
    public function deleteCategory($id) {
        $id = (int)$id;
        
        // Verificar si hay vacantes asociadas
        $checkSql = "SELECT COUNT(*) as total FROM vacantes WHERE categoria_id = $id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->fetch_assoc()['total'] > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar esta categoría porque tiene vacantes asociadas'
            ];
        }
        
        $sql = "DELETE FROM categorias_vacantes WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Categoría eliminada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $this->db->getConnection()->error
            ];
        }
    }
}

/**
 * Clase para gestionar aplicaciones a vacantes
 */

class ApplicationManager {
    public $db; // Cambiado a público para facilitar el acceso
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }
    
    /**
     * Obtener todas las aplicaciones con paginación y filtros opcionales
     */
    public function getApplications($page = 1, $per_page = 10, $filters = []) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT a.*, 
                       v.titulo as vacante_titulo,
                       c.nombre as candidato_nombre,
                       c.apellido as candidato_apellido,
                       c.email as candidato_email
                FROM aplicaciones a
                LEFT JOIN vacantes v ON a.vacante_id = v.id
                LEFT JOIN candidatos c ON a.candidato_id = c.id
                WHERE 1=1";
        
        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $estado = $this->db->escape($filters['estado']);
            $sql .= " AND a.estado = '$estado'";
        }
        
        if (!empty($filters['vacante_id'])) {
            $vacante_id = (int)$filters['vacante_id'];
            $sql .= " AND a.vacante_id = $vacante_id";
        }
        
        if (!empty($filters['candidato_id'])) {
            $candidato_id = (int)$filters['candidato_id'];
            $sql .= " AND a.candidato_id = $candidato_id";
        }
        
        // Ordenar
        $sql .= " ORDER BY a.fecha_aplicacion DESC LIMIT $offset, $per_page";
        
        // Ejecutar consulta
        $result = $this->db->query($sql);
        $applications = [];
        
        // Si hay resultados, procesarlos
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }
            
            // Contar total para paginación
            $countSql = "SELECT COUNT(*) as total FROM aplicaciones a WHERE 1=1";
            
            if (!empty($filters['estado'])) {
                $estado = $this->db->escape($filters['estado']);
                $countSql .= " AND a.estado = '$estado'";
            }
            
            if (!empty($filters['vacante_id'])) {
                $vacante_id = (int)$filters['vacante_id'];
                $countSql .= " AND a.vacante_id = $vacante_id";
            }
            
            if (!empty($filters['candidato_id'])) {
                $candidato_id = (int)$filters['candidato_id'];
                $countSql .= " AND a.candidato_id = $candidato_id";
            }
            
            $countResult = $this->db->query($countSql);
            $total = ($countResult) ? $countResult->fetch_assoc()['total'] : 0;
            
            return [
                'applications' => $applications,
                'total' => $total,
                'pages' => ceil($total / $per_page),
                'current_page' => $page
            ];
        }
        
        // Si no hay resultados, devolver array vacío
        return [
            'applications' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener aplicación por ID
     */
    public function getApplicationById($id) {
        $id = (int)$id;
        
        $sql = "SELECT a.*, 
                       v.titulo as vacante_titulo,
                       c.nombre as candidato_nombre,
                       c.apellido as candidato_apellido,
                       c.email as candidato_email,
                       c.telefono as candidato_telefono,
                       c.cv_path as candidato_cv
                FROM aplicaciones a
                LEFT JOIN vacantes v ON a.vacante_id = v.id
                LEFT JOIN candidatos c ON a.candidato_id = c.id
                WHERE a.id = $id";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Crear una nueva aplicación
     */
    public function createApplication($data) {
        // Validar datos requeridos
        if (empty($data['vacante_id']) || empty($data['candidato_id'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $vacante_id = (int)$data['vacante_id'];
        $candidato_id = (int)$data['candidato_id'];
        $estado = $this->db->escape($data['estado'] ?? 'recibida');
        $notas = $this->db->escape($data['notas'] ?? '');
        $carta_presentacion = $this->db->escape($data['carta_presentacion'] ?? '');
        $evaluaciones_pendientes = isset($data['evaluaciones_pendientes']) ? (int)$data['evaluaciones_pendientes'] : 1;
        
        // Datos adicionales
        $adicional = [];
        $campos_adicionales = [
            'experiencia', 'empresa_actual', 'cargo_actual', 'salario_esperado',
            'disponibilidad', 'fuente', 'modalidad_preferida', 'tipo_contrato_preferido'
        ];
        
        foreach ($campos_adicionales as $campo) {
            if (isset($data[$campo])) {
                $adicional[$campo] = $this->db->escape($data[$campo]);
            }
        }
        
        // Convertir datos adicionales a JSON
        $datos_json = !empty($adicional) ? "'" . $this->db->escape(json_encode($adicional)) . "'" : 'NULL';
        
        // Verificar si ya existe una aplicación
        $checkSql = "SELECT id FROM aplicaciones WHERE vacante_id = $vacante_id AND candidato_id = $candidato_id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Ya existe una aplicación
            return [
                'success' => false,
                'message' => 'Ya has aplicado a esta vacante anteriormente'
            ];
        }
        
        // Insertar la aplicación
        $sql = "INSERT INTO aplicaciones (
                    vacante_id, candidato_id, estado, notas, datos_adicionales,
                    evaluaciones_pendientes, fecha_aplicacion, created_at, updated_at
                ) VALUES (
                    $vacante_id, $candidato_id, '$estado', '$notas', $datos_json,
                    $evaluaciones_pendientes, NOW(), NOW(), NOW()
                )";
        
        if ($this->db->query($sql)) {
            $aplicacion_id = $this->db->lastInsertId();
            
            // Si hay carta de presentación, guardarla como respuesta
            if (!empty($carta_presentacion)) {
                $this->saveApplicationNote($aplicacion_id, 'Carta de Presentación', $carta_presentacion);
            }
            
            return [
                'success' => true,
                'message' => 'Aplicación creada con éxito',
                'id' => $aplicacion_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la aplicación: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Guardar nota en la aplicación
     */
    private function saveApplicationNote($aplicacion_id, $title, $content) {
        $aplicacion_id = (int)$aplicacion_id;
        $title = $this->db->escape($title);
        $content = $this->db->escape($content);
        
        $sql = "INSERT INTO etapas_proceso (
                    aplicacion_id, etapa, notas, estado, fecha, created_at, updated_at
                ) VALUES (
                    $aplicacion_id, '$title', '$content', 'completada', NOW(), NOW(), NOW()
                )";
        
        return $this->db->query($sql);
    }
    
    /**
     * Obtener aplicaciones por candidato
     * 
     * @param int $candidatoId ID del candidato
     * @param int $limit Límite de resultados (opcional)
     * @return array Aplicaciones del candidato
     */
    public function getApplicationsByCandidate($candidatoId, $limit = 0) {
        $candidatoId = (int)$candidatoId;
        
        $sql = "SELECT a.*, 
                       v.titulo as vacante_titulo,
                       v.estado as vacante_estado,
                       v.ubicacion as vacante_ubicacion,
                       c.nombre as categoria_nombre
                FROM aplicaciones a
                LEFT JOIN vacantes v ON a.vacante_id = v.id
                LEFT JOIN categorias_vacantes c ON v.categoria_id = c.id
                WHERE a.candidato_id = $candidatoId
                ORDER BY a.fecha_aplicacion DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $this->db->query($sql);
        $applications = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }
        }
        
        return $applications;
    }
    
    /**
     * Actualizar estado de una aplicación
     * 
     * @param int $id ID de la aplicación
     * @param string $status Nuevo estado de la aplicación
     * @param string $notes Notas adicionales (opcional)
     * @return bool Resultado de la operación
     */
    public function updateApplicationStatus($id, $status, $notes = '') {
        $id = (int)$id;
        $status = $this->db->escape($status);
        $notes = $this->db->escape($notes);
        
        // Primero registrar el cambio de estado en la tabla de etapas
        $etapaSql = "INSERT INTO etapas_proceso (
                        aplicacion_id, etapa, notas, estado, fecha, created_at, updated_at
                    ) VALUES (
                        $id, 'Cambio de estado a $status', '$notes', 'completada', NOW(), NOW(), NOW()
                    )";
        
        $this->db->query($etapaSql);
        
        // Luego actualizar el estado de la aplicación
        $sql = "UPDATE aplicaciones 
                SET estado = '$status', updated_at = NOW()";
                
        // Si hay notas, añadirlas al campo notas
        if (!empty($notes)) {
            $sql .= ", notas = CONCAT(IFNULL(notas, ''), '\n\n', '$notes')";
        }
        
        $sql .= " WHERE id = $id";
        
        return $this->db->query($sql);
    }

    /**
     * Obtener historial de etapas de una aplicación
     * 
     * @param int $aplicacionId ID de la aplicación
     * @return array Etapas de la aplicación
     */
	public function getApplicationStages($aplicacionId) {
		$aplicacionId = (int)$aplicacionId;
		
		// Eliminar JOIN con la tabla usuarios
		$sql = "SELECT ep.*
				FROM etapas_proceso ep
				WHERE ep.aplicacion_id = $aplicacionId
				ORDER BY ep.fecha DESC";
		
		$result = $this->db->query($sql);
		$stages = [];
		
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$stages[] = $row;
			}
		}
		
		return $stages;
	}

    /**
     * Agregar etapa a una aplicación
     * 
     * @param array $data Datos de la etapa
     * @return array Resultado de la operación
     */
    public function addApplicationStage($data) {
        // Validar datos requeridos
        if (empty($data['aplicacion_id']) || empty($data['etapa'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $aplicacion_id = (int)$data['aplicacion_id'];
        $etapa = $this->db->escape($data['etapa']);
        $notas = $this->db->escape($data['notas'] ?? '');
        $estado = $this->db->escape($data['estado'] ?? 'completada');
        $fecha = !empty($data['fecha']) ? "'" . $this->db->escape($data['fecha']) . "'" : 'NOW()';
        
        // Consulta SQL - Sin usuario_id
        $sql = "INSERT INTO etapas_proceso (
                    aplicacion_id, etapa, notas, estado, fecha, created_at, updated_at
                ) VALUES (
                    $aplicacion_id, '$etapa', '$notas', '$estado', $fecha, NOW(), NOW()
                )";
        
        if ($this->db->query($sql)) {
            // Si la etapa es un cambio de estado, actualizar el estado de la aplicación
            if (strpos($etapa, 'Cambio de estado') === 0) {
                $nuevoEstado = trim(str_replace('Cambio de estado a', '', $etapa));
                $this->updateApplicationStatus($aplicacion_id, $nuevoEstado);
            }
            
            return [
                'success' => true,
                'message' => 'Etapa agregada con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al agregar etapa: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Obtener entrevistas programadas para una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Entrevistas programadas
     */
		public function getScheduledInterviews($applicationId) {
			$applicationId = (int)$applicationId;
			
			// Eliminar JOIN con la tabla usuarios
			$sql = "SELECT ep.*
					FROM etapas_proceso ep
					WHERE ep.aplicacion_id = $applicationId 
					AND ep.etapa LIKE 'Entrevista%' 
					ORDER BY ep.fecha DESC";
					
			$result = $this->db->query($sql);
			$interviews = [];
			
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$interviews[] = $row;
				}
			}
			
			return $interviews;
		}

    /**
     * Analiza el texto de notas de entrevista para extraer información estructurada
     * 
     * @param string $notesText Texto de notas de la entrevista
     * @return array Información estructurada de la entrevista
     */
    public function parseInterviewInfo($notesText) {
        $info = [
            'tipo' => null,
            'fecha' => null,
            'lugar' => null
        ];
        
        // Extraer tipo
        if (preg_match('/Tipo:\s*([^\n]+)/i', $notesText, $matches)) {
            $info['tipo'] = trim($matches[1]);
        }
        
        // Extraer fecha
        if (preg_match('/Fecha y hora:\s*([^\n]+)/i', $notesText, $matches)) {
            $info['fecha'] = trim($matches[1]);
        }
        
        // Extraer lugar/enlace
        if (preg_match('/Lugar\/Enlace:\s*([^\n]+)/i', $notesText, $matches)) {
            $info['lugar'] = trim($matches[1]);
        }
        
        return $info;
    }

    /**
     * Actualizar estado de una entrevista
     * 
     * @param int $id ID de la etapa/entrevista
     * @param string $estado Nuevo estado (pendiente, completada, cancelada)
     * @return bool Resultado de la operación
     */
    public function updateInterviewStatus($id, $estado) {
        $id = (int)$id;
        $estado = $this->db->escape($estado);
        
        // Validar estado
        $estados_validos = ['pendiente', 'completada', 'cancelada'];
        if (!in_array($estado, $estados_validos)) {
            return false;
        }
        
        // Actualizar estado
        $sql = "UPDATE etapas_proceso SET estado = '$estado', updated_at = NOW() WHERE id = $id";
        return $this->db->query($sql);
    }

    /**
     * Obtener notas de una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Notas de la aplicación
     */
		public function getApplicationNotes($applicationId) {
			$applicationId = (int)$applicationId;
			
			// Eliminar JOIN con la tabla usuarios
			$sql = "SELECT ep.*
					FROM etapas_proceso ep
					WHERE ep.aplicacion_id = $applicationId 
					AND ep.etapa NOT LIKE 'Entrevista%'
					AND ep.etapa NOT LIKE 'Cambio de estado%'
					ORDER BY ep.fecha DESC";
					
			$result = $this->db->query($sql);
			$notes = [];
			
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$notes[] = $row;
				}
			}
			
			return $notes;
		}
    /**
     * Obtener historial de estados de una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Historial de estados
     */
    public function getApplicationHistory($applicationId) {
        $applicationId = (int)$applicationId;
        
        $sql = "SELECT ep.*
                FROM etapas_proceso ep
                WHERE ep.aplicacion_id = $applicationId 
                AND ep.etapa LIKE 'Cambio de estado%'
                ORDER BY ep.fecha DESC";
                
        $result = $this->db->query($sql);
        $history = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Extraer estados anterior y nuevo del texto de la etapa
                $pattern = '/Cambio de estado a (.+)/i';
                preg_match($pattern, $row['etapa'], $matches);
                
                $estado_nuevo = isset($matches[1]) ? trim($matches[1]) : '';
                $estado_anterior = '';
                
                $history[] = [
                    'id' => $row['id'],
                    'fecha_cambio' => $row['fecha'],
                    'estado_anterior' => $estado_anterior,
                    'estado_nuevo' => $estado_nuevo,
                    'comentario' => $row['notas'],
                    'usuario_id' => $row['usuario_id'] ?? null,
                    'usuario_nombre' => $row['usuario_nombre'] ?? ''
                ];
            }
        }
        
        return $history;
    }
	
	/**
 * Editar una nota de aplicación
 * 
 * @param int $notaId ID de la nota (etapa)
 * @param array $data Datos de la nota (etapa, notas)
 * @return array Resultado de la operación
 */
public function editApplicationNote($notaId, $data) {
    // Validar datos requeridos
    if (empty($notaId) || (empty($data['etapa']) && empty($data['notas']))) {
        return [
            'success' => false,
            'message' => 'Faltan campos obligatorios'
        ];
    }
    
    // Preparar datos
    $notaId = (int)$notaId;
    $updates = [];
    
    if (!empty($data['etapa'])) {
        $etapa = $this->db->escape($data['etapa']);
        $updates[] = "etapa = '$etapa'";
    }
    
    if (isset($data['notas'])) {
        $notas = $this->db->escape($data['notas']);
        $updates[] = "notas = '$notas'";
    }
    
    if (isset($data['estado'])) {
        $estado = $this->db->escape($data['estado']);
        $updates[] = "estado = '$estado'";
    }
    
    if (isset($data['fecha'])) {
        $fecha = $this->db->escape($data['fecha']);
        $updates[] = "fecha = '$fecha'";
    }
    
    $updates[] = "updated_at = NOW()";
    
    // Consulta SQL
    $sql = "UPDATE etapas_proceso SET " . implode(", ", $updates) . " WHERE id = $notaId";
    
    if ($this->db->query($sql)) {
        return [
            'success' => true,
            'message' => 'Nota actualizada con éxito',
            'id' => $notaId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al actualizar nota: ' . $this->db->getConnection()->error
        ];
    }
}

/**
 * Obtener una nota específica por su ID
 * 
 * @param int $notaId ID de la nota
 * @return array|null Datos de la nota o null si no existe
 */
public function getNoteById($notaId) {
    $notaId = (int)$notaId;
    
    $sql = "SELECT * FROM etapas_proceso WHERE id = $notaId";
    $result = $this->db->query($sql);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}
}

/**
 * Clase para gestionar candidatos
 */
class CandidateManager {
    public $db; // Cambiado a público para facilitar el acceso
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }

	/**
     * Verifica si un email ya existe en la base de datos
     */
    public function checkEmailExists($email) {
        $email = $this->db->escape($email);
        
        $sql = "SELECT * FROM candidatos WHERE email = '$email' LIMIT 1";
        return $this->db->query($sql);
    }
	
    /**
     * Obtener todos los candidatos con paginación y filtros opcionales
     */
    public function getCandidates($page = 1, $per_page = 10, $filters = []) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT a.id) as aplicaciones_count,
                       COUNT(DISTINCT e.id) as experiencias_count,
                       COUNT(DISTINCT ed.id) as educacion_count
                FROM candidatos c
                LEFT JOIN aplicaciones a ON c.id = a.candidato_id
                LEFT JOIN experiencia_laboral e ON c.id = e.candidato_id
                LEFT JOIN educacion ed ON c.id = ed.candidato_id
                WHERE 1=1";
        
        // Aplicar filtros
        if (!empty($filters['busqueda'])) {
            $busqueda = $this->db->escape($filters['busqueda']);
            $sql .= " AND (c.nombre LIKE '%$busqueda%' OR c.apellido LIKE '%$busqueda%' OR c.email LIKE '%$busqueda%')";
        }
        
        // Agrupar y ordenar
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC LIMIT $offset, $per_page";
        
        // Ejecutar consulta
        $result = $this->db->query($sql);
        $candidates = [];
        
        // Si hay resultados, procesarlos
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $candidates[] = $row;
            }
            
            // Contar total para paginación
            $countSql = "SELECT COUNT(*) as total FROM candidatos c WHERE 1=1";
            
            if (!empty($filters['busqueda'])) {
                $busqueda = $this->db->escape($filters['busqueda']);
                $countSql .= " AND (c.nombre LIKE '%$busqueda%' OR c.apellido LIKE '%$busqueda%' OR c.email LIKE '%$busqueda%')";
            }
            
            $countResult = $this->db->query($countSql);
            $total = ($countResult) ? $countResult->fetch_assoc()['total'] : 0;
            
            return [
                'candidates' => $candidates,
                'total' => $total,
                'pages' => ceil($total / $per_page),
                'current_page' => $page
            ];
        }
        
        // Si no hay resultados, devolver array vacío
        return [
            'candidates' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener un candidato por su ID
     */
    public function getCandidateById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM candidatos WHERE id = $id";
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    
    /**
     * Crea un nuevo candidato
     * 
     * @param array $data Datos del candidato
     * @return array Resultado de la operación
     */
    public function createCandidate($data) {
        // Validar datos requeridos
        if (empty($data['nombre']) || empty($data['apellido']) || empty($data['email'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Verificar si el email ya existe
        $email = $this->db->escape($data['email']);
        $checkSql = "SELECT id FROM candidatos WHERE email = '$email'";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Ya existe un candidato con este email'
            ];
        }
        
        // Construir la consulta SQL dinámicamente
        $fields = [];
        $values = [];
        
        // Campos conocidos que podrían venir en $data
        $posibles_campos = [
            'nombre', 'apellido', 'email', 'telefono', 'ubicacion', 'resumen',
            'cv_path', 'foto_path', 'linkedin', 'portfolio', 'user_id',
            'password', 'nivel_educativo', 'fecha_nacimiento', 'genero',
            'areas_interes', 'habilidades_destacadas', 'experiencia_general',
            'salario_esperado', 'modalidad_preferida', 'tipo_contrato_preferido',
            'disponibilidad', 'evaluaciones_pendientes', 'recibir_notificaciones',
            'disponibilidad_viajar', 'ubicacion_preferida', 'resumen_profesional'
        ];
        
        // Procesar cada campo posible
        foreach ($posibles_campos as $campo) {
            if (isset($data[$campo])) {
                $fields[] = $campo;
                
                // Tratar el valor según su tipo
                if ($campo === 'user_id' || $campo === 'evaluaciones_pendientes' || $campo === 'recibir_notificaciones') {
                    // Campos enteros
                    $values[] = !empty($data[$campo]) ? (int)$data[$campo] : 'NULL';
                } elseif ($campo === 'fecha_nacimiento') {
                    // Fechas
                    $values[] = !empty($data[$campo]) ? "'" . $this->db->escape($data[$campo]) . "'" : 'NULL';
                } else {
                    // Cadenas de texto
                    $values[] = "'" . $this->db->escape($data[$campo]) . "'";
                }
            }
        }
        
        // Añadir campos de fecha de creación/actualización
        $fields[] = 'created_at';
        $values[] = 'NOW()';
        $fields[] = 'updated_at';
        $values[] = 'NOW()';
        
        // Construir la consulta SQL
        $fields_str = implode(', ', $fields);
        $values_str = implode(', ', $values);
        
        $sql = "INSERT INTO candidatos ($fields_str) VALUES ($values_str)";
        
        // Ejecutar la consulta
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Candidato creado con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear el candidato: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Actualiza información de Drive para un candidato existente
     * 
     * @param int $id ID del candidato
     * @param array $driveData Datos de Google Drive
     * @return boolean Resultado de la operación
     */
    public function updateCandidateDriveInfo($id, $driveData) {
        $id = (int)$id;
        $fileId = $this->db->escape($driveData['fileId'] ?? '');
        $webViewLink = $this->db->escape($driveData['webViewLink'] ?? '');
        $folderId = $this->db->escape($driveData['folderId'] ?? '');
        
        $sql = "UPDATE candidatos SET
                    drive_file_id = '$fileId',
                    drive_view_link = '$webViewLink',
                    drive_folder_id = '$folderId',
                    updated_at = NOW()
                WHERE id = $id";
        
        return $this->db->query($sql);
    }
    
    /**
     * Actualizar datos de un candidato
     */
    public function updateCandidate($id, $data) {
        $id = (int)$id;
        
        // Validar datos requeridos
        if (empty($data['nombre']) || empty($data['apellido']) || empty($data['email'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Verificar si el email ya existe para otro candidato
        $email = $this->db->escape($data['email']);
        $checkSql = "SELECT id FROM candidatos WHERE email = '$email' AND id != $id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'El email ya está en uso por otro candidato'
            ];
        }
        
        // Construir la consulta SQL dinámicamente
        $updates = [];
        
        // Campos conocidos que podrían venir en $data
        $posibles_campos = [
            'nombre', 'apellido', 'email', 'telefono', 'ubicacion', 'resumen',
            'cv_path', 'foto_path', 'linkedin', 'portfolio', 'user_id',
            'password', 'nivel_educativo', 'fecha_nacimiento', 'genero',
            'areas_interes', 'habilidades_destacadas', 'experiencia_general',
            'salario_esperado', 'modalidad_preferida', 'tipo_contrato_preferido',
            'disponibilidad', 'evaluaciones_pendientes', 'recibir_notificaciones',
            'disponibilidad_viajar', 'ubicacion_preferida', 'resumen_profesional'
        ];
        
        // Procesar cada campo posible
        foreach ($posibles_campos as $campo) {
            if (isset($data[$campo])) {
                // Tratar el valor según su tipo
                if ($campo === 'user_id' || $campo === 'evaluaciones_pendientes' || $campo === 'recibir_notificaciones') {
                    // Campos enteros
                    $updates[] = "$campo = " . (!empty($data[$campo]) ? (int)$data[$campo] : 'NULL');
                } elseif ($campo === 'fecha_nacimiento') {
                    // Fechas
                    $updates[] = "$campo = " . (!empty($data[$campo]) ? "'" . $this->db->escape($data[$campo]) . "'" : 'NULL');
                } else {
                    // Cadenas de texto
                    $updates[] = "$campo = '" . $this->db->escape($data[$campo]) . "'";
                }
            }
        }
        
        // Añadir campo de actualización
        $updates[] = "updated_at = NOW()";
        
        // Construir la consulta SQL
        $updates_str = implode(', ', $updates);
        
        $sql = "UPDATE candidatos SET $updates_str WHERE id = $id";
        
        // Ejecutar la consulta
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Candidato actualizado con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar el candidato: ' . $this->db->getConnection()->error
            ];
        }
    }
	
		/**
	 * Agregar nota a un candidato
	 * 
	 * @param int $candidatoId ID del candidato
	 * @param string $titulo Título de la nota
	 * @param string $contenido Contenido de la nota
	 * @return array Resultado de la operación
	 */
	public function addCandidateNote($candidatoId, $titulo, $contenido) {
		$candidatoId = (int)$candidatoId;
		$titulo = $this->db->escape($titulo);
		$contenido = $this->db->escape($contenido);
		$usuario_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
		
		$sql = "INSERT INTO candidato_notas (
					candidato_id, titulo, contenido, usuario_id, created_at, updated_at
				) VALUES (
					$candidatoId, '$titulo', '$contenido', $usuario_id, NOW(), NOW()
				)";
		
		if ($this->db->query($sql)) {
			return [
				'success' => true,
				'message' => 'Nota agregada con éxito',
				'id' => $this->db->lastInsertId()
			];
		} else {
			return [
				'success' => false,
				'message' => 'Error al agregar nota: ' . $this->db->getConnection()->error
			];
		}
	}


	/** Obtener notas de un candidato
	 * 
	 * @param int $candidatoId ID del candidato
	 * @return array Notas del candidato
	 */
	public function getCandidateNotes($candidatoId) {
		$candidatoId = (int)$candidatoId;
		
		// Modificar la consulta para eliminar el JOIN con usuarios
		$sql = "SELECT cn.*
				FROM candidato_notas cn
				WHERE cn.candidato_id = $candidatoId
				ORDER BY cn.created_at DESC";
		
		$result = $this->db->query($sql);
		$notes = [];
		
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$notes[] = $row;
			}
		}
		
		return $notes;
	}
	
	/**
 * Obtener una nota específica por su ID
 * 
 * @param int $notaId ID de la nota
 * @return array|null Datos de la nota o null si no existe
 */
public function getNoteById($notaId) {
    $notaId = (int)$notaId;
    
    $sql = "SELECT * FROM candidato_notas WHERE id = $notaId";
    $result = $this->db->query($sql);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

/**
 * Editar una nota de candidato
 * 
 * @param int $notaId ID de la nota
 * @param array $data Datos actualizados
 * @return array Resultado de la operación
 */
public function editCandidateNote($notaId, $data) {
    // Validar datos requeridos
    if (empty($notaId) || (empty($data['titulo']) && empty($data['contenido']))) {
        return [
            'success' => false,
            'message' => 'Faltan campos obligatorios'
        ];
    }
    
    // Preparar datos
    $notaId = (int)$notaId;
    $updates = [];
    
    if (!empty($data['titulo'])) {
        $titulo = $this->db->escape($data['titulo']);
        $updates[] = "titulo = '$titulo'";
    }
    
    if (isset($data['contenido'])) {
        $contenido = $this->db->escape($data['contenido']);
        $updates[] = "contenido = '$contenido'";
    }
    
    $updates[] = "updated_at = NOW()";
    
    // Consulta SQL
    $sql = "UPDATE candidato_notas SET " . implode(", ", $updates) . " WHERE id = $notaId";
    
    if ($this->db->query($sql)) {
        return ['success' => true,
            'message' => 'Nota actualizada con éxito',
            'id' => $notaId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al actualizar nota: ' . $this->db->getConnection()->error
        ];
    }
}

/**
 * Eliminar una nota de candidato
 * 
 * @param int $notaId ID de la nota
 * @return array Resultado de la operación
 */
public function deleteCandidateNote($notaId) {
    $notaId = (int)$notaId;
    
    $sql = "DELETE FROM candidato_notas WHERE id = $notaId";
    
    if ($this->db->query($sql)) {
        return [
            'success' => true,
            'message' => 'Nota eliminada con éxito'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al eliminar nota: ' . $this->db->getConnection()->error
        ];
    }
}

/**
 * Busca un candidato por su dirección de email
 * 
 * @param string $email Email del candidato a buscar
 * @return array Información sobre el resultado de la búsqueda
 */
public function findCandidateByEmail($email) {
    $email = $this->db->escape($email);
    
    $sql = "SELECT * FROM candidatos WHERE email = '$email' LIMIT 1";
    $result = $this->db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return [
            'success' => true, 
            'exists' => true, 
            'candidate' => $result->fetch_assoc()
        ];
    } else {
        return [
            'success' => true, 
            'exists' => false
        ];
    }
}

}

/**
 * Clase para gestionar experiencia laboral de candidatos
 */
class ExperienceManager {
    private $db;
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }
    
    /**
     * Obtener experiencia laboral de un candidato
     */
    public function getCandidateExperience($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT * FROM experiencia_laboral 
                WHERE candidato_id = $candidateId 
                ORDER BY actual DESC, fecha_inicio DESC";
                
        $result = $this->db->query($sql);
        $experiences = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $experiences[] = $row;
            }
        }
        
        return $experiences;
    }
    
    /**
     * Agregar experiencia laboral
     */
    public function addExperience($data) {
        // Validar datos requeridos
        if (empty($data['candidato_id']) || empty($data['empresa']) || empty($data['cargo']) || empty($data['fecha_inicio'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $candidato_id = (int)$data['candidato_id'];
        $empresa = $this->db->escape($data['empresa']);
        $cargo = $this->db->escape($data['cargo']);
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $fecha_inicio = $this->db->escape($data['fecha_inicio']);
        $actual = !empty($data['actual']) ? 1 : 0;
        $fecha_fin = ($actual || empty($data['fecha_fin'])) ? 'NULL' : "'" . $this->db->escape($data['fecha_fin']) . "'";
        
        // Consulta SQL
        $sql = "INSERT INTO experiencia_laboral (
                    candidato_id, empresa, cargo, descripcion, fecha_inicio, 
                    fecha_fin, actual, created_at, updated_at
                ) VALUES (
                    $candidato_id, '$empresa', '$cargo', '$descripcion', '$fecha_inicio',
                    $fecha_fin, $actual, NOW(), NOW()
                )";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Experiencia laboral agregada con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al agregar experiencia laboral: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Actualizar experiencia laboral
     */
    public function updateExperience($id, $data) {
        // Validar datos requeridos
        if (empty($data['empresa']) || empty($data['cargo']) || empty($data['fecha_inicio'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        $id = (int)$id;
        $empresa = $this->db->escape($data['empresa']);
        $cargo = $this->db->escape($data['cargo']);
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $fecha_inicio = $this->db->escape($data['fecha_inicio']);
        $actual = !empty($data['actual']) ? 1 : 0;
        $fecha_fin = ($actual || empty($data['fecha_fin'])) ? 'NULL' : "'" . $this->db->escape($data['fecha_fin']) . "'";
        
        $sql = "UPDATE experiencia_laboral SET 
                    empresa = '$empresa',
                    cargo = '$cargo',
                    descripcion = '$descripcion',
                    fecha_inicio = '$fecha_inicio',
                    fecha_fin = $fecha_fin,
                    actual = $actual,
                    updated_at = NOW()
                WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Experiencia laboral actualizada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar experiencia laboral: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Eliminar experiencia laboral
     */
    public function deleteExperience($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM experiencia_laboral WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Experiencia laboral eliminada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar experiencia laboral: ' . $this->db->getConnection()->error
            ];
        }
    }
}

/**
 * Clase para gestionar educación de candidatos
 */
class EducationManager {
    private $db;
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }
    
    /**
     * Obtener educación de un candidato
     */
    public function getCandidateEducation($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT * FROM educacion 
                WHERE candidato_id = $candidateId 
                ORDER BY actual DESC, fecha_inicio DESC";
                
        $result = $this->db->query($sql);
        $education = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $education[] = $row;
            }
        }
        
        return $education;
    }
    
    /**
     * Agregar educación
     */
    public function addEducation($data) {
        // Validar datos requeridos
        if (empty($data['candidato_id']) || empty($data['institucion']) || empty($data['titulo']) || empty($data['fecha_inicio'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        // Preparar datos
        $candidato_id = (int)$data['candidato_id'];
        $institucion = $this->db->escape($data['institucion']);
        $titulo = $this->db->escape($data['titulo']);
        $campo_estudio = $this->db->escape($data['campo_estudio'] ?? '');
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $fecha_inicio = $this->db->escape($data['fecha_inicio']);
        $actual = !empty($data['actual']) ? 1 : 0;
        $fecha_fin = ($actual || empty($data['fecha_fin'])) ? 'NULL' : "'" . $this->db->escape($data['fecha_fin']) . "'";
        
        // Consulta SQL
        $sql = "INSERT INTO educacion (
                    candidato_id, institucion, titulo, campo_estudio, descripcion,
                    fecha_inicio, fecha_fin, actual, created_at, updated_at
                ) VALUES (
                    $candidato_id, '$institucion', '$titulo', '$campo_estudio', '$descripcion',
                    '$fecha_inicio', $fecha_fin, $actual, NOW(), NOW()
                )";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Educación agregada con éxito',
                'id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al agregar educación: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Actualizar educación
     */
    public function updateEducation($id, $data) {
        // Validar datos requeridos
        if (empty($data['institucion']) || empty($data['titulo']) || empty($data['fecha_inicio'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        $id = (int)$id;
        $institucion = $this->db->escape($data['institucion']);
        $titulo = $this->db->escape($data['titulo']);
        $campo_estudio = $this->db->escape($data['campo_estudio'] ?? '');
        $descripcion = $this->db->escape($data['descripcion'] ?? '');
        $fecha_inicio = $this->db->escape($data['fecha_inicio']);
        $actual = !empty($data['actual']) ? 1 : 0;
        $fecha_fin = ($actual || empty($data['fecha_fin'])) ? 'NULL' : "'" . $this->db->escape($data['fecha_fin']) . "'";
        
        $sql = "UPDATE educacion SET 
                    institucion = '$institucion',
                    titulo = '$titulo',
                    campo_estudio = '$campo_estudio',
                    descripcion = '$descripcion',
                    fecha_inicio = '$fecha_inicio',
                    fecha_fin = $fecha_fin,
                    actual = $actual,
                    updated_at = NOW()
                WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Educación actualizada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar educación: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Eliminar educación
     */
    public function deleteEducation($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM educacion WHERE id = $id";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Educación eliminada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar educación: ' . $this->db->getConnection()->error
            ];
        }
    }
}

/**
 * Clase para gestionar habilidades de candidatos
 */
class SkillManager {
    private $db;
    
    public function __construct() {
        // Intentar usar la clase Database existente, si no, usar VacanciesDatabase
        if (class_exists('Database')) {
            $this->db = Database::getInstance();
        } else {
            $this->db = VacanciesDatabase::getInstance();
        }
    }
    
    /**
     * Obtener todas las habilidades
     */
    public function getAllSkills() {
        $sql = "SELECT * FROM habilidades ORDER BY nombre ASC";
        $result = $this->db->query($sql);
        $skills = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $skills[] = $row;
            }
        }
        
        return $skills;
    }
    
    /**
     * Obtener habilidades de un candidato
     */
    public function getCandidateSkills($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT ch.*, h.nombre, h.tipo
                FROM candidato_habilidades ch
                JOIN habilidades h ON ch.habilidad_id = h.id
                WHERE ch.candidato_id = $candidateId
                ORDER BY h.tipo, h.nombre";
                
        $result = $this->db->query($sql);
        $skills = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $skills[] = $row;
            }
        }
        
        return $skills;
    }
    
    /**
     * Agregar habilidad a un candidato
     */
    public function addCandidateSkill($data) {
        // Validar datos requeridos
        if (empty($data['candidato_id']) || empty($data['habilidad_id'])) {
            return [
                'success' => false,
                'message' => 'Faltan campos obligatorios'
            ];
        }
        
        $candidato_id = (int)$data['candidato_id'];
        $habilidad_id = (int)$data['habilidad_id'];
        $nivel = $this->db->escape($data['nivel'] ?? 'intermedio');
        
        // Verificar si ya existe esta habilidad para el candidato
        $checkSql = "SELECT id FROM candidato_habilidades 
                     WHERE candidato_id = $candidato_id AND habilidad_id = $habilidad_id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Actualizar nivel si ya existe
            $sql = "UPDATE candidato_habilidades 
                    SET nivel = '$nivel', updated_at = NOW()
                    WHERE candidato_id = $candidato_id AND habilidad_id = $habilidad_id";
        } else {
            // Insertar nueva relación
            $sql = "INSERT INTO candidato_habilidades (
                        candidato_id, habilidad_id, nivel, created_at, updated_at
                    ) VALUES (
                        $candidato_id, $habilidad_id, '$nivel', NOW(), NOW()
                    )";
        }
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Habilidad agregada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al agregar habilidad: ' . $this->db->getConnection()->error
            ];
        }
    }
    
    /**
     * Eliminar habilidad de un candidato
     */
    public function deleteCandidateSkill($candidatoId, $habilidadId) {
        $candidatoId = (int)$candidatoId;
        $habilidadId = (int)$habilidadId;
        
        $sql = "DELETE FROM candidato_habilidades 
                WHERE candidato_id = $candidatoId AND habilidad_id = $habilidadId";
        
        if ($this->db->query($sql)) {
            return [
                'success' => true,
                'message' => 'Habilidad eliminada con éxito'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar habilidad: ' . $this->db->getConnection()->error
            ];
        }
    }
}

/**
 * Funciones de utilidad para el sistema de vacantes
 */
class VacancyUtils {
    /**
     * Formatear fecha para mostrar
     */
    public static function formatDate($date, $format = 'd/m/Y') {
        return date($format, strtotime($date));
    }
    
    /**
     * Obtener estado de vacante formateado
     */
    public static function getStatusBadge($status) {
        $statuses = [
            'borrador' => '<span class="badge bg-secondary">Borrador</span>',
            'publicada' => '<span class="badge bg-success">Publicada</span>',
            'cerrada' => '<span class="badge bg-danger">Cerrada</span>'
        ];
        
        return $statuses[$status] ?? '<span class="badge bg-secondary">Desconocido</span>';
    }
    
    /**
     * Obtener estado de aplicación formateado
     */
    public static function getApplicationStatusBadge($status) {
        $statuses = [
            'recibida' => '<span class="badge bg-info">Recibida</span>',
            'revision' => '<span class="badge bg-primary">En Revisión</span>',
            'entrevista' => '<span class="badge bg-warning text-dark">Entrevista</span>',
            'prueba' => '<span class="badge bg-warning text-dark">Prueba</span>',
            'oferta' => '<span class="badge bg-warning text-dark">Oferta</span>',
            'contratado' => '<span class="badge bg-success">Contratado</span>',
            'rechazado' => '<span class="badge bg-danger">Rechazado</span>'
        ];
        
        return $statuses[$status] ?? '<span class="badge bg-secondary">Desconocido</span>';
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
 * Agregar etapa a una aplicación
 */
public function addApplicationStage($data) {
    // Validar datos requeridos
    if (empty($data['aplicacion_id']) || empty($data['etapa'])) {
        return [
            'success' => false,
            'message' => 'Faltan campos obligatorios'
        ];
    }
    
    // Preparar datos
    $aplicacion_id = (int)$data['aplicacion_id'];
    $etapa = $this->db->escape($data['etapa']);
    $notas = $this->db->escape($data['notas'] ?? '');
    $estado = $this->db->escape($data['estado'] ?? 'completada');
    $fecha = !empty($data['fecha']) ? "'" . $this->db->escape($data['fecha']) . "'" : 'NOW()';
    
    // QUITAR usuario_id de la consulta SQL
    $sql = "INSERT INTO etapas_proceso (
                aplicacion_id, etapa, notas, estado, fecha, created_at, updated_at
            ) VALUES (
                $aplicacion_id, '$etapa', '$notas', '$estado', $fecha, NOW(), NOW()
            )";
    
    if ($this->db->query($sql)) {
        return [
            'success' => true,
            'message' => 'Etapa agregada con éxito',
            'id' => $this->db->lastInsertId()
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al agregar etapa: ' . $this->db->getConnection()->error
        ];
    }
}


}
?>