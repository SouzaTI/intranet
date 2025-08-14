<?php
// Diretório para armazenar os arquivos de status online
$online_dir = __DIR__ . '/online_status';

// Cria o diretório se ele não existir
if (!is_dir($online_dir)) {
    mkdir($online_dir, 0755, true);
}

// Tempo em segundos que um usuário é considerado online (5 minutos)
$timeout = 300; 

// Função para contar os usuários online
function count_online_users() {
    global $online_dir, $timeout;

    $online_count = 0;
    $files = scandir($online_dir);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = $online_dir . '/' . $file;
            // Verifica se o arquivo foi modificado dentro do tempo limite
            if (filemtime($filepath) > (time() - $timeout)) {
                $online_count++;
            } else {
                // Remove o arquivo de status antigo (garbage collection)
                unlink($filepath);
            }
        }
    }
    return $online_count;
}

// Função para atualizar o status do usuário atual
function update_user_status() {
    // session_start() deve ser chamada na página principal antes de usar $_SESSION
    if (isset($_SESSION['user_id'])) {
        global $online_dir;
        $session_file = $online_dir . '/user_' . $_SESSION['user_id'] . '.txt';
        // Cria ou atualiza o timestamp do arquivo
        touch($session_file);
    }
}
?>