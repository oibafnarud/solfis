<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: comments.php?message=comment-error');
    exit;
}

// Prevenir aprobación duplicada verificando el estado actual
$comment = new Comment();
$commentId = (int)$_GET['id'];

// Verificar si el comentario ya está aprobado
$commentData = $comment->getCommentById($commentId);
if (!$commentData) {
    header('Location: comments.php?message=comment-error');
    exit;
}

// Solo aprobar si el comentario no está ya aprobado
if ($commentData['status'] !== 'approved') {
    $result = $comment->approveComment($commentId);
    
    if ($result) {
        header('Location: comments.php?message=comment-approved');
    } else {
        header('Location: comments.php?message=comment-error');
    }
} else {
    // El comentario ya estaba aprobado
    header('Location: comments.php?message=comment-already-approved');
}
exit;