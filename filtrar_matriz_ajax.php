<?php
session_start();
require_once 'conexao.php';

// Define o papel do usuário de forma segura para evitar erros com visitantes
$user_role = $_SESSION['role'] ?? 'visitor';

// --- Lógica para Matriz de Comunicação (adaptada de index.php) ---
$funcionarios_matriz = [];
$total_paginas_matriz = 1;
$pagina_atual_matriz = 1;

// Define os filtros possíveis.
$filtros_disponiveis_matriz = ['nome', 'setor', 'email', 'ramal'];
$condicoes_matriz = [];
$parametros_matriz = [];
$tipos_parametros_matriz = '';

foreach ($filtros_disponiveis_matriz as $filtro) {
    if (!empty($_GET[$filtro])) {
        if ($filtro === 'setor') {
            $condicoes_matriz[] = "`setor` = ?";
            $parametros_matriz[] = $_GET[$filtro];
        } else {
            $condicoes_matriz[] = "`$filtro` LIKE ?";
            $parametros_matriz[] = '%' . $_GET[$filtro] . '%';
        }
        $tipos_parametros_matriz .= 's';
    }
}

// Monta a query de contagem
$sql_count_matriz = "SELECT COUNT(*) FROM matriz_comunicacao";
if (count($condicoes_matriz) > 0) {
    $sql_count_matriz .= " WHERE " . implode(' AND ', $condicoes_matriz);
}

$stmt_count = $conn->prepare($sql_count_matriz);
if ($stmt_count && count($parametros_matriz) > 0) {
    $stmt_count->bind_param($tipos_parametros_matriz, ...$parametros_matriz);
}
if ($stmt_count) {
    $stmt_count->execute();
    $total_resultados_matriz = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
} else {
    $total_resultados_matriz = 0;
}

// Define variáveis de paginação
$resultados_por_pagina_matriz = 20;
$total_paginas_matriz = $total_resultados_matriz > 0 ? ceil($total_resultados_matriz / $resultados_por_pagina_matriz) : 1;
$pagina_atual_matriz = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_atual_matriz = max(1, min($pagina_atual_matriz, $total_paginas_matriz));
$offset_matriz = ($pagina_atual_matriz - 1) * $resultados_por_pagina_matriz;

// Monta a query principal
$sql_matriz = "SELECT id, nome, setor, email, ramal FROM matriz_comunicacao";
if (count($condicoes_matriz) > 0) {
    $sql_matriz .= " WHERE " . implode(' AND ', $condicoes_matriz);
}
$sql_matriz .= " ORDER BY nome ASC LIMIT ?, ?";
$parametros_matriz[] = $offset_matriz;
$parametros_matriz[] = $resultados_por_pagina_matriz;
$tipos_parametros_matriz .= 'ii';

$stmt_main = $conn->prepare($sql_matriz);
if ($stmt_main) {
    $stmt_main->bind_param($tipos_parametros_matriz, ...$parametros_matriz);
    $stmt_main->execute();
    $result_matriz = $stmt_main->get_result();
    $funcionarios_matriz = $result_matriz->fetch_all(MYSQLI_ASSOC);
    $stmt_main->close();
}

// --- Geração do HTML para a resposta AJAX ---

// 1. HTML da Tabela
ob_start();
if (count($funcionarios_matriz) > 0) {
    foreach ($funcionarios_matriz as $funcionario) {
        // AQUI ESTÁ A CORREÇÃO: Usar a variável segura $user_role
        $is_admin_tab = in_array($user_role, ['admin', 'god']);
        include 'partials/matriz_comunicacao_row.php'; // Inclui o template da linha
    }
} else {
    echo '<tr><td colspan="4" class="py-4 px-4 text-center text-gray-500">Nenhum funcionário encontrado.</td></tr>';
}
$table_html = ob_get_clean();

// 2. HTML da Paginação
ob_start();
if ($total_paginas_matriz > 1) {
    $query_params = $_GET;
    echo '<nav class="flex items-center space-x-2">';
    for ($i = 1; $i <= $total_paginas_matriz; $i++) {
        $query_params['pagina'] = $i;
        // O link deve apontar para o próprio script AJAX para funcionar com a delegação de evento no JS
        $link = 'filtrar_matriz_ajax.php?' . http_build_query($query_params);
        $active_class = ($i == $pagina_atual_matriz) ? 'bg-[#254c90] text-white' : 'bg-white text-[#254c90] hover:bg-gray-100';
        echo "<a href=\"{$link}\" class=\"px-3 py-1 border border-gray-300 rounded-md text-sm {$active_class}\">{$i}</a>";
    }
    echo '</nav>';
}
$pagination_html = ob_get_clean();

// 3. Retorna a resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'table_html' => $table_html,
    'pagination_html' => $pagination_html
]);

$conn->close();
?>

