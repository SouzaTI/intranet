<?php
session_start();
require_once 'log_activity.php'; // Inclui o arquivo de log

// Registra o logout antes de destruir a sessão
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? 'desconhecido';
if ($userId) {
    logActivity($userId, 'Logout', "O usuário {$userName} saiu do sistema.");
}

session_destroy();
header("Location: index.php");
exit();
?>