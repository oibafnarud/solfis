<?php
/**
 * Clase para gestionar las pruebas psicométricas y evaluaciones de los candidatos
 */
class TestManager {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtiene las pruebas pendientes de un candidato
     * 
     * @param int $candidatoId ID del candidato
     * @return array Arreglo con las pruebas pendientes
     */
    public function getPendingTests($candidatoId) {
        $candidatoId = (int)$candidatoId;
        $pruebas = [];
        
        $sql = "SELECT p.*, pt.* FROM pruebas p
                INNER JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                LEFT JOIN (
                    SELECT * FROM pruebas_sesiones 
                    WHERE candidato_id = $candidatoId
                ) ps ON p.id = ps.prueba_id
                WHERE ps.id IS NULL
                AND p.activa = 1
                ORDER BY p.id DESC";
                
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pruebas[] = $row;
            }
        }
        
        return $pruebas;
    }
    
    /**
     * Obtiene las pruebas en progreso de un candidato
     * 
     * @param int $candidatoId ID del candidato
     * @return array Arreglo con las pruebas en progreso
     */
    public function getInProgressTests($candidatoId) {
        $candidatoId = (int)$candidatoId;
        $pruebas = [];
        
        $sql = "SELECT p.*, pt.*, ps.id as sesion_id, ps.estado, ps.fecha_inicio, ps.respuestas_count
                FROM pruebas_sesiones ps
                INNER JOIN pruebas p ON ps.prueba_id = p.id
                INNER JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                WHERE ps.candidato_id = $candidatoId
                AND ps.estado = 'en_progreso'
                ORDER BY ps.fecha_inicio DESC";
                
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pruebas[] = $row;
            }
        }
        
        return $pruebas;
    }
    
    /**
     * Obtiene las pruebas completadas de un candidato
     * 
     * @param int $candidatoId ID del candidato
     * @return array Arreglo con las pruebas completadas
     */
    public function getCompletedTests($candidatoId) {
        $candidatoId = (int)$candidatoId;
        $pruebas = [];
        
        $sql = "SELECT ps.*, ps.id as sesion_id, ps.estado, ps.fecha_inicio, ps.fecha_fin,
                       p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                       pt.nombre as tipo_nombre
                FROM pruebas_sesiones ps
                INNER JOIN pruebas p ON ps.prueba_id = p.id
                INNER JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                WHERE ps.candidato_id = $candidatoId
                AND ps.estado = 'completada'
                ORDER BY ps.fecha_fin DESC";
                
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pruebas[] = $row;
            }
        }
        
        return $pruebas;
    }
    
    /**
     * Obtiene una prueba por su ID
     * 
     * @param int $pruebaId ID de la prueba
     * @return array|null Datos de la prueba o null si no existe
     */
    public function getTestById($pruebaId) {
        $pruebaId = (int)$pruebaId;
        
        $sql = "SELECT p.*, pt.* 
                FROM pruebas p
                INNER JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                WHERE p.id = $pruebaId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Verifica si existe una sesión de prueba para un candidato y prueba
     * 
     * @param int $candidatoId ID del candidato
     * @param int $pruebaId ID de la prueba
     * @return array|null Datos de la sesión o null si no existe
     */
    public function checkExistingSession($candidatoId, $pruebaId) {
        $candidatoId = (int)$candidatoId;
        $pruebaId = (int)$pruebaId;
        
        $sql = "SELECT * FROM pruebas_sesiones 
                WHERE candidato_id = $candidatoId
                AND prueba_id = $pruebaId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtiene el número de pregunta actual para una sesión
     * 
     * @param int $sesionId ID de la sesión
     * @return int Número de pregunta actual
     */
    public function getCurrentQuestionNumber($sesionId) {
        $sesionId = (int)$sesionId;
        
        $sql = "SELECT COUNT(*) as count FROM pruebas_respuestas 
                WHERE sesion_id = $sesionId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['count'];
        }
        
        return 0;
    }
    
    /**
     * Obtiene las preguntas de una prueba
     * 
     * @param int $pruebaId ID de la prueba
     * @return array Arreglo con las preguntas
     */
    public function getTestQuestions($pruebaId) {
        $pruebaId = (int)$pruebaId;
        $preguntas = [];
        
        $sql = "SELECT * FROM pruebas_preguntas 
                WHERE prueba_id = $pruebaId 
                ORDER BY orden ASC";
                
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $preguntas[] = $row;
            }
        }
        
        return $preguntas;
    }
    
    /**
     * Obtiene las opciones de respuesta para una pregunta
     * 
     * @param int $preguntaId ID de la pregunta
     * @return array Arreglo con las opciones
     */
    public function getQuestionOptions($preguntaId) {
        $preguntaId = (int)$preguntaId;
        $opciones = [];
        
        $sql = "SELECT * FROM pruebas_opciones 
                WHERE pregunta_id = $preguntaId 
                ORDER BY orden ASC";
                
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $opciones[] = $row;
            }
        }
        
        return $opciones;
    }
    
    /**
     * Obtiene una respuesta para una pregunta en una sesión
     * 
     * @param int $sesionId ID de la sesión
     * @param int $preguntaId ID de la pregunta
     * @return array|null Datos de la respuesta o null si no existe
     */
    public function getAnswer($sesionId, $preguntaId) {
        $sesionId = (int)$sesionId;
        $preguntaId = (int)$preguntaId;
        
        $sql = "SELECT * FROM pruebas_respuestas 
                WHERE sesion_id = $sesionId
                AND pregunta_id = $preguntaId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Crea una nueva sesión de prueba
     * 
     * @param int $candidatoId ID del candidato
     * @param int $pruebaId ID de la prueba
     * @return int ID de la sesión creada
     */
    public function createSession($candidatoId, $pruebaId) {
        $candidatoId = (int)$candidatoId;
        $pruebaId = (int)$pruebaId;
        
        $sql = "INSERT INTO pruebas_sesiones 
                (candidato_id, prueba_id, estado, fecha_inicio, respuestas_count) 
                VALUES ($candidatoId, $pruebaId, 'en_progreso', NOW(), 0)";
                
        $this->db->query($sql);
        return $this->db->insert_id;
    }
    
    /**
     * Guarda una respuesta
     * 
     * @param int $sesionId ID de la sesión
     * @param int $preguntaId ID de la pregunta
     * @param mixed $respuesta Respuesta del candidato
     * @return bool Éxito de la operación
     */
    public function saveAnswer($sesionId, $preguntaId, $respuesta) {
        $sesionId = (int)$sesionId;
        $preguntaId = (int)$preguntaId;
        
        // Verificar el tipo de respuesta y procesarla adecuadamente
        if (is_int($respuesta)) {
            // Para opciones múltiples, verdadero/falso o escala
            $sql = "INSERT INTO pruebas_respuestas 
                    (sesion_id, pregunta_id, opcion_id, valor_escala, fecha) 
                    VALUES ($sesionId, $preguntaId, $respuesta, $respuesta, NOW())
                    ON DUPLICATE KEY UPDATE 
                    opcion_id = $respuesta,
                    valor_escala = $respuesta,
                    fecha = NOW()";
        } else {
            // Para respuestas abiertas
            $respuesta = $this->db->escape($respuesta);
            $sql = "INSERT INTO pruebas_respuestas 
                    (sesion_id, pregunta_id, texto_respuesta, fecha) 
                    VALUES ($sesionId, $preguntaId, '$respuesta', NOW())
                    ON DUPLICATE KEY UPDATE 
                    texto_respuesta = '$respuesta',
                    fecha = NOW()";
        }
        
        $result = $this->db->query($sql);
        
        if ($result) {
            // Actualizar contador de respuestas en la sesión
            $updateSql = "UPDATE pruebas_sesiones 
                          SET respuestas_count = (
                              SELECT COUNT(*) FROM pruebas_respuestas 
                              WHERE sesion_id = $sesionId
                          ) 
                          WHERE id = $sesionId";
            $this->db->query($updateSql);
            return true;
        }
        
        return false;
    }
    
    /**
     * Completa una sesión de prueba
     * 
     * @param int $sesionId ID de la sesión
     * @return bool Éxito de la operación
     */
/**
 * Completa una sesión de prueba sin llamar al método problemático processResults
 * 
 * @param int $sesionId ID de la sesión
 * @return bool Éxito de la operación
 */
public function completeSession($sesionId) {
    $sesionId = (int)$sesionId;
    
    try {
        // Obtener respuestas para esta sesión
        $sql = "SELECT r.*, o.valor
                FROM pruebas_respuestas r
                LEFT JOIN pruebas_opciones o ON r.opcion_id = o.id
                WHERE r.sesion_id = $sesionId";
                
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Error al obtener respuestas para sesión $sesionId: " . $this->db->error);
            // Seguimos adelante para al menos marcar la sesión como completada
        }
        
        // Calcular un puntaje básico (si hay respuestas)
        $puntajeTotal = 0;
        $puntajeMaximo = 0;
        $respuestas = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $respuestas[] = $row;
                // Si hay un valor numérico, sumarlo al puntaje
                if (isset($row['valor']) && is_numeric($row['valor'])) {
                    $puntajeTotal += $row['valor'];
                    $puntajeMaximo += 5; // Asumiendo escala de 0-5
                }
            }
        }
        
        // Calcular porcentaje de resultado
        $porcentaje = $puntajeMaximo > 0 ? round(($puntajeTotal / $puntajeMaximo) * 100) : 0;
        
        // Actualizar la sesión directamente
        $resultadosJson = !empty($respuestas) ? json_encode($respuestas) : '[]';
        
        // Escapar cadenas para SQL
        $resultadosJson = str_replace("'", "''", $resultadosJson); // Escape básico de comillas
        
        $sql = "UPDATE pruebas_sesiones 
                SET estado = 'completada', 
                    fecha_fin = NOW(),
                    resultado_global = $porcentaje,
                    resultados_json = '$resultadosJson'
                WHERE id = $sesionId";
                
        $this->db->query($sql);
        
        return true;
    } 
    catch (Exception $e) {
        error_log("Error al completar sesión $sesionId: " . $e->getMessage());
        // Intento básico de marcar la sesión como completada sin más análisis
        $sql = "UPDATE pruebas_sesiones SET estado = 'completada', fecha_fin = NOW() WHERE id = $sesionId";
        $this->db->query($sql);
        return false;
    }
}
    
    /**
     * Obtiene los resultados de una sesión
     * 
     * @param int $sesionId ID de la sesión
     * @return array Arreglo con los resultados
     */
    public function getSessionResults($sesionId) {
        $sesionId = (int)$sesionId;
        $resultados = [];
        
        // Obtener datos de la sesión y la prueba
        $sql = "SELECT ps.*, p.*, pt.*
                FROM pruebas_sesiones ps
                INNER JOIN pruebas p ON ps.prueba_id = p.id
                INNER JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                WHERE ps.id = $sesionId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $resultados['sesion'] = $result->fetch_assoc();
            
            // Obtener respuestas
            $sql = "SELECT pr.*, pp.texto as pregunta_texto, pp.tipo as pregunta_tipo,
                           po.texto as opcion_texto
                    FROM pruebas_respuestas pr
                    INNER JOIN pruebas_preguntas pp ON pr.pregunta_id = pp.id
                    LEFT JOIN pruebas_opciones po ON pr.opcion_id = po.id
                    WHERE pr.sesion_id = $sesionId
                    ORDER BY pp.orden ASC";
                    
            $result = $this->db->query($sql);
            
            if ($result) {
                $resultados['respuestas'] = [];
                while ($row = $result->fetch_assoc()) {
                    $resultados['respuestas'][] = $row;
                }
            }
            
            return $resultados;
        }
        
        return null;
    }
	
	/**
 * Busca y reemplaza este método en tu archivo TestManager.php
 * Este método procesa los resultados de una sesión de prueba sin la referencia a dimension_id
 */
public function processResults($sesionId) {
    $sesionId = (int)$sesionId;
    $resultados = [];
    
    try {
        // Obtener datos de la sesión
        $sql = "SELECT ps.*, p.id as prueba_id, p.titulo as prueba_titulo, pt.nombre as tipo_nombre
                FROM pruebas_sesiones ps
                INNER JOIN pruebas p ON ps.prueba_id = p.id
                LEFT JOIN pruebas_tipos pt ON p.tipo_id = pt.id
                WHERE ps.id = $sesionId";
                
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows == 0) {
            throw new Exception("No se encontró la sesión de prueba");
        }
        
        $sesion = $result->fetch_assoc();
        $pruebaId = $sesion['prueba_id'];
        
        // Obtener respuestas
        // NOTA: Esta es la consulta que causa el error - eliminamos la referencia a dimension_id
        $sql = "SELECT r.*, o.valor, o.texto as opcion_texto, p.texto as pregunta_texto
                FROM pruebas_respuestas r
                LEFT JOIN pruebas_opciones o ON r.opcion_id = o.id
                INNER JOIN pruebas_preguntas p ON r.pregunta_id = p.id
                WHERE r.sesion_id = $sesionId";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            throw new Exception("Error al obtener las respuestas: " . $this->db->error);
        }
        
        $respuestas = [];
        while ($row = $result->fetch_assoc()) {
            $respuestas[] = $row;
        }
        
        // Calcular puntaje total
        $puntajeTotal = 0;
        $puntajeMaximo = 0;
        
        foreach ($respuestas as $respuesta) {
            // Si hay un valor numérico, sumarlo al puntaje
            if (isset($respuesta['valor']) && is_numeric($respuesta['valor'])) {
                $puntajeTotal += $respuesta['valor'];
                $puntajeMaximo += 5; // Asumiendo escala de 0-5, ajusta según sea necesario
            }
        }
        
        // Calcular porcentaje de resultado
        $porcentaje = $puntajeMaximo > 0 ? round(($puntajeTotal / $puntajeMaximo) * 100) : 0;
        
        // Guardar resultados
        $sql = "UPDATE pruebas_sesiones SET 
                resultado_global = $porcentaje,
                resultados_json = '" . $this->db->escape(json_encode($respuestas)) . "'
                WHERE id = $sesionId";
                
        $this->db->query($sql);
        
        // Devolver resultados
        $resultados = [
            'sesion' => $sesion,
            'respuestas' => $respuestas,
            'puntaje_total' => $puntajeTotal,
            'puntaje_maximo' => $puntajeMaximo,
            'porcentaje' => $porcentaje
        ];
        
        return $resultados;
        
    } catch (Exception $e) {
        // Registrar error en log
        error_log("Error al procesar resultados: " . $e->getMessage());
        
        // Devolver resultado mínimo
        return [
            'sesion_id' => $sesionId,
            'error' => $e->getMessage()
        ];
    }
}
}
?>