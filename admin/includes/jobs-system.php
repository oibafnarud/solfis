/**
 * Clase InterviewScheduler - Gestiona la programación de entrevistas
 */
class InterviewScheduler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Programar una nueva entrevista
     * 
     * @param array $data Datos de la entrevista
     * @return int|false ID de la entrevista o false en caso de error
     */
    public function scheduleInterview($data) {
        $applicationId = (int)$data['application_id'];
        $interviewerId = (int)$data['interviewer_id'];
        $stageId = (int)$data['stage_id'];
        $dateTime = $this->db->escape($data['date_time']);
        $duration = isset($data['duration']) ? (int)$data['duration'] : 60;
        $location = isset($data['location']) ? $this->db->escape($data['location']) : null;
        $meetingLink = isset($data['meeting_link']) ? $this->db->escape($data['meeting_link']) : null;
        $notes = isset($data['notes']) ? $this->db->escape($data['notes']) : null;
        $status = isset($data['status']) ? $this->db->escape($data['status']) : 'scheduled';
        
        $sql = "INSERT INTO scheduled_interviews (
                    application_id, interviewer_id, stage_id, date_time, 
                    duration, location, meeting_link, notes, status, 
                    created_at, updated_at
                ) VALUES (
                    $applicationId, $interviewerId, $stageId, '$dateTime', 
                    $duration, ";
                    
        $sql .= $location ? "'$location'" : "NULL";
        $sql .= ", ";
        $sql .= $meetingLink ? "'$meetingLink'" : "NULL";
        $sql .= ", ";
        $sql .= $notes ? "'$notes'" : "NULL";
        $sql .= ", '$status', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            $interviewId = $this->db->lastInsertId();
            
            // Notificar al candidato y al entrevistador
            $this->sendInterviewNotifications($interviewId);
            
            return $interviewId;
        }
        
        return false;
    }
    
    /**
     * Enviar notificaciones de entrevista
     * 
     * @param int $interviewId ID de la entrevista
     * @return bool Éxito de la operación
     */
    private function sendInterviewNotifications($interviewId) {
        $id = (int)$interviewId;
        
        $sql = "SELECT i.*, 
                       a.candidate_id, 
                       v.title as vacancy_title,
                       s.name as stage_name,
                       c.user_id as candidate_user_id,
                       CONCAT(u1.name) as interviewer_name,
                       CONCAT(u2.name) as candidate_name,
                       u2.email as candidate_email
                FROM scheduled_interviews i
                JOIN application_stages s ON i.stage_id = s.id
                JOIN job_applications a ON i.application_id = a.id
                JOIN job_vacancies v ON a.vacancy_id = v.id
                JOIN candidates c ON a.candidate_id = c.id
                JOIN users u1 ON i.interviewer_id = u1.id
                JOIN users u2 ON c.user_id = u2.id
                WHERE i.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $interview = $result->fetch_assoc();
            
            // Crear notificación para el candidato
            $notification = new Notification();
            $candidateNotification = [
                'user_id' => $interview['candidate_user_id'],
                'type' => 'interview_scheduled',
                'title' => 'Entrevista programada',
                'message' => 'Se ha programado una entrevista para la vacante ' . $interview['vacancy_title'] . ' el ' . 
                             date('d/m/Y', strtotime($interview['date_time'])) . ' a las ' . 
                             date('H:i', strtotime($interview['date_time'])),
                'link' => '/vacantes/perfil/detalle-aplicacion.php?id=' . $interview['application_id']
            ];
            
            $notification->createNotification($candidateNotification);
            
            // Enviar email al candidato (implementación depende del sistema de email)
            // $emailSender = new EmailSender();
            // ...
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Actualizar una entrevista existente
     * 
     * @param int $id ID de la entrevista
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateInterview($id, $data) {
        $id = (int)$id;
        
        // Construir consulta de actualización
        $updateFields = [];
        
        if (isset($data['interviewer_id'])) {
            $interviewerId = (int)$data['interviewer_id'];
            $updateFields[] = "interviewer_id = $interviewerId";
        }
        
        if (isset($data['date_time'])) {
            $dateTime = $this->db->escape($data['date_time']);
            $updateFields[] = "date_time = '$dateTime'";
        }
        
        if (isset($data['duration'])) {
            $duration = (int)$data['duration'];
            $updateFields[] = "duration = $duration";
        }
        
        if (isset($data['location'])) {
            if (empty($data['location'])) {
                $updateFields[] = "location = NULL";
            } else {
                $location = $this->db->escape($data['location']);
                $updateFields[] = "location = '$location'";
            }
        }
        
        if (isset($data['meeting_link'])) {
            if (empty($data['meeting_link'])) {
                $updateFields[] = "meeting_link = NULL";
            } else {
                $meetingLink = $this->db->escape($data['meeting_link']);
                $updateFields[] = "meeting_link = '$meetingLink'";
            }
        }
        
        if (isset($data['notes'])) {
            if (empty($data['notes'])) {
                $updateFields[] = "notes = NULL";
            } else {
                $notes = $this->db->escape($data['notes']);
                $updateFields[] = "notes = '$notes'";
            }
        }
        
        if (isset($data['status'])) {
            $status = $this->db->escape($data['status']);
            $updateFields[] = "status = '$status'";
        }
        
        if (isset($data['feedback'])) {
            if (empty($data['feedback'])) {
                $updateFields[] = "feedback = NULL";
            } else {
                $feedback = $this->db->escape($data['feedback']);
                $updateFields[] = "feedback = '$feedback'";
            }
        }
        
        if (empty($updateFields)) {
            return true; // No hay nada que actualizar
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        $sql = "UPDATE scheduled_interviews SET " . implode(", ", $updateFields) . " WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && isset($data['status']) && ($data['status'] === 'rescheduled' || isset($data['date_time']))) {
            // Notificar al candidato sobre el cambio
            $this->sendInterviewNotifications($id);
        }
        
        return $result;
    }
    
    /**
     * Cancelar una entrevista
     * 
     * @param int $id ID de la entrevista
     * @param string $reason Motivo de la cancelación
     * @return bool Éxito de la operación
     */
    public function cancelInterview($id, $reason = null) {
        $id = (int)$id;
        $reasonStr = $reason ? $this->db->escape($reason) : null;
        
        $sql = "UPDATE scheduled_interviews SET 
                status = 'cancelled', 
                notes = " . ($reasonStr ? "CONCAT(IFNULL(notes, ''), '\n\nCancelado: $reasonStr')" : "CONCAT(IFNULL(notes, ''), '\n\nCancelado')") . ",
                updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Obtener entrevistas programadas para un candidato
     * 
     * @param int $candidateId ID del candidato
     * @return array Lista de entrevistas
     */
    public function getInterviewsByCandidate($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT i.*, 
                       v.title as vacancy_title, 
                       v.department as vacancy_department,
                       u.name as interviewer_name,
                       s.name as stage_name
                FROM scheduled_interviews i
                JOIN application_stages st ON i.stage_id = st.id
                JOIN job_applications a ON i.application_id = a.id
                JOIN job_vacancies v ON a.vacancy_id = v.id
                JOIN recruitment_stages s ON st.stage_id = s.id
                JOIN users u ON i.interviewer_id = u.id
                WHERE a.candidate_id = $candidateId
                ORDER BY i.date_time DESC";
                
        $result = $this->db->query($sql);
        $interviews = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $interviews[] = $row;
        }
        
        return $interviews;
    }
    
    /**
     * Obtener entrevistas programadas para un entrevistador
     * 
     * @param int $interviewerId ID del entrevistador
     * @param string $timeframe Periodo de tiempo ('today', 'week', 'upcoming', 'past', 'all')
     * @return array Lista de entrevistas
     */
    public function getInterviewsByInterviewer($interviewerId, $timeframe = 'upcoming') {
        $interviewerId = (int)$interviewerId;
        
        $sql = "SELECT i.*, 
                       v.title as vacancy_title, 
                       v.department as vacancy_department,
                       c.name as candidate_name,
                       s.name as stage_name
                FROM scheduled_interviews i
                JOIN application_stages st ON i.stage_id = st.id
                JOIN job_applications a ON i.application_id = a.id
                JOIN job_vacancies v ON a.vacancy_id = v.id
                JOIN recruitment_stages s ON st.stage_id = s.id
                JOIN candidates ca ON a.candidate_id = ca.id
                JOIN users c ON ca.user_id = c.id
                WHERE i.interviewer_id = $interviewerId";
                
        // Filtrar por periodo de tiempo
        switch ($timeframe) {
            case 'today':
                $sql .= " AND DATE(i.date_time) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND YEARWEEK(i.date_time, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'upcoming':
                $sql .= " AND i.date_time >= NOW() AND i.status IN ('scheduled', 'rescheduled')";
                break;
            case 'past':
                $sql .= " AND i.date_time < NOW()";
                break;
            // 'all' no requiere filtro adicional
        }
                
        $sql .= " ORDER BY i.date_time";
                
        $result = $this->db->query($sql);
        $interviews = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $interviews[] = $row;
        }
        
        return $interviews;
    }
    
    /**
     * Obtener una entrevista por su ID
     * 
     * @param int $id ID de la entrevista
     * @return array|null Datos de la entrevista o null si no existe
     */
    public function getInterviewById($id) {
        $id = (int)$id;
        
        $sql = "SELECT i.*, 
                       v.title as vacancy_title, 
                       v.department as vacancy_department,
                       a.candidate_id,
                       a.vacancy_id,
                       u1.name as interviewer_name,
                       u2.name as candidate_name,
                       u2.email as candidate_email,
                       s.name as stage_name
                FROM scheduled_interviews i
                JOIN application_stages st ON i.stage_id = st.id
                JOIN job_applications a ON i.application_id = a.id
                JOIN job_vacancies v ON a.vacancy_id = v.id
                JOIN recruitment_stages s ON st.stage_id = s.id
                JOIN users u1 ON i.interviewer_id = u1.id
                JOIN candidates c ON a.candidate_id = c.id
                JOIN users u2 ON c.user_id = u2.id
                WHERE i.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}

/**
 * Clase Notification - Gestiona las notificaciones del sistema
 */
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear una nueva notificación
     * 
     * @param array $data Datos de la notificación
     * @return int|false ID de la notificación o false en caso de error
     */
    public function createNotification($data) {
        $userId = (int)$data['user_id'];
        $type = $this->db->escape($data['type']);
        $title = $this->db->escape($data['title']);
        $message = $this->db->escape($data['message']);
        $link = isset($data['link']) ? $this->db->escape($data['link']) : null;
        
        $sql = "INSERT INTO notifications (
                    user_id, type, title, message, link, is_read, created_at
                ) VALUES (
                    $userId, '$type', '$title', '$message', ";
                    
        $sql .= $link ? "'$link'" : "NULL";
        $sql .= ", 0, NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener notificaciones para un usuario
     * 
     * @param int $userId ID del usuario
     * @param bool $onlyUnread Mostrar solo notificaciones no leídas
     * @param int $limit Límite de notificaciones a obtener
     * @return array Lista de notificaciones
     */
    public function getUserNotifications($userId, $onlyUnread = false, $limit = 10) {
        $userId = (int)$userId;
        $limit = (int)$limit;
        
        $sql = "SELECT * FROM notifications 
                WHERE user_id = $userId";
                
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
                
        $result = $this->db->query($sql);
        $notifications = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Marcar una notificación como leída
     * 
     * @param int $id ID de la notificación
     * @return bool Éxito de la operación
     */
    public function markAsRead($id) {
        $id = (int)$id;
        
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Marcar todas las notificaciones de un usuario como leídas
     * 
     * @param int $userId ID del usuario
     * @return bool Éxito de la operación
     */
    public function markAllAsRead($userId) {
        $userId = (int)$userId;
        
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $userId AND is_read = 0";
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una notificación
     * 
     * @param int $id ID de la notificación
     * @return bool Éxito de la operación
     */
    public function deleteNotification($id) {
        $id = (int)$id;
        
        $sql = "DELETE FROM notifications WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Contar notificaciones no leídas para un usuario
     * 
     * @param int $userId ID del usuario
     * @return int Número de notificaciones no leídas
     */
    public function countUnreadNotifications($userId) {
        $userId = (int)$userId;
        
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = $userId AND is_read = 0";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return (int)$result->fetch_assoc()['count'];
        }
        
        return 0;
    }
}

/**
 * Clase CandidateEvaluation - Gestiona las evaluaciones de candidatos
 */
class CandidateEvaluation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear una nueva evaluación
     * 
     * @param array $data Datos de la evaluación
     * @return int|false ID de la evaluación o false en caso de error
     */
    public function createEvaluation($data) {
        $applicationId = (int)$data['application_id'];
        $stageId = (int)$data['stage_id'];
        $evaluatorId = (int)$data['evaluator_id'];
        $score = isset($data['score']) && is_numeric($data['score']) ? (float)$data['score'] : 'NULL';
        $strengths = isset($data['strengths']) ? $this->db->escape($data['strengths']) : null;
        $weaknesses = isset($data['weaknesses']) ? $this->db->escape($data['weaknesses']) : null;
        $notes = isset($data['notes']) ? $this->db->escape($data['notes']) : null;
        $recommendation = $this->db->escape($data['recommendation']);
        
        $sql = "INSERT INTO candidate_evaluations (
                    application_id, stage_id, evaluator_id, score, 
                    strengths, weaknesses, notes, recommendation, 
                    created_at, updated_at
                ) VALUES (
                    $applicationId, $stageId, $evaluatorId, " . ($score !== 'NULL' ? $score : 'NULL') . ", ";
                    
        $sql .= $strengths ? "'$strengths'" : "NULL";
        $sql .= ", ";
        $sql .= $weaknesses ? "'$weaknesses'" : "NULL";
        $sql .= ", ";
        $sql .= $notes ? "'$notes'" : "NULL";
        $sql .= ", '$recommendation', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            $evaluationId = $this->db->lastInsertId();
            
            // Guardar las puntuaciones por criterio si existen
            if (isset($data['criteria_scores']) && is_array($data['criteria_scores'])) {
                foreach ($data['criteria_scores'] as $criteriaId => $criteriaData) {
                    $this->saveCriteriaScore($evaluationId, $criteriaId, $criteriaData['score'], $criteriaData['comment'] ?? null);
                }
            }
            
            return $evaluationId;
        }
        
        return false;
    }
    
    /**
     * Guardar la puntuación de un criterio de evaluación
     * 
     * @param int $evaluationId ID de la evaluación
     * @param int $criteriaId ID del criterio
     * @param float $score Puntuación
     * @param string $comment Comentario opcional
     * @return bool Éxito de la operación
     */
    private function saveCriteriaScore($evaluationId, $criteriaId, $score, $comment = null) {
        $evaluationId = (int)$evaluationId;
        $criteriaId = (int)$criteriaId;
        $score = (float)$score;
        $comment = $comment ? $this->db->escape($comment) : null;
        
        $sql = "INSERT INTO evaluation_scores (
                    evaluation_id, criteria_id, score, comment, created_at
                ) VALUES (
                    $evaluationId, $criteriaId, $score, ";
                    
        $sql .= $comment ? "'$comment'" : "NULL";
        $sql .= ", NOW())";
                
        return $this->db->query($sql);
    }
    
    /**
     * Obtener una evaluación por su ID
     * 
     * @param int $id ID de la evaluación
     * @return array|null Datos de la evaluación o null si no existe
     */
    public function getEvaluationById($id) {
        $id = (int)$id;
        
        $sql = "SELECT e.*, 
                       u.name as evaluator_name,
                       s.name as stage_name,
                       a.candidate_id,
                       a.vacancy_id,
                       v.title as vacancy_title,
                       c.user_id as candidate_user_id,
                       cu.name as candidate_name
                FROM candidate_evaluations e
                JOIN users u ON e.evaluator_id = u.id
                JOIN application_stages s ON e.stage_id = s.id
                JOIN job_applications a ON e.application_id = a.id
                JOIN job_vacancies v ON a.vacancy_id = v.id
                JOIN candidates c ON a.candidate_id = c.id
                JOIN users cu ON c.user_id = cu.id
                WHERE e.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $evaluation = $result->fetch_assoc();
            
            // Obtener las puntuaciones por criterio
            $evaluation['criteria_scores'] = $this->getEvaluationCriteriaScores($id);
            
            return $evaluation;
        }
        
        return null;
    }
    
    /**
     * Obtener las puntuaciones por criterio de una evaluación
     * 
     * @param int $evaluationId ID de la evaluación
     * @return array Lista de puntuaciones por criterio
     */
    public function getEvaluationCriteriaScores($evaluationId) {
        $evaluationId = (int)$evaluationId;
        
        $sql = "SELECT s.*, c.name as criteria_name, c.description as criteria_description, c.weight
                FROM evaluation_scores s
                JOIN evaluation_criteria c ON s.criteria_id = c.id
                WHERE s.evaluation_id = $evaluationId
                ORDER BY c.name";
                
        $result = $this->db->query($sql);
        $scores = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $scores[] = $row;
        }
        
        return $scores;
    }
    
    /**
     * Obtener evaluaciones para una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Lista de evaluaciones
     */
    public function getEvaluationsByApplication($applicationId) {
        $applicationId = (int)$applicationId;
        
        $sql = "SELECT e.*, 
                       u.name as evaluator_name,
                       s.name as stage_name
                FROM candidate_evaluations e
                JOIN users u ON e.evaluator_id = u.id
                JOIN application_stages s ON e.stage_id = s.id
                WHERE e.application_id = $applicationId
                ORDER BY e.created_at DESC";
                
        $result = $this->db->query($sql);
        $evaluations = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $evaluations[] = $row;
        }
        
        return $evaluations;
    }
    
    /**
     * Actualizar una evaluación existente
     * 
     * @param int $id ID de la evaluación
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateEvaluation($id, $data) {
        $id = (int)$id;
        
        // Construir consulta de actualización
        $updateFields = [];
        
        if (isset($data['score'])) {
            $score = is_numeric($data['score']) ? (float)$data['score'] : 'NULL';
            $updateFields[] = "score = " . ($score !== 'NULL' ? $score : 'NULL');
        }
        
        if (isset($data['strengths'])) {
            if (empty($data['strengths'])) {
                $updateFields[] = "strengths = NULL";
            } else {
                $strengths = $this->db->escape($data['strengths']);
                $updateFields[] = "strengths = '$strengths'";
            }
        }
        
        if (isset($data['weaknesses'])) {
            if (empty($data['weaknesses'])) {
                $updateFields[] = "weaknesses = NULL";
            } else {
                $weaknesses = $this->db->escape($data['weaknesses']);
                $updateFields[] = "weaknesses = '$weaknesses'";
            }
        }
        
        if (isset($data['notes'])) {
            if (empty($data['notes'])) {
                $updateFields[] = "notes = NULL";
            } else {
                $notes = $this->db->escape($data['notes']);
                $updateFields[] = "notes = '$notes'";
            }
        }
        
        if (isset($data['recommendation'])) {
            $recommendation = $this->db->escape($data['recommendation']);
            $updateFields[] = "recommendation = '$recommendation'";
        }
        
        if (empty($updateFields)) {
            return true; // No hay nada que actualizar
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        $sql = "UPDATE candidate_evaluations SET " . implode(", ", $updateFields) . " WHERE id = $id";
        $result = $this->db->query($sql);
        
        // Actualizar las puntuaciones por criterio si existen
        if ($result && isset($data['criteria_scores']) && is_array($data['criteria_scores'])) {
            // Primero eliminar las puntuaciones existentes
            $this->deleteEvaluationCriteriaScores($id);
            
            // Luego crear las nuevas
            foreach ($data['criteria_scores'] as $criteriaId => $criteriaData) {
                $this->saveCriteriaScore($id, $criteriaId, $criteriaData['score'], $criteriaData['comment'] ?? null);
            }
        }
        
        return $result;
    }
    
    /**
     * Eliminar las puntuaciones por criterio de una evaluación
     * 
     * @param int $evaluationId ID de la evaluación
     * @return bool Éxito de la operación
     */
    private function deleteEvaluationCriteriaScores($evaluationId) {
        $evaluationId = (int)$evaluationId;
        
        $sql = "DELETE FROM evaluation_scores WHERE evaluation_id = $evaluationId";
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una evaluación
     * 
     * @param int $id ID de la evaluación
     * @return bool Éxito de la operación
     */
    public function deleteEvaluation($id) {
        $id = (int)$id;
        
        // Primero eliminar las puntuaciones por criterio
        $this->deleteEvaluationCriteriaScores($id);
        
        // Luego eliminar la evaluación
        $sql = "DELETE FROM candidate_evaluations WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener criterios de evaluación disponibles
     * 
     * @return array Lista de criterios
     */
    public function getEvaluationCriteria() {
        $sql = "SELECT * FROM evaluation_criteria ORDER BY name";
        $result = $this->db->query($sql);
        $criteria = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $criteria[] = $row;
        }
        
        return $<?php
/**
 * Sistema de Vacantes SolFis
 * 
 * Este archivo contiene todas las clases necesarias para el funcionamiento
 * del sistema de vacantes y reclutamiento de SolFis.
 */

// Asegurarse de que el sistema base esté incluido
if (!class_exists('Database')) {
    require_once 'blog-system.php';
}

/**
 * Clase JobVacancy - Gestiona las vacantes de empleo
 */
class JobVacancy {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las vacantes con paginación y filtros opcionales
     * 
     * @param int $page Número de página
     * @param int $per_page Elementos por página
     * @param array $filters Filtros opcionales (category_id, location, work_mode, search, status)
     * @return array Vacantes, total y paginación
     */
    public function getVacancies($page = 1, $per_page = 10, $filters = []) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
                FROM job_vacancies v 
                LEFT JOIN job_categories c ON v.category_id = c.id 
                LEFT JOIN users u ON v.created_by = u.id 
                WHERE 1=1";
        
        // Aplicar filtros
        if (isset($filters['category_id']) && !empty($filters['category_id'])) {
            $categoryId = (int)$filters['category_id'];
            $sql .= " AND v.category_id = $categoryId";
        }
        
        if (isset($filters['location']) && !empty($filters['location'])) {
            $location = $this->db->escape($filters['location']);
            $sql .= " AND v.location = '$location'";
        }
        
        if (isset($filters['work_mode']) && !empty($filters['work_mode'])) {
            $workMode = $this->db->escape($filters['work_mode']);
            $sql .= " AND v.work_mode = '$workMode'";
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->escape($filters['search']);
            $sql .= " AND (v.title LIKE '%$search%' OR v.description LIKE '%$search%' OR v.requirements LIKE '%$search%' OR v.responsibilities LIKE '%$search%' OR v.department LIKE '%$search%')";
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $status = $this->db->escape($filters['status']);
            $sql .= " AND v.status = '$status'";
        } else {
            // Si no se especifica un estado, solo mostrar las publicadas
            $sql .= " AND v.status = 'published'";
        }
        
        // Añadir condición para no mostrar vacantes expiradas
        if (!isset($filters['include_expired']) || !$filters['include_expired']) {
            $sql .= " AND (v.expires_at IS NULL OR v.expires_at > NOW())";
        }
        
        // Ordenar resultados (destacadas primero, luego por fecha de publicación)
        $sql .= " ORDER BY v.featured DESC, v.published_at DESC LIMIT $offset, $per_page";
        
        $result = $this->db->query($sql);
        $vacancies = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $vacancies[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM job_vacancies v WHERE 1=1";
        
        // Aplicar los mismos filtros al conteo
        if (isset($filters['category_id']) && !empty($filters['category_id'])) {
            $categoryId = (int)$filters['category_id'];
            $countSql .= " AND v.category_id = $categoryId";
        }
        
        if (isset($filters['location']) && !empty($filters['location'])) {
            $location = $this->db->escape($filters['location']);
            $countSql .= " AND v.location = '$location'";
        }
        
        if (isset($filters['work_mode']) && !empty($filters['work_mode'])) {
            $workMode = $this->db->escape($filters['work_mode']);
            $countSql .= " AND v.work_mode = '$workMode'";
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->escape($filters['search']);
            $countSql .= " AND (v.title LIKE '%$search%' OR v.description LIKE '%$search%' OR v.requirements LIKE '%$search%' OR v.responsibilities LIKE '%$search%' OR v.department LIKE '%$search%')";
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $status = $this->db->escape($filters['status']);
            $countSql .= " AND v.status = '$status'";
        } else {
            // Si no se especifica un estado, solo contar las publicadas
            $countSql .= " AND v.status = 'published'";
        }
        
        // Añadir condición para no contar vacantes expiradas
        if (!isset($filters['include_expired']) || !$filters['include_expired']) {
            $countSql .= " AND (v.expires_at IS NULL OR v.expires_at > NOW())";
        }
        
        $countResult = $this->db->query($countSql);
        $totalVacancies = 0;
        
        if ($countResult && $countResult->num_rows > 0) {
            $totalVacancies = $countResult->fetch_assoc()['total'];
        }
        
        return [
            'vacancies' => $vacancies,
            'total' => $totalVacancies,
            'pages' => ceil($totalVacancies / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener una vacante por su ID
     * 
     * @param int $id ID de la vacante
     * @return array|null Datos de la vacante o null si no existe
     */
    public function getVacancyById($id) {
        $id = (int)$id;
        
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
                FROM job_vacancies v 
                LEFT JOIN job_categories c ON v.category_id = c.id 
                LEFT JOIN users u ON v.created_by = u.id 
                WHERE v.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener vacantes relacionadas (misma categoría)
     * 
     * @param int $vacancyId ID de la vacante actual (para excluirla)
     * @param int $categoryId ID de la categoría
     * @param int $limit Número máximo de vacantes a retornar
     * @return array Vacantes relacionadas
     */
    public function getRelatedVacancies($vacancyId, $categoryId, $limit = 3) {
        $vacancyId = (int)$vacancyId;
        $categoryId = (int)$categoryId;
        $limit = (int)$limit;
        
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM job_vacancies v 
                LEFT JOIN job_categories c ON v.category_id = c.id 
                WHERE v.id != $vacancyId AND v.category_id = $categoryId AND v.status = 'published' 
                AND (v.expires_at IS NULL OR v.expires_at > NOW())
                ORDER BY v.featured DESC, v.published_at DESC 
                LIMIT $limit";
                
        $result = $this->db->query($sql);
        $vacancies = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $vacancies[] = $row;
        }
        
        return $vacancies;
    }
    
    /**
     * Crear una nueva vacante
     * 
     * @param array $data Datos de la vacante
     * @return int|false ID de la nueva vacante o false en caso de error
     */
    public function createVacancy($data) {
        // Escapar datos
        $title = $this->db->escape($data['title']);
        $department = $this->db->escape($data['department']);
        $location = $this->db->escape($data['location']);
        $workMode = $this->db->escape($data['work_mode']);
        $description = $this->db->escape($data['description']);
        $requirements = $this->db->escape($data['requirements']);
        $responsibilities = $this->db->escape($data['responsibilities']);
        $benefits = isset($data['benefits']) ? $this->db->escape($data['benefits']) : '';
        $salaryMin = isset($data['salary_min']) && !empty($data['salary_min']) ? (float)$data['salary_min'] : 'NULL';
        $salaryMax = isset($data['salary_max']) && !empty($data['salary_max']) ? (float)$data['salary_max'] : 'NULL';
        $showSalary = isset($data['show_salary']) && $data['show_salary'] ? 1 : 0;
        $categoryId = (int)$data['category_id'];
        $status = $this->db->escape($data['status']);
        $featured = isset($data['featured']) && $data['featured'] ? 1 : 0;
        $createdBy = (int)$data['created_by'];
        
        // Formatear fechas
        $publishedAt = $status === 'published' ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';
        $expiresAt = isset($data['expires_at']) && !empty($data['expires_at']) ? "'" . date('Y-m-d H:i:s', strtotime($data['expires_at'])) . "'" : 'NULL';
        
        $sql = "INSERT INTO job_vacancies (
                    title, department, location, work_mode, description, 
                    requirements, responsibilities, benefits, 
                    salary_min, salary_max, show_salary, 
                    category_id, status, featured, 
                    published_at, expires_at, 
                    created_by, created_at, updated_at
                ) VALUES (
                    '$title', '$department', '$location', '$workMode', '$description', 
                    '$requirements', '$responsibilities', '$benefits', 
                    $salaryMin, $salaryMax, $showSalary, 
                    $categoryId, '$status', $featured, 
                    $publishedAt, $expiresAt, 
                    $createdBy, NOW(), NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar una vacante existente
     * 
     * @param int $id ID de la vacante
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateVacancy($id, $data) {
        $id = (int)$id;
        
        // Obtener datos actuales para verificar cambios de estado
        $current = $this->getVacancyById($id);
        
        // Escapar datos
        $title = $this->db->escape($data['title']);
        $department = $this->db->escape($data['department']);
        $location = $this->db->escape($data['location']);
        $workMode = $this->db->escape($data['work_mode']);
        $description = $this->db->escape($data['description']);
        $requirements = $this->db->escape($data['requirements']);
        $responsibilities = $this->db->escape($data['responsibilities']);
        $benefits = isset($data['benefits']) ? $this->db->escape($data['benefits']) : '';
        $salaryMin = isset($data['salary_min']) && !empty($data['salary_min']) ? (float)$data['salary_min'] : 'NULL';
        $salaryMax = isset($data['salary_max']) && !empty($data['salary_max']) ? (float)$data['salary_max'] : 'NULL';
        $showSalary = isset($data['show_salary']) && $data['show_salary'] ? 1 : 0;
        $categoryId = (int)$data['category_id'];
        $status = $this->db->escape($data['status']);
        $featured = isset($data['featured']) && $data['featured'] ? 1 : 0;
        
        // Actualizar published_at si cambia a publicado
        $publishedAtSql = '';
        if ($current['status'] !== 'published' && $status === 'published') {
            $publishedAtSql = ", published_at = NOW()";
        }
        
        // Formatear fecha de expiración
        $expiresAtSql = '';
        if (isset($data['expires_at'])) {
            if (empty($data['expires_at'])) {
                $expiresAtSql = ", expires_at = NULL";
            } else {
                $expiresAt = date('Y-m-d H:i:s', strtotime($data['expires_at']));
                $expiresAtSql = ", expires_at = '$expiresAt'";
            }
        }
        
        $sql = "UPDATE job_vacancies SET 
                    title = '$title', 
                    department = '$department', 
                    location = '$location', 
                    work_mode = '$workMode', 
                    description = '$description', 
                    requirements = '$requirements', 
                    responsibilities = '$responsibilities', 
                    benefits = '$benefits', 
                    salary_min = $salaryMin, 
                    salary_max = $salaryMax, 
                    show_salary = $showSalary, 
                    category_id = $categoryId, 
                    status = '$status', 
                    featured = $featured, 
                    updated_at = NOW()
                    $publishedAtSql
                    $expiresAtSql
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Cambiar el estado de una vacante
     * 
     * @param int $id ID de la vacante
     * @param string $status Nuevo estado ('draft', 'published', 'closed', 'archived')
     * @return bool Éxito de la operación
     */
    public function changeStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->escape($status);
        
        // Obtener estado actual
        $current = $this->getVacancyById($id);
        
        // Si cambia a publicado y no estaba publicado antes, actualizar published_at
        $publishedAtSql = '';
        if ($current['status'] !== 'published' && $status === 'published') {
            $publishedAtSql = ", published_at = NOW()";
        }
        
        $sql = "UPDATE job_vacancies SET 
                    status = '$status', 
                    updated_at = NOW()
                    $publishedAtSql
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una vacante
     * 
     * @param int $id ID de la vacante
     * @return bool Éxito de la operación
     */
    public function deleteVacancy($id) {
        $id = (int)$id;
        
        // Primero eliminar preguntas asociadas
        $questionSql = "DELETE FROM job_vacancy_questions WHERE vacancy_id = $id";
        $this->db->query($questionSql);
        
        // Luego eliminar la vacante
        $sql = "DELETE FROM job_vacancies WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Incrementar contador de vistas
     * 
     * @param int $id ID de la vacante
     * @return bool Éxito de la operación
     */
    public function incrementViews($id) {
        $id = (int)$id;
        
        $sql = "UPDATE job_vacancies SET views = views + 1 WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Incrementar contador de aplicaciones
     * 
     * @param int $id ID de la vacante
     * @return bool Éxito de la operación
     */
    public function incrementApplications($id) {
        $id = (int)$id;
        
        $sql = "UPDATE job_vacancies SET applications = applications + 1 WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Obtener ubicaciones únicas
     * 
     * @return array Lista de ubicaciones
     */
    public function getUniqueLocations() {
        $sql = "SELECT DISTINCT location FROM job_vacancies WHERE status = 'published' ORDER BY location ASC";
        $result = $this->db->query($sql);
        $locations = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $locations[] = $row['location'];
        }
        
        return $locations;
    }
    
    /**
     * Buscar vacantes por término
     * 
     * @param string $query Término de búsqueda
     * @param int $page Número de página
     * @param int $per_page Elementos por página
     * @return array Resultados y paginación
     */
    public function searchVacancies($query, $page = 1, $per_page = 10) {
        return $this->getVacancies($page, $per_page, ['search' => $query]);
    }
}

/**
 * Clase JobCategory - Gestiona las categorías de vacantes
 */
class JobCategory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las categorías
     * 
     * @return array Lista de categorías
     */
    public function getCategories() {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM job_vacancies v WHERE v.category_id = c.id AND v.status = 'published') as vacancy_count 
                FROM job_categories c 
                ORDER BY c.name ASC";
                
        $result = $this->db->query($sql);
        $categories = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Obtener una categoría por su ID
     * 
     * @param int $id ID de la categoría
     * @return array|null Datos de la categoría o null si no existe
     */
    public function getCategoryById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM job_categories WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener una categoría por su slug
     * 
     * @param string $slug Slug de la categoría
     * @return array|null Datos de la categoría o null si no existe
     */
    public function getCategoryBySlug($slug) {
        $slug = $this->db->escape($slug);
        
        $sql = "SELECT * FROM job_categories WHERE slug = '$slug'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Crear una nueva categoría
     * 
     * @param array $data Datos de la categoría
     * @return int|false ID de la nueva categoría o false en caso de error
     */
    public function createCategory($data) {
        $name = $this->db->escape($data['name']);
        $slug = $this->db->escape($data['slug']);
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        
        $sql = "INSERT INTO job_categories (name, slug, description, created_at, updated_at) 
                VALUES ('$name', '$slug', '$description', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar una categoría existente
     * 
     * @param int $id ID de la categoría
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateCategory($id, $data) {
        $id = (int)$id;
        $name = $this->db->escape($data['name']);
        $slug = $this->db->escape($data['slug']);
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        
        $sql = "UPDATE job_categories SET 
                    name = '$name', 
                    slug = '$slug', 
                    description = '$description', 
                    updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una categoría
     * 
     * @param int $id ID de la categoría
     * @return bool Éxito de la operación
     */
    public function deleteCategory($id) {
        $id = (int)$id;
        
        // Verificar si hay vacantes asociadas
        $checkSql = "SELECT COUNT(*) as count FROM job_vacancies WHERE category_id = $id";
        $result = $this->db->query($checkSql);
        
        if ($result && $result->fetch_assoc()['count'] > 0) {
            return false; // No se puede eliminar si tiene vacantes asociadas
        }
        
        $sql = "DELETE FROM job_categories WHERE id = $id";
        return $this->db->query($sql);
    }
}

/**
 * Clase JobVacancyQuestion - Gestiona las preguntas personalizadas para vacantes
 */
class JobVacancyQuestion {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las preguntas de una vacante
     * 
     * @param int $vacancyId ID de la vacante
     * @return array Lista de preguntas
     */
    public function getQuestionsByVacancy($vacancyId) {
        $vacancyId = (int)$vacancyId;
        
        $sql = "SELECT * FROM job_vacancy_questions 
                WHERE vacancy_id = $vacancyId 
                ORDER BY `order` ASC";
                
        $result = $this->db->query($sql);
        $questions = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
        
        return $questions;
    }
    
    /**
     * Obtener una pregunta por su ID
     * 
     * @param int $id ID de la pregunta
     * @return array|null Datos de la pregunta o null si no existe
     */
    public function getQuestionById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM job_vacancy_questions WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Crear una nueva pregunta
     * 
     * @param array $data Datos de la pregunta
     * @return int|false ID de la nueva pregunta o false en caso de error
     */
    public function createQuestion($data) {
        $vacancyId = (int)$data['vacancy_id'];
        $question = $this->db->escape($data['question']);
        $type = $this->db->escape($data['type']);
        $options = isset($data['options']) ? $this->db->escape(json_encode($data['options'])) : 'NULL';
        $required = isset($data['required']) && $data['required'] ? 1 : 0;
        $order = isset($data['order']) ? (int)$data['order'] : 0;
        
        $sql = "INSERT INTO job_vacancy_questions (
                    vacancy_id, question, type, options, required, `order`, created_at
                ) VALUES (
                    $vacancyId, '$question', '$type', ";
                    
        $sql .= $options !== 'NULL' ? "'$options'" : "NULL";
        
        $sql .= ", $required, $order, NOW())";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar una pregunta existente
     * 
     * @param int $id ID de la pregunta
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateQuestion($id, $data) {
        $id = (int)$id;
        $question = $this->db->escape($data['question']);
        $type = $this->db->escape($data['type']);
        $options = isset($data['options']) ? $this->db->escape(json_encode($data['options'])) : 'NULL';
        $required = isset($data['required']) && $data['required'] ? 1 : 0;
        $order = isset($data['order']) ? (int)$data['order'] : 0;
        
        $sql = "UPDATE job_vacancy_questions SET 
                    question = '$question', 
                    type = '$type', 
                    options = ";
                    
        $sql .= $options !== 'NULL' ? "'$options'" : "NULL";
        
        $sql .= ", required = $required, 
                    `order` = $order
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar una pregunta
     * 
     * @param int $id ID de la pregunta
     * @return bool Éxito de la operación
     */
    public function deleteQuestion($id) {
        $id = (int)$id;
        
        // Eliminar respuestas asociadas a esta pregunta
        $answerSql = "DELETE FROM job_application_answers WHERE question_id = $id";
        $this->db->query($answerSql);
        
        // Eliminar la pregunta
        $sql = "DELETE FROM job_vacancy_questions WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Eliminar todas las preguntas de una vacante
     * 
     * @param int $vacancyId ID de la vacante
     * @return bool Éxito de la operación
     */
    public function deleteQuestionsByVacancy($vacancyId) {
        $vacancyId = (int)$vacancyId;
        
        // Obtener IDs de las preguntas para eliminar respuestas
        $questionIds = [];
        $questions = $this->getQuestionsByVacancy($vacancyId);
        
        foreach ($questions as $question) {
            $questionIds[] = $question['id'];
        }
        
        if (!empty($questionIds)) {
            // Eliminar respuestas asociadas
            $questionIdsStr = implode(',', $questionIds);
            $answerSql = "DELETE FROM job_application_answers WHERE question_id IN ($questionIdsStr)";
            $this->db->query($answerSql);
        }
        
        // Eliminar preguntas
        $sql = "DELETE FROM job_vacancy_questions WHERE vacancy_id = $vacancyId";
        return $this->db->query($sql);
    }
}

/**
 * Clase Candidate - Gestiona los perfiles de candidatos
 */
class Candidate {
    private $db;
    private $uploadDir = 'uploads/resumes/';
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Crear directorio de uploads si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Obtener un candidato por su ID
     * 
     * @param int $id ID del candidato
     * @return array|null Datos del candidato o null si no existe
     */
    public function getCandidateById($id) {
        $id = (int)$id;
        
        $sql = "SELECT c.*, u.name, u.email, u.image 
                FROM candidates c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtener un candidato por su ID de usuario
     * 
     * @param int $userId ID del usuario
     * @return array|null Datos del candidato o null si no existe
     */
    public function getCandidateByUserId($userId) {
        $userId = (int)$userId;
        
        $sql = "SELECT c.*, u.name, u.email, u.image 
                FROM candidates c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.user_id = $userId";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Crear un nuevo perfil de candidato
     * 
     * @param array $data Datos del candidato
     * @return int|false ID del nuevo candidato o false en caso de error
     */
    public function createCandidate($data) {
        $userId = (int)$data['user_id'];
        $phone = isset($data['phone']) ? $this->db->escape($data['phone']) : null;
        $dateOfBirth = isset($data['date_of_birth']) ? $this->db->escape($data['date_of_birth']) : null;
        $headline = isset($data['headline']) ? $this->db->escape($data['headline']) : null;
        $summary = isset($data['summary']) ? $this->db->escape($data['summary']) : null;
        $address = isset($data['address']) ? $this->db->escape($data['address']) : null;
        $city = isset($data['city']) ? $this->db->escape($data['city']) : null;
        $country = isset($data['country']) ? $this->db->escape($data['country']) : null;
        $postalCode = isset($data['postal_code']) ? $this->db->escape($data['postal_code']) : null;
        $linkedinUrl = isset($data['linkedin_url']) ? $this->db->escape($data['linkedin_url']) : null;
        $website = isset($data['website']) ? $this->db->escape($data['website']) : null;
        $portfolioUrl = isset($data['portfolio_url']) ? $this->db->escape($data['portfolio_url']) : null;
        $cvPath = isset($data['cv_path']) ? $this->db->escape($data['cv_path']) : null;
        $profileCompleted = isset($data['profile_completed']) ? (int)$data['profile_completed'] : 0;
        $isAvailable = isset($data['is_available']) ? (int)$data['is_available'] : 1;
        
        // Verificar que el usuario no tenga ya un perfil de candidato
        $checkSql = "SELECT id FROM candidates WHERE user_id = $userId";
        $result = $this->db->query($checkSql);
        
        if ($result && $result->num_rows > 0) {
            // Ya tiene perfil, actualizar en lugar de crear
            $candidateId = $result->fetch_assoc()['id'];
            return $this->updateCandidate($candidateId, $data);
        }
        
        $sql = "INSERT INTO candidates (
                    user_id, phone, date_of_birth, headline, summary, 
                    address, city, country, postal_code, 
                    linkedin_url, website, portfolio_url, 
                    cv_path, profile_completed, is_available, 
                    created_at, updated_at
                ) VALUES (
                    $userId, " .
                    ($phone ? "'$phone'" : "NULL") . ", " .
                    ($dateOfBirth ? "'$dateOfBirth'" : "NULL") . ", " .
                    ($headline ? "'$headline'" : "NULL") . ", " .
                    ($summary ? "'$summary'" : "NULL") . ", " .
                    ($address ? "'$address'" : "NULL") . ", " .
                    ($city ? "'$city'" : "NULL") . ", " .
                    ($country ? "'$country'" : "NULL") . ", " .
                    ($postalCode ? "'$postalCode'" : "NULL") . ", " .
                    ($linkedinUrl ? "'$linkedinUrl'" : "NULL") . ", " .
                    ($website ? "'$website'" : "NULL") . ", " .
                    ($portfolioUrl ? "'$portfolioUrl'" : "NULL") . ", " .
                    ($cvPath ? "'$cvPath'" : "NULL") . ", " .
                    "$profileCompleted, $isAvailable, 
                    NOW(), NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar un perfil de candidato existente
     * 
     * @param int $id ID del candidato
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateCandidate($id, $data) {
        $id = (int)$id;
        
        // Construir pares clave-valor para actualización
        $updates = [];
        
        if (isset($data['phone'])) {
            $phone = $this->db->escape($data['phone']);
            $updates[] = "phone = " . ($phone ? "'$phone'" : "NULL");
        }
        
        if (isset($data['date_of_birth'])) {
            $dateOfBirth = $this->db->escape($data['date_of_birth']);
            $updates[] = "date_of_birth = " . ($dateOfBirth ? "'$dateOfBirth'" : "NULL");
        }
        
        if (isset($data['headline'])) {
            $headline = $this->db->escape($data['headline']);
            $updates[] = "headline = " . ($headline ? "'$headline'" : "NULL");
        }
        
        if (isset($data['summary'])) {
            $summary = $this->db->escape($data['summary']);
            $updates[] = "summary = " . ($summary ? "'$summary'" : "NULL");
        }
        
        if (isset($data['address'])) {
            $address = $this->db->escape($data['address']);
            $updates[] = "address = " . ($address ? "'$address'" : "NULL");
        }
        
        if (isset($data['city'])) {
            $city = $this->db->escape($data['city']);
            $updates[] = "city = " . ($city ? "'$city'" : "NULL");
        }
        
        if (isset($data['country'])) {
            $country = $this->db->escape($data['country']);
            $updates[] = "country = " . ($country ? "'$country'" : "NULL");
        }
        
        if (isset($data['postal_code'])) {
            $postalCode = $this->db->escape($data['postal_code']);
            $updates[] = "postal_code = " . ($postalCode ? "'$postalCode'" : "NULL");
        }
        
        if (isset($data['linkedin_url'])) {
            $linkedinUrl = $this->db->escape($data['linkedin_url']);
            $updates[] = "linkedin_url = " . ($linkedinUrl ? "'$linkedinUrl'" : "NULL");
        }
        
        if (isset($data['website'])) {
            $website = $this->db->escape($data['website']);
            $updates[] = "website = " . ($website ? "'$website'" : "NULL");
        }
        
        if (isset($data['portfolio_url'])) {
            $portfolioUrl = $this->db->escape($data['portfolio_url']);
            $updates[] = "portfolio_url = " . ($portfolioUrl ? "'$portfolioUrl'" : "NULL");
        }
        
        if (isset($data['cv_path'])) {
            $cvPath = $this->db->escape($data['cv_path']);
            $updates[] = "cv_path = " . ($cvPath ? "'$cvPath'" : "NULL");
            $updates[] = "cv_updated_at = NOW()";
        }
        
        if (isset($data['profile_completed'])) {
            $profileCompleted = (int)$data['profile_completed'];
            $updates[] = "profile_completed = $profileCompleted";
        }
        
        if (isset($data['is_available'])) {
            $isAvailable = (int)$data['is_available'];
            $updates[] = "is_available = $isAvailable";
        }
        
        // Si no hay actualizaciones, terminar
        if (empty($updates)) {
            return true;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE candidates SET " . implode(", ", $updates) . " WHERE id = $id";
        return $this->db->query($sql);
    }
    
    /**
     * Actualizar la ruta del CV de un candidato
     * 
     * @param int $id ID del candidato
     * @param string $cvPath Nueva ruta del CV
     * @return bool Éxito de la operación
     */
    public function updateCvPath($id, $cvPath) {
        $id = (int)$id;
        $cvPath = $this->db->escape($cvPath);
        
        $sql = "UPDATE candidates SET 
                    cv_path = '$cvPath', 
                    cv_updated_at = NOW(), 
                    updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Subir un CV para un candidato
     * 
     * @param array $file Archivo subido ($_FILES['cv'])
     * @param int $candidateId ID del candidato
     * @return array Resultado de la operación (success, path, message)
     */
    public function uploadResume($file, $candidateId) {
        $candidateId = (int)$candidateId;
        
        // Verificar que el archivo sea válido
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Error al subir el archivo.'
            ];
        }
        
        // Verificar tamaño máximo (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande. Máximo 5MB.'
            ];
        }
        
        // Verificar tipo de archivo
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['pdf', 'doc', 'docx'];
        
        if (!in_array($fileType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Solo se permiten archivos PDF, DOC y DOCX.'
            ];
        }
        
        // Crear nombre de archivo único
        $fileName = 'cv_' . $candidateId . '_' . time() . '.' . $fileType;
        $filePath = $this->uploadDir . $fileName;
        
        // Mover archivo a directorio de uploads
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'path' => $filePath,
                'file_name' => $fileName
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al guardar el archivo.'
            ];
        }
    }
    
    /**
     * Agregar experiencia laboral a un candidato
     * 
     * @param array $data Datos de la experiencia
     * @return int|false ID de la nueva experiencia o false en caso de error
     */
    public function addExperience($data) {
        $candidateId = (int)$data['candidate_id'];
        $company = $this->db->escape($data['company']);
        $position = $this->db->escape($data['position']);
        $startDate = $this->db->escape($data['start_date']);
        $endDate = isset($data['end_date']) && !empty($data['end_date']) ? "'" . $this->db->escape($data['end_date']) . "'" : "NULL";
        $current = isset($data['current']) && $data['current'] ? 1 : 0;
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        
        $sql = "INSERT INTO candidate_experiences (
                    candidate_id, company, position, start_date, end_date, current, description, created_at, updated_at
                ) VALUES (
                    $candidateId, '$company', '$position', '$startDate', $endDate, $current, '$description', NOW(), NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener experiencias laborales de un candidato
     * 
     * @param int $candidateId ID del candidato
     * @return array Lista de experiencias
     */
    public function getExperiences($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT * FROM candidate_experiences 
                WHERE candidate_id = $candidateId 
                ORDER BY current DESC, end_date DESC, start_date DESC";
                
        $result = $this->db->query($sql);
        $experiences = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $experiences[] = $row;
        }
        
        return $experiences;
    }
    
    /**
     * Agregar educación a un candidato
     * 
     * @param array $data Datos de la educación
     * @return int|false ID de la nueva educación o false en caso de error
     */
    public function addEducation($data) {
        $candidateId = (int)$data['candidate_id'];
        $institution = $this->db->escape($data['institution']);
        $degree = $this->db->escape($data['degree']);
        $field = $this->db->escape($data['field']);
        $startDate = $this->db->escape($data['start_date']);
        $endDate = isset($data['end_date']) && !empty($data['end_date']) ? "'" . $this->db->escape($data['end_date']) . "'" : "NULL";
        $current = isset($data['current']) && $data['current'] ? 1 : 0;
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        
        $sql = "INSERT INTO candidate_education (
                    candidate_id, institution, degree, field, start_date, end_date, current, description, created_at, updated_at
                ) VALUES (
                    $candidateId, '$institution', '$degree', '$field', '$startDate', $endDate, $current, '$description', NOW(), NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener educación de un candidato
     * 
     * @param int $candidateId ID del candidato
     * @return array Lista de educación
     */
    public function getEducation($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT * FROM candidate_education 
                WHERE candidate_id = $candidateId 
                ORDER BY current DESC, end_date DESC, start_date DESC";
                
        $result = $this->db->query($sql);
        $education = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $education[] = $row;
        }
        
        return $education;
    }
    
    /**
     * Agregar habilidad a un candidato
     * 
     * @param array $data Datos de la habilidad
     * @return int|false ID de la nueva habilidad o false en caso de error
     */
    public function addSkill($data) {
        $candidateId = (int)$data['candidate_id'];
        $skill = $this->db->escape($data['skill']);
        $level = $this->db->escape($data['level']);
        
        $sql = "INSERT INTO candidate_skills (
                    candidate_id, skill, level, created_at
                ) VALUES (
                    $candidateId, '$skill', '$level', NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener habilidades de un candidato
     * 
     * @param int $candidateId ID del candidato
     * @return array Lista de habilidades
     */
    public function getSkills($candidateId) {
        $candidateId = (int)$candidateId;
        
        $sql = "SELECT * FROM candidate_skills 
                WHERE candidate_id = $candidateId 
                ORDER BY level DESC, skill ASC";
                
        $result = $this->db->query($sql);
        $skills = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $skills[] = $row;
        }
        
        return $skills;
    }
}

/**
 * Clase JobApplication - Gestiona las aplicaciones a vacantes
 */
class JobApplication {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear una nueva aplicación a una vacante
     * 
     * @param array $data Datos de la aplicación
     * @return int|false ID de la nueva aplicación o false en caso de error
     */
    public function createApplication($data) {
        $vacancyId = (int)$data['vacancy_id'];
        $candidateId = (int)$data['candidate_id'];
        $coverLetter = $this->db->escape($data['cover_letter']);
        $resumePath = isset($data['resume_path']) ? $this->db->escape($data['resume_path']) : 'NULL';
        
        // Verificar si ya existe una aplicación
        if ($this->hasApplied($candidateId, $vacancyId)) {
            return false; // Ya ha aplicado a esta vacante
        }
        
        $sql = "INSERT INTO job_applications (
                    vacancy_id, candidate_id, cover_letter, resume_path, status, created_at, updated_at
                ) VALUES (
                    $vacancyId, $candidateId, '$coverLetter', ";
                    
        $sql .= $resumePath !== 'NULL' ? "'$resumePath'" : "NULL";
        
        $sql .= ", 'pending', NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            // Crear primera etapa de selección automáticamente
            $applicationId = $this->db->lastInsertId();
            $this->createFirstStage($applicationId);
            
            return $applicationId;
        }
        
        return false;
    }
    
    /**
     * Crear la primera etapa de selección para una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return bool Éxito de la operación
     */
    private function createFirstStage($applicationId) {
        $applicationId = (int)$applicationId;
        
        // Obtener la primera etapa del proceso
        $sql = "SELECT id FROM recruitment_stages WHERE `order` = 1 LIMIT 1";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $stageId = $result->fetch_assoc()['id'];
            
            // Crear la etapa para esta aplicación
            $stageSql = "INSERT INTO application_stages (
                            application_id, stage_id, status, created_by, created_at, updated_at
                        ) VALUES (
                            $applicationId, $stageId, 'pending', 1, NOW(), NOW()
                        )";
                        
            return $this->db->query($stageSql);
        }
        
        return false;
    }
    
    /**
     * Verificar si un candidato ya ha aplicado a una vacante
     * 
     * @param int $candidateId ID del candidato
     * @param int $vacancyId ID de la vacante
     * @return bool True si ya ha aplicado, false en caso contrario
     */
    public function hasApplied($candidateId, $vacancyId) {
        $candidateId = (int)$candidateId;
        $vacancyId = (int)$vacancyId;
        
        $sql = "SELECT id FROM job_applications 
                WHERE candidate_id = $candidateId AND vacancy_id = $vacancyId";
                
        $result = $this->db->query($sql);
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Guardar respuesta a una pregunta personalizada
     * 
     * @param int $applicationId ID de la aplicación
     * @param int $questionId ID de la pregunta
     * @param string $answer Respuesta
     * @return bool Éxito de la operación
     */
    public function saveAnswer($applicationId, $questionId, $answer) {
        $applicationId = (int)$applicationId;
        $questionId = (int)$questionId;
        $answer = $this->db->escape($answer);
        
        // Verificar si ya existe una respuesta para esta pregunta
        $checkSql = "SELECT id FROM job_application_answers 
                    WHERE application_id = $applicationId AND question_id = $questionId";
        $result = $this->db->query($checkSql);
        
        if ($result && $result->num_rows > 0) {
            // Actualizar respuesta existente
            $id = $result->fetch_assoc()['id'];
            $sql = "UPDATE job_application_answers SET 
                    answer = '$answer' 
                    WHERE id = $id";
        } else {
            // Crear nueva respuesta
            $sql = "INSERT INTO job_application_answers (
                        application_id, question_id, answer, created_at
                    ) VALUES (
                        $applicationId, $questionId, '$answer', NOW()
                    )";
        }
        
        return $this->db->query($sql);
    }
    
    /**
     * Obtener respuestas a preguntas para una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Lista de respuestas
     */
    public function getAnswers($applicationId) {
        $applicationId = (int)$applicationId;
        
        $sql = "SELECT a.*, q.question, q.type 
                FROM job_application_answers a 
                LEFT JOIN job_vacancy_questions q ON a.question_id = q.id 
                WHERE a.application_id = $applicationId 
                ORDER BY q.order ASC";
                
        $result = $this->db->query($sql);
        $answers = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        
        return $answers;
    }
    
    /**
     * Obtener aplicaciones para un candidato
     * 
     * @param int $candidateId ID del candidato
     * @param int $page Número de página
     * @param int $per_page Elementos por página
     * @return array Aplicaciones, total y paginación
     */
    public function getApplicationsByCandidate($candidateId, $page = 1, $per_page = 10) {
        $candidateId = (int)$candidateId;
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT a.*, v.title, v.department, v.location, v.work_mode, 
                       c.name as category_name, 
                       (SELECT COUNT(*) FROM application_stages WHERE application_id = a.id) as stages_count,
                       (SELECT s.name FROM application_stages ast 
                        LEFT JOIN recruitment_stages s ON ast.stage_id = s.id 
                        WHERE ast.application_id = a.id 
                        ORDER BY ast.created_at DESC LIMIT 1) as current_stage
                FROM job_applications a 
                LEFT JOIN job_vacancies v ON a.vacancy_id = v.id 
                LEFT JOIN job_categories c ON v.category_id = c.id 
                WHERE a.candidate_id = $candidateId 
                ORDER BY a.created_at DESC 
                LIMIT $offset, $per_page";
                
        $result = $this->db->query($sql);
        $applications = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM job_applications 
                     WHERE candidate_id = $candidateId";
        $countResult = $this->db->query($countSql);
        $totalApplications = 0;
        
        if ($countResult && $countResult->num_rows > 0) {
            $totalApplications = $countResult->fetch_assoc()['total'];
        }
        
        return [
            'applications' => $applications,
            'total' => $totalApplications,
            'pages' => ceil($totalApplications / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener aplicaciones para una vacante
     * 
     * @param int $vacancyId ID de la vacante
     * @param int $page Número de página
     * @param int $per_page Elementos por página
     * @param string $status Filtro por estado (opcional)
     * @return array Aplicaciones, total y paginación
     */
    public function getApplicationsByVacancy($vacancyId, $page = 1, $per_page = 10, $status = null) {
        $vacancyId = (int)$vacancyId;
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT a.*, 
                       u.name as candidate_name, u.email as candidate_email, 
                       (SELECT COUNT(*) FROM application_stages WHERE application_id = a.id) as stages_count,
                       (SELECT s.name FROM application_stages ast 
                        LEFT JOIN recruitment_stages s ON ast.stage_id = s.id 
                        WHERE ast.application_id = a.id 
                        ORDER BY ast.created_at DESC LIMIT 1) as current_stage
                FROM job_applications a 
                LEFT JOIN candidates c ON a.candidate_id = c.id 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE a.vacancy_id = $vacancyId";
                
        if ($status) {
            $status = $this->db->escape($status);
            $sql .= " AND a.status = '$status'";
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT $offset, $per_page";
                
        $result = $this->db->query($sql);
        $applications = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        
        // Contar total para paginación
        $countSql = "SELECT COUNT(*) as total FROM job_applications 
                     WHERE vacancy_id = $vacancyId";
                     
        if ($status) {
            $countSql .= " AND status = '$status'";
        }
        
        $countResult = $this->db->query($countSql);
        $totalApplications = 0;
        
        if ($countResult && $countResult->num_rows > 0) {
            $totalApplications = $countResult->fetch_assoc()['total'];
        }
        
        return [
            'applications' => $applications,
            'total' => $totalApplications,
            'pages' => ceil($totalApplications / $per_page),
            'current_page' => $page
        ];
    }
    
    /**
     * Obtener una aplicación por su ID
     * 
     * @param int $id ID de la aplicación
     * @return array|null Datos de la aplicación o null si no existe
     */
    public function getApplicationById($id) {
        $id = (int)$id;
        
        $sql = "SELECT a.*, v.title as vacancy_title, v.department, v.location, v.work_mode, 
                       c.name as category_name, 
                       u.name as candidate_name, u.email as candidate_email, 
                       ca.phone as candidate_phone, ca.headline as candidate_headline, 
                       ca.summary as candidate_summary, ca.city as candidate_city, 
                       ca.country as candidate_country
                FROM job_applications a 
                LEFT JOIN job_vacancies v ON a.vacancy_id = v.id 
                LEFT JOIN job_categories c ON v.category_id = c.id 
                LEFT JOIN candidates ca ON a.candidate_id = ca.id 
                LEFT JOIN users u ON ca.user_id = u.id 
                WHERE a.id = $id";
                
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Cambiar el estado de una aplicación
     * 
     * @param int $id ID de la aplicación
     * @param string $status Nuevo estado
     * @param string $rejectionReason Razón de rechazo (opcional)
     * @return bool Éxito de la operación
     */
    public function changeStatus($id, $status, $rejectionReason = null) {
        $id = (int)$id;
        $status = $this->db->escape($status);
        
        $sql = "UPDATE job_applications SET 
                    status = '$status', 
                    updated_at = NOW()";
                    
        if ($status === 'rejected' && $rejectionReason) {
            $rejectionReason = $this->db->escape($rejectionReason);
            $sql .= ", rejection_reason = '$rejectionReason'";
        }
        
        $sql .= " WHERE id = $id";
        
        return $this->db->query($sql);
    }
    
    /**
     * Marcar email de rechazo como enviado
     * 
     * @param int $id ID de la aplicación
     * @return bool Éxito de la operación
     */
    public function markRejectionEmailSent($id) {
        $id = (int)$id;
        
        $sql = "UPDATE job_applications SET 
                    rejection_email_sent = 1, 
                    updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Agregar una nota a una aplicación
     * 
     * @param int $id ID de la aplicación
     * @param string $note Texto de la nota
     * @return bool Éxito de la operación
     */
    public function addNote($id, $note) {
        $id = (int)$id;
        $note = $this->db->escape($note);
        
        // Obtener notas actuales
        $application = $this->getApplicationById($id);
        
        if (!$application) {
            return false;
        }
        
        $currentNotes = $application['notes'];
        $newNote = date('Y-m-d H:i:s') . ': ' . $note;
        
        // Combinar notas
        $notes = $currentNotes ? $currentNotes . "\n\n" . $newNote : $newNote;
        $notes = $this->db->escape($notes);
        
        $sql = "UPDATE job_applications SET 
                    notes = '$notes', 
                    updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
}

/**
 * Clase RecruitmentStage - Gestiona las etapas del proceso de selección
 */
class RecruitmentStage {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las etapas del proceso de selección
     * 
     * @return array Lista de etapas
     */
    public function getStages() {
        $sql = "SELECT * FROM recruitment_stages 
                WHERE is_active = 1 
                ORDER BY `order` ASC";
                
        $result = $this->db->query($sql);
        $stages = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $stages[] = $row;
        }
        
        return $stages;
    }
    
    /**
     * Obtener una etapa por su ID
     * 
     * @param int $id ID de la etapa
     * @return array|null Datos de la etapa o null si no existe
     */
    public function getStageById($id) {
        $id = (int)$id;
        
        $sql = "SELECT * FROM recruitment_stages WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Crear una nueva etapa
     * 
     * @param array $data Datos de la etapa
     * @return int|false ID de la nueva etapa o false en caso de error
     */
    public function createStage($data) {
        $name = $this->db->escape($data['name']);
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        $order = isset($data['order']) ? (int)$data['order'] : 0;
        $isActive = isset($data['is_active']) && $data['is_active'] ? 1 : 0;
        
        $sql = "INSERT INTO recruitment_stages (
                    name, description, `order`, is_active, created_at, updated_at
                ) VALUES (
                    '$name', '$description', $order, $isActive, NOW(), NOW()
                )";
                
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar una etapa existente
     * 
     * @param int $id ID de la etapa
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateStage($id, $data) {
        $id = (int)$id;
        $name = $this->db->escape($data['name']);
        $description = isset($data['description']) ? $this->db->escape($data['description']) : '';
        $order = isset($data['order']) ? (int)$data['order'] : 0;
        $isActive = isset($data['is_active']) && $data['is_active'] ? 1 : 0;
        
        $sql = "UPDATE recruitment_stages SET 
                    name = '$name', 
                    description = '$description', 
                    `order` = $order, 
                    is_active = $isActive, 
                    updated_at = NOW() 
                WHERE id = $id";
                
        return $this->db->query($sql);
    }
    
    /**
     * Obtener las etapas de una aplicación
     * 
     * @param int $applicationId ID de la aplicación
     * @return array Lista de etapas
     */
    public function getApplicationStages($applicationId) {
        $applicationId = (int)$applicationId;
        
        $sql = "SELECT s.*, r.name as stage_name, r.description as stage_description,
                       u.name as created_by_name 
                FROM application_stages s 
                LEFT JOIN recruitment_stages r ON s.stage_id = r.id 
                LEFT JOIN users u ON s.created_by = u.id 
                WHERE s.application_id = $applicationId 
                ORDER BY s.created_at ASC";
                
        $result = $this->db->query($sql);
        $stages = [];
        
        while ($result && $row = $result->fetch_assoc()) {
            $stages[] = $row;
        }
        
        return $stages;
    }
    
    /**
     * Agregar una etapa a una aplicación
     * 
     * @param array $data Datos de la etapa
     * @return int|false ID de la nueva etapa o false en caso de error
     */
    public function addApplicationStage($data) {
        $applicationId = (int)$data['application_id'];
        $stageId = (int)$data['stage_id'];
        $status = $this->db->escape($data['status'] ?? 'pending');
        $feedback = isset($data['feedback']) ? $this->db->escape($data['feedback']) : null;
        $score = isset($data['score']) && is_numeric($data['score']) ? (float)$data['score'] : 'NULL';
        $scheduledDate = isset($data['scheduled_date']) && !empty($data['scheduled_date']) ? "'" . $this->db->escape($data['scheduled_date']) . "'" : 'NULL';
        $completedDate = isset($data['completed_date']) && !empty($data['completed_date']) ? "'" . $this->db->escape($data['completed_date']) . "'" : 'NULL';
        $createdBy = (int)$data['created_by'];
        
        $sql = "INSERT INTO application_stages (
                    application_id, stage_id, status, feedback, score, 
                    scheduled_date, completed_date, created_by, created_at, updated_at
                ) VALUES (
                    $applicationId, $stageId, '$status', ";
                    
        $sql .= $feedback ? "'$feedback'" : "NULL";
        $sql .= ", $score, $scheduledDate, $completedDate, $createdBy, NOW(), NOW())";
                
        if ($this->db->query($sql)) {
            // Actualizar estado de la aplicación según la etapa
            $this->updateApplicationStatus($applicationId, $stageId, $status);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar el estado de una etapa de aplicación
     * 
     * @param int $id ID de la etapa
     * @param array $data Datos actualizados
     * @return bool Éxito de la operación
     */
    public function updateApplicationStage($id, $data) {
        $id = (int)$id;
        $status = isset($data['status']) ? $this->db->escape($data['status']) : null;
        
        // Obtener información actual de la etapa
        $sql = "SELECT application_id, stage_id FROM application_stages WHERE id = $id";
        $result = $this->db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        $current = $result->fetch_assoc();
        $applicationId = $current['application_id'];
        $stageId = $current['stage_id'];
        
        // Construir consulta de actualización
        $updateFields = [];
        
        if (isset($data['status'])) {
            $status = $this->db->escape($data['status']);
            $updateFields[] = "status = '$status'";
        }
        
        if (isset($data['feedback'])) {
            $feedback = $this->db->escape($data['feedback']);
            $updateFields[] = "feedback = '$feedback'";
        }
        
        if (isset($data['score'])) {
            $score = is_numeric($data['score']) ? (float)$data['score'] : 'NULL';
            $updateFields[] = "score = $score";
        }
        
        if (isset($data['scheduled_date'])) {
            if (empty($data['scheduled_date'])) {
                $updateFields[] = "scheduled_date = NULL";
            } else {
                $scheduledDate = $this->db->escape($data['scheduled_date']);
                $updateFields[] = "scheduled_date = '$scheduledDate'";
            }
        }
        
        if (isset($data['completed_date'])) {
            if (empty($data['completed_date'])) {
                $updateFields[] = "completed_date = NULL";
            } else {
                $completedDate = $this->db->escape($data['completed_date']);
                $updateFields[] = "completed_date = '$completedDate'";
            }
        }
        
        if (empty($updateFields)) {
            return true; // No hay nada que actualizar
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        $sql = "UPDATE application_stages SET " . implode(", ", $updateFields) . " WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result && isset($data['status'])) {
            // Actualizar estado de la aplicación según la etapa
            $this->updateApplicationStatus($applicationId, $stageId, $data['status']);
        }
        
        return $result;
    }
    
    /**
     * Actualizar el estado de la aplicación según la etapa
     * 
     * @param int $applicationId ID de la aplicación
     * @param int $stageId ID de la etapa
     * @param string $status Estado de la etapa
     * @return bool Éxito de la operación
     */
    private function updateApplicationStatus($applicationId, $stageId, $status) {
        $applicationId = (int)$applicationId;
        
        // Obtener información de la etapa
        $stageSql = "SELECT name FROM recruitment_stages WHERE id = $stageId";
        $stageResult = $this->db->query($stageSql);
        
        if (!$stageResult || $stageResult->num_rows === 0) {
            return false;
        }
        
        $stageName = $stageResult->fetch_assoc()['name'];
        $applicationStatus = 'pending';
        
        // Mapear estado de la etapa al estado de la aplicación
        if ($stageName === 'Rechazado' || $status === 'failed') {
            $applicationStatus = 'rejected';
        } elseif ($stageName === 'Oferta' && $status === 'passed') {
            $applicationStatus = 'offered';
        } elseif ($stageName === 'Contratado' && $status === 'passed') {
            $applicationStatus = 'hired';
        } elseif ($status === 'in_progress') {
            $applicationStatus = 'interviewing';
        } elseif ($status === 'pending' || $status === 'passed') {
            $applicationStatus = 'reviewed';
        }
        
        $application = new JobApplication();
        return $application->changeStatus($applicationId, $applicationStatus);
    }
}

/**
 * Clase JobStatistics - Proporciona estadísticas sobre vacantes y aplicaciones
 */
class JobStatistics {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener estadísticas generales
     * 
     * @return array Estadísticas generales
     */
    public function getGeneralStats() {
        $stats = [
            'total_vacancies' => 0,
            'active_vacancies' => 0,
            'total_applications' => 0,
            'applications_last_30_days' => 0,
            'hiring_rate' => 0,
            'average_applications_per_vacancy' => 0,
            'most_popular_category' => '',
            'most_popular_location' => ''
        ];
        
        // Total de vacantes
        $sql = "SELECT COUNT(*) as total FROM job_vacancies";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['total_vacancies'] = $result->fetch_assoc()['total'];
        }
        
        // Vacantes activas
        $sql = "SELECT COUNT(*) as total FROM job_vacancies 
                WHERE status = 'published' 
                AND (expires_at IS NULL OR expires_at > NOW())";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['active_vacancies'] = $result->fetch_assoc()['total'];
        }
        
        // Total de aplicaciones
        $sql = "SELECT COUNT(*) as total FROM job_applications";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['total_applications'] = $result->fetch_assoc()['total'];
        }
        
        // Aplicaciones en los últimos 30 días
        $sql = "SELECT COUNT(*) as total FROM job_applications 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['applications_last_30_days'] = $result->fetch_assoc()['total'];
        }
        
        // Tasa de contratación
        $sql = "SELECT 
                (SELECT COUNT(*) FROM job_applications WHERE status = 'hired') as hired,
                (SELECT COUNT(*) FROM job_applications) as total";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['hiring_rate'] = $row['total'] > 0 ? round(($row['hired'] / $row['total']) * 100, 2) : 0;
        }
        
        // Media de aplicaciones por vacante
        $stats['average_applications_per_vacancy'] = $stats['active_vacancies'] > 0 ? 
                                                    round($stats['total_applications'] / $stats['active_vacancies'], 2) : 0;
        
        // Categoría más popular
        $sql = "SELECT c.name, COUNT(v.id) as vacancy_count 
                FROM job_categories c 
                LEFT JOIN job_vacancies v ON c.id = v.category_id 
                WHERE v.status = 'published' 
                GROUP BY c.id 
                ORDER BY vacancy_count DESC 
                LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['most_popular_category'] = $result->fetch_assoc()['name'];
        }
        
        // Ubicación más popular
        $sql = "SELECT location, COUNT(*) as count 
                FROM job_vacancies 
                WHERE status = 'published' 
                GROUP BY location 
                ORDER BY count DESC 
                LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $stats['most_popular_location'] = $result->fetch_assoc()['location'];
        }
        
        return $stats;
    }
    
    /**
     * Obtener estadísticas de aplicaciones por mes
     * 
     * @param int $months Número de meses a incluir
     * @return array Estadísticas por mes
     */
    public function getApplicationsByMonth($months = 6) {
        $months = (int)$months;
        $stats = [];
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM job_applications
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
                
        $result = $this->db->query($sql);
        
        while ($result && $row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * Obtener estadísticas de vacantes por categoría
     * 
     * @return array Estadísticas por categoría
     */
    public function getVacanciesByCategory() {
        $stats = [];
        
        $sql = "SELECT 
                    c.name, 
                    COUNT(v.id) as vacancy_count,
                    SUM(v.applications) as application_count
                FROM job_categories c
                LEFT JOIN job_vacancies v ON c.id = v.category_id
                WHERE v.status = 'published'
                GROUP BY c.id
                ORDER BY vacancy_count DESC";
                
        $result = $this->db->query($sql);
        
        while ($result && $row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * Obtener estadísticas de fuentes de tráfico
     * 
     * @return array Estadísticas por fuente
     */
    public function getTrafficSources() {
        // Ejemplo de datos - En una implementación real, esto vendría de la base de datos
        return [
            ['source' => 'Directo', 'count' => 120],
            ['source' => 'LinkedIn', 'count' => 85],
            ['source' => 'Indeed', 'count' => 65],
            ['source' => 'Facebook', 'count' => 40],
            ['source' => 'Twitter', 'count' => 25],
            ['source' => 'Google', 'count' => 15]
        ];
    }
    
    /**
     * Obtener estadísticas de vacantes más vistas
     * 
     * @param int $limit Número de vacantes a incluir
     * @return array Vacantes más vistas
     */
    public function getMostViewedVacancies($limit = 5) {
        $limit = (int)$limit;
        $stats = [];
        
        $sql = "SELECT 
                    id, title, department, location, views, applications,
                    (applications / IF(views > 0, views, 1) * 100) as conversion_rate
                FROM job_vacancies
                WHERE status = 'published'
                ORDER BY views DESC
                LIMIT $limit";
                
        $result = $this->db->query($sql);
        
        while ($result && $row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}
?>