<?php
// Ajuste os dados conforme seu ambiente
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'intranet';

$conn = new mysqli($host, $user, $pass, $db);

// Verifica conexão
if ($conn->connect_error) {
    die('Erro de conexão com o banco de dados: ' . $conn->connect_error);
}