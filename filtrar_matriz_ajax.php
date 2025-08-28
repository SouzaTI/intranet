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
            $condicoes_matriz[] = "mc.`setor` = ?";
            $parametros_matriz[] = $_GET[$filtro];
        } else {
            $condicoes_matriz[] = "mc.`$filtro` LIKE ?";
            $parametros_matriz[] = '%' . $_GET[$filtro] . '%';
        }
        $tipos_parametros_matriz .= 's';
    }
}

// Monta a query de contagem
$sql_count_matriz = "SELECT COUNT(mc.id) FROM matriz_comunicacao mc";
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
$sql_matriz = "SELECT mc.id, mc.nome, mc.setor, mc.email, mc.ramal, u.username AS associated_username, u.profile_photo AS associated_user_photo FROM matriz_comunicacao mc LEFT JOIN users u ON mc.id = u.matriz_comunicacao_id";
if (count($condicoes_matriz) > 0) {
    $sql_matriz .= " WHERE " . implode(' AND ', $condicoes_matriz);
}
$sql_matriz .= " ORDER BY mc.nome ASC LIMIT ?, ?";
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

// 1. HTML dos Cards
ob_start();
if (count($funcionarios_matriz) > 0) {
    foreach ($funcionarios_matriz as $funcionario) {
        $is_admin = in_array($user_role, ['admin', 'god']);
?>
        <div class="matriz-card contact-card-clickable bg-gray-50 rounded-xl shadow-lg overflow-hidden relative cursor-pointer hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
            data-id="<?= $funcionario['id'] ?>"
            data-nome="<?= htmlspecialchars($funcionario['nome']) ?>"
            data-setor="<?= htmlspecialchars($funcionario['setor']) ?>"
            data-email="<?= htmlspecialchars($funcionario['email']) ?>"
            data-ramal="<?= htmlspecialchars($funcionario['ramal']) ?>">

            <!-- Botão de Edição para Admin -->
            <?php if ($is_admin): ?>
                <a href="#" class="edit-trigger-card absolute top-3 right-3 z-20 p-2 block rounded-full bg-white/60 hover:bg-white transition-colors duration-200" title="Editar Card">
                    <i class="fa-solid fa-pen-to-square text-gray-500 hover:text-blue-700"></i>
                </a>
            <?php endif; ?>

            <!-- Cabeçalho do Card -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 p-3 flex items-center">
                <img src="img/Slogan branco.png" alt="Logo" class="h-8 w-auto mr-4">
                <h3 class="text-white font-bold text-sm uppercase tracking-wider">Matriz de Comunicação</h3>
            </div>

            <!-- Corpo do Card -->
            <div class="p-5 flex sm:flex-row flex-col sm:space-x-5 items-center">
                <!-- Foto do Usuário -->
                <div class="flex-shrink-0 mb-4 sm:mb-0">
                    <?php 
                    $photo_path = $funcionario['associated_user_photo'];
                    if (!empty($photo_path) && file_exists($photo_path)): 
                    ?>
                        <img src="<?= htmlspecialchars($photo_path) ?>?t=<?= time() ?>" alt="Foto de <?= htmlspecialchars($funcionario['nome']) ?>" class="h-28 w-28 rounded-full object-cover border-4 border-white shadow-lg">
                    <?php else: ?>
                        <div class="h-28 w-28 rounded-full bg-gray-200 flex items-center justify-center border-4 border-white shadow-lg">
                            <i class="fas fa-user fa-3x text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Detalhes do Funcionário -->
                <div class="flex-grow text-center sm:text-left">
                    <div class="font-bold text-2xl text-gray-800 mb-2 cell-content-wrapper" data-column="nome">
                        <span class="cell-content"><?= htmlspecialchars($funcionario['nome']) ?></span>
                    </div>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p class="cell-content-wrapper" data-column="setor">
                            <strong class="font-semibold text-gray-700"><i class="fas fa-briefcase w-4 mr-1"></i>Setor:</strong>
                            <span class="cell-content ml-1"><?= htmlspecialchars($funcionario['setor']) ?></span>
                        </p>
                        <p class="cell-content-wrapper" data-column="email">
                            <strong class="font-semibold text-gray-700"><i class="fas fa-envelope w-4 mr-1"></i>Email:</strong>
                            <span class="cell-content ml-1"><?= htmlspecialchars($funcionario['email']) ?></span>
                        </p>
                        <p class="cell-content-wrapper" data-column="ramal">
                            <strong class="font-semibold text-gray-700"><i class="fas fa-phone w-4 mr-1"></i>Ramal:</strong>
                            <span class="cell-content ml-1"><?= htmlspecialchars($funcionario['ramal']) ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
} else {
    echo '<div class="col-span-full text-center text-gray-500 py-4">Nenhum resultado encontrado.</div>';
}
$cards_html = ob_get_clean();

// 2. HTML da Paginação
ob_start();
if ($total_paginas_matriz > 1) {
    $query_params = $_GET;
    echo '<nav class="flex items-center space-x-2">';
    for ($i = 1; $i <= $total_paginas_matriz; $i++) {
        $query_params['pagina'] = $i;
        // O link deve apontar para o próprio script AJAX para funcionar com a delegação de evento no JS
        $link = 'index.php?section=matriz_comunicacao&' . http_build_query($query_params);
        $active_class = ($i == $pagina_atual_matriz) ? 'bg-[#254c90] text-white' : 'bg-white text-[#254c90] hover:bg-gray-100';
        echo "<a href=\"{$link}\" class=\"px-3 py-1 border border-gray-300 rounded-md text-sm {$active_class}\">{$i}</a>";
    }
    echo '</nav>';
}
$pagination_html = ob_get_clean();

// 3. Retorna a resposta JSON
header('Content-Type: application/json');
echo json_encode([
        'table_html' => $cards_html,
    'pagination_html' => $pagination_html
]);


$conn->close();
?>
