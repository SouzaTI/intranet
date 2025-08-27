<?php
/**
 * Este arquivo renderiza a tabela da Matriz de Comunicação para a aba "Informações".
 * Ele assume que a variável $funcionarios_matriz já foi populada
 * pelo script que o está incluindo (index.php).
 */
?>
<div class="overflow-x-auto">
    <!-- Formulário oculto para AJAX -->
    <form id="form-filtro-matriz-tab" class="hidden">
        <input type="hidden" name="section" value="information">
        <input type="hidden" name="tab" value="matriz">
        <input type="hidden" id="filtro_setor_hidden" name="setor" value="<?= htmlspecialchars($_GET['setor'] ?? '') ?>">
    </form>

    <!-- Barra de Ações e Filtros -->
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-4">
        <!-- Filtro de Setores por Botões -->
        <div id="filtro-setor-botoes" class="flex flex-wrap gap-2 items-center">
            <span class="text-sm font-medium text-gray-700 mr-2">Setores:</span>
            <?php
            $current_setor = $_GET['setor'] ?? '';
            $class_todos = 'filter-pill-btn ' . (empty($current_setor) ? 'active' : 'inactive');
            ?>
            <button type="button" data-setor="" class="filtro-setor-btn <?= $class_todos ?>">Todos</button>
            <?php
            $result_setores_botoes = $conn->query("SELECT DISTINCT setor FROM matriz_comunicacao WHERE setor IS NOT NULL AND setor != '' ORDER BY setor ASC");
            if ($result_setores_botoes) {
                while ($setor_item = $result_setores_botoes->fetch_assoc()) {
                    $nome_setor = htmlspecialchars($setor_item['setor']);
                    $class_setor = 'filter-pill-btn ' . (($current_setor === $setor_item['setor']) ? 'active' : 'inactive');
                    echo "<button type=\"button\" data-setor=\"{$nome_setor}\" class=\"filtro-setor-btn {$class_setor}\">{$nome_setor}</button>";
                }
            }
            ?>
        </div>
        <!-- Botão Adicionar -->
        <div>
            <?php 
            // Usa a variável $user_role definida em index.php para evitar erro quando não logado
            if (isset($user_role) && in_array($user_role, ['admin', 'god'])): 
            ?>
                <button type="button" id="btn-adicionar-funcionario-tab" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" title="Adicionar novo registro"><i class="fas fa-plus"></i> Adicionar</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulário para Adicionar Novo Funcionário (oculto por padrão) -->
    <div id="form-adicionar-funcionario-tab" class="hidden bg-gray-100 p-6 rounded-lg border border-gray-300 my-6">
        <h3 class="text-lg font-semibold text-[#254c90] mb-4">Adicionar Novo Funcionário</h3>
        <form action="adicionar_funcionario_matriz.php" method="POST" class="space-y-4">
            <input type="hidden" name="source" value="information_tab">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div><label for="novo_nome_tab" class="block text-sm font-medium text-gray-700">Nome:</label><input type="text" id="novo_nome_tab" name="nome" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"></div>
                <div><label for="novo_setor_tab" class="block text-sm font-medium text-gray-700">Setor:</label><input type="text" id="novo_setor_tab" name="setor" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"></div>
                <div><label for="novo_email_tab" class="block text-sm font-medium text-gray-700">E-mail:</label><input type="email" id="novo_email_tab" name="email" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"></div>
                <div><label for="novo_ramal_tab" class="block text-sm font-medium text-gray-700">Ramal:</label><input type="text" id="novo_ramal_tab" name="ramal" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"></div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-cancelar-adicao-tab" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Salvar Funcionário</button>
            </div>
        </form>
    </div>

    <table class="min-w-full bg-white">
        <thead class="bg-[#254c90] text-white">
            <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold">Nome</th>
                <th class="py-3 px-4 text-left text-sm font-semibold">Setor</th>
                <th class="py-3 px-4 text-left text-sm font-semibold">E-mail</th>
                <th class="py-3 px-4 text-left text-sm font-semibold">Ramal</th>
                <?php if (isset($user_role) && in_array($user_role, ['admin', 'god'])): ?>
                    <th class="py-3 px-4 text-center text-sm font-semibold">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="matriz-comunicacao-tbody" class="divide-y divide-gray-200">
            <?php if (isset($funcionarios_matriz) && count($funcionarios_matriz) > 0): ?>
                <?php foreach ($funcionarios_matriz as $funcionario): ?>
                    <?php 
                    $is_admin_tab = isset($user_role) && in_array($user_role, ['admin', 'god']); 
                    ?>
                    <tr class="hover:bg-gray-50 matriz-card" data-id="<?= $funcionario['id'] ?>">
                        <td class="py-3 px-4 text-sm text-gray-700" data-column="nome">
                            <div class="cell-content-wrapper">
                                <span class="cell-content"><?= htmlspecialchars($funcionario['nome']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-700" data-column="setor">
                            <div class="cell-content-wrapper">
                                <span class="cell-content"><?= htmlspecialchars($funcionario['setor']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-700" data-column="email">
                            <div class="cell-content-wrapper">
                                <span class="cell-content"><?= htmlspecialchars($funcionario['email']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-700" data-column="ramal">
                            <div class="cell-content-wrapper">
                                <span class="cell-content"><?= htmlspecialchars($funcionario['ramal']) ?></span>
                            </div>
                        </td>
                        <?php if ($is_admin_tab): ?>
                            <td class="py-3 px-4 text-sm text-center">
                                <i class="fa-solid fa-pen-to-square edit-trigger-card cursor-pointer text-blue-500 hover:text-blue-700" title="Editar"></i>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo (isset($user_role) && in_array($user_role, ['admin', 'god'])) ? '5' : '4'; ?>" class="py-4 px-4 text-center text-gray-500">Nenhum funcionário encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Controles de Paginação -->
    <div id="matriz-comunicacao-pagination" class="mt-6 flex justify-center">
        <nav class="flex items-center space-x-2">
            <?php
            if (isset($total_paginas_matriz) && $total_paginas_matriz > 1):
                $query_params = $_GET;
                for ($i = 1; $i <= $total_paginas_matriz; $i++):
                    $query_params['pagina'] = $i;
                    $link = 'index.php?' . http_build_query($query_params);
                    $active_class = ($i == $pagina_atual_matriz) ? 'bg-[#254c90] text-white' : 'bg-white text-[#254c90] hover:bg-gray-100';
            ?>
                <a href="<?= $link ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm <?= $active_class ?>"><?= $i ?></a>
            <?php endfor; endif; ?>
        </nav>
    </div>
</div>
