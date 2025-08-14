<?php
// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao.php';

/**
 * Registra uma atividade no sistema.
 *
 * @param int|null $userId O ID do usuário que realizou a ação. Pode ser null para ações sem usuário logado (ex: tentativa de login falha).
 * @param string $action Uma breve descrição da ação (ex: "Login", "Atualizou perfil").
 * @param string|null $details Detalhes adicionais sobre a ação.
 * @param string $status O status da atividade (ex: "success", "error"). Padrão é "success".
 * @param string|null $ipAddress O endereço IP de onde a ação foi realizada. Se null, tentará obter o IP do servidor.
 * @return bool Retorna true em caso de sucesso, false em caso de falha.
 */
function logActivity(?int $userId, string $action, ?string $details = null, string $status = 'success', ?string $ipAddress = null): bool
{
    global $conn; // Usa a conexão global definida em conexao.php

    if ($ipAddress === null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    // Converte o endereço de loopback IPv6 para o formato IPv4 para melhor legibilidade
    if ($ipAddress === '::1') {
        $ipAddress = '127.0.0.1';
    }

    // Prepara a query SQL para inserir o log
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, status, ip_address) VALUES (?, ?, ?, ?, ?)");

    if ($stmt === false) {
        // Erro na preparação da query
        error_log("Erro ao preparar a query de log: " . $conn->error);
        return false;
    }

    // 's' para string, 'i' para integer. 's' para details, status e ip_address
    // Se userId for null, bind_param espera um tipo 's' para null, então tratamos isso.
    if ($userId === null) {
        $nullUserId = null;
        $stmt->bind_param("sssss", $nullUserId, $action, $details, $status, $ipAddress);
    } else {
        $stmt->bind_param("issss", $userId, $action, $details, $status, $ipAddress);
    }

    $success = $stmt->execute();

    if ($success === false) {
        error_log("Erro ao executar a query de log: " . $stmt->error);
    }

    $stmt->close();

    return $success;
}