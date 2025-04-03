<?php
/**
 * Sistema Principal de Vacantes SolFis
 * 
 * Este archivo contiene todas las clases necesarias para el funcionamiento
 * del sistema de vacantes, incluyendo modelos para todas las entidades.
 */

// Clase para gestionar la base de datos (usando la misma conexión del sistema)
// Si ya existe una clase Database en blog-system.php, puedes comentar esta
// Clase para gestionar la base de datos (usando la misma conexión del sistema)
class VacanciesDatabase {
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

/**
 * Clase para gestionar vacantes
 */
class VacancyManager {
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
            $categoria = $this->db->escape($filters['categoria']);
            $sql .= " AND v.categoria_id = '$categoria'";
        }
        
        if (!empty($filters['busqueda'])) {
            $busqueda = $this->db->escape($filters['busqueda']);
            $sql .= " AND (v.titulo LIKE '%$busqueda%' OR v.descripcion LIKE '%$busqueda%')";
        }
        
        // Ordenar
        $sql .= " ORDER BY v.fecha_publicacion DESC LIMIT $offset, $per_page";
        
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
                $categoria = $this->db->escape($filters['categoria']);
                $countSql .= " AND v.categoria_id = '$categoria'";
            }
            
            if (!empty($filters['busqueda'])) {
                $busqueda = $this->db->escape($filters['busqueda']);
                $countSql .= " AND (v.titulo LIKE '%$busqueda%' OR v.descripcion LIKE '%$busqueda%')";
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
        $categoria_id = (int)($data['categoria']);
        $ubicacion = $this->db->escape($data['ubicacion'] ?? '');
        $modalidad = $this->db->escape($data['modalidad'] ?? 'presencial');
        $tipo_contrato = $this->db->escape($data['tipo_contrato'] ?? 'tiempo_completo');
        $experiencia = $this->db->escape($data['experiencia'] ?? '');
        $salario_min = !empty($data['salario_min']) ? (float)$data['salario_min'] : 0;
        $salario_max = !empty($data['salario_max']) ? (float)$data['salario_max'] : 0;
        $mostrar_salario = !empty($data['mostrar_salario']) ? 1 : 0;
        $estado = $this->db->escape($data['estado'] ?? 'borrador');
        $destacada = !empty($data['destacada']) ? 1 : 0;
        
        // Fechas
        $fecha_publicacion = !empty($data['fecha_publicacion']) ? "'" . $this->db->escape($data['fecha_publicacion']) . "'" : 'NULL';
        $fecha_cierre = !empty($data['fecha_cierre']) ? "'" . $this->db->escape($data['fecha_cierre']) . "'" : 'NULL';
        
        // Consulta SQL
        $sql = "INSERT INTO vacantes (
                    titulo, slug, descripcion, requisitos, responsabilidades, beneficios, 
                    categoria_id, ubicacion, modalidad, tipo_contrato, experiencia,
                    salario_min, salario_max, mostrar_salario, estado, destacada,
                    fecha_publicacion, fecha_cierre, created_at, updated_at
                ) VALUES (
                    '$titulo', '$slug', '$descripcion', '$requisitos', '$responsabilidades', '$beneficios',
                    $categoria_id, '$ubicacion', '$modalidad', '$tipo_contrato', '$experiencia',
                    $salario_min, $salario_max, $mostrar_salario, '$estado', $destacada,
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
    $categoria_id = (int)($data['categoria']);
    $ubicacion = $this->db->escape($data['ubicacion'] ?? '');
    $modalidad = $this->db->escape($data['modalidad'] ?? 'presencial');
    $tipo_contrato = $this->db->escape($data['tipo_contrato'] ?? 'tiempo_completo');
    $experiencia = $this->db->escape($data['experiencia'] ?? '');
    $salario_min = !empty($data['salario_min']) ? (float)$data['salario_min'] : 0;
    $salario_max = !empty($data['salario_max']) ? (float)$data['salario_max'] : 0;
    $mostrar_salario = !empty($data['mostrar_salario']) ? 1 : 0;
    $estado = $this->db->escape($data['estado'] ?? 'borrador');
    $destacada = !empty($data['destacada']) ? 1 : 0;
    
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
	 * Busca un candidato por su dirección de email
	 * 
	 * @param string $email Email del candidato a buscar
	 * @return array Información sobre el resultado de la búsqueda
	 */
	public function findCandidateByEmail($email) {
		try {
			$stmt = $this->db->prepare('SELECT * FROM candidatos WHERE email = :email LIMIT 1');
			$stmt->bindParam(':email', $email);
			$stmt->execute();
			
			$candidate = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if ($candidate) {
				return [
					'success' => true, 
					'exists' => true, 
					'candidate' => $candidate
				];
			} else {
				return [
					'success' => true, 
					'exists' => false
				];
			}
		} catch (PDOException $e) {
			return [
				'success' => false, 
				'message' => 'Error al buscar candidato: ' . $e->getMessage()
			];
		}
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
					vacante_id, candidato_id, estado, notas, fecha_aplicacion, created_at, updated_at
				) VALUES (
					$vacante_id, $candidato_id, '$estado', '$notas', NOW(), NOW(), NOW()
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
}

/**
 * Clase para gestionar categorías de vacantes
 */
class CategoryManager {
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
    
    // Implementar más métodos según sea necesario
}

/**
 * Clase para gestionar aplicaciones a vacantes
 */
class ApplicationManager {
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
     * Obtener todas las aplicaciones con paginación y filtros opcionales
     */
    public function getApplications($page = 1, $per_page = 10, $filters = []) {
        // Implementar la funcionalidad
        // Similar a getVacancies pero para la tabla aplicaciones
        
        // Por ahora devolvemos datos simulados
        return [
            'applications' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
    
    // Implementar más métodos según sea necesario
}

/**
     * Clase para gestionar candidatos
     */
    class CandidateManager {
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
         * Crear un nuevo candidato
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
            
            // Preparar datos
            $nombre = $this->db->escape($data['nombre']);
            $apellido = $this->db->escape($data['apellido']);
            $telefono = $this->db->escape($data['telefono'] ?? '');
            $ubicacion = $this->db->escape($data['ubicacion'] ?? '');
            $resumen = $this->db->escape($data['resumen'] ?? '');
            $cv_path = $this->db->escape($data['cv_path'] ?? '');
            $linkedin = $this->db->escape($data['linkedin'] ?? '');
            $portfolio = $this->db->escape($data['portfolio'] ?? '');
            $user_id = !empty($data['user_id']) ? (int)$data['user_id'] : 'NULL';
            
            // Consulta SQL
            $sql = "INSERT INTO candidatos (
                        nombre, apellido, email, telefono, ubicacion, resumen,
                        cv_path, linkedin, portfolio, user_id, created_at, updated_at
                    ) VALUES (
                        '$nombre', '$apellido', '$email', '$telefono', '$ubicacion', '$resumen',
                        '$cv_path', '$linkedin', '$portfolio', $user_id, NOW(), NOW()
                    )";
            
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
        
        // Más métodos según sea necesario
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
        
        // Más métodos según sea necesario
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
        
        // Más métodos según sea necesario
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
    }
?>