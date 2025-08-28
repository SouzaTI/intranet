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

    <!-- Grid de Cards -->
    <div id="matriz-comunicacao-tbody" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php if (isset($funcionarios_matriz) && count($funcionarios_matriz) > 0): ?>
            <?php foreach ($funcionarios_matriz as $funcionario): ?>
                <?php 
                $is_admin = isset($user_role) && in_array($user_role, ['admin', 'god']);
                // Precisamos buscar a foto do usuário associado, se houver.
                // Esta lógica está no AJAX, vamos replicá-la aqui.
                $funcionario['associated_user_photo'] = ''; // Default
                if (!empty($funcionario['id'])) {
                    $stmt_user = $conn->prepare("SELECT profile_photo FROM users WHERE matriz_comunicacao_id = ?");
                    if ($stmt_user) {
                        $stmt_user->bind_param("i", $funcionario['id']);
                        $stmt_user->execute();
                        $result_user = $stmt_user->get_result();
                        if ($user_data = $result_user->fetch_assoc()) {
                            $funcionario['associated_user_photo'] = $user_data['profile_photo'];
                        }
                        $stmt_user->close();
                    }
                }
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
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center text-gray-500 py-10">
                <div class="mx-auto w-24 h-24">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2zm3-12V3m12 0v2" />
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum funcionário encontrado</h3>
                <p class="mt-1 text-sm text-gray-500">Tente ajustar seus filtros ou adicione um novo funcionário.</p>
            </div>
        <?php endif; ?>
    </div>

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
