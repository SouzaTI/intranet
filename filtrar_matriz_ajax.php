<?php
session_start();
require_once 'conexao.php';

// --- Lógica de Filtro e Paginação (reutilizada do index.php) ---
$funcionarios_matriz = [];
$total_paginas_matriz = 1;
$pagina_atual_matriz = 1;

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

$sql_count_matriz = "SELECT COUNT(*) FROM matriz_comunicacao";
if (count($condicoes_matriz) > 0) {
    $sql_count_matriz .= " WHERE " . implode(' AND ', $condicoes_matriz);
}

$stmt_count = $conn->prepare($sql_count_matriz);
if (count($parametros_matriz) > 0) {
    $stmt_count->bind_param($tipos_parametros_matriz, ...$parametros_matriz);
}
$stmt_count->execute();
$total_resultados_matriz = $stmt_count->get_result()->fetch_row()[0];

$resultados_por_pagina_matriz = 20;
$total_paginas_matriz = $total_resultados_matriz > 0 ? ceil($total_resultados_matriz / $resultados_por_pagina_matriz) : 1;
$pagina_atual_matriz = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_atual_matriz = max(1, min($pagina_atual_matriz, $total_paginas_matriz));
$offset_matriz = ($pagina_atual_matriz - 1) * $resultados_por_pagina_matriz;

$sql_matriz = "SELECT id, nome, setor, email, ramal FROM matriz_comunicacao";
if (count($condicoes_matriz) > 0) {
    $sql_matriz .= " WHERE " . implode(' AND ', $condicoes_matriz);
}
$sql_matriz .= " ORDER BY nome ASC LIMIT ?, ?";
$parametros_matriz_main = $parametros_matriz;
$parametros_matriz_main[] = $offset_matriz;
$parametros_matriz_main[] = $resultados_por_pagina_matriz;
$tipos_parametros_matriz_main = $tipos_parametros_matriz . 'ii';

$stmt_main = $conn->prepare($sql_matriz);
$stmt_main->bind_param($tipos_parametros_matriz_main, ...$parametros_matriz_main);
$stmt_main->execute();
$result_matriz = $stmt_main->get_result();
$funcionarios_matriz = $result_matriz->fetch_all(MYSQLI_ASSOC);

// --- Geração do HTML para a Tabela ---
ob_start();
if (count($funcionarios_matriz) > 0):
    foreach ($funcionarios_matriz as $funcionario):
        $is_admin_ajax = in_array($_SESSION['role'], ['admin', 'god']);
?>
        <tr class="hover:bg-gray-50" data-id="<?= $funcionario['id'] ?>">
            <td class="py-3 px-4 text-sm text-gray-700" data-column="nome">
                <div class="cell-content-wrapper">
                    <span class="cell-content"><?= htmlspecialchars($funcionario['nome']) ?></span>
                    <?php if ($is_admin_ajax): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                </div>
            </td>
            <td class="py-3 px-4 text-sm text-gray-700" data-column="setor">
                <div class="cell-content-wrapper">
                    <span class="cell-content"><?= htmlspecialchars($funcionario['setor']) ?></span>
                    <?php if ($is_admin_ajax): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                </div>
            </td>
            <td class="py-3 px-4 text-sm text-gray-700" data-column="email">
                <div class="cell-content-wrapper">
                    <span class="cell-content"><?= htmlspecialchars($funcionario['email']) ?></span>
                    <?php if ($is_admin_ajax): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                </div>
            </td>
            <td class="py-3 px-4 text-sm text-gray-700" data-column="ramal">
                <div class="cell-content-wrapper">
                    <span class="cell-content"><?= htmlspecialchars($funcionario['ramal']) ?></span>
                    <?php if ($is_admin_ajax): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                </div>
            </td>
        </tr>
    <?php
    endforeach;
else: ?>
    <tr>
        <td colspan="4" class="py-4 px-4 text-center text-gray-500">Nenhum funcionário encontrado.</td>
    </tr>
<?php endif;
$table_html = ob_get_clean();

// --- Geração do HTML para a Paginação ---
ob_start();
if (isset($total_paginas_matriz) && $total_paginas_matriz > 1):
    $query_params = $_GET;
    ?>
    <nav class="flex items-center space-x-2">
        <?php for ($i = 1; $i <= $total_paginas_matriz; $i++):
            $query_params['pagina'] = $i;
            $link = 'index.php?' . http_build_query($query_params);
            $active_class = ($i == $pagina_atual_matriz) ? 'bg-[#254c90] text-white' : 'bg-white text-[#254c90] hover:bg-gray-100';
            ?><a href="<?= $link ?>" class="ajax-page-link px-3 py-1 border border-gray-300 rounded-md text-sm <?= $active_class ?>"><?= $i ?></a><?php
        endfor; ?>
    </nav>
<?php endif;
$pagination_html = ob_get_clean();

// --- Resposta JSON ---
header('Content-Type: application/json');
echo json_encode(['table_html' => $table_html, 'pagination_html' => $pagination_html]);