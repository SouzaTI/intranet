<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Função para verificar permissão de visualização de seção (copiada de index.php para consistência)
function can_view_section($section_name) {
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) {
        return true;
    }
    if (isset($_SESSION['allowed_sections']) && in_array($section_name, $_SESSION['allowed_sections'])) {
        return true;
    }
    return false;
}

$user_role = $_SESSION['role'] ?? 'user';
$message = '';
$message_type = ''; // 'success' or 'error'

// Lógica para adicionar/editar vaga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $titulo = $_POST['titulo'] ?? '';
    $setor = $_POST['setor'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $requisitos = $_POST['requisitos'] ?? '';
    $modelo_folheto = $_POST['modelo_folheto'] ?? '';
    $data_publicacao = $_POST['data_publicacao'] ?? '';

    if (empty($titulo) || empty($setor) || empty($data_publicacao)) {
        $message = 'Título, Setor e Data de Publicação são campos obrigatórios.';
        $message_type = 'error';
    } else {
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO vagas (titulo, setor, descricao, requisitos, modelo_folheto, data_publicacao) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $titulo, $setor, $descricao, $requisitos, $modelo_folheto, $data_publicacao);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Adicionou Vaga', "Título: " . substr($titulo, 0, 100) . "...");
                $message = 'Vaga adicionada com sucesso!';
                $message_type = 'success';
            } else {
                logActivity($_SESSION['user_id'], 'Erro ao adicionar Vaga', "Título: " . substr($titulo, 0, 100) . "...", 'error');
                $message = 'Erro ao adicionar vaga: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("UPDATE vagas SET titulo = ?, setor = ?, descricao = ?, requisitos = ?, modelo_folheto = ?, data_publicacao = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $titulo, $setor, $descricao, $requisitos, $modelo_folheto, $data_publicacao, $id);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Editou Vaga', "ID: {$id} | Título: " . substr($titulo, 0, 100) . "...");
                $message = 'Vaga atualizada com sucesso!';
                $message_type = 'success';
            } else {
                logActivity($_SESSION['user_id'], 'Erro ao editar Vaga', "ID: {$id} | Título: " . substr($titulo, 0, 100) . "...", 'error');
                $message = 'Erro ao atualizar vaga: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM vagas WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Excluiu Vaga', "ID: {$id}");
                $message = 'Vaga excluída com sucesso!';
                $message_type = 'success';
            } else {
                logActivity($_SESSION['user_id'], 'Erro ao excluir Vaga', "ID: {$id}", 'error');
                $message = 'Erro ao excluir vaga: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Busca todos os setores para o formulário
$setores = [];
$result_setores = $conn->query("SELECT * FROM setores ORDER BY nome ASC");
if ($result_setores) {
    while ($setor = $result_setores->fetch_assoc()) {
        $setores[] = $setor;
    }
}

// Lógica para exibir o formulário de adição/edição
$edit_vaga = null;
if (isset($_GET['action']) && $_GET['action'] === 'add' && can_view_section('mural_vagas_admin')) {
    // Exibir formulário de adição
    $page_title = 'Adicionar Nova Vaga';
} elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && can_view_section('mural_vagas_admin')) {
    // Exibir formulário de edição
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM vagas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_vaga = $result->fetch_assoc();
    $stmt->close();
    if (!$edit_vaga) {
        $message = 'Vaga não encontrada para edição.';
        $message_type = 'error';
        $page_title = 'Mural de Vagas'; // Fallback
    } else {
        $page_title = 'Editar Vaga: ' . htmlspecialchars($edit_vaga['titulo']);
    }
} else {
    // Exibir mural de vagas
    $page_title = 'Mural de Vagas';
}

// Busca as vagas para exibição no mural
$vagas = [];
$result_vagas = $conn->query("SELECT * FROM vagas ORDER BY data_publicacao DESC, id DESC");
if ($result_vagas) {
    while ($row = $result_vagas->fetch_assoc()) {
        $vagas[] = $row;
    }
}

// Define o tema com base na empresa do usuário (copiado de index.php)
$themeClass = '';
$logoPath = 'img/logo.svg';
$samImagePath = 'img/SAM.png';
$virtualAssistantName = 'SAM';
$companyDisplayName = 'Comercial Souza';
if (isset($_SESSION['empresa']) && strtolower($_SESSION['empresa']) === 'mixkar') {
    $themeClass = 'theme-mixkar';
    $logoPath = 'img/mixkar/logo-mixkar.png';
    $samImagePath = 'img/mixkar/KAI.png';
    $virtualAssistantName = 'KAI';
    $companyDisplayName = 'Mixkar';
}
$sam_avatar_path = $samImagePath;
$username = $_SESSION['username']; // Para o cabeçalho
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Sistema Intranet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css"/>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link rel="stylesheet" href="tour.css.php">
    <script src="lib/tinymce/tinymce/js/tinymce/tinymce.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="src/css/style.css">
    <style>
        /* Estilos para os "folhetos" de vaga */
        .vaga-folheto {
            background-color: #ffffff;
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
            padding: 1.5rem; /* p-6 */
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
            border: 1px solid #e5e7eb; /* border-gray-200 */
        }
        .vaga-folheto:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); /* hover:shadow-xl */
            transform: translateY(-0.25rem); /* hover:-translate-y-1 */
        }
        .vaga-folheto h3 {
            font-size: 1.25rem; /* text-xl */
            font-weight: 700; /* font-bold */
            color: #1d3870; /* text-[#1d3870] */
            margin-bottom: 0.75rem; /* mb-3 */
        }
        .vaga-folheto .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #4b5563; /* text-gray-700 */
        }
        .vaga-folheto .info-item i {
            width: 1.25rem; /* w-5 */
            margin-right: 0.5rem; /* mr-2 */
            color: #6b7280; /* text-gray-500 */
        }
        .vaga-folheto .description-preview {
            font-size: 0.875rem; /* text-sm */
            color: #4b5563; /* text-gray-700 */
            margin-top: 1rem;
            margin-bottom: 1rem;
            max-height: 4.5em; /* Aproximadamente 3 linhas de texto */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .vaga-folheto .read-more {
            color: #254c90;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .vaga-folheto .read-more:hover {
            color: #1d3870;
        }

        /* Estilos para o modelo Compacto */
        .vaga-folheto-compact {
            padding: 1rem; /* p-4 */
            font-size: 0.875rem; /* text-sm */
        }
        .vaga-folheto-compact h3 {
            font-size: 1rem; /* text-base */
            margin-bottom: 0.5rem;
        }
        .vaga-folheto-compact .info-item {
            font-size: 0.75rem; /* text-xs */
            margin-bottom: 0.25rem;
        }
        .vaga-folheto-compact .description-preview {
            max-height: 3em; /* Aproximadamente 2 linhas de texto */
            -webkit-line-clamp: 2;
        }

        /* Estilos para o modelo Destaque */
        .vaga-folheto-highlight {
            border: 2px solid #254c90; /* Cor principal do tema */
            background-color: #e0f2f7; /* Um azul claro */
            box-shadow: 0 15px 20px -5px rgba(37, 76, 144, 0.2), 0 6px 6px -3px rgba(37, 76, 144, 0.1);
        }
        .vaga-folheto-highlight h3 {
            color: #254c90;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .vaga-folheto-highlight .info-item i {
            color: #254c90;
        }
        .tinymce-editor-container {
            border: 1px solid #d1d5db; /* border-gray-300 */
            border-radius: 0.375rem; /* rounded-md */
            overflow: hidden; /* Para garantir que o TinyMCE se ajuste */
        }
    </style>
</head>
<body class="<?= $themeClass ?>">
    <div class="flex h-screen">
        <!-- Sidebar (simplificada para este arquivo) -->
        <div id="sidebar" class="sidebar text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform md:relative md:translate-x-0 transition duration-200 ease-in-out z-20">
            <div class="flex items-center justify-between px-4">
                <div class="flex items-center space-x-2">
                    <img src="<?= $logoPath ?>" alt="Logo" class="w-32">
                </div>
            </div>
            <nav class="mt-10">
                <a href="index.php" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2">
                    <i class="fas fa-home w-6"></i>
                    <span>Voltar para Início</span>
                </a>
                <?php if (can_view_section('mural_vagas_admin')): ?>
                    <a href="mural_vagas.php?action=add" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#34495E] text-white flex items-center space-x-2 mt-2">
                        <i class="fas fa-plus-circle w-6"></i>
                        <span>Adicionar Nova Vaga</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header (simplificado) -->
            <header class="main-header shadow-sm relative z-30">
                <div class="flex items-center justify-between p-4">
                    <h2 class="text-xl font-semibold text-white" id="pageTitle"><?= $page_title ?></h2>
                    <div class="flex items-center space-x-4">
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
                            <div id="profileDropdown" class="absolute right-0 mt-12 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden">
                                <a href="logout.php" class="block px-4 py-2 text-[#4A90E2] hover:bg-[#e5e7eb] text-sm flex items-center"><i class="fas fa-sign-out-alt w-6 text-center mr-1"></i>Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
                <?php if ($message): ?>
                    <div class="bg-<?= $message_type === 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $message_type === 'success' ? 'green' : 'red' ?>-500 text-<?= $message_type === 'success' ? 'green' : 'red' ?>-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
                        <p class="font-bold"><?= $message_type === 'success' ? 'Sucesso!' : 'Erro!' ?></p>
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit') && can_view_section('mural_vagas_admin')): ?>
                    <!-- Formulário de Adição/Edição de Vaga -->
                    <div class="bg-white rounded-lg shadow overflow-hidden p-6">
                        <h2 class="text-2xl font-bold text-[#4A90E2] mb-6 border-b pb-3"><?= $page_title ?></h2>
                        <form action="mural_vagas.php" method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="<?= $edit_vaga ? 'edit' : 'add' ?>">
                            <?php if ($edit_vaga): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($edit_vaga['id']) ?>">
                            <?php endif; ?>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="titulo" class="block text-sm font-medium text-[#4A90E2] mb-1">Título da Vaga</label>
                                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($edit_vaga['titulo'] ?? '') ?>" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                                </div>
                                <div>
                                    <label for="setor" class="block text-sm font-medium text-[#4A90E2] mb-1">Setor</label>
                                    <select id="setor" name="setor" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                                        <option value="">Selecione um setor</option>
                                        <?php foreach ($setores as $setor): ?>
                                            <option value="<?= htmlspecialchars($setor['nome']) ?>" <?= (isset($edit_vaga['setor']) && $edit_vaga['setor'] === $setor['nome']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($setor['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="descricao" class="block text-sm font-medium text-[#4A90E2] mb-1">Descrição da Vaga</label>
                                <div class="tinymce-editor-container">
                                    <textarea id="descricao" name="descricao" class="procedure-editor"><?= htmlspecialchars($edit_vaga['descricao'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div>
                                <label for="requisitos" class="block text-sm font-medium text-[#4A90E2] mb-1">Requisitos</label>
                                <div class="tinymce-editor-container">
                                    <textarea id="requisitos" name="requisitos" class="procedure-editor"><?= htmlspecialchars($edit_vaga['requisitos'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="modelo_folheto" class="block text-sm font-medium text-[#4A90E2] mb-1">Modelo do Folheto</label>
                                    <select id="modelo_folheto" name="modelo_folheto" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]" >
                                        <option value="default" <?= (isset($edit_vaga['modelo_folheto']) && $edit_vaga['modelo_folheto'] === 'default') ? 'selected' : '' ?>>Padrão</option>
                                        <option value="compact" <?= (isset($edit_vaga['modelo_folheto']) && $edit_vaga['modelo_folheto'] === 'compact') ? 'selected' : '' ?>>Compacto</option>
                                        <option value="highlight" <?= (isset($edit_vaga['modelo_folheto']) && $edit_vaga['modelo_folheto'] === 'highlight') ? 'selected' : '' ?>>Destaque</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="data_publicacao" class="block text-sm font-medium text-[#4A90E2] mb-1">Data de Publicação</label>
                                    <input type="date" id="data_publicacao" name="data_publicacao" value="<?= htmlspecialchars($edit_vaga['data_publicacao'] ?? date('Y-m-d')) ?>" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#4A90E2]">
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <button type="button" onclick="window.location.href='mural_vagas.php'" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">Cancelar</button>
                                <button type="submit" class="px-6 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                    <?= $edit_vaga ? 'Atualizar Vaga' : 'Adicionar Vaga' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Mural de Vagas -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-6 border-b pb-4">
                            <h2 class="text-2xl font-bold text-[#4A90E2]">Mural de Vagas</h2>
                            <?php if (can_view_section('mural_vagas_admin')): ?>
                                <a href="mural_vagas.php?action=add" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium flex items-center">
                                    <i class="fas fa-plus-circle mr-2"></i>Adicionar Nova Vaga
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (count($vagas) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($vagas as $vaga): ?>
                                    <?php
                                        $folheto_class = 'vaga-folheto';
                                        if (isset($vaga['modelo_folheto'])) {
                                            if ($vaga['modelo_folheto'] === 'compact') {
                                                $folheto_class .= ' vaga-folheto-compact';
                                            } elseif ($vaga['modelo_folheto'] === 'highlight') {
                                                $folheto_class .= ' vaga-folheto-highlight';
                                            }
                                        }
                                    ?>
                                    <div class="<?= $folheto_class ?>">
                                        <h3><?= htmlspecialchars($vaga['titulo']) ?></h3>
                                        <div class="info-item">
                                            <i class="fas fa-building"></i>
                                            <span>Setor: <strong><?= htmlspecialchars($vaga['setor']) ?></strong></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Publicado em: <strong><?= date('d/m/Y', strtotime($vaga['data_publicacao'])) ?></strong></span>
                                        </div>
                                        <p class="description-preview">
                                            <?= strip_tags($vaga['descricao']) ?>
                                        </p>
                                        <div class="mt-auto flex justify-between items-center">
                                            <a href="#" onclick="showVagaDetails(<?= $vaga['id'] ?>); return false;" class="read-more">Ver Detalhes</a>
                                            <?php if (can_view_section('mural_vagas_admin')): ?>
                                                <div class="flex space-x-2">
                                                    <a href="mural_vagas.php?action=edit&id=<?= $vaga['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Editar"><i class="fas fa-edit"></i></a>
                                                    <form action="mural_vagas.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta vaga?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $vaga['id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Excluir"><i class="fas fa-trash-alt"></i></button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-gray-500 py-16">
                                <i class="fas fa-info-circle text-5xl mb-4 text-gray-400"></i>
                                <p class="text-xl">Nenhuma vaga cadastrada no momento.</p>
                                <?php if (can_view_section('mural_vagas_admin')): ?>
                                    <p class="mt-2">Clique em "Adicionar Nova Vaga" para começar.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal de Detalhes da Vaga -->
    <div id="vagaDetailsModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-2xl transform transition-all scale-95 opacity-0">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 id="modalVagaTitle" class="text-2xl font-bold text-[#4A90E2]"></h3>
                <button id="closeVagaModal" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-4">
                <div>
                    <h4 class="font-semibold text-lg text-gray-800">Setor</h4>
                    <p id="modalVagaSetor" class="text-gray-700"></p>
                </div>
                <div>
                    <h4 class="font-semibold text-lg text-gray-800">Descrição</h4>
                    <div id="modalVagaDescricao" class="text-gray-700 prose max-w-none"></div>
                </div>
                <div>
                    <h4 class="font-semibold text-lg text-gray-800">Requisitos</h4>
                    <div id="modalVagaRequisitos" class="text-gray-700 prose max-w-none"></div>
                </div>
                 <div>
                    <h4 class="font-semibold text-lg text-gray-800">Data de Publicação</h4>
                    <p id="modalVagaData" class="text-gray-600 text-sm"></p>
                </div>
            </div>
            <div class="flex justify-end mt-6 pt-4 border-t">
                <button id="closeVagaModalBtn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        // Script para o dropdown do perfil (simplificado)
        const profileDropdownBtn = document.getElementById('profileDropdownBtn');
        if (profileDropdownBtn) {
            profileDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('profileDropdown').classList.toggle('hidden');
            });
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown && !dropdown.classList.contains('hidden') && !profileDropdownBtn.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // Inicialização do TinyMCE
        if(typeof tinymce !== 'undefined'){
            tinymce.init({
                selector: 'textarea.procedure-editor',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                height: 300,
                menubar: false,
                readonly: false,
                license_key: 'gpl',
                images_upload_url: 'upload_image.php',
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', 'upload_image.php');
                    
                    xhr.onload = () => {
                        if (xhr.status >= 400) {
                            reject('HTTP Error: ' + xhr.status); return;
                        }
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location != 'string') {
                            reject('Invalid JSON: ' + xhr.responseText); return;
                        }
                        resolve(json.location);
                    };
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                })
            });
        }

        // Lógica do Modal de Detalhes da Vaga
        const vagaDetailsModal = document.getElementById('vagaDetailsModal');
        const closeVagaModalBtn = document.getElementById('closeVagaModalBtn');
        const closeVagaModalIcon = document.getElementById('closeVagaModal');

        function showVagaDetails(vagaId) {
            // Em um cenário real, você faria uma requisição AJAX para buscar os detalhes da vaga
            // Por enquanto, vamos simular com os dados já carregados na página (se houver)
            const vagasData = <?= json_encode($vagas) ?>;
            const vaga = vagasData.find(v => v.id == vagaId);

            if (vaga) {
                document.getElementById('modalVagaTitle').textContent = vaga.titulo;
                document.getElementById('modalVagaSetor').textContent = vaga.setor;
                document.getElementById('modalVagaDescricao').innerHTML = vaga.descricao; // Usar innerHTML para renderizar HTML do TinyMCE
                document.getElementById('modalVagaRequisitos').innerHTML = vaga.requisitos; // Usar innerHTML
                document.getElementById('modalVagaData').textContent = new Date(vaga.data_publicacao).toLocaleDateString('pt-BR');
                
                vagaDetailsModal.classList.remove('hidden');
                setTimeout(() => {
                    vagaDetailsModal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
                    vagaDetailsModal.querySelector('.transform').classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        }

        function closeVagaModal() {
            vagaDetailsModal.querySelector('.transform').classList.remove('scale-100', 'opacity-100');
            vagaDetailsModal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                vagaDetailsModal.classList.add('hidden');
            }, 200);
        }

        if (closeVagaModalBtn) {
            closeVagaModalBtn.addEventListener('click', closeVagaModal);
        }
        if (closeVagaModalIcon) {
            closeVagaModalIcon.addEventListener('click', closeVagaModal);
        }
    </script>
</body>
</html>