<?php
/**
 * Portal del Candidato - SolFis
 * candidate/test.php - Realizar pruebas psicométricas
 */

// Iniciar sesión
session_start();

// Incluir clases necesarias
require_once '../includes/config.php';
require_once '../includes/jobs-system.php';
require_once '../includes/TestManager.php';

// Verificar autenticación
if (!isset($_SESSION['candidate_id'])) {
    // Guardar URL actual para redireccionar después de login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$candidateId = $_SESSION['candidate_id'];
$testManager = new TestManager();

// Determinar acción
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'list':
        // Obtener pruebas asignadas al candidato
        $tests = $testManager->getTestsByCandidate($candidateId);
        
        // Agrupar por estado
        $pendingTests = [];
        $inProgressTests = [];
        $completedTests = [];
        
        foreach ($tests as $test) {
            switch ($test['estado']) {
                case 'pendiente':
                    $pendingTests[] = $test;
                    break;
                case 'en_progreso':
                    $inProgressTests[] = $test;
                    break;
                case 'completada':
                    $completedTests[] = $test;
                    break;
            }
        }
        
        // Obtener información de resumen
        $testsCount = count($tests);
        $completedCount = count($completedTests);
        $pendingCount = count($pendingTests) + count($inProgressTests);
        $completionPercentage = $testsCount > 0 ? round(($completedCount / $testsCount) * 100) : 0;
        
        // Obtener resultados del candidato
        $results = $testManager->getResultsByCandidate($candidateId);
        $indicesResults = $testManager->getIndicesResultsByCandidate($candidateId);
        
        // Cargar vista de listado
        include 'views/test/list.php';
        break;
        
    case 'start':
        // Iniciar una prueba
        $testId = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        
        if (!$testId) {
            setFlashMessage('error', 'Prueba no válida');
            header('Location: test.php');
            exit;
        }
        
        // Verificar si la prueba está asignada al candidato
        $isAssigned = $testManager->isTestAssignedToCandidate($candidateId, $testId);
        
        if (!$isAssigned) {
            setFlashMessage('error', 'Esta prueba no está asignada a su perfil');
            header('Location: test.php');
            exit;
        }
        
        // Verificar si ya está completada
        $isCompleted = $testManager->isTestCompletedByCandidate($candidateId, $testId);
        
        if ($isCompleted) {
            setFlashMessage('info', 'Esta prueba ya ha sido completada');
            header('Location: test.php?action=results&test_id=' . $testId);
            exit;
        }
        
        // Obtener o crear sesión para la prueba
        $sessionId = $testManager->getOrCreateSession($candidateId, $testId);
        
        // Redireccionar al formulario de la prueba
        header('Location: test.php?action=take&session_id=' . $sessionId);
        exit;
        break;
        
    case 'take':
        // Realizar una prueba
        $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
        
        if (!$sessionId) {
            setFlashMessage('error', 'Sesión no válida');
            header('Location: test.php');
            exit;
        }
        
        // Verificar que la sesión pertenece al candidato
        $sessionInfo = $testManager->getSessionInfo($sessionId);
        
        if (!$sessionInfo || $sessionInfo['candidato_id'] != $candidateId) {
            setFlashMessage('error', 'No tiene permisos para acceder a esta sesión');
            header('Location: test.php');
            exit;
        }
        
        // Verificar si la sesión ya está completada
        if ($sessionInfo['estado'] == 'completada') {
            setFlashMessage('info', 'Esta prueba ya ha sido completada');
            header('Location: test.php?action=results&test_id=' . $sessionInfo['prueba_id']);
            exit;
        }
        
        // Obtener información de la prueba
        $testInfo = $testManager->getTestById($sessionInfo['prueba_id']);
        
        if (!$testInfo) {
            setFlashMessage('error', 'Prueba no encontrada');
            header('Location: test.php');
            exit;
        }
        
        // Obtener preguntas de la prueba
        $questions = $testManager->getTestQuestions($sessionInfo['prueba_id'], $sessionId);
        
        // Verificar si ya se respondieron todas las preguntas
        $allAnswered = true;
        foreach ($questions as $question) {
            if (empty($question['previous_answer'])) {
                $allAnswered = false;
                break;
            }
        }
        
        // Calcular progreso
        $answeredCount = 0;
        foreach ($questions as $question) {
            if (!empty($question['previous_answer'])) {
                $answeredCount++;
            }
        }
        $progressPercentage = count($questions) > 0 ? round(($answeredCount / count($questions)) * 100) : 0;
        
        // Cargar vista para realizar la prueba
        include 'views/test/take.php';
        break;
        
    case 'save_answer':
        // Guardar respuesta (AJAX)
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        $sessionId = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        $questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        
        if (!$sessionId || !$questionId) {
            echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
            exit;
        }
        
        // Verificar que la sesión pertenece al candidato
        $sessionInfo = $testManager->getSessionInfo($sessionId);
        
        if (!$sessionInfo || $sessionInfo['candidato_id'] != $candidateId) {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos para esta sesión']);
            exit;
        }
        
        // Guardar respuesta
        $result = $testManager->saveAnswer($sessionId, $questionId, $_POST);
        
        if ($result) {
            // Obtener estadísticas actualizadas
            $progress = $testManager->getSessionProgress($sessionId);
            
            echo json_encode([
                'success' => true, 
                'answer_id' => $result,
                'progress' => $progress
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar respuesta']);
        }
        exit;
        break;
        
    case 'complete':
        // Completar prueba
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: test.php');
            exit;
        }
        
        $sessionId = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        
        if (!$sessionId) {
            setFlashMessage('error', 'Sesión no válida');
            header('Location: test.php');
            exit;
        }
        
        // Verificar que la sesión pertenece al candidato
        $sessionInfo = $testManager->getSessionInfo($sessionId);
        
        if (!$sessionInfo || $sessionInfo['candidato_id'] != $candidateId) {
            setFlashMessage('error', 'No tiene permisos para esta sesión');
            header('Location: test.php');
            exit;
        }
        
        // Completar la prueba
        $result = $testManager->completeTest($sessionId);
        
        if ($result['success']) {
            setFlashMessage('success', 'Prueba completada correctamente. Los resultados estarán disponibles en breve.');
            header('Location: test.php?action=results&test_id=' . $sessionInfo['prueba_id']);
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: test.php?action=take&session_id=' . $sessionId);
        }
        exit;
        break;
        
    case 'results':
        // Ver resultados de prueba
        $testId = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        
        if ($testId) {
            // Verificar que la prueba está completada por el candidato
            $isCompleted = $testManager->isTestCompletedByCandidate($candidateId, $testId);
            
            if (!$isCompleted) {
                setFlashMessage('error', 'Esta prueba aún no ha sido completada');
                header('Location: test.php');
                exit;
            }
            
            // Obtener información de la prueba
            $testInfo = $testManager->getTestById($testId);
            
            // Obtener resultados de la prueba
            $results = $testManager->getResultsByTest($candidateId, $testId);
            
            // Cargar vista de resultados específicos
            include 'views/test/results_specific.php';
        } else {
            // Ver resultados globales
            $results = $testManager->getResultsByCandidate($candidateId);
            $indicesResults = $testManager->getIndicesResultsByCandidate($candidateId);
            
            // Obtener el núcleo motivacional si existe
            $motivationalCore = $testManager->getMotivationalCoreByCandidate($candidateId);
            
            // Cargar vista de resultados globales
            include 'views/test/results_global.php';
        }
        break;
        
    default:
        header('Location: test.php');
        exit;
}
?>