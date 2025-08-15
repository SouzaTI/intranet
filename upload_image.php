<?php
// upload_image.php

// Diretório para salvar as imagens
$target_dir = "uploads/";

// Verifica se a pasta de uploads existe, se não, cria
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Pega o arquivo enviado
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $file_name = uniqid() . '-' . basename($file["name"]);
    $target_file = $target_dir . $file_name;

    // Move o arquivo para o diretório de uploads
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Retorna a localização da imagem em formato JSON para o TinyMCE
        echo json_encode(['location' => $target_file]);
    } else {
        // Retorna um erro se não conseguir mover o arquivo
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to move uploaded file.']);
    }
} else {
    // Retorna um erro se nenhum arquivo foi enviado
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No file uploaded.']);
}
?>