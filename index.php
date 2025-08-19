<?php
session_start();
require_once 'conexao.php'; // ajuste o nome se for diferente
require_once 'log_activity.php'; // Inclui a função de log

// Obter todas as FAQs ativas para exibição na página principal
$faqs_public = [];
$result_faqs_public = $conn->query("SELECT id, question, answer FROM faqs WHERE is_active = 1 ORDER BY id ASC");
if ($result_faqs_public) {
    while ($row = $result_faqs_public->fetch_assoc()) {
        $faqs_public[] = $row;
    }
}

// --- Início da Lógica para Atalho de FAQ ---
if (isset($_GET['faq_atalho']) && !empty($_GET['faq_atalho'])) {
    $atalho_slug = $_GET['faq_atalho'];
    $stmt_atalho = $conn->prepare("SELECT id FROM faqs WHERE atalho = ? AND is_active = 1");
    if ($stmt_atalho) {
        $stmt_atalho->bind_param("s", $atalho_slug);
        $stmt_atalho->execute();
        $result_atalho = $stmt_atalho->get_result();
        if ($faq_row = $result_atalho->fetch_assoc()) {
            // Redireciona para a seção de FAQ com um parâmetro para o JS identificar
            header("Location: index.php?section=faq&highlight_faq=" . $faq_row['id']);
            exit();
        }
        $stmt_atalho->close();
    }
}
// --- Fim da Lógica para Atalho de FAQ ---

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Função para verificar permissão de visualização de seção
function can_view_section($section_name) {
    // Admins e God podem ver tudo
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) {
        return true;
    }
    
    // Usuários normais verificam a lista de permissões na sessão
    if (isset($_SESSION['allowed_sections']) && in_array($section_name, $_SESSION['allowed_sections'])) {
        return true;
    }

    // Se chegou até aqui, o usuário não tem permissão
    return false;
}

// Como o usuário é sempre redirecionado se não estiver logado, podemos pegar os dados da sessão diretamente.
$username = $_SESSION['username'];

// Conta PDFs
$pdfCount = $conn->query("SELECT COUNT(*) as total FROM arquivos WHERE tipo='pdf'")->fetch_assoc()['total'] ?? 0;
// Conta Informações (Word, PowerPoint, outros, ou ajuste conforme sua regra)
$infoCount = $conn->query("SELECT COUNT(*) as total FROM arquivos WHERE tipo='word' OR tipo='ppt' OR tipo='informacao'")->fetch_assoc()['total'] ?? 0;

// Busca todos os setores para o menu e formulários
$setores = [];
$result_setores = $conn->query("SELECT * FROM setores ORDER BY nome ASC");
if ($result_setores) {
    while ($setor = $result_setores->fetch_assoc()) {
        $setores[] = $setor;
    }
}

// Lista de todas as seções disponíveis para o painel de permissões
$available_sections = [
    'dashboard' => 'Página Inicial',
    'documents' => 'Normas e Procedimentos',
    'information' => 'Informações (Visualização)',
    'matriz_comunicacao' => 'Matriz de Comunicação',
    'sugestoes' => 'Sugestões e Reclamações (Envio)',
    'create_procedure' => 'Criar Procedimento',
    'faq' => 'FAQ',    
    'profile' => 'Meu Perfil',
    'about' => 'Sobre Nós',
    'sistema' => 'Sistema',
    // Seções de Admin
    
    'info-upload' => 'Cadastrar Informação (Admin)',
    'registros_sugestoes' => 'Registros de Sugestões (Admin)',
    'settings' => 'Configurações (Admin)',
    'manage_faq_section' => 'Gerenciar FAQs',
];

// Busca todos os usuários para a aba de permissões (apenas para admins)
$usuarios = [];
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) {
    $result_usuarios = $conn->query("
        SELECT u.id, u.username, u.role, s.nome as setor_nome
        FROM users u
        LEFT JOIN setores s ON u.setor_id = s.id
        ORDER BY u.username ASC
    ");
    if ($result_usuarios) {
        while ($usuario = $result_usuarios->fetch_assoc()) {
            $usuarios[] = $usuario;
        }
    }
}

// Busca sistemas externos para a página de Sistemas
$sistemas_externos = [];
$user_setor_id = $_SESSION['setor_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'user';
$user_department = $_SESSION['department'] ?? null;

// Se o usuário for admin ou god, mostra todos os sistemas. Senão, filtra por setor.
if (in_array($user_role, ['admin', 'god'])) {
    $sql_sistemas = "SELECT * FROM sistemas_externos ORDER BY nome ASC";
    $result_sistemas = $conn->query($sql_sistemas);
} else {
    // A consulta para usuários normais filtra por setor_id ou por atalhos globais (setor_id IS NULL)
    $sql_sistemas = "SELECT * FROM sistemas_externos WHERE setor_id = ? OR setor_id IS NULL ORDER BY nome ASC";
    $stmt_sistemas = $conn->prepare($sql_sistemas);
    if ($stmt_sistemas) {
        $stmt_sistemas->bind_param("i", $user_setor_id);
        $stmt_sistemas->execute();
        $result_sistemas = $stmt_sistemas->get_result();
        $stmt_sistemas->close();
    } else {
        $result_sistemas = false; // Garante que a variável exista em caso de falha na preparação
    }
}

if ($result_sistemas) {
    while ($sistema = $result_sistemas->fetch_assoc()) {
        $sistemas_externos[] = $sistema;
    }
}

// --- Início da Lógica para Matriz de Comunicação ---
$funcionarios_matriz = [];
$total_paginas_matriz = 1;
$pagina_atual_matriz = 1;
$query_string_matriz = '';

// Define os filtros possíveis.
$filtros_disponiveis_matriz = ['nome', 'setor', 'email', 'ramal'];
$condicoes_matriz = [];
$parametros_matriz = [];
$tipos_parametros_matriz = '';

foreach ($filtros_disponiveis_matriz as $filtro) {
    if (!empty($_GET[$filtro])) {
        if ($filtro === 'setor') {
            // Para o filtro de setor (pílulas), usamos correspondência exata
            $condicoes_matriz[] = "`setor` = ?";
            $parametros_matriz[] = $_GET[$filtro];
        } else {
            // Para outros filtros, mantemos a busca parcial com LIKE
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
if (count($parametros_matriz) > 0) {
    $stmt_count->bind_param($tipos_parametros_matriz, ...$parametros_matriz);
}
$stmt_count->execute();
$total_resultados_matriz = $stmt_count->get_result()->fetch_row()[0];

// Define variáveis de paginação
$resultados_por_pagina_matriz = 20; // Você pode ajustar este valor
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
$stmt_main->bind_param($tipos_parametros_matriz, ...$parametros_matriz);
$stmt_main->execute();
$result_matriz = $stmt_main->get_result();
$funcionarios_matriz = $result_matriz->fetch_all(MYSQLI_ASSOC);
// --- Fim da Lógica para Matriz de Comunicação ---

// --- Início da Lógica para Gerenciar FAQs (incorporado de manage_faq.php) ---
$manage_faq_message = '';
$manage_faq_to_edit = null;

// Lidar com ações de POST (adicionar, editar, excluir) para FAQs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faq_action'])) {
    if ($_POST['faq_action'] === 'add' || $_POST['faq_action'] === 'edit') {
        $question = $_POST['question'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $atalho = trim($_POST['atalho'] ?? '');
        $atalho = empty($atalho) ? null : preg_replace('/[^a-z0-9\-]/', '', strtolower($atalho)); // Sanitiza o atalho
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $id = $_POST['id'] ?? null;

        if (empty($question) || empty($answer)) {
            $manage_faq_message = '<div class="alert alert-danger">Pergunta e resposta não podem ser vazias.</div>';
        } else {
            if ($_POST['faq_action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO faqs (question, answer, atalho, is_active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $question, $answer, $atalho, $is_active);
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Adicionou FAQ', "Pergunta: " . substr($question, 0, 100) . "...");
                    $manage_faq_message = '<div class="alert alert-success">FAQ adicionada com sucesso!</div>';
                } else {
                    logActivity($_SESSION['user_id'], 'Erro ao adicionar FAQ', "Pergunta: " . substr($question, 0, 100) . "...", 'error');
                    $manage_faq_message = '<div class="alert alert-danger">Erro ao adicionar FAQ: ' . $conn->error . '</div>';
                }
            } else { // faq_action is edit
                $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, atalho = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssii", $question, $answer, $atalho, $is_active, $id);
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Editou FAQ', "ID: {$id} | Pergunta: " . substr($question, 0, 100) . "...");
                    $manage_faq_message = '<div class="alert alert-success">FAQ atualizada com sucesso!</div>';
                } else {
                    logActivity($_SESSION['user_id'], 'Erro ao editar FAQ', "ID: {$id} | Pergunta: " . substr($question, 0, 100) . "...", 'error');
                    $manage_faq_message = '<div class="alert alert-danger">Erro ao atualizar FAQ: ' . $conn->error . '</div>';
                }
            }
            $stmt->close();
        }
    } elseif ($_POST['faq_action'] === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Busca a pergunta antes de deletar para o log
            $stmt_get_question = $conn->prepare("SELECT question FROM faqs WHERE id = ?");
            $stmt_get_question->bind_param("i", $id);
            $stmt_get_question->execute();
            $faq_question = $stmt_get_question->get_result()->fetch_assoc()['question'] ?? "ID: {$id}";
            $stmt_get_question->close();

            $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Excluiu FAQ', "Pergunta: " . substr($faq_question, 0, 100) . "...");
                $manage_faq_message = '<div class="alert alert-success">FAQ excluída com sucesso!</div>';
            } else {
                logActivity($_SESSION['user_id'], 'Erro ao excluir FAQ', "Tentativa para FAQ: " . substr($faq_question, 0, 100) . "...", 'error');
                $manage_faq_message = '<div class="alert alert-danger">Erro ao excluir FAQ: ' . $conn->error . '</div>';
            }
            $stmt->close();
        }
    }
}

// Lidar com ações de GET (editar FAQ específica)
if (isset($_GET['faq_action']) && $_GET['faq_action'] === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, question, answer, atalho, is_active FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $manage_faq_to_edit = $result->fetch_assoc();
    $stmt->close();
}

// Obter todas as FAQs para exibição na seção de gerenciamento
$manage_faqs = [];
$result_manage_faqs = $conn->query("SELECT id, question, answer, is_active FROM faqs ORDER BY created_at DESC");
if ($result_manage_faqs) {
    while ($row = $result_manage_faqs->fetch_assoc()) {
        $manage_faqs[] = $row;
    }
}
// --- Fim da Lógica para Gerenciar FAQs ---

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Intranet</title>
    <!-- 1. Adicionar CSS do Shepherd.js e o nosso CSS customizado (agora como .php) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css"/>
    <link rel="stylesheet" href="tour.css.php">
    <!-- Adicione o script do TinyMCE aqui. Substitua 'no-api-key' pela sua chave. -->
    <script src="https://cdn.tiny.cloud/1/5qvlwlt06xkybekjra4hcv0z7czafww8a0wcki2x19ftngew/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- 3. Adicionar JS do Shepherd.js e o nosso script do tour (movido para o head com 'defer') -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
    <div class="flex h-screen bg-[#F5F7FA]">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform md:relative md:translate-x-0 transition duration-200 ease-in-out z-20">
            <div class="flex items-center justify-between px-4">
                <div class="flex items-center space-x-2">
                    <img src="img/logo.svg" alt="Logo" class="w-32">
                </div>
                <button id="closeSidebar" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="mt-10">
                <div class="px-4 py-2 uppercase text-xs font-semibold">Menu Principal</div>
                <?php if (can_view_section('dashboard')): ?>
                <a href="#" data-section="dashboard" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('dashboard', true); return false;">
                    <i class="fas fa-home w-6"></i>
                    <span>Página Inicial</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('documents')): ?>
                <a href="#" data-section="documents" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('documents', true); return false;">
                    <i class="fas fa-book w-6"></i>
                    <span>Normas e Procedimentos</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('information')): ?>
                <a href="#" data-section="information" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('information', true); return false;">
                    <i class="fas fa-info-circle w-6"></i>
                    <span>Informações</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('matriz_comunicacao')): ?>
                <a href="#" data-section="matriz_comunicacao" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('matriz_comunicacao', true); return false;">
                    <i class="fas fa-sitemap w-6"></i>
                    <span>Matriz de Comunicação</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('create_procedure')): ?>
                <a href="#" data-section="create_procedure" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('create_procedure', true); return false;">
                    <i class="fas fa-file-signature w-6"></i>
                    <span>Criar Procedimento</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('sugestoes')): ?>
                <a href="#" data-section="sugestoes" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('sugestoes', true); return false;">
                    <i class="fas fa-comment-dots w-6"></i>
                    <span>Sugestões e Reclamações</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('sistema')): ?>
                <a href="#" data-section="sistema" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('sistema', true); return false;">
                    <i class="fas fa-desktop w-6"></i>
                    <span>Sistemas</span>
                </a>
                <?php endif; ?>

                <!-- Bloco de Administração -->
                <?php if (can_view_section('settings') || can_view_section('registros_sugestoes') || can_view_section('info-upload')): ?>
                <div class="px-4 py-2 mt-8 uppercase text-xs font-semibold">Administração</div>
                
                <?php if (can_view_section('settings')): ?>
                <a href="#" data-section="settings" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('settings', true); return false;">
                    <i class="fas fa-cog w-6"></i>
                    <span>Configurações</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('registros_sugestoes')): ?>
                <a href="#" data-section="registros_sugestoes" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('registros_sugestoes', true); return false;">
                        <i class="fas fa-clipboard-list w-6"></i>
                        <span>Registros de Sugestões</span>
                    </a>
                <?php endif; ?>
                <?php if (can_view_section('info-upload')): ?>
                <a href="#" data-section="info-upload" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('info-upload', true); return false;">
                    <i class="fas fa-bullhorn w-6"></i>
                    <span>Cadastrar Informação</span>
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Links restantes -->
                <?php if (can_view_section('about')): ?>
                <a href="#" data-section="about" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2" onclick="showSection('about', true); return false;">
                    <i class="fas fa-users w-6"></i>
                    <span>Sobre Nós</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-[#2C3E50] shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-3">
                        <button id="openSidebar" class="md:hidden focus:outline-none">
                            <i class="fas fa-bars text-white"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-white" id="pageTitle">Página Inicial</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Buscar..." class="search-input py-2 pl-10 pr-4 rounded-md border border-[#4A6572] focus:outline-none focus:border-[#2C3E50] w-64 g-white text-[#4A90E2] placeholder-[#AAB7C4]">
                            <i class="fas fa-search text-white absolute left-3 top-3"></i>
                        </div>
                        <?php if (can_view_section('faq')): ?>
                        <a href="#" data-section="faq" onclick="showSection('faq', true); return false;" class="text-white hover:opacity-80 transition flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-[#34495E]">
                            <i class="fas fa-question-circle"></i>
                            <span>FAQ</span>
                        </a>
                        <?php endif; ?>

                        <!-- 2. Adicionar o botão para iniciar o tour -->
                        <button id="startTourButton" class="text-white hover:opacity-80 transition flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-[#34495E]">
                            <i class="fas fa-route"></i>
                            <span>Fazer Tour</span>
                        </button>

                        <!-- Ícone de Notificações -->
                        <div class="relative">
                            <button id="notificationsBell" class="text-white hover:opacity-80 transition p-2 rounded-md hover:bg-[#34495E] relative">
                                <i class="fas fa-bell"></i>
                                <span id="notification-count-badge" class="notification-badge hidden"></span>
                            </button>
                            <div id="notificationsDropdown" class="absolute right-0 mt-4 w-80 md:w-96 bg-white rounded-lg shadow-lg z-50 hidden">
                                <div class="flex justify-between items-center px-4 py-2 border-b">
                                    <span class="font-semibold text-sm text-gray-700">Notificações</span>
                                    <a href="#" id="mark-all-as-read" class="text-xs text-blue-600 hover:underline">Marcar todas como lidas</a>
                                </div>
                                <div id="notificationsList" class="max-h-80 overflow-y-auto">
                                    <!-- As notificações serão inseridas aqui via JS -->
                                    <div class="p-4 text-center text-sm text-gray-500">Carregando...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Perfil do usuário logado -->
                        <div class="flex items-center space-x-3 relative">
                            <button id="profileDropdownBtn" class="flex items-center space-x-2 hover:opacity-80 transition focus:outline-none">
                                <?php if (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])): ?>
                                    <img src="<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Foto de Perfil" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#2C3E50] font-semibold">
                                        <?= strtoupper(substr($username, 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($username); ?></span>
                                <i class="fas fa-chevron-down text-white text-xs"></i>
                            </button>
                            <div id="profileDropdown" class="absolute right-0 mt-12 w-40 bg-white rounded-lg shadow-lg py-2 z-50 hidden">
                                <a href="#" data-section="profile" onclick="showSection('profile', true); return false;" class="block px-4 py-2 text-[#4A90E2] hover:bg-[#e5e7eb] text-sm">Meu Perfil</a>
                                <a href="logout.php" class="block px-4 py-2 text-[#4A90E2] hover:bg-[#e5e7eb] text-sm">Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Main Content Area -->
            <div class="p-4 bg-gray-200">
                <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <?php
                        $status_class = $_GET['status'] === 'success' 
                            ? 'bg-green-100 border-green-500 text-green-700' 
                            : 'bg-red-100 border-red-500 text-red-700';
                        $icon_class = $_GET['status'] === 'success'
                            ? 'fa-check-circle'
                            : 'fa-exclamation-triangle';
                    ?>
                    <div id="status-message" class="<?= $status_class ?> border-l-4 p-4 mb-4 rounded-r-lg" role="alert">
                        <p class="font-bold flex items-center"><i class="fas <?= $icon_class ?> mr-2"></i> <?= $_GET['status'] === 'success' ? 'Sucesso' : 'Erro' ?></p>
                        <p><?= htmlspecialchars($_GET['msg']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <main class="flex-1 overflow-y-auto bg-gray-200 p-4">
                <!-- Dashboard Section -->
                <section id="dashboard" class="space-y-6">
                    <!-- Bem-vindo -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h1 class="text-3xl font-bold text-[#4A90E2] mb-2">Bem-vindo à Intranet da Comercial Souza!</h1>
                        <p class="text-[#4A90E2] text-lg">Aqui você encontra as informações e ferramentas que precisa para o seu dia a dia.</p>
                    </div>

                    <!-- Comunicados Importantes -->
                    <?php
                    // Corrige erro: inicializa $comunicados e $carrosselImgs como arrays vazios
                    $comunicados = [];
                    $carrosselImgs = [];

                    // Carrega comunicados importantes
                    $hoje = date('Y-m-d');
                    $resultCom = $conn->query("SELECT * FROM informacoes WHERE categoria='Comunicados Importantes' AND (data_inicial IS NULL OR data_inicial <= '$hoje') AND (data_final IS NULL OR data_final >= '$hoje') ORDER BY data_publicacao DESC");
                    if ($resultCom && $resultCom->num_rows > 0) {
                        while ($row = $resultCom->fetch_assoc()) {
                            $comunicados[] = $row;
                        }
                    }

                    // Carrega imagens do carrossel (limite 10 mais recentes)
                    $resultCarrossel = $conn->query("SELECT * FROM carrossel_imagens ORDER BY data_upload DESC LIMIT 10");
                    if ($resultCarrossel && $resultCarrossel->num_rows > 0) {
                        while ($img = $resultCarrossel->fetch_assoc()) {
                            $carrosselImgs[] = $img;
                        }
                    }
                    ?>

                    <!-- Espaço extra para descer os blocos -->
                    <div class="mb-12"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Bloco Comunicados Importantes em lista -->
                        <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                            <div class="border-b pb-2 mb-4 font-semibold text-lg text-green-700 flex items-center gap-2">
                                <i class="fas fa-bullhorn"></i> Últimos Comunicados
                            </div>
                            <?php if (count($comunicados) > 0): ?>
                                <ul class="space-y-4">
                                    <?php foreach ($comunicados as $row): ?>
                                        <li class="border-l-4 pl-4 <?php
                                            $cor = $row['cor'] ?? 'blue';
                                            echo [
                                                'blue' => 'border-blue-500',
                                                'green' => 'border-green-500',
                                                'orange' => 'border-orange-500'
                                            ][$cor] ?? 'border-blue-500';
                                        ?>">
                                            <div class="font-semibold text-[#4A90E2]"><?php echo htmlspecialchars($row['titulo']); ?></div>
                                            <div class="text-gray-700"><?php echo nl2br(htmlspecialchars($row['descricao'])); ?></div>
                                            <div class="text-xs text-gray-500 mt-1"><i class="far fa-calendar-alt"></i> Publicado em: <?php echo date('d/m/Y', strtotime($row['data_publicacao'])); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-gray-500">Nenhum comunicado importante cadastrado.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Bloco Carrossel de Imagens -->
                        <?php if (count($carrosselImgs) > 0): ?>
                            <div class="bg-white rounded-lg shadow flex items-center justify-center p-0 relative overflow-hidden" style="min-height: 520px;">
                                <div id="carrossel-imagens" class="relative w-full h-[520px] flex items-center justify-center">
                                    <?php foreach ($carrosselImgs as $i => $img): ?>
                                        <div class="carousel-img-item absolute inset-0 flex items-center justify-center transition-all duration-700 ease-in-out opacity-0 scale-95 <?php echo $i === 0 ? 'opacity-100 scale-100 z-10' : 'z-0'; ?>">
                                            <img src="uploads/<?php echo htmlspecialchars($img['imagem']); ?>"
                                                 alt="Carrossel"
                                                 class="max-h-[480px] max-w-full rounded-lg shadow-lg object-contain"
                                                 style="margin:auto; transition: box-shadow 0.5s, transform 0.7s;">
                                        </div>
                                    <?php endforeach; ?>
                                    <button id="prevCarrosselImg" class="absolute left-4 top-1/2 -translate-y-1/2 bg-[#254c90] text-white rounded-full p-3 shadow hover:bg-[#1d3870] z-20 transition-all duration-300"><i class="fas fa-chevron-left"></i></button>
                                    <button id="nextCarrosselImg" class="absolute right-4 top-1/2 -translate-y-1/2 bg-[#254c90] text-white rounded-full p-3 shadow hover:bg-[#1d3870] z-20 transition-all duration-300"><i class="fas fa-chevron-right"></i></button>
                                </div>
                                
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Substitua o bloco do rodapé por este, logo após o grid dos comunicados/carrossel, ainda dentro do <main> -->
                    <div class="w-full flex justify-center mt-12">
    <footer class="w-full max-w-5xl flex flex-col items-center justify-center">
        <div class="text-[#4A90E2] text-sm font-medium mb-2 text-center">
            Todos os direitos reservados à Comercial Souza &copy; 2025
        </div>
        <div class="flex flex-wrap justify-center items-center gap-4 mb-2">
            <a href="https://www.comercialsouzaatacado.com.br/" target="_blank" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] transition flex items-center gap-2">
                <i class="fas fa-globe"></i> Site
            </a>
            <a href="https://instagram.com/comercialsouza" target="_blank" class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition flex items-center gap-2">
                <i class="fab fa-instagram"></i> Instagram
            </a>
            <a href="https://linkedin.com/company/comercialsouza" target="_blank" class="px-4 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800 transition flex items-center gap-2">
                <i class="fab fa-linkedin"></i> LinkedIn
            </a>
        </div>
    </footer>
</div>

                </section>
                <!-- Documents Section -->
                <section id="documents" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" id="search-input-docs" placeholder="Filtrar normas e procedimentos..." class="search-input py-2 pl-10 pr-4 rounded-md border border-[#1d3870] focus:outline-none focus:border-[#254c90] w-64 bg-white text-[#4A90E2] placeholder-[#254c90]">
                                <i class="fas fa-search text-[#4A90E2] absolute left-3 top-3"></i>
                            </div>
                            <select id="department-filter-docs" class="border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:border-[#254c90] bg-white text-[#4A90E2]">
                                <option value="all">Todos os departamentos</option>
                                <?php
                                // Busca os departamentos distintos diretamente da tabela de arquivos para popular o filtro
                                $result_deps_docs = $conn->query("SELECT DISTINCT departamento FROM arquivos WHERE tipo='pdf' AND departamento IS NOT NULL AND departamento != '' ORDER BY departamento ASC");
                                if ($result_deps_docs) {
                                    while ($dep = $result_deps_docs->fetch_assoc()) {
                                        echo '<option value="'.htmlspecialchars($dep['departamento']).'">'.htmlspecialchars($dep['departamento']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div id="documents-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        // Lógica para buscar os documentos da seção "Normas e Procedimentos"
                        $sql_docs = "SELECT * FROM arquivos WHERE tipo='pdf'";
                        $params_docs = [];
                        $types_docs = '';

                        // Se o usuário não for admin ou god, filtra pelo departamento dele
                        if (!in_array($user_role, ['admin', 'god'])) {
                            $sql_docs .= " AND departamento = ?";
                            $params_docs[] = $user_department;
                            $types_docs .= 's';
                        }

                        $sql_docs .= " ORDER BY data_upload DESC";

                        $stmt_docs = $conn->prepare($sql_docs);
                        $result = false; // Inicializa como falso
                        if ($stmt_docs) {
                            if (!empty($params_docs)) {
                                $stmt_docs->bind_param($types_docs, ...$params_docs);
                            }
                            $stmt_docs->execute();
                            $result = $stmt_docs->get_result();
                        }

                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                // Ícone de acordo com o tipo
                                $icon = '';
                                $tipo = strtolower($row['tipo']);
                                if ($tipo === 'pdf') {
                                    $topBg = 'bg-red-50';
                                    $icon = '<i class="fas fa-file-pdf text-red-500 text-5xl"></i>';
                                } elseif (
                                    $tipo === 'excel' ||
                                    $tipo === 'planilha excel' ||
                                    $tipo === 'planilha' ||
                                    $tipo === 'xlsx' ||
                                    $tipo === 'xls'
                                ) {
                                    $topBg = 'bg-green-50';
                                    $icon = '<i class="fas fa-file-excel text-green-500 text-5xl"></i>';
                                } elseif ($tipo === 'word' || $tipo === 'documento word' || $tipo === 'docx' || $tipo === 'doc') {
                                    $topBg = 'bg-blue-50';
                                    $icon = '<i class="fas fa-file-word text-blue-500 text-5xl"></i>';
                                } elseif ($tipo === 'apresentação powerpoint' || $tipo === 'ppt' || $tipo === 'pptx') {
                                    $topBg = 'bg-orange-50';
                                    $icon = '<i class="fas fa-file-powerpoint text-orange-500 text-5xl"></i>';
                                } else {
                                    $topBg = 'bg-gray-50';
                                    $icon = '<i class="fas fa-file text-gray-400 text-5xl"></i>';
                                }
                                echo '
                                <div class="document-card bg-white rounded-lg shadow overflow-hidden flex flex-col" data-department="'.htmlspecialchars($row['departamento']).'">
                                    <div class="w-full flex items-center justify-center '.$topBg.'" style="height:80px;">
                                        '.$icon.'
                                    </div>
                                    <div class="p-6 flex-1 flex flex-col">
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">'.htmlspecialchars($row['titulo']).'</h3>
                                        <p class="text-gray-700 mb-2">'.htmlspecialchars($row['descricao']).'</p>
                                        <div class="flex items-end justify-between mt-auto">
                                            <span class="text-xs text-gray-500">Atualizado: '.date('d/m/Y', strtotime($row['data_upload'])).'</span>
                                            <div class="flex items-center gap-4">
                                                <a href="uploads/'.$row['nome_arquivo'].'" target="_blank" class="text-gray-600 hover:text-blue-600" title="Visualizar"><i class="fas fa-eye"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ';
                            }
                        }
                        ?>
                    </div>
                </section>
                <!-- Information Section -->
                <section id="information" class="hidden">
                    <div>
                        <!-- Abas de Navegação -->
                        <nav class="flex" aria-label="Tabs">
                            <button class="folder-tab active" data-tab="comunicados">
                                <i class="fas fa-bullhorn mr-2"></i>Comunicados
                            </button>
                        </nav>

                        <!-- Container para o conteúdo das abas -->
                        <div class="folder-tab-content-container shadow">
                        <!-- Conteúdo da Aba: Comunicados -->
                        <div id="info-tab-comunicados" class="info-tab-content space-y-6">
                            <div class="bg-white rounded-lg shadow mb-6">
                                <div class="p-4 border-b font-semibold text-lg text-[#4A90E2]">Comunicados Importantes</div>
                                <div class="p-4 space-y-4">
                                    <?php
                                    $hoje = date('Y-m-d');
                                    $result = $conn->query("SELECT * FROM informacoes WHERE categoria='Comunicados Importantes' AND (data_inicial IS NULL OR data_inicial <= '$hoje') AND (data_final IS NULL OR data_final >= '$hoje') ORDER BY data_publicacao DESC");
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $cor = $row['cor'] ?? 'blue';
                                            $corBarra = [
                                                'blue' => 'border-blue-500',
                                                'green' => 'border-green-500',
                                                'orange' => 'border-orange-500'
                                            ][$cor] ?? 'border-blue-500';
                                            echo '<div class="border-l-4 '.$corBarra.' pl-4">
                                                <div class="font-semibold text-[#4A90E2]">'.htmlspecialchars($row['titulo']).'</div>
                                                <div class="text-gray-700">'.nl2br(htmlspecialchars($row['descricao'])).'</div>
                                                <div class="text-xs text-gray-500 mt-1"><i class="far fa-calendar-alt"></i> Publicado em: '.date('d/m/Y', strtotime($row['data_publicacao'])).'</div>
                                            </div>';
                                        }
                                    } else {
                                        echo '<div class="text-gray-500">Nenhum comunicado importante cadastrado.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg shadow">
                                <div class="p-4 border-b font-semibold text-lg text-[#4A90E2]">Informações Úteis</div>
                                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php
                                    $result = $conn->query("SELECT * FROM informacoes WHERE categoria='Informações Úteis' ORDER BY data_publicacao DESC");
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<div class="border rounded-lg p-4 bg-gray-50">
                                                <div class="font-semibold text-[#4A90E2]">'.htmlspecialchars($row['titulo']).'</div>
                                                <div class="text-gray-700">'.nl2br(htmlspecialchars($row['descricao'])).'</div>
                                                <div class="text-xs text-gray-500 mt-1"><i class="far fa-calendar-alt"></i> Publicado em: '.date('d/m/Y', strtotime($row['data_publicacao'])).'</div>
                                                <a href="#" class="text-indigo-600 text-xs mt-2 inline-block">Ver detalhes &gt;</a>
                                            </div>';
                                        }
                                    } else {
                                        echo '<div class="text-gray-500">Nenhuma informação útil cadastrada.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </section>
                
                <!-- Informações/Avisos Section -->
                <section id="info-upload" class="hidden space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-[#254c90]">
            <h3 class="text-lg font-semibold text-[#4A90E2]">Cadastrar Aviso/Informação</h3>
        </div>
        <div class="p-6">
            <form id="infoForm" class="space-y-6" method="POST" action="cadastrar_informacao.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Título</label>
                        <input type="text" name="titulo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Categoria</label>
                        <select name="categoria" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" required>
                            <option value="Comunicados Importantes">Comunicados Importantes</option>
                            <option value="Informações Úteis">Informações Úteis</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Exibir a partir de</label>
                        <input type="date" name="data_inicial" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Exibir até</label>
                        <input type="date" name="data_final" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Descrição</label>
                    <textarea name="descricao" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" rows="4" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Data de Publicação</label>
                    <input type="date" name="data_publicacao" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Cor da Barra (opcional)</label>
                    <select name="cor" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                        <option value="blue">Azul</option>
                        <option value="green">Verde</option>
                        <option value="orange">Laranja</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Imagem (opcional)</label>
                    <input type="file" name="imagem" accept="image/*" class="w-full border border-[#1d3870] rounded-md px-4 py-2 bg-white text-[#4A90E2]">
                    <span class="text-xs text-gray-500">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</span>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                        Cadastrar Informação
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Formulário para cadastrar imagem do carrossel -->
    <div class="bg-white rounded-lg shadow p-6 mt-8">
        <h3 class="text-lg font-semibold text-[#4A90E2] mb-4">Adicionar Imagem ao Carrossel</h3>
        <form method="POST" enctype="multipart/form-data" action="cadastrar_carrossel.php">
            <div>
                <label class="block text-sm font-medium text-[#4A90E2] mb-1">Imagem</label>
                <input type="file" name="imagem" accept="image/*" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 bg-white text-[#4A90E2]">
                <span class="text-xs text-gray-500">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</span>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Adicionar ao Carrossel</button>
            </div>
        </form>
    </div>
</section>
                <!-- Sugestões e Reclamações Section -->
                <section id="sugestoes" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-2">Sugestões e Reclamações</h2>
                        <p class="text-[#4A90E2] mb-6">Sua opinião é muito importante para nós. Envie sua sugestão ou reclamação para ajudar a melhorar nosso ambiente de trabalho!</p>
                        
                        <form id="sugestaoForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[#4A90E2] mb-1">Tipo de Mensagem</label>
                                <select name="tipo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" required>
                                    <option value="">Selecione...</option>
                                    <option value="sugestao">Sugestão</option>
                                    <option value="reclamacao">Reclamação</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Seu E-mail (Opcional)</label>
                                    <input type="email" name="email" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" placeholder="seunome@comercialsouza.com.br">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Seu Telefone (Opcional)</label>
                                    <input type="tel" name="telefone" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" placeholder="(XX) XXXXX-XXXX">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#4A90E2] mb-1">Sua Mensagem</label>
                                <textarea name="mensagem" rows="5" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" placeholder="Digite sua mensagem aqui..." required></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    Enviar Mensagem
                                </button>
                            </div>
                        </form>
                        <div id="sugestaoStatus" class="mt-4 text-center"></div>
                    </div>
                </section>
                <!-- FAQ Section -->
                <section id="faq" class="hidden space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6 border-b pb-3">
            <h2 class="text-3xl font-bold text-[#4A90E2]">FAQ - Perguntas Frequentes</h2>
            <?php if (can_view_section('manage_faq_section')): ?>
                <a href="#" data-section="manage_faq_section" onclick="showSection('manage_faq_section', true); return false;" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Gerenciar FAQs
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Nova Estrutura de Chat Interativo com Layout de App -->
        <div class="max-w-4xl mx-auto faq-chat-window">
            <!-- Cabeçalho da Janela de Chat -->
            <div class="bg-[#2a5298] p-4 border-b border-gray-200 flex items-center space-x-4">
                <img src="img/SAM.png" alt="SAM Avatar" class="w-16 h-16 rounded-full object-cover border-2 border-blue-200 sam-animated-avatar">
                <div>
                    <h3 class="font-bold text-lg text-white">SAM - Assistente Virtual</h3>
                    <p class="text-sm text-green-300 flex items-center"><i class="fas fa-circle text-xs mr-2"></i>Online</p>
                </div>
            </div>

            <!-- Corpo do Chat -->
            <div id="faq-chat-area" class="p-4 space-y-6 overflow-y-auto faq-chat-body">
                <!-- O chat será preenchido pelo JavaScript -->
            </div>

            <!-- Área de "Digitação" com Sugestões e Reset -->
            <div class="bg-white p-4 border-t border-gray-200">
                <div id="faq-suggestions-area" class="flex flex-wrap gap-3 justify-center mb-3">
                    <!-- Botões de sugestão serão inseridos aqui -->
                </div>
                <div id="faq-reset-area" class="text-center hidden">
                    <button id="faq-reset-btn" class="text-xs text-gray-500 hover:text-gray-700 hover:underline">
                        <i class="fas fa-sync-alt mr-1"></i>Reiniciar conversa
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sobre Nós Section -->
<section id="about" class="hidden space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-[#4A90E2] mb-4">Sobre Nós</h2>
        <p class="text-[#4A90E2] mb-4">
            A Comercial Souza iniciou sua jornada somente com os dois irmãos que compravam, vendiam e entregavam, e desde então vem construindo uma história de sucesso. Ao longo dessa trajetória buscamos sempre o comprometimento com nossos clientes, colaboradores e fornecedores. Nossa equipe é composta por profissionais especializados e capacitados para oferecer o melhor serviço e atendimento, nosso relacionamento se dá de maneira consultiva, visitando o cliente e entendendo suas necessidades. Atualmente atendemos redes de supermercados e mercados de pequeno e médio porte com uma ampla linha de produtos, nos segmentos: Alimentar, bebidas, perfumaria, limpeza e bazar, com mais de 3.000 itens, 7 supervisores e mais de 150 representantes externos. Contamos com um amplo CD de armazenagem e tecnologia de ponta (ERP e Força de Vendas), a Comercial Souza vem se constituindo no mercado como uma empresa inovadora, temos agilidade em nossas entregas e com isso melhorando o abastecimento e rentabilidades em nossos clientes.
        </p>
        <div class="mb-4">
            <div class="font-bold text-white bg-blue-600 rounded-t px-4 py-2">Nossa Visão</div>
            <div class="bg-white border border-blue-600 rounded-b px-4 py-2 text-[#4A90E2]">
                Ser referência em distribuição na área de atuação com produtos de qualidade, buscando sempre excelência em logística, inovações e tecnologias para atender melhor às necessidades dos nossos clientes.
            </div>
        </div>
        <div class="mb-4">
            <div class="font-bold text-white bg-green-600 rounded-t px-4 py-2">Nossa Missão</div>
            <div class="bg-white border border-green-600 rounded-b px-4 py-2 text-[#4A90E2]">
                Entregar eficiência, qualidade no atendimento e agilidade em todos os processos. Agregar valores para nossos clientes, colaboradores e fornecedores.
            </div>
        </div>
        <div>
            <div class="font-bold text-white bg-cyan-600 rounded-t px-4 py-2">Nossos Valores</div>
            <div class="bg-white border border-cyan-600 rounded-b px-4 py-2 text-[#4A90E2]">
                <ul class="list-disc pl-5">
                    <li>Confiança e respeito às pessoas;</li>
                    <li>Simplicidade, ética e Transparência;</li>
                    <li>Profissionais disciplinados e comprometidos;</li>
                    <li>Ótimo ambiente de trabalho.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Sistemas Section -->
<section id="sistema" class="hidden space-y-6">
    <h2 class="text-2xl font-bold text-[#4A90E2]">Acesso Rápido aos Sistemas</h2>
    <?php if (count($sistemas_externos) > 0): ?>
        <div id="systems-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($sistemas_externos as $sistema): ?>
            <a href="<?= htmlspecialchars($sistema['link']) ?>" target="_blank" rel="noopener noreferrer" class="document-card bg-white rounded-lg shadow p-6 flex flex-col items-center justify-center text-center hover:bg-gray-50 transition-transform transform hover:-translate-y-1">
                    <i class="<?= htmlspecialchars($sistema['icon_class']) ?> text-4xl text-[#4A90E2] mb-3"></i>
                    <h3 class="font-semibold text-lg text-[#1d3870]"><?= htmlspecialchars($sistema['nome']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <i class="fas fa-info-circle text-3xl text-gray-400 mb-3"></i>
            <p class="text-[#4A90E2]">
                Nenhum atalho de sistema foi cadastrado ainda.
            </p>
            <p class="text-sm text-gray-500 mt-1">Peça a um administrador para adicionar os atalhos na tela de Configurações.</p>
        </div>
    <?php endif; ?>
</section>

                <!-- Registros de Sugestões Section (Admin only) -->
                <section id="registros_sugestoes" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-4">Registros de Sugestões e Reclamações</h2>
                        <p class="text-[#4A90E2] mb-6">Acompanhe e gerencie as mensagens enviadas pelos colaboradores.</p>
                        <div id="registros-container">
                            <!-- O conteúdo da tabela será carregado aqui via JavaScript -->
                        </div>
                    </div>
                </section>

                <!-- Settings Section (Admin only) -->
                <section id="settings" class="hidden space-y-6">
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])): ?>
                    <div>
                        <!-- Abas de Navegação -->
                        <nav class="flex" aria-label="Tabs">
                            <button class="folder-tab active" data-tab="users">
                                <i class="fas fa-users-cog mr-2"></i>Usuários/Permissões
                            </button>
                            <button class="folder-tab" data-tab="acesso">
                                <i class="fas fa-shield-alt mr-2"></i>Acessos
                            </button>
                        </nav>

                        <!-- Container para o conteúdo das abas -->
                        <div class="folder-tab-content-container shadow">
                        <!-- Conteúdo da Aba: Usuários/Permissões -->                        
                        <div id="settings-tab-users" class="settings-tab-content">
                            <div class="flex justify-between items-center mb-4 border-b pb-2">
                                <h3 class="text-lg font-semibold text-[#4A90E2]">Gerenciar Usuários e Permissões</h3>
                                <button id="openCreateUserModalBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Criar Novo Usuário
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Setor</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Nível</th>
                                            <th class="py-2 px-4 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td class="py-3 px-4 text-sm text-gray-800 font-medium"><?= htmlspecialchars($usuario['username']) ?></td>                                                <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($usuario['setor_nome'] ?? 'N/A') ?></td>
                                                <td class="py-3 px-4 text-sm text-gray-600"><?= ucfirst(htmlspecialchars($usuario['role'])) ?></td>
                                                <td class="py-3 px-4 text-sm text-center"><button class="open-permissions-modal px-3 py-1 bg-[#254c90] text-white text-xs font-semibold rounded-md hover:bg-[#1d3870]" data-userid="<?= $usuario['id'] ?>" data-username="<?= htmlspecialchars($usuario['username']) ?>">Gerenciar</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Conteúdo da Aba: Acesso -->
                        <div id="settings-tab-acesso" class="settings-tab-content hidden">
                            <h3 class="text-lg font-semibold text-[#4A90E2] mb-4 border-b pb-2">Gerenciar Acessos</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Card para Adicionar Atalho de Sistema -->
                                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                                    <h4 class="text-lg font-semibold text-[#4A90E2] mb-4">Adicionar Novo Atalho de Sistema</h4>
                                    <form action="gerenciar_sistemas.php" method="POST" class="space-y-4">
                                        <input type="hidden" name="action" value="add">
                                        <div>
                                            <label for="nome_sistema" class="block text-sm font-medium text-[#4A90E2]">Nome do Sistema</label>
                                            <input type="text" id="nome_sistema" name="nome" required class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        </div>
                                        <div>
                                            <label for="link_sistema" class="block text-sm font-medium text-[#4A90E2]">Link do Sistema</label>
                                            <input type="url" id="link_sistema" name="link" required placeholder="https://..." class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        </div>
                                        <div>
                                            <label for="departamento_sistema" class="block text-sm font-medium text-[#4A90E2]">Departamento (Opcional)</label>
                                            <select id="departamento_sistema" name="setor_id" class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                                                <option value="">Visível para Todos</option>
                                                <?php foreach ($setores as $setor): ?>
                                                    <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">Selecione um departamento ou deixe em "Todos".</p>
                                        </div>
                                        <div>
                                            <label for="icon_sistema" class="block text-sm font-medium text-[#4A90E2]">Ícone (Font Awesome)</label>
                                            <input type="text" id="icon_sistema" name="icon_class" placeholder="Ex: fas fa-cogs" class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                            <p class="text-xs text-gray-500 mt-1">Opcional. Veja os ícones em <a href="https://fontawesome.com/v6/search" target="_blank" class="text-blue-500 underline">fontawesome.com</a>.</p>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Adicionar Atalho</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- Card para Listar Atalhos -->
                                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                                    <h4 class="text-lg font-semibold text-[#4A90E2] mb-4">Atalhos Cadastrados</h4>
                                    <ul class="space-y-3 max-h-96 overflow-y-auto">
                                        <?php foreach ($sistemas_externos as $sistema): ?>
                                            <li class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                                <div>
                                                    <span class="text-[#4A90E2] font-medium"><i class="<?= htmlspecialchars($sistema['icon_class']) ?> mr-2 text-gray-500"></i><?= htmlspecialchars($sistema['nome']) ?></span>
                                                    <span class="block text-xs text-gray-500 ml-6"><?= htmlspecialchars($sistema['departamento'] ?? 'Visível para Todos') ?></span>
                                                </div>
                                                <form action="gerenciar_sistemas.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este atalho?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="sistema_id" value="<?= $sistema['id'] ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Excluir Atalho"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-red-500">Acesso negado.</p>
                <?php endif; ?>
                </section>

                <!-- Manage FAQs Section (Admin only) -->
                <section id="manage_faq_section" class="hidden space-y-6">
                    <?php if (can_view_section('manage_faq_section')): ?>
                        <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-xl border border-gray-200">
                            <h1 class="text-3xl font-extrabold text-[#4A90E2] mb-6 pb-3 border-b-4 border-[#254c90]/50">Gerenciar Perguntas Frequentes (FAQs)</h1>

                            <?php
                            // Ajusta as classes das mensagens de feedback para o padrão do projeto
                            if (!empty($manage_faq_message)) {
                                $status_class = strpos($manage_faq_message, 'alert-success') !== false
                                    ? 'bg-green-100 border-green-500 text-green-700'
                                    : 'bg-red-100 border-red-500 text-red-700';
                                echo '<div class="' . $status_class . ' border-l-4 p-4 mb-4 rounded-lg shadow-sm" role="alert">' . str_replace(['<div class="alert alert-success">', '<div class="alert alert-danger">', '</div>'], '', $manage_faq_message) . '</div>';
                            }
                            ?>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Formulário de Adição/Edição de FAQ -->
                                <div>
                                    <div class="p-6 border border-gray-200 rounded-lg bg-gradient-to-r from-gray-50 to-white shadow-sm h-full">
                                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-5"><?php echo $manage_faq_to_edit ? 'Editar FAQ' : 'Adicionar Nova FAQ'; ?></h2>
                                        <form id="faqManageForm" action="index.php?section=manage_faq_section" method="POST" class="space-y-4">
                                            <?php if ($manage_faq_to_edit): ?>
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($manage_faq_to_edit['id']); ?>">
                                                <input type="hidden" name="faq_action" value="edit">
                                            <?php else: ?>
                                                <input type="hidden" name="faq_action" value="add">
                                            <?php endif; ?>

                                            <div>
                                                <label for="question" class="block text-sm font-semibold text-[#4A90E2] mb-1">Pergunta:</label>
                                                <input type="text" id="question" name="question" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-[#254c90] focus:border-transparent transition duration-200 ease-in-out text-gray-800 placeholder-gray-400" value="<?php echo htmlspecialchars($manage_faq_to_edit['question'] ?? ''); ?>" required>
                                            </div>
                                            <div>
                                                <label for="answer" class="block text-sm font-semibold text-[#4A90E2] mb-1">Resposta:</label>
                                                <textarea id="answer" name="answer" rows="5" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-[#254c90] focus:border-transparent transition duration-200 ease-in-out text-gray-800 placeholder-gray-400" required><?php echo htmlspecialchars($manage_faq_to_edit['answer'] ?? ''); ?></textarea>
                                            </div>

                                            <!-- Nova Ferramenta de Links -->
                                            <div class="p-4 border border-gray-200 rounded-md bg-gray-50 space-y-3">
                                                <h3 class="text-md font-semibold text-[#4A90E2]">Ferramenta: Adicionar Link ao Texto da Resposta</h3>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label for="link_text" class="block text-sm font-medium text-gray-700 mb-1">Texto do Link</label>
                                                        <input type="text" id="link_text" placeholder="Ex: Clique aqui" class="w-full border-gray-300 rounded-md shadow-sm">
                                                    </div>
                                                    <div>
                                                        <label for="link_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Destino</label>
                                                        <select id="link_type" class="w-full border-gray-300 rounded-md shadow-sm">
                                                            <option value="internal">Página Interna</option>
                                                            <option value="external">URL Externa</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="link_destination_internal">
                                                    <label for="link_internal_page" class="block text-sm font-medium text-gray-700 mb-1">Página da Intranet</label>
                                                    <select id="link_internal_page" class="w-full border-gray-300 rounded-md shadow-sm">
                                                        <?php foreach ($available_sections as $key => $label): ?>
                                                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div id="link_destination_external" class="hidden">
                                                    <label for="link_external_url" class="block text-sm font-medium text-gray-700 mb-1">URL Completa</label>
                                                    <input type="url" id="link_external_url" placeholder="https://www.exemplo.com" class="w-full border-gray-300 rounded-md shadow-sm">
                                                </div>
                                                <button type="button" id="insert_link_btn" class="mt-2 px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Inserir Link</button>
                                            </div>

                                            
                                            <div class="flex items-center">
                                                <input type="checkbox" id="is_active" name="is_active" class="h-4 w-4 text-[#4A90E2] focus:ring-[#254c90] border-gray-300 rounded cursor-pointer" <?php echo ($manage_faq_to_edit['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                                <label for="is_active" class="ml-2 block text-sm text-gray-700 cursor-pointer">Ativa</label>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <button type="submit" class="flex-1 px-6 py-2 bg-[#254c90] text-white font-semibold rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90] focus:ring-offset-2 transition duration-200 ease-in-out">Salvar FAQ</button>
                                                <?php if ($manage_faq_to_edit): ?>
                                                    <a href="index.php?section=manage_faq_section" class="flex-1 text-center px-6 py-2 bg-gray-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition duration-200 ease-in-out">Cancelar Edição</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Lista de FAQs Existentes (Accordion) -->
                                <div>
                                    <h2 class="text-2xl font-bold text-[#4A90E2] mb-4">FAQs Existentes</h2>
                                    <?php if (empty($manage_faqs)): ?>
                                        <p class="text-gray-600 p-4 bg-gray-50 rounded-md border border-gray-200">Nenhuma FAQ encontrada. Adicione uma nova FAQ acima.</p>
                                    <?php else: ?>
                                        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
                                            <?php foreach ($manage_faqs as $faq): ?>
                                                <div class="border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                                    <button class="faq-accordion-header w-full flex justify-between items-center p-4 bg-gray-100 hover:bg-gray-200 focus:outline-none transition duration-200 ease-in-out">
                                                        <span class="font-semibold text-[#4A90E2] text-left text-lg"><?php echo htmlspecialchars($faq['question']); ?></span>
                                                        <i class="fas fa-chevron-down text-gray-600 transform transition-transform duration-300 text-xl"></i>
                                                    </button>
                                                    <div class="faq-accordion-content hidden p-4 bg-white border-t border-gray-200">
                                                        <p class="text-gray-700 mb-4 leading-relaxed"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                                        <div class="flex space-x-3">
                                                            <a href="index.php?section=manage_faq_section&faq_action=edit&id=<?php echo htmlspecialchars($faq['id']); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 ease-in-out">
                                                                <i class="fas fa-edit mr-2"></i> Editar
                                                            </a>
                                                            <form action="index.php?section=manage_faq_section" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir esta FAQ?');">
                                                                <input type="hidden" name="faq_action" value="delete">
                                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($faq['id']); ?>">
                                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200 ease-in-out">
                                                                    <i class="fas fa-trash-alt mr-2"></i> Excluir
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-red-500 text-center p-6 bg-red-50 rounded-lg border border-red-200 shadow-sm">Acesso negado. Você não tem permissão para gerenciar FAQs.</p>
                    <?php endif; ?>
                </section>

                <!-- Create Procedure Section (Admin only) -->
                <section id="create_procedure" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-[#254c90]">
                            <h3 class="text-lg font-semibold text-[#4A90E2]">Criar Novo Procedimento</h3>
                            <p class="text-sm text-gray-600 mt-1">Preencha os campos abaixo para gerar um novo documento de procedimento em PDF.</p>
                        </div>
                        <div class="p-6">
                            <form id="createProcedureForm" action="save_procedure.php" method="POST" class="space-y-6">
                                <!-- Cabeçalho do Documento -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Título do Procedimento</label>
                                        <input type="text" name="titulo" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Código</label>
                                        <input type="text" name="codigo" placeholder="Ex: CS-PRO-GA-01" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Versão</label>
                                        <input type="text" name="versao" placeholder="Ex: v1.0" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#4A90E2] mb-1">Data de Emissão</label>
                                        <input type="text" name="data_emissao" value="<?= date('d/m/Y') ?>" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Descrição da Alteração</label>
                                    <input type="text" name="descricao_alteracao" value="Emissão inicial" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-[#4A90E2] mb-1">Departamento</label>
                                    <select name="setor_id" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                                        <option value="">Selecione um setor</option>
                                        <?php foreach ($setores as $setor): ?>
                                            <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <hr class="my-4">
                                <!-- Corpo do Procedimento -->
                                <div class="space-y-4">
                                    <h4 class="text-md font-semibold text-[#1d3870]">Conteúdo do Procedimento</h4>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">1. Objetivo</label><textarea name="objetivo" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">2. Campo de Aplicação</label><textarea name="aplicacao" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">3. Referências</label><textarea name="referencias" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">4. Definições</label><textarea name="definicoes" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">5. Responsabilidades</label><textarea name="responsabilidades" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">6. Descrição do Procedimento</label><textarea name="descricao_procedimento" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">7. Registros</label><textarea name="registros" class="procedure-editor"></textarea></div>
                                    <div><label class="block text-sm font-medium text-[#4A90E2] mb-1">8. Anexos</label><textarea name="anexos" class="procedure-editor"></textarea></div>
                                </div>
                                <div class="flex justify-end pt-4 border-t">
                                    <button type="reset" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Limpar</button>
                                    <button type="submit" class="px-6 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        <i class="fas fa-file-pdf mr-2"></i>Gerar e Salvar Procedimento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Profile Section -->
                <section id="profile" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-6 border-b pb-3">Meu Perfil</h2>
                        
                        <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-8">

                            <!-- Seção de Alterar Foto -->
                            <div>
                                <h3 class="text-lg font-semibold text-[#1d3870] mb-4">Alterar Foto de Perfil</h3>
                                <div class="flex items-center space-x-6">
                                    <?php if (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])): ?>
                                        <img src="<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Foto de Perfil" class="w-24 h-24 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-3xl font-semibold">
                                            <?= strtoupper(substr($username, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <label for="profile_photo" class="block text-sm font-medium text-gray-700">Nova foto</label>
                                        <input type="file" name="profile_photo" id="profile_photo" accept="image/png, image/jpeg, image/gif" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#e9eef5] file:text-[#4A90E2] hover:file:bg-[#dbeafe]">
                                        <p class="text-xs text-gray-500 mt-1">PNG, JPG ou GIF (Máx. 2MB).</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção de Alterar Senha -->
                            <div>
                                <h3 class="text-lg font-semibold text-[#1d3870] mb-4">Alterar Senha</h3>
                                <div class="space-y-4 max-w-md">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700">Senha Atual</label>
                                        <input type="password" name="current_password" id="current_password" class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        <p class="text-xs text-gray-500 mt-1">Deixe os campos de senha em branco se não quiser alterá-la.</p>
                                    </div>
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                                        <input type="password" name="new_password" id="new_password" class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmar Nova Senha</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    </div>
                                </div>
                            </div>

                            <!-- Botão de Salvar -->
                            <div class="pt-5 border-t">
                                <div class="flex justify-end">
                                    <button type="submit" class="px-6 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#254c90]">
                                        Salvar Alterações
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Matriz de Comunicação Section -->
                <section id="matriz_comunicacao" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-4">Matriz de Comunicação</h2>
                        
                        <!-- Formulário de Filtros -->
                        <form id="matriz-filter-form" action="index.php" method="GET" class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                            <input type="hidden" name="section" value="matriz_comunicacao">
                            <div class="flex flex-wrap gap-4 items-center justify-between">
                                <!-- Filtro de Setores por Pílulas (à esquerda) -->
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="text-sm font-medium text-gray-700 mr-2">Filtrar por Setor:</span>
                                    <?php
                                    $current_setor = $_GET['setor'] ?? '';
                                    $base_params = $_GET;
                                    unset($base_params['setor'], $base_params['pagina']); // Remove o setor e a paginação atuais para reconstruir o link
                                    $base_params['section'] = 'matriz_comunicacao'; // Garante que a seção correta seja mantida

                                    // Botão "Todos"
                                    $class_todos = empty($current_setor) ? 'active' : 'inactive';
                                    echo "<button type=\"button\" data-setor=\"\" class=\"filter-pill-btn matriz-filter-btn {$class_todos}\">Todos</button>";

                                    // Botões de Setores
                                    $result_setores_botoes = $conn->query("SELECT DISTINCT setor FROM matriz_comunicacao WHERE setor IS NOT NULL AND setor != '' ORDER BY setor ASC");
                                    if ($result_setores_botoes) {
                                        while ($setor_item = $result_setores_botoes->fetch_assoc()) {
                                            $nome_setor = htmlspecialchars($setor_item['setor']);
                                            $class_setor = ($current_setor === $setor_item['setor']) ? 'active' : 'inactive';
                                            
                                            echo "<button type=\"button\" data-setor=\"{$nome_setor}\" class=\"filter-pill-btn matriz-filter-btn {$class_setor}\">{$nome_setor}</button>";
                                        }
                                    }
                                    ?>
                                </div>
                                <!-- Botão Adicionar (à direita) -->
                                <div>
                                    <?php if (in_array($user_role, ['admin', 'god'])): ?>
                                        <button type="button" id="btn-copiar-emails" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 mr-2" title="Copiar e-mails do filtro atual"><i class="fas fa-copy"></i> Copiar E-mails</button>
                                        <button type="button" id="btn-adicionar-funcionario" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" title="Adicionar novo registro"><i class="fas fa-plus"></i> Adicionar Novo</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <!-- Formulário para Adicionar Novo Funcionário (oculto por padrão) -->
                        <div id="form-adicionar-funcionario" class="hidden bg-gray-100 p-6 rounded-lg border border-gray-300 my-6">
                            <h3 class="text-lg font-semibold text-[#4A90E2] mb-4">Adicionar Novo Funcionário</h3>
                            <form action="adicionar_funcionario_matriz.php" method="POST" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="novo_nome" class="block text-sm font-medium text-gray-700">Nome:</label>
                                        <input type="text" id="novo_nome" name="nome" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label for="novo_setor" class="block text-sm font-medium text-gray-700">Setor:</label>
                                        <input type="text" id="novo_setor" name="setor" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label for="novo_email" class="block text-sm font-medium text-gray-700">E-mail:</label>
                                        <input type="email" id="novo_email" name="email" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                                    </div>
                                    <div>
                                        <label for="novo_ramal" class="block text-sm font-medium text-gray-700">Ramal:</label>
                                        <input type="text" id="novo_ramal" name="ramal" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" id="btn-cancelar-adicao" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Salvar Funcionário</button>
                                </div>
                            </form>
                        </div>

                        <!-- Tabela de Resultados -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-[#254c90] text-white">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Nome</th>
                                        <th class="py-3 px-4 text-left">Setor</th>
                                        <th class="py-3 px-4 text-left">E-mail</th>
                                        <th class="py-3 px-4 text-left">Ramal</th>
                                    </tr>
                                </thead>
                                <tbody id="matriz-comunicacao-tbody-main" class="divide-y divide-gray-200">
                                    <?php if (count($funcionarios_matriz) > 0): ?>
                                        <?php foreach ($funcionarios_matriz as $funcionario): ?>
                                            <?php $is_admin = in_array($user_role, ['admin', 'god']); ?>
                                            <tr data-id="<?= $funcionario['id'] ?>">
                                                <td class="py-3 px-4" data-column="nome">
                                                    <div class="cell-content-wrapper">
                                                        <span class="cell-content"><?= htmlspecialchars($funcionario['nome']) ?></span>
                                                        <?php if ($is_admin): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4" data-column="setor">
                                                    <div class="cell-content-wrapper">
                                                        <span class="cell-content"><?= htmlspecialchars($funcionario['setor']) ?></span>
                                                        <?php if ($is_admin): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4" data-column="email">
                                                    <div class="cell-content-wrapper">
                                                        <span class="cell-content"><?= htmlspecialchars($funcionario['email']) ?></span>
                                                        <?php if ($is_admin): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4" data-column="ramal">
                                                    <div class="cell-content-wrapper">
                                                        <span class="cell-content"><?= htmlspecialchars($funcionario['ramal']) ?></span>
                                                        <?php if ($is_admin): ?><i class="fas fa-pencil-alt edit-trigger"></i><?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">Nenhum resultado encontrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Controles de Paginação -->
                        <div id="matriz-comunicacao-pagination-main" class="mt-6 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php
                                if ($total_paginas_matriz > 1):
                                    $query_params = $_GET;                                    
                                    // GARANTIR que a seção correta está no link de paginação.
                                    // Este é o ponto crucial da correção.
                                    $query_params['section'] = 'matriz_comunicacao';
                                    for ($i = 1; $i <= $total_paginas_matriz; $i++):
                                        $query_params['pagina'] = $i;                                        
                                        $link = 'index.php?' . http_build_query($query_params);
                                        $active_class = ($i == $pagina_atual_matriz) ? 'bg-[#254c90] text-white' : 'bg-white text-[#4A90E2] hover:bg-gray-100';
                                ?>
                                    <a href="<?= $link ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm <?= $active_class ?>"><?= $i ?></a>
                                <?php endfor; endif; ?>
                            </nav>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <!-- Modal de Permissões -->
    <div id="permissionsModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl transform transition-all scale-95 opacity-0">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-semibold text-[#4A90E2]">Gerenciar Permissões: <span id="modalUsername" class="font-bold"></span></h3>
                <button id="closePermissionsModal" class="text-gray-500 hover:text-gray-800">&times;</button>
            </div>
            <form id="permissionsForm" action="update_user_permissions.php" method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="space-y-6">
                    <!-- Nível de Acesso (Role) -->
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-2">Nível de Acesso Principal</label>
                        <select name="role" id="modalUserRole" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                            <option value="user">Usuário</option>
                            <option value="admin">Admin</option>
                            <option value="god">God</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Admin e God têm acesso a todas as telas por padrão.</p>
                    </div>
                    <!-- Setor do Usuário -->
                    <div>
                        <label class="block text-sm font-medium text-[#4A90E2] mb-2">Setor</label>
                        <select name="setor_id" id="modalUserSetor" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                            <option value="">Nenhum</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Permissões de Tela (Sections) -->
                    <div id="sectionsPermissionsContainer">
                        <label class="block text-sm font-medium text-[#4A90E2] mb-2">Acesso às Telas (para nível "Usuário")</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 border p-4 rounded-md max-h-64 overflow-y-auto">
                            <?php foreach ($available_sections as $key => $label): ?>
                                <div>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="sections[]" value="<?= $key ?>" class="form-checkbox h-5 w-5 text-[#4A90E2] rounded border-gray-300 focus:ring-[#1d3870] custom-checkbox">
                                        <span class="text-gray-700"><?= htmlspecialchars($label) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-6 pt-4 border-t">
                    <button type="button" id="cancelPermissions" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Salvar Permissões</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal de Criação de Usuário -->
    <div id="createUserModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg transform transition-all scale-95 opacity-0">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-semibold text-[#4A90E2]">Criar Novo Usuário</h3>
                <button id="closeCreateUserModal" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <form id="createUserForm" action="create_user_admin.php" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="new_username" class="block text-sm font-medium text-[#4A90E2]">Nome de Usuário</label>
                        <input type="text" name="username" id="new_username" required class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-[#4A90E2]">Senha</label>
                        <input type="password" name="password" id="create_user_password" required class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                    </div>
                    <div>
                        <label for="new_user_role" class="block text-sm font-medium text-[#4A90E2]">Nível de Acesso</label>
                        <select name="role" id="new_user_role" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                            <option value="user">Usuário</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="new_user_setor" class="block text-sm font-medium text-[#4A90E2]">Setor</label>
                        <select name="setor_id" id="new_user_setor" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                            <option value="" disabled selected>Selecione um setor...</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end mt-6 pt-4 border-t">
                    <button type="button" id="cancelCreateUser" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#4A90E2] mb-2">Sucesso!</h3>
                <p class="text-[#4A90E2] mb-6">Seu arquivo foi enviado com sucesso e está disponível no sistema.</p>
                <button id="closeModal" class="w-full px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    <div id="excel-viewer" class="w-full h-[700px] mb-6 hidden">
        <iframe id="excel-iframe" class="w-full h-full rounded-lg border" frameborder="0"></iframe>
        <div class="flex justify-end mt-2">
            <button id="close-excel-viewer" class="px-3 py-1 bg-white text-[#4A90E2] rounded hover:bg-[#e5e7eb]">Fechar</button>
        </div>
    </div>
    <div id="excel-table-container" class="w-full mb-6 hidden bg-[#1d3870] rounded-lg shadow p-4 overflow-auto text-white"></div>
    <script>
    const faqsData = <?php echo json_encode($faqs_public); ?>;
    <?php
        $user_profile_photo_path = !empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : '';
        $user_initial = strtoupper(substr($_SESSION['username'], 0, 1));
        $user_avatar_html = $user_profile_photo_path ? "'<img src=\"{$user_profile_photo_path}\" alt=\"Você\" class=\"chat-avatar\">'" : "'<div class=\"w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold text-lg border-2 border-white shadow-sm\">{$user_initial}</div>'";

        $sam_avatar_path = 'img/SAM.png';
        $sam_avatar_html = file_exists($sam_avatar_path) ? "'<img src=\"{$sam_avatar_path}\" alt=\"SAM\" class=\"chat-avatar\">'" : "'<div class=\"w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 text-lg border-2 border-white shadow-sm\"><i class=\"fas fa-robot\"></i></div>'";
    ?>
    const userAvatarHtml = <?php echo $user_avatar_html; ?>;
    const samAvatarHtml = <?php echo $sam_avatar_html; ?>;
    </script>
    <script src="src/js/script.js"></script>
    <!-- Scripts do Tour: Colocados no final do body para garantir a ordem de carregamento correta -->
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js"></script>
    <script src="tour.js.php"></script>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</body>
