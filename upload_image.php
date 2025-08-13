<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

// Qualquer usuário logado pode fazer upload de imagens para procedimentos.
// A verificação de 'user_id' garante que apenas usuários autenticados possam acessar.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => ['message' => 'Acesso negado.']]);
    exit();
}

// Medida de segurança básica para o upload
$accepted_origins = ["http://localhost", "http://sua-intranet.com.br"]; // Adicione o domínio da sua intranet em produção

if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        http_response_code(403);
        echo json_encode(['error' => ['message' => 'Origem não permitida.']]);
        return;
    }
}

// Lida com o upload do arquivo
$temp = $_FILES['file']['tmp_name'];
$filetype = $_FILES['file']['type'];

// Valida o tipo de arquivo
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($filetype, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Tipo de arquivo inválido. Apenas imagens são permitidas.']]);
    return;
}

// Cria um nome de arquivo único para evitar sobreposições
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$unique_name = 'proc_img_' . uniqid() . '.' . $ext;
$filetowrite = __DIR__ . '/uploads/' . $unique_name;

if (move_uploaded_file($temp, $filetowrite)) {
    // Retorna a localização do arquivo para o TinyMCE
    $location = 'uploads/' . $unique_name;
    echo json_encode(['location' => $location]);
    $userId = $_SESSION['user_id'] ?? null;
    logActivity($userId, 'Upload de Imagem', "Arquivo: {$unique_name}");
} else {
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'Falha ao salvar o arquivo no servidor.']]);
    $userId = $_SESSION['user_id'] ?? null;
    logActivity($userId, 'Erro no Upload de Imagem', "Arquivo: {$unique_name}", 'error');
}
?>