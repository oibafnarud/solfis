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
        $session_id = (int)$session_id;
        
        // Actualizar estado de la sesión
        $sql = "UPDATE sesiones_prueba 
                SET estado = 'completada', fecha_fin = NOW() 
                WHERE id = $session_id";
                
        $this->db->query($sql);
        
        // Procesar resultados
        $this->processResults($session_id);
        
        return true;
    }
    
    /**
     * Procesa los resultados de una sesión de prueba
     */
    private function processResults($session_id) {
        $session_id = (int)$session_id;
        
        // Obtener datos de la sesión
        $sessionSql = "SELECT prueba_id, candidato_id FROM sesiones_prueba WHERE id = $session_id";
        $sessionResult = $this->db->query($sessionSql);
        
        if (!$sessionResult || $sessionResult->num_rows === 0) {
            return false;
        }
        
        $session = $sessionResult->fetch_assoc();
        $prueba_id = $session['prueba_id'];
        $candidato_id = $session['candidato_id'];
        
        // Obtener dimensiones evaluadas en la prueba
        $dimensionsSql = "SELECT d.id, d.nombre, d.descripcion, d.valor_min, d.valor_max
                          FROM dimensiones d
                          JOIN pruebas_dimensiones pd ON d.id = pd.dimension_id
                          WHERE pd.prueba_id = $prueba_id";
        $dimensionsResult = $this->db->query($dimensionsSql);
        
        if (!$dimensionsResult) {
            return false;
        }
        
        $dimensiones = [];
        while ($row = $dimensionsResult->fetch_assoc()) {
            $dimensiones[$row['id']] = $row;
        }
        
        // Para cada dimensión, calcular el resultado
        foreach ($dimensiones as $dimension_id => $dimension) {
            // Obtener respuestas que afectan a esta dimensión
            $answersSql = "SELECT r.*, o.valor, o.dimension_id
                           FROM respuestas r
                           JOIN preguntas p ON r.pregunta_id = p.id
                           LEFT JOIN opciones_respuesta o ON r.opcion_id = o.id
                           WHERE r.sesion_id = $session_id
                           AND (o.dimension_id = $dimension_id OR p.dimension_id = $dimension_id)";
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
                
                if ($answer['opcion_id']) {
                    // Respuesta de opción múltiple
                    $valor = $answer['valor'];
                } else if ($answer['valor_escala'] !== null) {
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
        
        return true;
    }
    
    /**
     * Normaliza un valor a escala 0-100
     */
    private function normalizeValue($value, $dimension) {
        // Obtener rangos min y max de la dimensión
        $min = $dimension['valor_min'];
        $max = $dimension['valor_max'];
        
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
        
        $sql = "SELECT * 
                FROM interpretaciones
                WHERE dimension_id = $dimension_id
                AND rango_min <= $value AND rango_max >= $value";
                
        $result = $this->db->query($sql);
        
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }
    
    /**
     * Guarda un resultado de prueba
     */
    private function saveResult($session_id, $dimension_id, $value, $interpretacion) {
        $session_id = (int)$session_id;
        $dimension_id = (int)$dimension_id;
        $value = (float)$value;
        
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
     * Obtiene los resultados de una sesión de prueba
     */
    public function getSessionResults($session_id) {
        $session_id = (int)$session_id;
        
        $sql = "SELECT r.*, d.nombre as dimension_nombre, d.descripcion as dimension_descripcion,
                       i.descripcion as interpretacion, i.recomendacion, i.nivel
                FROM resultados r
                JOIN dimensiones d ON r.dimension_id = d.id
                LEFT JOIN interpretaciones i ON d.id = i.dimension_id 
                    AND r.valor BETWEEN i.rango_min AND i.rango_max
                WHERE r.sesion_id = $session_id
                ORDER BY r.valor DESC";
                
        $result = $this->db->query($sql);
        $results = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        
        return $results;
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
        
        $sql = "SELECT p.*
                FROM pruebas p
                WHERE p.estado = 'activa'
                AND p.id NOT IN (
                    SELECT prueba_id 
                    FROM sesiones_prueba 
                    WHERE candidato_id = $candidato_id AND estado = 'completada'
                )
                ORDER BY p.categoria_id, p.id";
                
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
     * Obtiene las pruebas completadas por un candidato
     */
    public function getCompletedTests($candidato_id) {
        $candidato_id = (int)$candidato_id;
        
        $sql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                       p.categoria_id, c.nombre as categoria_nombre
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE s.candidato_id = $candidato_id
                AND s.estado = 'completada'
                ORDER BY s.fecha_fin DESC";
                
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
     * Obtiene las pruebas en progreso para un candidato
     */
    public function getInProgressTests($candidato_id) {
        $candidato_id = (int)$candidato_id;
        
        $sql = "SELECT s.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                WHERE s.candidato_id = $candidato_id
                AND s.estado = 'en_progreso'
                ORDER BY s.fecha_inicio DESC";
                
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
     * Obtiene el porcentaje de coincidencia entre un candidato y un perfil ideal para una vacante
     */
    public function getProfileMatchPercentage($candidato_id, $vacante_id) {
        $candidato_id = (int)$candidato_id;
        $vacante_id = (int)$vacante_id;
        
        // Obtener perfil ideal asociado a la vacante
        $perfilSql = "SELECT vp.perfil_id
                      FROM vacantes_perfiles vp
                      WHERE vp.vacante_id = $vacante_id
                      LIMIT 1";
        $perfilResult = $this->db->query($perfilSql);
        
        if (!$perfilResult || $perfilResult->num_rows === 0) {
            return 0; // No hay perfil ideal
        }
        
        $perfil_id = $perfilResult->fetch_assoc()['perfil_id'];
        
        // Obtener valores del perfil ideal
        $valoresSql = "SELECT pv.*, d.nombre as dimension_nombre
                       FROM perfiles_valores pv
                       JOIN dimensiones d ON pv.dimension_id = d.id
                       WHERE pv.perfil_id = $perfil_id";
        $valoresResult = $this->db->query($valoresSql);
        
        if (!$valoresResult) {
            return 0;
        }
        
        $valores = [];
        $ponderacionTotal = 0;
        
        while ($row = $valoresResult->fetch_assoc()) {
            $valores[$row['dimension_id']] = $row;
            $ponderacionTotal += $row['ponderacion'];
        }
        
        if (count($valores) === 0) {
            return 0;
        }
        
        // Obtener resultados del candidato para las dimensiones del perfil
        $match = 0;
        
        foreach ($valores as $dimension_id => $valor) {
            // Buscar el mejor resultado del candidato para esta dimensión
            $resultadoSql = "SELECT r.valor
                             FROM resultados r
                             JOIN sesiones_prueba s ON r.sesion_id = s.id
                             WHERE s.candidato_id = $candidato_id
                             AND r.dimension_id = $dimension_id
                             AND s.estado = 'completada'
                             ORDER BY s.fecha_fin DESC
                             LIMIT 1";
            $resultadoResult = $this->db->query($resultadoSql);
            
            if (!$resultadoResult || $resultadoResult->num_rows === 0) {
                continue; // No hay resultado para esta dimensión
            }
            
            $resultado = $resultadoResult->fetch_assoc()['valor'];
            
            // Calcular coincidencia para esta dimensión
            $coincidencia = $this->calculateDimensionMatch($resultado, $valor);
            
            // Sumar al total ponderado
            $match += $coincidencia * ($valor['ponderacion'] / $ponderacionTotal);
        }
        
        return round($match);
    }
    
    /**
     * Calcula la coincidencia de una dimensión
     */
    private function calculateDimensionMatch($valor, $perfil) {
        // Si el valor está dentro del rango ideal
        if ($valor >= $perfil['valor_min'] && $valor <= $perfil['valor_max']) {
            // Cuanto más cercano al valor ideal, mejor coincidencia
            $distancia = abs($valor - $perfil['valor_ideal']);
            $rangoTotal = $perfil['valor_max'] - $perfil['valor_min'];
            
            // Normalizar a escala 0-100 e invertir (menor distancia = mayor coincidencia)
            return 100 - (($distancia / $rangoTotal) * 100);
        }
        
        return 0; // Fuera del rango aceptable
    }
}
?>