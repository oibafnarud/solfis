<?php
/**
 * Clase para gestionar todas las operaciones relacionadas con pruebas psicométricas
 * Versión mejorada con funcionalidades integradas de los reparadores
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
        
        // Verificar y reparar la prueba antes de crear la sesión
        $this->verifyAndRepairTest($prueba_id);
        
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
        $sql = "SELECT tipo_pregunta FROM preguntas WHERE id = $question_id";
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        $tipo = $result->fetch_assoc()['tipo_pregunta'];
        
        // Preparar los datos según el tipo de pregunta
        $opcion_id = 'NULL';
        $texto_respuesta = 'NULL';
        $valor_escala = 'NULL';
        
        switch ($tipo) {
            case 'opcion_multiple':
            case 'pares':
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
            
            // Obtener info de la sesión para verificar el tipo de prueba
            $sessionInfo = $this->getSessionById($session_id);
            if (!$sessionInfo) {
                error_log("No se encontró la sesión $session_id");
                return false;
            }
            
            // Verificar y reparar la prueba antes de procesar resultados
            $this->verifyAndRepairTest($sessionInfo['prueba_id']);
            
            // Actualizar estado de la sesión
            $sql = "UPDATE sesiones_prueba 
                    SET estado = 'completada', fecha_fin = NOW() 
                    WHERE id = $session_id";
                    
            $this->db->query($sql);
            
            // Procesar resultados según el tipo de prueba
            $testStructure = $this->detectTestStructure($sessionInfo['prueba_id']);
            
            if ($testStructure['tiene_pares']) {
                // Procesar pruebas de tipo pares (IPL, CMV)
                $this->processPairsResults($session_id, $sessionInfo);
            } else {
                // Procesar pruebas estándar (TAC, ECF)
                $this->processStandardResults($session_id, $sessionInfo);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error al completar sesión $session_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesa los resultados para pruebas de tipo pares (IPL, CMV)
     * Basado en la lógica de regenerarResultadosSesion y procesarResultadosSesion de los reparadores
     */
    private function processPairsResults($session_id, $sessionInfo) {
        $session_id = (int)$session_id;
        $prueba_id = (int)$sessionInfo['prueba_id'];
        $candidato_id = (int)$sessionInfo['candidato_id'];
        
        // Primero eliminar resultados existentes
        $this->db->query("DELETE FROM resultados WHERE sesion_id = $session_id");
        
        // Contar respuestas agrupadas por dimensión (de las opciones seleccionadas)
        $sql = "SELECT o.dimension_id, d.nombre as dimension_nombre, COUNT(*) as count
                FROM respuestas r
                JOIN opciones_respuesta o ON r.opcion_id = o.id
                JOIN dimensiones d ON o.dimension_id = d.id
                WHERE r.sesion_id = $session_id
                GROUP BY o.dimension_id, d.nombre";
                
        $result = $this->db->query($sql);
        
        $total_respuestas = 0;
        $conteo_dimensiones = [];
        $resultados_generados = 0;
        $dimensiones_procesadas = 0;
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conteo_dimensiones[$row['dimension_id']] = [
                    'nombre' => $row['dimension_nombre'],
                    'count' => $row['count']
                ];
                $total_respuestas += $row['count'];
                $dimensiones_procesadas++;
            }
            
            // Insertar resultados
            if ($total_respuestas > 0) {
                foreach ($conteo_dimensiones as $dimension_id => $data) {
                    $porcentaje = round(($data['count'] / $total_respuestas) * 100);
                    
                    $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                  VALUES ($session_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if ($this->db->query($insert_sql)) {
                        $resultados_generados++;
                    }
                }
            }
        }
        
        // Calcular y actualizar resultado global
        if ($resultados_generados > 0) {
            $sql = "SELECT AVG(valor) as promedio FROM resultados WHERE sesion_id = $session_id";
            $result = $this->db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $resultado_global = round($result->fetch_assoc()['promedio']);
                
                $update_sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $session_id";
                $this->db->query($update_sql);
            }
        }
        
        return [
            "dimensiones_procesadas" => $dimensiones_procesadas,
            "resultados_generados" => $resultados_generados
        ];
    }
    
    /**
     * Procesa los resultados para pruebas estándar (TAC, ECF)
     */
    private function processStandardResults($session_id, $sessionInfo) {
        $session_id = (int)$session_id;
        $prueba_id = (int)$sessionInfo['prueba_id'];
        $candidato_id = (int)$sessionInfo['candidato_id'];
        
        // Eliminar resultados existentes
        $this->db->query("DELETE FROM resultados WHERE sesion_id = $session_id");
        
        // Obtener dimensiones y sus respuestas
        $sql = "SELECT p.dimension_id, d.nombre, COUNT(*) as total_preguntas,
                       SUM(CASE WHEN o.valor IS NOT NULL THEN o.valor ELSE r.valor_escala END) as suma_valores
                FROM respuestas r
                JOIN preguntas p ON r.pregunta_id = p.id
                JOIN dimensiones d ON p.dimension_id = d.id
                LEFT JOIN opciones_respuesta o ON r.opcion_id = o.id
                WHERE r.sesion_id = $session_id
                GROUP BY p.dimension_id, d.nombre";
                
        $result = $this->db->query($sql);
        $resultados_generados = 0;
        $dimensiones_procesadas = 0;
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimension_id = $row['dimension_id'];
                $total_preguntas = $row['total_preguntas'];
                
                if ($total_preguntas > 0) {
                    // Calcular el valor promedio
                    $valor_promedio = $row['suma_valores'] / $total_preguntas;
                    
                    // Normalizar a escala 0-100
                    $porcentaje = round(($valor_promedio / 5) * 100); // Asumiendo escala 0-5
                    
                    // Insertar resultado
                    $insert_sql = "INSERT INTO resultados (sesion_id, dimension_id, valor, percentil, candidato_id)
                                  VALUES ($session_id, $dimension_id, $porcentaje, $porcentaje, $candidato_id)";
                    
                    if ($this->db->query($insert_sql)) {
                        $resultados_generados++;
                    }
                    
                    $dimensiones_procesadas++;
                }
            }
        }
        
        // Calcular y actualizar resultado global
        if ($resultados_generados > 0) {
            $sql = "SELECT AVG(valor) as promedio FROM resultados WHERE sesion_id = $session_id";
            $result = $this->db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $resultado_global = round($result->fetch_assoc()['promedio']);
                
                $update_sql = "UPDATE sesiones_prueba SET resultado_global = $resultado_global WHERE id = $session_id";
                $this->db->query($update_sql);
            }
        }
        
        return [
            "dimensiones_procesadas" => $dimensiones_procesadas,
            "resultados_generados" => $resultados_generados
        ];
    }
    
    /**
     * Detecta la estructura de una prueba (tipo de preguntas, dimensiones)
     * Basado en detectarEstructuraPrueba de reparador-pruebas.php
     */
    public function detectTestStructure($prueba_id) {
        $prueba_id = (int)$prueba_id;
        
        // Verificar tipo de preguntas
        $sql = "SELECT tipo_pregunta, COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id GROUP BY tipo_pregunta";
        $result = $this->db->query($sql);
        
        $estructura = [
            'tipos_pregunta' => [],
            'dimensiones_en_preguntas' => 0,
            'dimensiones_en_opciones' => 0,
            'total_preguntas' => 0,
            'tiene_pares' => false,
            'prueba_tipo' => 'desconocida'
        ];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $estructura['tipos_pregunta'][$row['tipo_pregunta']] = $row['total'];
                $estructura['total_preguntas'] += $row['total'];
                
                if ($row['tipo_pregunta'] == 'pares') {
                    $estructura['tiene_pares'] = true;
                }
            }
        }
        
        // Contar preguntas con dimensión asignada
        $sql = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = $prueba_id AND dimension_id IS NOT NULL";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $estructura['dimensiones_en_preguntas'] = $result->fetch_assoc()['total'];
        }
        
        // Contar opciones con dimensión asignada
        $sql = "SELECT COUNT(*) as total FROM opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                WHERE p.prueba_id = $prueba_id AND o.dimension_id IS NOT NULL";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $estructura['dimensiones_en_opciones'] = $result->fetch_assoc()['total'];
        }
        
        // Determinar el tipo de prueba
        $sql = "SELECT titulo FROM pruebas WHERE id = $prueba_id";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $titulo = strtolower($result->fetch_assoc()['titulo']);
            
            if (strpos($titulo, 'personalidad') !== false) {
                $estructura['prueba_tipo'] = 'ipl';
            } elseif (strpos($titulo, 'aptitudes') !== false || strpos($titulo, 'cognitiv') !== false) {
                $estructura['prueba_tipo'] = 'tac';
            } elseif (strpos($titulo, 'competencias') !== false) {
                $estructura['prueba_tipo'] = 'ecf';
            } elseif (strpos($titulo, 'motivacion') !== false || strpos($titulo, 'valores') !== false || strpos($titulo, 'cmv') !== false) {
                $estructura['prueba_tipo'] = 'cmv';
            }
        }
        
        return $estructura;
    }
    
    /**
     * Verificar y reparar una prueba si es necesario
     */
    public function verifyAndRepairTest($prueba_id) {
        $prueba_id = (int)$prueba_id;
        
        // Detectar estructura
        $estructura = $this->detectTestStructure($prueba_id);
        
        // Verificar si necesita reparación
        $necesita_reparacion = false;
        $mensajes = [];
        
        if ($estructura['tiene_pares'] && $estructura['dimensiones_en_opciones'] == 0) {
            $necesita_reparacion = true;
            $mensajes[] = "La prueba de tipo pares no tiene dimensiones asignadas a las opciones.";
        } elseif (!$estructura['tiene_pares'] && $estructura['dimensiones_en_preguntas'] == 0) {
            $necesita_reparacion = true;
            $mensajes[] = "Esta prueba no tiene dimensiones asignadas a las preguntas.";
        }
        
        // Si necesita reparación, proceder según el tipo de prueba
        if ($necesita_reparacion) {
            switch ($estructura['prueba_tipo']) {
                case 'ipl':
                    $resultados = $this->repararDimensionesIPL($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura del Inventario de Personalidad Laboral (IPL).";
                    break;
                case 'tac':
                    $resultados = $this->repararDimensionesTAC($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura del Test de Aptitudes Cognitivas (TAC).";
                    break;
                case 'ecf':
                    $resultados = $this->repararDimensionesECF($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura de la Evaluación de Competencias Fundamentales (ECF).";
                    break;
                case 'cmv':
                    $resultados = $this->repararDimensionesCMV($prueba_id);
                    $mensajes[] = "Se ha reparado la estructura del Cuestionario de Motivaciones y Valores (CMV).";
                    break;
                default:
                    // Intentar reparar según la estructura detectada
                    if ($estructura['tiene_pares']) {
                        $resultados = $this->repararDimensionesIPL($prueba_id);
                        $mensajes[] = "Se ha reparado la estructura de la prueba de tipo pares.";
                    } else {
                        $resultados = $this->repararDimensionesTAC($prueba_id);
                        $mensajes[] = "Se ha reparado la estructura de la prueba estándar.";
                    }
                    break;
            }
            
            return [
                'success' => true,
                'reparacion_realizada' => true,
                'mensajes' => $mensajes,
                'resultados' => $resultados ?? []
            ];
        }
        
        return [
            'success' => true,
            'reparacion_realizada' => false,
            'mensajes' => ["La prueba no necesita reparación."]
        ];
    }
    
    /**
     * Repara las dimensiones para pruebas IPL (Inventario de Personalidad Laboral)
     * Basado en repararDimensionesIPL de reparador-pruebas.php
     */
    private function repararDimensionesIPL($prueba_id) {
        $prueba_id = (int)$prueba_id;
        $resultados = [];
        
        // Verificar dimensiones existentes para IPL
        $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN (
            'Extroversión vs. Introversión', 
            'Estabilidad vs. Reactividad Emocional', 
            'Apertura vs. Convencionalidad', 
            'Responsabilidad', 
            'Cooperación vs. Independencia'
        )";
        $result = $this->db->query($sql);
        
        $dimensiones_ipl = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimensiones_ipl[$row['nombre']] = $row['id'];
            }
        }
        
        // Si no existen todas las dimensiones, crearlas
        $dimensiones_necesarias = [
            'Extroversión vs. Introversión',
            'Estabilidad vs. Reactividad Emocional',
            'Apertura vs. Convencionalidad',
            'Responsabilidad',
            'Cooperación vs. Independencia'
        ];
        
        foreach ($dimensiones_necesarias as $dimension) {
            if (!isset($dimensiones_ipl[$dimension])) {
                $sql = "INSERT INTO dimensiones (nombre, tipo, bipolar) VALUES ('$dimension', 'personalidad', 1)";
                
                if ($this->db->query($sql)) {
                    $dimensiones_ipl[$dimension] = $this->db->lastInsertId();
                    $resultados[] = "Dimensión '$dimension' creada con ID: " . $this->db->lastInsertId();
                }
            }
        }
        
        // Si IPL es de tipo pares, vincular opciones a dimensiones
        $sql = "SELECT COUNT(*) as count FROM preguntas WHERE prueba_id = $prueba_id AND tipo_pregunta = 'pares'";
        $result = $this->db->query($sql);
        $es_prueba_pares = ($result && $result->fetch_assoc()['count'] > 0);
        
        $asignaciones = [
            'extroversion' => 0,
            'estabilidad' => 0,
            'apertura' => 0,
            'responsabilidad' => 0,
            'cooperacion' => 0
        ];
        
        if ($es_prueba_pares) {
            // Para pruebas de tipo pares, asignar dimensiones a las opciones
            $sql = "SELECT o.id 
                    FROM opciones_respuesta o
                    JOIN preguntas p ON o.pregunta_id = p.id
                    WHERE p.prueba_id = $prueba_id AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
            $result = $this->db->query($sql);
            
            $opciones_sin_dimension = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $opciones_sin_dimension[] = $row['id'];
                }
            }
            
            $dimension_keys = array_keys($dimensiones_ipl);
            $total_opciones = count($opciones_sin_dimension);
            
            for ($i = 0; $i < $total_opciones; $i++) {
                $opcion_id = $opciones_sin_dimension[$i];
                // Determinar qué dimensión asignar (distribución equitativa)
                $dimension_index = $i % count($dimension_keys);
                $dimension_nombre = $dimension_keys[$dimension_index];
                $dimension_id = $dimensiones_ipl[$dimension_nombre];
                
                // Actualizar opción
                $update_sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_id WHERE id = $opcion_id";
                if ($this->db->query($update_sql)) {
                    // Determinar qué contador incrementar
                    if ($dimension_nombre == 'Extroversión vs. Introversión') {
                        $asignaciones['extroversion']++;
                    } elseif ($dimension_nombre == 'Estabilidad vs. Reactividad Emocional') {
                        $asignaciones['estabilidad']++;
                    } elseif ($dimension_nombre == 'Apertura vs. Convencionalidad') {
                        $asignaciones['apertura']++;
                    } elseif ($dimension_nombre == 'Responsabilidad') {
                        $asignaciones['responsabilidad']++;
                    } elseif ($dimension_nombre == 'Cooperación vs. Independencia') {
                        $asignaciones['cooperacion']++;
                    }
                }
            }
        } else {
            // Para pruebas estándar, asignar dimensiones a las preguntas
            $sql = "SELECT id FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
            $result = $this->db->query($sql);
            
            $preguntas_sin_dimension = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $preguntas_sin_dimension[] = $row['id'];
                }
            }
            
            $dimension_keys = array_keys($dimensiones_ipl);
            $total_preguntas = count($preguntas_sin_dimension);
            
            for ($i = 0; $i < $total_preguntas; $i++) {
                $pregunta_id = $preguntas_sin_dimension[$i];
                // Determinar qué dimensión asignar (distribución equitativa)
                $dimension_index = $i % count($dimension_keys);
                $dimension_nombre = $dimension_keys[$dimension_index];
                $dimension_id = $dimensiones_ipl[$dimension_nombre];
                
                // Actualizar pregunta
                $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
                if ($this->db->query($update_sql)) {
                    // Determinar qué contador incrementar
                    if ($dimension_nombre == 'Extroversión vs. Introversión') {
                        $asignaciones['extroversion']++;
                    } elseif ($dimension_nombre == 'Estabilidad vs. Reactividad Emocional') {
                        $asignaciones['estabilidad']++;
                    } elseif ($dimension_nombre == 'Apertura vs. Convencionalidad') {
                        $asignaciones['apertura']++;
                    } elseif ($dimension_nombre == 'Responsabilidad') {
                        $asignaciones['responsabilidad']++;
                    } elseif ($dimension_nombre == 'Cooperación vs. Independencia') {
                        $asignaciones['cooperacion']++;
                    }
                }
            }
        }
        
        return [
            'dimensiones_creadas' => count($resultados),
            'asignaciones' => $asignaciones,
            'mensajes' => $resultados,
            'es_prueba_pares' => $es_prueba_pares,
            'elementos_procesados' => $es_prueba_pares ? count($opciones_sin_dimension ?? []) : count($preguntas_sin_dimension ?? [])
        ];
    }
    
    /**
     * Repara las dimensiones para pruebas TAC (Test de Aptitudes Cognitivas)
     * Basado en repararDimensionesTAC de reparador-pruebas.php
     */
    private function repararDimensionesTAC($prueba_id) {
        $prueba_id = (int)$prueba_id;
        $resultados = [];
        
        // Verificar dimensiones existentes para TAC
        $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN ('Razonamiento Verbal', 'Razonamiento Numérico', 'Razonamiento Lógico', 'Atención al Detalle')";
        $result = $this->db->query($sql);
        
        $dimensiones_tac = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimensiones_tac[$row['nombre']] = $row['id'];
            }
        }
        
        // Si no existen todas las dimensiones, crearlas
        $dimensiones_necesarias = [
            'Razonamiento Verbal',
            'Razonamiento Numérico',
            'Razonamiento Lógico',
            'Atención al Detalle'
        ];
        
        foreach ($dimensiones_necesarias as $dimension) {
            if (!isset($dimensiones_tac[$dimension])) {
                $sql = "INSERT INTO dimensiones (nombre, tipo) VALUES ('$dimension', 'cognitiva')";
                
                if ($this->db->query($sql)) {
                    $dimensiones_tac[$dimension] = $this->db->lastInsertId();
                    $resultados[] = "Dimensión '$dimension' creada con ID: " . $this->db->lastInsertId();
                }
            }
        }
        
        // Asignar dimensiones a preguntas según su contenido
        $sql = "SELECT id, texto FROM preguntas WHERE prueba_id = $prueba_id AND dimension_id IS NULL";
        $result = $this->db->query($sql);
        
        $asignaciones = [
            'verbal' => 0,
            'numerico' => 0,
            'logico' => 0,
            'detalle' => 0
        ];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $texto = strtolower($row['texto']);
                $pregunta_id = $row['id'];
                $dimension_id = null;
                
                // Asignar dimensión según el contenido de la pregunta
                if (strpos($texto, 'palabra') !== false || 
                    strpos($texto, 'texto') !== false || 
                    strpos($texto, 'lectura') !== false) {
                    $dimension_id = $dimensiones_tac['Razonamiento Verbal'];
                    $asignaciones['verbal']++;
                }
                elseif (strpos($texto, 'número') !== false || 
                       strpos($texto, 'cálculo') !== false || 
                       strpos($texto, 'matemáticas') !== false) {
                    $dimension_id = $dimensiones_tac['Razonamiento Numérico'];
                    $asignaciones['numerico']++;
                }
                elseif (strpos($texto, 'lógica') !== false || 
                       strpos($texto, 'secuencia') !== false || 
                       strpos($texto, 'patrón') !== false) {
                    $dimension_id = $dimensiones_tac['Razonamiento Lógico'];
                    $asignaciones['logico']++;
                }
                else {
                    $dimension_id = $dimensiones_tac['Atención al Detalle'];
                    $asignaciones['detalle']++;
                }
                
                // Actualizar pregunta
                if ($dimension_id) {
                    $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
                    $this->db->query($update_sql);
                }
            }
        }
        
        return [
            'dimensiones_creadas' => count($resultados),
            'asignaciones' => $asignaciones,
            'mensajes' => $resultados
        ];
    }
    
    /**
     * Repara las dimensiones para pruebas ECF (Evaluación de Competencias Fundamentales)
     * Basado en repararDimensionesECF de reparador-pruebas.php
     */
    private function repararDimensionesECF($prueba_id) {
        $prueba_id = (int)$prueba_id;
        $resultados = [];
        
        // Verificar dimensiones existentes para ECF
        $sql = "SELECT id, nombre FROM dimensiones WHERE nombre IN (
            'Comunicación Básica', 
            'Trabajo en Equipo', 
            'Adaptabilidad', 
            'Integridad', 
            'Meticulosidad vs. Flexibilidad'
        )";
        $result = $this->db->query($sql);
        
        $dimensiones_ecf = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dimensiones_ecf[$row['nombre']] = $row['id'];
            }
        }
        
        // Si no existen todas las dimensiones, crearlas
        $dimensiones_necesarias = [
            'Comunicación Básica',
            'Trabajo en Equipo',
            'Adaptabilidad',
            'Integridad',
            'Meticulosidad vs. Flexibilidad'
        ];
        
        foreach ($dimensiones_necesarias as $dimension) {
            if (!isset($dimensiones_ecf[$dimension])) {
                $sql = "INSERT INTO dimensiones (nombre, tipo) VALUES ('$dimension', 'competencia')";
                
                if ($this->db->query($sql)) {
                    $dimensiones_ecf[$dimension] = $this->db->lastInsertId();
                    $resultados[] = "Dimensión '$dimension' creada con ID: " . $this->db->lastInsertId();
                }
            }
        }
        
        // Obtener todas las preguntas sin dimensión
        $sql = "SELECT id FROM preguntas WHERE prueba_id = $prueba_id AND (dimension_id IS NULL OR dimension_id = 0)";
        $result = $this->db->query($sql);
        
        $preguntas_sin_dimension = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $preguntas_sin_dimension[] = $row['id'];
            }
        }
        
        // Asignar dimensiones a las preguntas de forma equitativa
        $asignaciones = [
            'comunicacion' => 0,
            'trabajo_equipo' => 0,
            'adaptabilidad' => 0,
            'integridad' => 0,
            'meticulosidad' => 0
        ];
        
        $dimension_keys = array_keys($dimensiones_ecf);
        $total_preguntas = count($preguntas_sin_dimension);
        
        for ($i = 0; $i < $total_preguntas; $i++) {
            $pregunta_id = $preguntas_sin_dimension[$i];
            // Determinar qué dimensión asignar (distribución equitativa)
            $dimension_index = $i % count($dimension_keys);
            $dimension_nombre = $dimension_keys[$dimension_index];
            $dimension_id = $dimensiones_ecf[$dimension_nombre];
            
            // Actualizar pregunta
            $update_sql = "UPDATE preguntas SET dimension_id = $dimension_id WHERE id = $pregunta_id";
            if ($this->db->query($update_sql)) {
                // Determinar qué contador incrementar
                if ($dimension_nombre == 'Comunicación Básica') {
                    $asignaciones['comunicacion']++;
                } elseif ($dimension_nombre == 'Trabajo en Equipo') {
                    $asignaciones['trabajo_equipo']++;
                } elseif ($dimension_nombre == 'Adaptabilidad') {
                    $asignaciones['adaptabilidad']++;
                } elseif ($dimension_nombre == 'Integridad') {
                    $asignaciones['integridad']++;
                } elseif ($dimension_nombre == 'Meticulosidad vs. Flexibilidad') {
                    $asignaciones['meticulosidad']++;
                }
            }
        }
        
        return [
            'dimensiones_creadas' => count($resultados),
            'asignaciones' => $asignaciones,
            'mensajes' => $resultados,
            'preguntas_procesadas' => $total_preguntas
        ];
    }
    
    /**
     * Repara las dimensiones para pruebas CMV (Cuestionario de Motivaciones y Valores)
     * Basado en la lógica de reparar-pruebas-cmv.php
     */
    private function repararDimensionesCMV($prueba_id) {
        $prueba_id = (int)$prueba_id;
        $resultados = [];
        
        // Verificar dimensiones existentes para motivación
        $sql = "SELECT id, nombre FROM dimensiones WHERE nombre LIKE 'Motivación por %'";
        $result = $this->db->query($sql);
        
        $dimensiones_cmv = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $nombre_corto = str_replace('Motivación por ', '', $row['nombre']);
                $dimensiones_cmv[strtolower($nombre_corto)] = $row['id'];
            }
        }
        
        // Si no existen todas las dimensiones, crearlas
        $dimensiones_necesarias = [
            'Logro' => 'Motivación por Logro',
            'Poder' => 'Motivación por Poder',
            'Afiliación' => 'Motivación por Afiliación',
            'Seguridad' => 'Motivación por Seguridad',
            'Autonomía' => 'Motivación por Autonomía',
            'Servicio' => 'Motivación por Servicio',
            'Reto' => 'Motivación por Reto',
            'Equilibrio' => 'Motivación por Equilibrio'
        ];
        
        foreach ($dimensiones_necesarias as $nombre_corto => $nombre_completo) {
            $clave = strtolower($nombre_corto);
            if (!isset($dimensiones_cmv[$clave])) {
                $sql = "INSERT INTO dimensiones (nombre, tipo) VALUES ('$nombre_completo', 'motivacion')";
                
                if ($this->db->query($sql)) {
                    $dimensiones_cmv[$clave] = $this->db->lastInsertId();
                    $resultados[] = "Dimensión '$nombre_completo' creada con ID: " . $this->db->lastInsertId();
                }
            }
        }
        
        // Actualizar opciones para cada dimensión
        $actualizaciones = [];
        
        // Motivación por Logro
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['logro']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%logro%' OR o.texto LIKE '%meta%' 
                    OR o.texto LIKE '%alcanzar%' OR o.texto LIKE '%superar%'
                    OR o.texto LIKE '%excelencia%' OR o.texto LIKE '%establecer y alcanzar%'
                    OR o.texto LIKE '%obtener reconocimiento%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['logro'] = $this->db->affected_rows;
        
        // Motivación por Poder
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['poder']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%poder%' OR o.texto LIKE '%influencia%' 
                    OR o.texto LIKE '%dirigi%' OR o.texto LIKE '%autoridad%'
                    OR o.texto LIKE '%liderazgo%' OR o.texto LIKE '%influir%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['poder'] = $this->db->affected_rows;
        
        // Motivación por Afiliación
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['afiliación']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%relacion%' OR o.texto LIKE '%colaborativo%' 
                    OR o.texto LIKE '%grupo%' OR o.texto LIKE '%equipo%'
                    OR o.texto LIKE '%social%' OR o.texto LIKE '%conexión%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['afiliacion'] = $this->db->affected_rows;
        
        // Motivación por Seguridad
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['seguridad']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%segur%' OR o.texto LIKE '%estab%' 
                    OR o.texto LIKE '%predecible%' OR o.texto LIKE '%claridad%'
                    OR o.texto LIKE '%futuro%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['seguridad'] = $this->db->affected_rows;
        
        // Motivación por Autonomía
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['autonomía']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%autonom%' OR o.texto LIKE '%independ%' 
                    OR o.texto LIKE '%tomar mis propias%' OR o.texto LIKE '%decisiones%'
                    OR o.texto LIKE '%mi manera%' OR o.texto LIKE '%sin interferenci%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['autonomia'] = $this->db->affected_rows;
        
        // Motivación por Servicio
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['servicio']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%servicio%' OR o.texto LIKE '%ayudar%' 
                    OR o.texto LIKE '%contribuir%' OR o.texto LIKE '%impacto%'
                    OR o.texto LIKE '%bienestar%' OR o.texto LIKE '%mejora%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['servicio'] = $this->db->affected_rows;
        
        // Motivación por Reto
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['reto']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%reto%' OR o.texto LIKE '%desaf%' 
                    OR o.texto LIKE '%problem%' OR o.texto LIKE '%compl%'
                    OR o.texto LIKE '%obstác%' OR o.texto LIKE '%enfrentar%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['reto'] = $this->db->affected_rows;
        
        // Motivación por Equilibrio
        $sql = "UPDATE opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                SET o.dimension_id = {$dimensiones_cmv['equilibrio']}
                WHERE p.prueba_id = $prueba_id
                AND (o.texto LIKE '%equilibr%' OR o.texto LIKE '%balance%' 
                    OR o.texto LIKE '%vida personal%' OR o.texto LIKE '%tiempo%'
                    OR o.texto LIKE '%calidad de vida%' OR o.texto LIKE '%disfrut%')
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $actualizaciones['equilibrio'] = $this->db->affected_rows;
        
        // Distribuir las opciones restantes equitativamente entre las dimensiones
        $sql = "SELECT o.id 
                FROM opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                WHERE p.prueba_id = $prueba_id AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        
        $opciones_sin_dimension = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $opciones_sin_dimension[] = $row['id'];
            }
        }
        
        $dimensiones_cmv_arr = array_values($dimensiones_cmv);
        $total_opciones = count($opciones_sin_dimension);
        
        for ($i = 0; $i < $total_opciones; $i++) {
            $opcion_id = $opciones_sin_dimension[$i];
            $dimension_id = $dimensiones_cmv_arr[$i % count($dimensiones_cmv_arr)];
            
            $sql = "UPDATE opciones_respuesta SET dimension_id = $dimension_id WHERE id = $opcion_id";
            $this->db->query($sql);
        }
        
        // Ver cuántas opciones quedan sin asignar
        $sql = "SELECT COUNT(*) as pendientes
                FROM opciones_respuesta o
                JOIN preguntas p ON o.pregunta_id = p.id
                WHERE p.prueba_id = $prueba_id
                AND (o.dimension_id IS NULL OR o.dimension_id = 0)";
        $result = $this->db->query($sql);
        $pendientes = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['pendientes'] : 0;
        
        return [
            'dimensiones_creadas' => count($resultados),
            'actualizaciones' => $actualizaciones,
            'total_actualizadas' => array_sum($actualizaciones),
            'opciones_distribuidas' => $total_opciones,
            'pendientes' => $pendientes,
            'mensajes' => $resultados
        ];
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
            
            // Verificar si la sesión tiene resultados, si no, intentar procesarlos
            $countSql = "SELECT COUNT(*) as count FROM resultados WHERE sesion_id = $session_id";
            $countResult = $this->db->query($countSql);
            $hasResults = ($countResult && $countResult->fetch_assoc()['count'] > 0);
            
            if (!$hasResults && $session['estado'] === 'completada') {
                // Determinar el tipo de prueba
                $estructura = $this->detectTestStructure($session['prueba_id']);
                
                if ($estructura['tiene_pares']) {
                    // Procesar pruebas de tipo pares (IPL, CMV)
                    $this->processPairsResults($session_id, $session);
                } else {
                    // Procesar pruebas estándar (TAC, ECF)
                    $this->processStandardResults($session_id, $session);
                }
            }
            
            // Obtener resultados de las dimensiones
            $resultsSql = "SELECT r.*, d.nombre as dimension_nombre, d.descripcion as dimension_descripcion
                          FROM resultados r
                          JOIN dimensiones d ON r.dimension_id = d.id
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
                // Determinar nivel según el valor
                $nivel = 'medio';
                if ($result['valor'] <= 33) {
                    $nivel = 'bajo';
                } else if ($result['valor'] >= 67) {
                    $nivel = 'alto';
                }
                
                $result['nivel'] = $nivel;
                $nivelesCount[$nivel]++;
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
     * Calcula y actualiza el resultado global de una sesión
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
     */
    public function getCompletedTests($candidato_id) {
        $candidato_id = (int)$candidato_id;
        
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
        
        $result = $this->db->query($sql);
        $tests = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Asegurar que sesion_id esté disponible
                if (!isset($row['sesion_id']) && isset($row['id'])) {
                    $row['sesion_id'] = $row['id'];
                }
                
                // Asegurar que resultado_global tenga un valor
                if (!isset($row['resultado_global']) || $row['resultado_global'] === null) {
                    // Verificar si hay resultados para esta sesión
                    $hasResults = $this->checkSessionResults($row['id']);
                    
                    if (!$hasResults) {
                        // Procesar resultados si no existen
                        $this->processResults($row['id']);
                    }
                    
                    // Calcular el resultado global
                    $row['resultado_global'] = $this->calculateGlobalResult($row['id']);
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
        }
        
        return $tests;
    }
    
    /**
     * Verifica si una sesión tiene resultados
     */
    private function checkSessionResults($session_id) {
        $session_id = (int)$session_id;
        
        $sql = "SELECT COUNT(*) as count FROM resultados WHERE sesion_id = $session_id";
        $result = $this->db->query($sql);
        
        return ($result && $result->fetch_assoc()['count'] > 0);
    }
    
    /**
     * Procesa los resultados de una sesión según el tipo de prueba
     */
    private function processResults($session_id) {
        // Obtener info de la sesión
        $sessionInfo = $this->getSessionById($session_id);
        if (!$sessionInfo) {
            return false;
        }
        
        // Verificar el tipo de prueba y procesar según corresponda
        $estructura = $this->detectTestStructure($sessionInfo['prueba_id']);
        
        if ($estructura['tiene_pares']) {
            return $this->processPairsResults($session_id, $sessionInfo);
        } else {
            return $this->processStandardResults($session_id, $sessionInfo);
        }
    }
    
    /**
     * Obtiene los índices compuestos
     * @return array Listado de índices compuestos
     */
    public function getIndicesCompuestos() {
        $sql = "SELECT * FROM indices_compuestos ORDER BY nombre";
        $result = $this->db->query($sql);
        $indices = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $indices[] = $row;
            }
        }
        
        return $indices;
    }
}
?>