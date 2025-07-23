<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: intranet.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "intranet");
$id = intval($_POST['id']);
$titulo = $conn->real_escape_string($_POST['titulo']);
$conteudo = $conn->real_escape_string($_POST['conteudo']);
$conn->query("UPDATE sidebar_botoes SET titulo='$titulo', conteudo='$conteudo' WHERE id=$id");
header("Location: intranet.php?botao_id=$id");
exit();