<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

if (!in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: intranet.php");
    exit();
}

$id = intval($_POST['id']);
$titulo = $conn->real_escape_string($_POST['titulo']);
$conteudo = $conn->real_escape_string($_POST['conteudo']);

if ($conn->query("UPDATE sidebar_botoes SET titulo='$titulo', conteudo='$conteudo' WHERE id=$id")) {
    $loggedInUserId = $_SESSION['user_id'];
    logActivity($loggedInUserId, 'Editou o botão', "Botão: {$titulo} (ID: {$id})");
} else {
    $loggedInUserId = $_SESSION['user_id'];
    logActivity($loggedInUserId, 'Erro ao editar o botão', "Tentativa para botão: {$titulo} (ID: {$id})", 'error');
}

header("Location: intranet.php?botao_id=$id");
exit();