<?php
/**
 * Clase para gestionar todas las operaciones relacionadas con pruebas psicométricas
 */
class TestManager {
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
     * Obtiene todas las pruebas disponibles
     */
    public function getAllTests() {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono
                FROM pruebas p
                JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE p.estado = 'activa'
                ORDER BY c.orden ASC, p.titulo ASC";
        
        $result = $this->db->query($sql);
        $tests = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tests[] = $row;
            }
        }
        
        return $tests;
    }
    
    /**
     * Obtiene las pruebas por categoría
     */
    public function getTestsByCategory($categoria_id) {
        $categoria_id = (int)$categoria_id;
        
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono
                FROM pruebas p
                JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE p.categoria_id = $categoria_id AND p.estado = 'activa'
                ORDER BY p.titulo ASC";
        
        $result = $this->db->query($sql);
        $tests = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tests[] = $row;
            }
        }
        
        return $tests;
    }
    
    /**
     * Obtiene las categorías de pruebas
     */
    public function getTestCategories() {
        $sql = "SELECT c.*, COUNT(p.id) as pruebas_count
                FROM pruebas_categorias c
                LEFT JOIN pruebas p ON c.id = p.categoria_id AND p.estado = 'activa'
                GROUP BY c.id
                ORDER BY c.orden ASC";
                
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
     * Obtiene una prueba por su ID
     */
    public function getTestById($id) {
        $id = (int)$id;
        
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono
                FROM pruebas p
                JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE p.id = $id";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Obtiene las preguntas de una prueba
     */
    public function getTestQuestions($test_id) {
        $test_id = (int)$test_id;
        
        $sql = "SELECT *
                FROM preguntas
                WHERE prueba_id = $test_id AND activa = 1
                ORDER BY orden ASC";
                
        $result = $this->db->query($sql);
        $questions = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $questions[] = $row;
            }
        }
        
        return $questions;
    }
    
    /**
     * Obtiene las opciones de respuesta para una pregunta
     */
    public function getQuestionOptions($question_id) {
        $question_id = (int)$question_id;
        
        $sql = "SELECT *
                FROM opciones_respuesta
                WHERE pregunta_id = $question_id
                ORDER BY orden ASC";
                
        $result = $this->db->query($sql);
        $options = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $options[] = $row;
            }
        }
        
        return $options;
    }
    
    /**
     * Verifica si existe una sesión de prueba para un candidato y prueba específica
     */
    public function checkExistingSession($candidato_id, $prueba_id) {
        $candidato_id = (int)$candidato_id;
        $prueba_id = (int)$prueba_id;
        
        $sql = "SELECT *
                FROM sesiones_prueba
                WHERE candidato_id = $candidato_id AND prueba_id = $prueba_id
                ORDER BY id DESC
                LIMIT 1";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Crea una nueva sesión de prueba
     */
    public function createSession($candidato_id, $prueba_id) {
        $candidato_id = (int)$candidato_id;
        $prueba_id = (int)$prueba_id;
        
        // Obtener información del navegador y dispositivo
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Detectar dispositivo básico
        $dispositivo = 'Desconocido';
        if (strpos($user_agent, 'Mobile') !== false || strpos($user_agent, 'Android') !== false) {
            $dispositivo = 'Móvil';
        } elseif (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
            $dispositivo = 'Tablet';
        } else {
            $dispositivo = 'Desktop';
        }
        
        $sql = "INSERT INTO sesiones_prueba (
                    candidato_id, prueba_id, fecha_inicio, estado, ip, navegador, dispositivo
                ) VALUES (
                    $candidato_id, $prueba_id, NOW(), 'en_progreso', '$ip', '" . $this->db->escape($user_agent) . "', '$dispositivo'
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return null;
    }
    
    /**
     * Obtiene una sesión de prueba por su ID
     */
    public function getSessionById($id) {
        $id = (int)$id;
        
        $sql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                WHERE s.id = $id";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Obtiene el número de la pregunta actual en una sesión
     */
    public function getCurrentQuestionNumber($session_id) {
        $session_id = (int)$session_id;
        
        // Contar respuestas ya guardadas
        $sql = "SELECT COUNT(*) as total
                FROM respuestas
                WHERE sesion_id = $session_id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['total'];
        }
        
        return 0;
    }
    
    /**
     * Guarda una respuesta a una pregunta
     */
    public function saveAnswer($session_id, $question_id, $respuesta) {
        $session_id = (int)$session_id;
        $question_id = (int)$question_id;
        
        // Verificar el tipo de pregunta
        $sql = "SELECT tipo FROM preguntas WHERE id = $question_id";
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        $tipo = $result->fetch_assoc()['tipo'];
        
        // Preparar los datos según el tipo de pregunta
        $opcion_id = 'NULL';
        $texto_respuesta = 'NULL';
        $valor_escala = 'NULL';
        
        switch ($tipo) {
            case 'opcion_multiple':
                $opcion_id = (int)$respuesta;
                break;
                
            case 'verdadero_falso':
                $valor_escala = (int)$respuesta;
                break;
                
            case 'escala_likert':
                $valor_escala = (int)$respuesta;
                break;
                
            case 'respuesta_abierta':
                $texto_respuesta = "'" . $this->db->escape($respuesta) . "'";
                break;
        }
        
        // Verificar si ya existe una respuesta para esta pregunta
        $checkSql = "SELECT id FROM respuestas 
                     WHERE sesion_id = $session_id AND pregunta_id = $question_id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Actualizar respuesta existente
            $sql = "UPDATE respuestas 
                    SET opcion_id = $opcion_id,
                        texto_respuesta = $texto_respuesta,
                        valor_escala = $valor_escala,
                        tiempo_respuesta = TIMESTAMPDIFF(SECOND, 
                            (SELECT fecha_inicio FROM sesiones_prueba WHERE id = $session_id), 
                            NOW())
                    WHERE sesion_id = $session_id AND pregunta_id = $question_id";
        } else {
            // Insertar nueva respuesta
            $sql = "INSERT INTO respuestas (
                        sesion_id, pregunta_id, opcion_id, texto_respuesta, valor_escala, tiempo_respuesta
                    ) VALUES (
                        $session_id, $question_id, $opcion_id, $texto_respuesta, $valor_escala,
                        TIMESTAMPDIFF(SECOND, 
                            (SELECT fecha_inicio FROM sesiones_prueba WHERE id = $session_id), 
                            NOW())
                    )";
        }
        
        return $this->db->query($sql);
    }
    
    /**
     * Obtiene una respuesta para una pregunta en una sesión
     */
    public function getAnswer($session_id, $question_id) {
        $session_id = (int)$session_id;
        $question_id = (int)$question_id;
        
        $sql = "SELECT *
                FROM respuestas
                WHERE sesion_id = $session_id AND pregunta_id = $question_id";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Marca una sesión como completada y procesa los resultados
     */
    public function completeSession($session_id) {
        try {
            $session_id = (int)$session_id;
            
            // Actualizar estado de la sesión
            $sql = "UPDATE sesiones_prueba 
                    SET estado = 'completada', fecha_fin = NOW() 
                    WHERE id = $session_id";
                    
            $this->db->query($sql);
            
            // Procesar resultados
            $this->processResults($session_id);
            
            return true;
        } catch (Exception $e) {
            error_log("Error al completar sesión $session_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesa los resultados de una sesión de prueba
     * Versión corregida para evitar errores SQL
     */
    private function processResults($session_id) {
        $session_id = (int)$session_id;
        
        try {
            // Obtener datos de la sesión
            $sessionSql = "SELECT prueba_id, candidato_id FROM sesiones_prueba WHERE id = $session_id";
            $sessionResult = $this->db->query($sessionSql);
            
            if (!$sessionResult || $sessionResult->num_rows === 0) {
                error_log("No se encontró la sesión $session_id");
                return false;
            }
            
            $session = $sessionResult->fetch_assoc();
            $prueba_id = $session['prueba_id'];
            $candidato_id = $session['candidato_id'];
            
            // Verificar si existe la tabla dimensiones
            $checkDimensionesTable = "SHOW TABLES LIKE 'dimensiones'";
            $dimensionesTableExist = $this->db->query($checkDimensionesTable);
            
            if (!$dimensionesTableExist || $dimensionesTableExist->num_rows === 0) {
                // Si la tabla no existe, crearla
                $this->createDimensionesTable();
            }
            
            // Verificar si existe la tabla pruebas_dimensiones
            $checkPDTable = "SHOW TABLES LIKE 'pruebas_dimensiones'";
            $pdTableExist = $this->db->query($checkPDTable);
            
            if (!$pdTableExist || $pdTableExist->num_rows === 0) {
                // Si la tabla no existe, crearla
                $this->createPruebasDimensionesTable();
            }
            
            // Obtener dimensiones evaluadas en la prueba
            $dimensionsSql = "SELECT d.id, d.nombre, d.descripcion, d.valor_min, d.valor_max
                              FROM dimensiones d
                              JOIN pruebas_dimensiones pd ON d.id = pd.dimension_id
                              WHERE pd.prueba_id = $prueba_id";
            $dimensionsResult = $this->db->query($dimensionsSql);
            
            if (!$dimensionsResult || $dimensionsResult->num_rows === 0) {
                // Si no hay dimensiones, crear una general y asociarla
                $defaultDimensionId = $this->createDefaultDimension($prueba_id);
                
                // Obtener de nuevo con la dimensión general
                $dimensionsSql = "SELECT d.id, d.nombre, d.descripcion, d.valor_min, d.valor_max
                                  FROM dimensiones d
                                  JOIN pruebas_dimensiones pd ON d.id = pd.dimension_id
                                  WHERE pd.prueba_id = $prueba_id";
                $dimensionsResult = $this->db->query($dimensionsSql);
            }
            
            if (!$dimensionsResult) {
                error_log("No se encontraron dimensiones para la prueba $prueba_id");
                return false;
            }
            
            $dimensiones = [];
            while ($row = $dimensionsResult->fetch_assoc()) {
                $dimensiones[$row['id']] = $row;
            }
            
            // Para cada dimensión, calcular el resultado
            foreach ($dimensiones as $dimension_id => $dimension) {
                // CORREGIDO: Obtener respuestas que afectan a esta dimensión sin usar p.dimension_id
                $answersSql = "SELECT r.*, o.valor, o.dimension_id
                               FROM respuestas r
                               JOIN preguntas p ON r.pregunta_id = p.id
                               LEFT JOIN opciones_respuesta o ON r.opcion_id = o.id
                               WHERE r.sesion_id = $session_id";
                               
                // Verificar si existe la columna dimension_id en la tabla opciones_respuesta
                $checkColumnSql = "SHOW COLUMNS FROM opciones_respuesta LIKE 'dimension_id'";
                $columnResult = $this->db->query($checkColumnSql);
                
                if ($columnResult && $columnResult->num_rows > 0) {
                    // Si existe, filtrar por dimension_id
                    $answersSql .= " AND (o.dimension_id = $dimension_id OR o.dimension_id IS NULL)";
                }
                
                $answersResult = $this->db->query($answersSql);
                
                if (!$answersResult) {
                    continue;
                }
                
                // Calcular valor promedio
                $total = 0;
                $count = 0;
                
                while ($answer = $answersResult->fetch_assoc()) {
                    // Determinar el valor según el tipo de respuesta
                    $valor = 0;
                    
                    if (isset($answer['opcion_id']) && $answer['opcion_id']) {
                        // Respuesta de opción múltiple
                        $valor = isset($answer['valor']) ? $answer['valor'] : 1;
                    } else if (isset($answer['valor_escala']) && $answer['valor_escala'] !== null) {
                        // Respuesta de escala
                        $valor = $answer['valor_escala'];
                    }
                    
                    $total += $valor;
                    $count++;
                }
                
                if ($count === 0) {
                    continue;
                }
                
                // Calcular promedio y normalizar a escala 0-100
                $promedio = $total / $count;
                $valor_normalizado = $this->normalizeValue($promedio, $dimension);
                
                // Buscar interpretación para este valor
                $interpretacion = $this->getInterpretation($dimension_id, $valor_normalizado);
                
                // Insertar resultado
                $this->saveResult($session_id, $dimension_id, $valor_normalizado, $interpretacion);
            }
            
            // Calcular y guardar resultado global
            $this->calculateGlobalResult($session_id);
            
            return true;
        } catch (Exception $e) {
            error_log("Error procesando resultados para sesión $session_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear tabla dimensiones si no existe
     */
    private function createDimensionesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS dimensiones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(100) NOT NULL,
                    descripcion TEXT NULL,
                    valor_min FLOAT NOT NULL DEFAULT 0,
                    valor_max FLOAT NOT NULL DEFAULT 100,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
        
        $this->db->query($sql);
    }
    
    /**
     * Crear tabla pruebas_dimensiones si no existe
     */
    private function createPruebasDimensionesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS pruebas_dimensiones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    prueba_id INT NOT NULL,
                    dimension_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_prueba_id (prueba_id),
                    INDEX idx_dimension_id (dimension_id)
                )";
        
        $this->db->query($sql);
    }
    
    /**
     * Crear dimensión general por defecto y asociarla a la prueba
     */
    private function createDefaultDimension($prueba_id) {
        // Verificar si ya existe una dimensión general
        $checkSql = "SELECT id FROM dimensiones WHERE nombre = 'General' LIMIT 1";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $dimension_id = $checkResult->fetch_assoc()['id'];
        } else {
            // Crear dimensión general
            $createSql = "INSERT INTO dimensiones (nombre, descripcion, valor_min, valor_max)
                          VALUES ('General', 'Dimensión general para evaluación', 0, 100)";
            $this->db->query($createSql);
            
            $dimension_id = $this->db->lastInsertId();
        }
        
        // Asociar dimensión a la prueba
        $associateSql = "INSERT INTO pruebas_dimensiones (prueba_id, dimension_id)
                         VALUES ($prueba_id, $dimension_id)";
        $this->db->query($associateSql);
        
        return $dimension_id;
    }
    
    /**
     * Normaliza un valor a escala 0-100
     */
    private function normalizeValue($value, $dimension) {
        // Obtener rangos min y max de la dimensión
        $min = isset($dimension['valor_min']) ? $dimension['valor_min'] : 0;
        $max = isset($dimension['valor_max']) ? $dimension['valor_max'] : 100;
        
        // Evitar división por cero
        if ($max - $min == 0) return 50;
        
        // Normalizar a escala 0-100
        $normalized = (($value - $min) / ($max - $min)) * 100;
        
        // Asegurar que está en el rango 0-100
        return max(0, min(100, $normalized));
    }
    
    /**
     * Obtiene la interpretación para un valor en una dimensión
     */
    private function getInterpretation($dimension_id, $value) {
        $dimension_id = (int)$dimension_id;
        
        // Verificar si existe la tabla interpretaciones
        $checkTableSql = "SHOW TABLES LIKE 'interpretaciones'";
        $tableResult = $this->db->query($checkTableSql);
        
        if (!$tableResult || $tableResult->num_rows === 0) {
            // Crear tabla si no existe
            $this->createInterpretacionesTable();
            
            // Crear interpretaciones básicas
            $this->createDefaultInterpretations($dimension_id);
        }
        
        $sql = "SELECT * 
                FROM interpretaciones
                WHERE dimension_id = $dimension_id
                AND rango_min <= $value AND rango_max >= $value";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Si no hay interpretaciones específicas, crear una básica y devolverla
        return $this->createBasicInterpretation($dimension_id, $value);
    }
    
    /**
     * Crear tabla interpretaciones si no existe
     */
    private function createInterpretacionesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS interpretaciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    dimension_id INT NOT NULL,
                    nivel VARCHAR(20) NOT NULL,
                    rango_min FLOAT NOT NULL,
                    rango_max FLOAT NOT NULL,
                    descripcion TEXT NOT NULL,
                    recomendacion TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_dimension_id (dimension_id)
                )";
        
        $this->db->query($sql);
    }
    
    /**
     * Crear interpretaciones por defecto para una dimensión
     */
    private function createDefaultInterpretations($dimension_id) {
        // Nivel bajo
        $sqlBajo = "INSERT INTO interpretaciones 
                    (dimension_id, nivel, rango_min, rango_max, descripcion, recomendacion)
                    VALUES 
                    ($dimension_id, 'bajo', 0, 33, 
                    'Nivel bajo en esta dimensión. Se identifican áreas de oportunidad significativas.', 
                    'Se recomienda trabajar en el desarrollo de esta área.')";
        
        // Nivel medio
        $sqlMedio = "INSERT INTO interpretaciones 
                    (dimension_id, nivel, rango_min, rango_max, descripcion, recomendacion)
                    VALUES 
                    ($dimension_id, 'medio', 34, 66, 
                    'Nivel medio en esta dimensión. Se muestra un desempeño adecuado con áreas de mejora.', 
                    'Se recomienda reforzar los aspectos específicos indicados en la evaluación.')";
        
        // Nivel alto
        $sqlAlto = "INSERT INTO interpretaciones 
                    (dimension_id, nivel, rango_min, rango_max, descripcion, recomendacion)
                    VALUES 
                    ($dimension_id, 'alto', 67, 100, 
                    'Nivel alto en esta dimensión. Se demuestra una notable competencia en esta área.', 
                    'Se recomienda mantener y compartir este conocimiento/habilidad.')";
        
        $this->db->query($sqlBajo);
        $this->db->query($sqlMedio);
        $this->db->query($sqlAlto);
    }
    
    /**
     * Crear una interpretación básica para un valor
     */
    private function createBasicInterpretation($dimension_id, $value) {
        // Determinar nivel según el valor
        $nivel = 'medio';
        $descripcion = 'Nivel medio en esta dimensión.';
        $recomendacion = 'Se recomienda evaluar aspectos específicos para identificar áreas de mejora.';
        
        if ($value <= 33) {
            $nivel = 'bajo';
            $descripcion = 'Nivel bajo en esta dimensión.';
            $recomendacion = 'Se recomienda fortalecer esta área.';
        } else if ($value >= 67) {
            $nivel = 'alto';
            $descripcion = 'Nivel alto en esta dimensión.';
            $recomendacion = 'Se recomienda aprovechar esta fortaleza.';
        }
        
        // Determinar rangos
        $rango_min = 0;
        $rango_max = 100;
        
        if ($nivel == 'bajo') {
            $rango_min = 0;
            $rango_max = 33;
        } else if ($nivel == 'medio') {
            $rango_min = 34;
            $rango_max = 66;
        } else {
            $rango_min = 67;
            $rango_max = 100;
        }
        
        // Insertar interpretación
        $sql = "INSERT INTO interpretaciones 
                (dimension_id, nivel, rango_min, rango_max, descripcion, recomendacion)
                VALUES 
                ($dimension_id, '$nivel', $rango_min, $rango_max, 
                '$descripcion', '$recomendacion')";
        
        $this->db->query($sql);
        
        // Devolver la interpretación creada
        return [
            'id' => $this->db->lastInsertId(),
            'dimension_id' => $dimension_id,
            'nivel' => $nivel,
            'rango_min' => $rango_min,
            'rango_max' => $rango_max,
            'descripcion' => $descripcion,
            'recomendacion' => $recomendacion
        ];
    }
    
    /**
     * Guarda un resultado de prueba
     */
    private function saveResult($session_id, $dimension_id, $value, $interpretacion) {
        $session_id = (int)$session_id;
        $dimension_id = (int)$dimension_id;
        $value = (float)$value;
        
        // Verificar si existe la tabla resultados
        $checkTableSql = "SHOW TABLES LIKE 'resultados'";
        $tableResult = $this->db->query($checkTableSql);
        
        if (!$tableResult || $tableResult->num_rows === 0) {
            // Crear tabla si no existe
            $this->createResultadosTable();
        }
        
        // Calcular percentil (simulado por ahora)
        $percentil = round($value);
        
        // Textos de interpretación
        $interpretacion_texto = 'NULL';
        $nivel = 'NULL';
        
        if ($interpretacion) {
            $interpretacion_texto = "'" . $this->db->escape($interpretacion['descripcion']) . "'";
            $nivel = "'" . $this->db->escape($interpretacion['nivel']) . "'";
        }
        
        // Verificar si ya existe un resultado para esta dimensión
        $checkSql = "SELECT id FROM resultados 
                     WHERE sesion_id = $session_id AND dimension_id = $dimension_id";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Actualizar resultado existente
            $sql = "UPDATE resultados 
                    SET valor = $value,
                        percentil = $percentil,
                        interpretacion = $interpretacion_texto
                    WHERE sesion_id = $session_id AND dimension_id = $dimension_id";
        } else {
            // Insertar nuevo resultado
            $sql = "INSERT INTO resultados (
                        sesion_id, dimension_id, valor, percentil, interpretacion
                    ) VALUES (
                        $session_id, $dimension_id, $value, $percentil, $interpretacion_texto
                    )";
        }
        
        return $this->db->query($sql);
    }
    
    /**
     * Crear tabla resultados si no existe
     */
    private function createResultadosTable() {
        $sql = "CREATE TABLE IF NOT EXISTS resultados (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sesion_id INT NOT NULL,
                    dimension_id INT NOT NULL,
                    valor FLOAT NOT NULL,
                    percentil INT NULL,
                    interpretacion TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_sesion_id (sesion_id),
                    INDEX idx_dimension_id (dimension_id)
                )";
        
        $this->db->query($sql);
    }
    
    /**
     * Calcular y guardar el resultado global de una sesión
     */
    private function calculateGlobalResult($session_id) {
        $session_id = (int)$session_id;
        
        // Obtener el promedio de los valores de las dimensiones
        $globalSql = "SELECT AVG(valor) as promedio FROM resultados WHERE sesion_id = $session_id";
        $globalResult = $this->db->query($globalSql);
        
        if ($globalResult && $globalResult->num_rows > 0) {
            $valorPromedio = $globalResult->fetch_assoc()['promedio'];
            $valorGlobal = round($valorPromedio);
            
            // Verificar si la columna resultado_global existe en la tabla sesiones_prueba
            $checkColumnSql = "SHOW COLUMNS FROM sesiones_prueba LIKE 'resultado_global'";
            $columnResult = $this->db->query($checkColumnSql);
            
            if (!$columnResult || $columnResult->num_rows === 0) {
                // Añadir la columna si no existe
                $addColumnSql = "ALTER TABLE sesiones_prueba ADD COLUMN resultado_global FLOAT NULL";
                $this->db->query($addColumnSql);
            }
            
            // Actualizar el resultado global en la sesión
            $updateSql = "UPDATE sesiones_prueba SET resultado_global = $valorGlobal WHERE id = $session_id";
            $this->db->query($updateSql);
            
            return $valorGlobal;
        }
        
        return null;
    }
    
    /**
     * Obtiene los resultados de una sesión de prueba
     */
    public function getSessionResults($session_id) {
        $session_id = (int)$session_id;
        
        try {
            // Obtener datos de la sesión
            $sessionSql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                                  c.nombre as categoria_nombre
                           FROM sesiones_prueba s
                           JOIN pruebas p ON s.prueba_id = p.id
                           LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
                           WHERE s.id = $session_id";
                           
            $sessionResult = $this->db->query($sessionSql);
            
            if (!$sessionResult || $sessionResult->num_rows === 0) {
                return null;
            }
            
            $session = $sessionResult->fetch_assoc();
            
            // Obtener resultados de las dimensiones
            $resultsSql = "SELECT r.*, d.nombre as dimension_nombre, d.descripcion as dimension_descripcion,
                                 i.descripcion as interpretacion, i.recomendacion, i.nivel
                          FROM resultados r
                          JOIN dimensiones d ON r.dimension_id = d.id
                          LEFT JOIN interpretaciones i ON d.id = i.dimension_id 
                              AND r.valor BETWEEN i.rango_min AND i.rango_max
                          WHERE r.sesion_id = $session_id
                          ORDER BY r.valor DESC";
                          
            $resultsResult = $this->db->query($resultsSql);
            $results = [];
            
            if ($resultsResult) {
                while ($row = $resultsResult->fetch_assoc()) {
                    $results[] = $row;
                }
            }
            
            // Calcular resultado global si no existe
            if (!isset($session['resultado_global']) || $session['resultado_global'] === null) {
                $session['resultado_global'] = $this->calculateGlobalResult($session_id);
            }
            
            // Obtener recomendaciones generales basadas en el nivel promedio
            $recomendaciones = '';
            $nivelesCount = ['bajo' => 0, 'medio' => 0, 'alto' => 0];
            
            foreach ($results as $result) {
                if (isset($result['nivel'])) {
                    $nivelesCount[$result['nivel']]++;
                }
            }
            
            // Determinar nivel predominante
            $nivelPredominante = 'medio';
            if ($nivelesCount['alto'] > $nivelesCount['medio'] && $nivelesCount['alto'] > $nivelesCount['bajo']) {
                $nivelPredominante = 'alto';
            } else if ($nivelesCount['bajo'] > $nivelesCount['medio'] && $nivelesCount['bajo'] > $nivelesCount['alto']) {
                $nivelPredominante = 'bajo';
            }
            
            // Recomendaciones según nivel
            switch ($nivelPredominante) {
                case 'alto':
                    $recomendaciones = 'Tus resultados son excelentes. Continúa profundizando en estas áreas y considera compartir tu conocimiento con otros.';
                    break;
                case 'medio':
                    $recomendaciones = 'Has demostrado un buen nivel en las áreas evaluadas. Para seguir mejorando, identifica los temas en los que obtuviste menor puntuación y dedica tiempo a fortalecerlos.';
                    break;
                case 'bajo':
                    $recomendaciones = 'Hay varias áreas de oportunidad en tu evaluación. Te recomendamos revisar los temas evaluados y practicar para fortalecer tus conocimientos y habilidades.';
                    break;
            }
            
            // Retornar estructura completa de resultados
            return [
                'sesion' => $session,
                'dimensiones' => $results,
                'recomendaciones' => $recomendaciones
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo resultados para sesión $session_id: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene estadísticas de una sesión de prueba
     */
    public function getSessionStats($session_id) {
        $session_id = (int)$session_id;
        
        // Obtener prueba asociada a la sesión
        $sessionSql = "SELECT prueba_id FROM sesiones_prueba WHERE id = $session_id";
        $sessionResult = $this->db->query($sessionSql);
        
        if (!$sessionResult || $sessionResult->num_rows === 0) {
            return ['respondidas' => 0, 'total' => 0];
        }
        
        $prueba_id = $sessionResult->fetch_assoc()['prueba_id'];
        
        // Contar respuestas
        $respuestasSql = "SELECT COUNT(*) as total FROM respuestas WHERE sesion_id = $session_id";
        $respuestasResult = $this->db->query($respuestasSql);
        $respondidas = ($respuestasResult) ? $respuestasResult->fetch_assoc()['total'] : 0;
        
        // Contar total de preguntas
        $preguntasSql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id AND activa = 1";
        $preguntasResult = $this->db->query($preguntasSql);
        $total = ($preguntasResult) ? $preguntasResult->fetch_assoc()['total'] : 0;
        
        return [
            'respondidas' => $respondidas,
            'total' => $total
        ];
    }
    
    /**
     * Obtiene las pruebas pendientes para un candidato
     * Versión corregida sin referencia a pruebas_tipos
     */
    public function getPendingTests($candidato_id) {
        $candidato_id = (int)$candidato_id;
        
        // Obtener todas las pruebas activas
        $allTestsSql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono
                        FROM pruebas p
                        JOIN pruebas_categorias c ON p.categoria_id = c.id
                        WHERE p.estado = 'activa'
                        ORDER BY c.orden ASC, p.titulo ASC";
                        
        $allTestsResult = $this->db->query($allTestsSql);
        $allTests = [];
        
        if ($allTestsResult) {
            while ($row = $allTestsResult->fetch_assoc()) {
                $allTests[] = $row;
            }
        }
        
        // Obtener pruebas ya completadas por el candidato
        $completedTestsSql = "SELECT DISTINCT prueba_id 
                              FROM sesiones_prueba 
                              WHERE candidato_id = $candidato_id AND estado = 'completada'";
                              
        $completedTestsResult = $this->db->query($completedTestsSql);
        $completedTests = [];
        
        if ($completedTestsResult) {
            while ($row = $completedTestsResult->fetch_assoc()) {
                $completedTests[] = $row['prueba_id'];
            }
        }
        
        // Obtener pruebas en progreso
        $inProgressTestsSql = "SELECT DISTINCT prueba_id 
                               FROM sesiones_prueba 
                               WHERE candidato_id = $candidato_id AND estado = 'en_progreso'";
                               
        $inProgressTestsResult = $this->db->query($inProgressTestsSql);
        $inProgressTests = [];
        
        if ($inProgressTestsResult) {
            while ($row = $inProgressTestsResult->fetch_assoc()) {
                $inProgressTests[] = $row['prueba_id'];
            }
        }
        
        // Filtrar las pruebas que no están completadas ni en progreso
        $pendingTests = [];
        
        foreach ($allTests as $test) {
            // Solo incluir si no está en la lista de completadas ni en progreso
            if (!in_array($test['id'], $completedTests) && !in_array($test['id'], $inProgressTests)) {
                $pendingTests[] = $test;
            }
        }
        
        return $pendingTests;
    }
    
    /**
     * Obtiene las pruebas en progreso para un candidato
     */
    public function getInProgressTests($candidato_id) {
        $candidato_id = (int)$candidato_id;
        
        $sql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                       p.categoria_id, c.nombre as categoria_nombre
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE s.candidato_id = $candidato_id
                AND s.estado = 'en_progreso'
                ORDER BY s.fecha_inicio DESC";
                
        $result = $this->db->query($sql);
        $tests = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Calcular preguntas respondidas
                $countSql = "SELECT COUNT(*) as total FROM respuestas WHERE sesion_id = {$row['id']}";
                $countResult = $this->db->query($countSql);
                $respondidas = ($countResult) ? $countResult->fetch_assoc()['total'] : 0;
                
                // Contar preguntas totales
                $preguntasSql = "SELECT COUNT(*) as total FROM preguntas 
                                 WHERE prueba_id = {$row['prueba_id']} AND activa = 1";
                $preguntasResult = $this->db->query($preguntasSql);
                $total = ($preguntasResult) ? $preguntasResult->fetch_assoc()['total'] : 0;
                
                // Añadir conteos al resultado
                $row['respuestas_count'] = $respondidas;
                $row['preguntas_count'] = $total;
                
                $tests[] = $row;
            }
        }
        
        return $tests;
    }
    
/**
 * Obtiene las pruebas completadas por un candidato
 * Versión mejorada para asegurar que retorna resultados completos
 */
public function getCompletedTests($candidato_id) {
    $candidato_id = (int)$candidato_id;
    
    // Log para diagnóstico
    error_log("Obteniendo pruebas completadas para candidato ID: $candidato_id");
    
    // Consulta SQL mejorada para incluir todos los campos necesarios
    $sql = "SELECT s.*, 
                   s.id as sesion_id, 
                   p.id as prueba_id,
                   p.titulo as prueba_titulo, 
                   p.descripcion as prueba_descripcion,
                   p.categoria_id, 
                   c.nombre as categoria_nombre
            FROM sesiones_prueba s
            JOIN pruebas p ON s.prueba_id = p.id
            LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
            WHERE s.candidato_id = $candidato_id
            AND s.estado = 'completada'
            ORDER BY s.fecha_fin DESC";
    
    error_log("SQL: $sql");
    
    $result = $this->db->query($sql);
    $tests = [];
    
    if ($result) {
        error_log("Número de resultados: " . $result->num_rows);
        
        while ($row = $result->fetch_assoc()) {
            // Log para diagnóstico
            error_log("Prueba completada encontrada: " . json_encode($row));
            
            // Asegurar que sesion_id esté disponible
            if (!isset($row['sesion_id']) && isset($row['id'])) {
                $row['sesion_id'] = $row['id'];
            }
            
            // Asegurar que resultado_global tenga un valor
            if (!isset($row['resultado_global']) || $row['resultado_global'] === null) {
                // Intentar obtener resultado de la tabla resultados
                $resultsSql = "SELECT AVG(valor) as promedio FROM resultados WHERE sesion_id = {$row['id']}";
                $resultsResult = $this->db->query($resultsSql);
                
                if ($resultsResult && $resultsResult->num_rows > 0) {
                    $valorPromedio = $resultsResult->fetch_assoc()['promedio'];
                    $row['resultado_global'] = round($valorPromedio);
                    
                    // Actualizar en la base de datos
                    $updateSql = "UPDATE sesiones_prueba SET resultado_global = {$row['resultado_global']} WHERE id = {$row['id']}";
                    $this->db->query($updateSql);
                } else {
                    // Si no hay resultados, asignar un valor predeterminado
                    $row['resultado_global'] = 0;
                }
            }
            
            // Asegurar que las fechas tengan un formato válido
            if (empty($row['fecha_fin'])) {
                $row['fecha_fin'] = date('Y-m-d H:i:s');
            }
            
            if (empty($row['fecha_inicio'])) {
                $row['fecha_inicio'] = date('Y-m-d H:i:s', strtotime($row['fecha_fin'] . ' -30 minutes'));
            }
            
            $tests[] = $row;
        }
    } else {
        error_log("Error en la consulta SQL: " . $this->db->getConnection()->error);
    }
    
    return $tests;
}
}
?>