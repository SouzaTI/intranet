<?php
session_start();
require_once 'conexao.php'; // ajuste o nome se for diferente

// Função para verificar permissão de visualização de seção
function can_view_section($section_name) {
    // Admins e God podem ver tudo
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) {
        return true;
    }
    // Usuários normais verificam a lista de permissões na sessão
    return isset($_SESSION['allowed_sections']) && in_array($section_name, $_SESSION['allowed_sections']);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'] ?? 'Usuário';

// Conta PDFs
$pdfCount = $conn->query("SELECT COUNT(*) as total FROM arquivos WHERE tipo='pdf'")->fetch_assoc()['total'] ?? 0;
// Conta Planilhas (Excel)
$excelCount = $conn->query("SELECT COUNT(*) as total FROM arquivos WHERE tipo='excel' OR tipo='planilha' OR tipo='Planilha Excel' OR tipo='xlsx' OR tipo='xls'")->fetch_assoc()['total'] ?? 0;
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

// Busca arquivos de Normas e Procedimentos e agrupa por setor
$normas_por_setor = [];
$sql_normas = "
    SELECT a.*, s.nome as nome_setor
    FROM arquivos a
    LEFT JOIN setores s ON a.setor_id = s.id
    WHERE a.departamento = 'Normas e Procedimentos'
    ORDER BY s.nome, a.titulo ASC
";
$result_normas = $conn->query($sql_normas);
if ($result_normas) {
    while ($norma = $result_normas->fetch_assoc()) {
        $nome_setor = $norma['nome_setor'] ?? 'Geral (Sem Setor)';
        $normas_por_setor[$nome_setor][] = $norma;
    }
}

// Lista de todas as seções disponíveis para o painel de permissões
$available_sections = [
    'dashboard' => 'Página Inicial',
    'documents' => 'Documentos PDF',
    'spreadsheets' => 'Planilhas',
    'information' => 'Informações (Visualização)',
    'matriz_comunicacao' => 'Matriz de Comunicação',
    'sugestoes' => 'Sugestões e Reclamações (Envio)',
    'faq' => 'FAQ',
    'normas' => 'Normas e Procedimentos',
    'about' => 'Sobre Nós',
    // Seções de Admin
    'upload' => 'Upload de Arquivos (Admin)',
    'info-upload' => 'Cadastrar Informação (Admin)',
    'registros_sugestoes' => 'Registros de Sugestões (Admin)',
    'settings' => 'Configurações (Admin)',
];

// Busca todos os usuários para a aba de permissões (apenas para admins)
$usuarios = [];
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) {
    $result_usuarios = $conn->query("SELECT id, username, department, role FROM users ORDER BY username ASC");
    if ($result_usuarios) {
        while ($usuario = $result_usuarios->fetch_assoc()) {
            $usuarios[] = $usuario;
        }
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
            // Para o filtro de setor (agora um select), usamos correspondência exata
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Intranet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
        }
        .sidebar {
            background: #254c90 !important;
        }
        .document-card {
            transition: all 0.2s ease;
        }
        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(37, 76, 144, 0.3);
        }
        .hover\:bg-indigo-700:hover, .hover\:bg-indigo-600:hover, .hover\:bg-indigo-900:hover, .hover\:bg-gray-100:hover, .hover\:bg-gray-300:hover, .hover\:bg-gray-50:hover {
            background-color: #1d3870 !important;
        }
        .rounded-full, .rounded-lg, .rounded-md {
            border-radius: 0.5rem !important;
        }
        .shadow, .shadow-lg, .shadow-xl {
            box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
        }
        .border, .border-gray-300, .border-gray-200, .border-dashed {
            border: 1px solid #1d3870 !important;
        }
        .focus\:ring-indigo-500:focus {
            box-shadow: 0 0 0 2px #254c90 !important;
        }
        #excel-table-container {
            max-width: 100%;
            max-height: 500px;
            overflow: auto;
            background: #fff;
            color: #254c90;
            border-radius: 0.5rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.10);
            margin-bottom: 1.5rem;
            padding: 1rem;
        }
        #excel-table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        #excel-table-container th, #excel-table-container td {
            border: 1px solid #254c90;
            padding: 6px 10px;
            font-size: 0.95rem;
            color: #254c90;
            background: #f8fafc;
        }
        #excel-table-container th {
            background: #254c90;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        table, th, td {
            font-family: 'Inter', Arial, sans-serif;
        }
        .text-main { color: #254c90; }
        .bg-main { background: #254c90; }

        /* Ajuste de cor de hover para a Matriz de Comunicação */
        #matriz_comunicacao table tbody tr:hover {
            background-color: #f1f5f9 !important; /* Cor cinza-azulado bem clara */
        }
        .cell-content-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .edit-trigger {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            cursor: pointer;
            color: #3b82f6; /* Azul */
            font-size: 0.8rem;
            margin-left: 8px;
        }
        tr:hover .edit-trigger { opacity: 1; }
        .cell-content[contenteditable="true"] {
            background-color: #e0e7ff !important;
            outline: 2px solid #4f46e5; /* Indigo */
            border-radius: 4px;
            padding: 2px 4px;
        }
        td.cell-saving { background-color: #fefce8 !important; } /* Amarelo */
        td.cell-success { background-color: #dcfce7 !important; transition: background-color 0.5s; } /* Verde */
        td.cell-error { background-color: #fee2e2 !important; transition: background-color 0.5s; } /* Vermelho */
    </style>
</head>
<body>
    <div class="flex h-screen bg-[#254c90]">
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
                <a href="#" data-section="dashboard" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('dashboard'); return false;">
                    <i class="fas fa-home w-6"></i>
                    <span>Página Inicial</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('documents')): ?>
                <a href="#" data-section="documents" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('documents'); return false;">
                    <i class="fas fa-file-pdf w-6"></i>
                    <span>Documentos PDF</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('spreadsheets')): ?>
                <a href="#" data-section="spreadsheets" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('spreadsheets'); return false;">
                    <i class="fas fa-file-excel w-6"></i>
                    <span>Planilhas</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('information')): ?>
                <a href="#" data-section="information" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('information'); return false;">
                    <i class="fas fa-info-circle w-6"></i>
                    <span>Informações</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('matriz_comunicacao')): ?>
                <a href="#" data-section="matriz_comunicacao" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('matriz_comunicacao'); return false;">
                    <i class="fas fa-address-book w-6"></i>
                    <span>Matriz de Comunicação</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('sugestoes')): ?>
                <a href="#" data-section="sugestoes" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('sugestoes'); return false;">
                    <i class="fas fa-comment-dots w-6"></i>
                    <span>Sugestões e Reclamações</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('faq')): ?>
                <a href="#" data-section="faq" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('faq'); return false;">
                    <i class="fas fa-question-circle w-6"></i>
                    <span>FAQ</span>
                </a>
                <?php endif; ?>
                <!-- Menu Normas e Procedimentos com Submenu -->
                <?php if (can_view_section('normas')): ?>
                <div>
                    <a href="#" id="normas-menu-toggle" class="sidebar-link w-full flex justify-between items-center py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white">
                        <span class="flex items-center space-x-2">
                            <i class="fas fa-book w-6"></i>
                            <span>Normas e Procedimentos</span>
                        </span>
                        <i id="normas-arrow" class="fas fa-chevron-down text-xs transition-transform"></i>
                    </a>
                    <div id="normas-submenu" class="hidden text-sm mt-2 pl-8 space-y-2">
                        <a href="#" class="sidebar-link block py-1.5 px-2 rounded hover:bg-[#1d3870] text-white" data-section="normas" onclick="showSection('normas', 'all'); return false;">Ver Todos</a>
                        <?php foreach ($setores as $setor): ?>
                            <a href="#" class="sidebar-link block py-1.5 px-2 rounded hover:bg-[#1d3870] text-white" data-section="normas" data-setor-filter="<?php echo htmlspecialchars($setor['nome']); ?>" onclick="showSection('normas', '<?php echo htmlspecialchars($setor['nome']); ?>'); return false;">
                                <?php echo htmlspecialchars($setor['nome']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="px-4 py-2 mt-8 uppercase text-xs font-semibold">Administração</div>
                <?php if (can_view_section('upload')): ?>
                    <a href="#" data-section="upload" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('upload'); return false;">
                        <i class="fas fa-upload w-6"></i>
                        <span>Upload de Arquivos</span>
                    </a>
                <?php endif; ?>
                <?php if (can_view_section('settings')): ?>
                    <a href="#" data-section="settings" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('settings'); return false;">
                        <i class="fas fa-cog w-6"></i>
                        <span>Configurações</span>
                    </a>
                <?php endif; ?>
                <?php if (can_view_section('registros_sugestoes')): ?>
                    <a href="#" data-section="registros_sugestoes" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('registros_sugestoes'); return false;">
                        <i class="fas fa-clipboard-list w-6"></i>
                        <span>Registros de Sugestões</span>
                    </a>
                <?php endif; ?>

                <!-- Links restantes -->
                <?php if (can_view_section('info-upload')): ?>
                <a href="#" data-section="info-upload" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('info-upload'); return false;">
                    <i class="fas fa-bullhorn w-6"></i>
                    <span>Cadastrar Informação</span>
                </a>
                <?php endif; ?>
                <?php if (can_view_section('about')): ?>
                <a href="#" data-section="about" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('about'); return false;">
                    <i class="fas fa-users w-6"></i>
                    <span>Sobre Nós</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-[#254c90] shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-3">
                        <button id="openSidebar" class="md:hidden focus:outline-none">
                            <i class="fas fa-bars text-white"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-white" id="pageTitle">Página Inicial</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Buscar..." class="search-input py-2 pl-10 pr-4 rounded-md border border-[#1d3870] focus:outline-none focus:border-[#254c90] w-64 g-white text-[#254c90] placeholder-[#254c90]">
                            <i class="fas fa-search text-white absolute left-3 top-3"></i>
                        </div>
                        <div class="flex items-center space-x-3 relative">
                            <button id="profileDropdownBtn" class="flex items-center space-x-2 hover:opacity-80 transition focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#254c90] font-semibold">
                                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                                </div>
                                <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($username); ?></span>
                                <i class="fas fa-chevron-down text-white text-xs"></i>
                            </button>
                            <div id="profileDropdown" class="absolute right-0 mt-12 w-40 bg-white rounded shadow-lg py-2 z-50 hidden">
                                <a href="logout.php" class="block px-4 py-2 text-[#254c90] hover:bg-[#e5e7eb] text-sm">Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-[#f8f9fb] p-4">
                <!-- Dashboard Section -->
                <section id="dashboard" class="space-y-6">
                    <!-- Bem-vindo -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h1 class="text-3xl font-bold text-[#254c90] mb-2">Bem-vindo à Intranet da Comercial Souza!</h1>
                        <p class="text-[#254c90] text-lg">Aqui você encontra as informações e ferramentas que precisa para o seu dia a dia.</p>
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
                                            <div class="font-semibold text-[#254c90]"><?php echo htmlspecialchars($row['titulo']); ?></div>
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
                                <script>
                                    const imgItems = document.querySelectorAll('#carrossel-imagens .carousel-img-item');
                                    let imgCurrent = 0;
                                    function showCarrosselImg(idx, direction = 1) {
                                        imgItems.forEach((el, i) => {
                                            el.classList.remove('opacity-100', 'scale-100', 'z-10');
                                            el.classList.add('opacity-0', 'scale-95', 'z-0');
                                            if (i === idx) {
                                                el.classList.add('opacity-100', 'scale-100', 'z-10');
                                                el.classList.remove('opacity-0', 'scale-95', 'z-0');
                                            }
                                        });
                                    }
                                    document.getElementById('prevCarrosselImg').onclick = function() {
                                        imgCurrent = (imgCurrent - 1 + imgItems.length) % imgItems.length;
                                        showCarrosselImg(imgCurrent, -1);
                                    };
                                    document.getElementById('nextCarrosselImg').onclick = function() {
                                        imgCurrent = (imgCurrent + 1) % imgItems.length;
                                        showCarrosselImg(imgCurrent, 1);
                                    };
                                    // Passa automaticamente a cada 4 segundos com animação suave
                                    setInterval(function() {
                                        imgCurrent = (imgCurrent + 1) % imgItems.length;
                                        showCarrosselImg(imgCurrent, 1);
                                    }, 4000);
                                    // Inicializa
                                    showCarrosselImg(imgCurrent, 1);
                                </script>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Substitua o bloco do rodapé por este, logo após o grid dos comunicados/carrossel, ainda dentro do <main> -->
                    <div class="w-full flex justify-center mt-12">
    <footer class="w-full max-w-5xl flex flex-col items-center justify-center">
        <div class="text-[#254c90] text-sm font-medium mb-2 text-center">
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
                                <input type="text" placeholder="Filtrar documentos..." class="search-input py-2 pl-10 pr-4 rounded-md border border-[#1d3870] focus:outline-none focus:border-[#254c90] w-64 bg-white text-[#254c90] placeholder-[#254c90]">
                                <i class="fas fa-search text-[#254c90] absolute left-3 top-3"></i>
                            </div>
                            <select class="border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:border-[#254c90] bg-white text-[#254c90]">
                                <option>Todos os departamentos</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        // Exemplo para a seção de Documentos PDF
                        $result = $conn->query("SELECT * FROM arquivos WHERE tipo='pdf' ORDER BY data_upload DESC");
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
                            <div class="document-card bg-white rounded-lg shadow overflow-hidden flex flex-col">
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
                                            <a href="uploads/'.$row['nome_arquivo'].'" download class="text-gray-600 hover:text-blue-600" title="Baixar"><i class="fas fa-download"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ';
                        }
                        ?>
                    </div>
                </section>
                <!-- Spreadsheets Section -->
                <section id="spreadsheets" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" placeholder="Filtrar planilhas..." class="search-input py-2 pl-10 pr-4 rounded-md border border-[#1d3870] focus:outline-none focus:border-[#254c90] w-64 bg-white text-[#254c90] placeholder-[#254c90]">
                                <i class="fas fa-search text-[#254c90] absolute left-3 top-3"></i>
                            </div>
                            <select class="border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:border-[#254c90] bg-white text-[#254c90]">
                                <option>Todos os departamentos</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        $result = $conn->query("SELECT * FROM arquivos WHERE tipo LIKE '%planilha%' OR tipo LIKE '%excel%' OR tipo LIKE '%xls%' ORDER BY data_upload DESC");
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $topBg = 'bg-green-50';
                                $icon = '<i class="fas fa-file-excel text-green-500 text-5xl"></i>';
                                echo '
                                <div class="document-card bg-white rounded-lg shadow overflow-hidden flex flex-col">
                                    <div class="w-full flex items-center justify-center '.$topBg.'" style="height:80px;">
                                        '.$icon.'
                                    </div>
                                    <div class="p-6 flex-1 flex flex-col">
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">'.htmlspecialchars($row['titulo']).'</h3>
                                        <p class="text-gray-700 mb-2">'.htmlspecialchars($row['descricao']).'</p>
                                        <div class="flex items-end justify-between mt-auto">
                                            <span class="text-xs text-gray-500">Atualizado: '.date('d/m/Y', strtotime($row['data_upload'])).'</span>
                                            <div class="flex items-center gap-4">
                                                <a href="uploads/'.$row['nome_arquivo'].'" download class="text-gray-600 hover:text-blue-600" title="Baixar"><i class="fas fa-download"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ';
                            }
                        } else {
                            echo '<div class="col-span-3 bg-[#1d3870] rounded-lg shadow p-6 text-center text-white">Nenhuma planilha cadastrada.</div>';
                        }
                        ?>
                    </div>
                </section>
                <!-- Information Section -->
                <section id="information" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b font-semibold text-lg text-[#254c90]">Comunicados Importantes</div>
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
                        <div class="font-semibold text-[#254c90]">'.$row['titulo'].'</div>
                        <div class="text-gray-700">'.$row['descricao'].'</div>
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
        <div class="p-4 border-b font-semibold text-lg text-[#254c90]">Informações Úteis</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $result = $conn->query("SELECT * FROM informacoes WHERE categoria='Informações Úteis' ORDER BY data_publicacao DESC");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="border rounded-lg p-4 bg-gray-50">
                        <div class="font-semibold text-[#254c90]">'.$row['titulo'].'</div>
                        <div class="text-gray-700">'.$row['descricao'].'</div>
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
                </section>
                <!-- Upload Section -->
                <section id="upload" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-[#254c90]">
                            <h3 class="text-lg font-semibold text-[#254c90]">Enviar Novo Arquivo</h3>
                        </div>
                        <div class="p-6">
                            <form id="uploadForm" class="space-y-6" method="POST" enctype="multipart/form-data" action="upload.php">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-[#254c90] mb-1">Título do Arquivo</label>
                                        <input type="text" name="titulo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90] placeholder-[#254c90]" placeholder="Ex: Relatório Financeiro Q2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#254c90] mb-1">Tipo de Documento</label>
                                        <select name="tipo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                                            <option>PDF</option>
                                            <option>Planilha Excel</option>
                                            <option>Documento Word</option>
                                            <option>Apresentação PowerPoint</option>
                                            <option>Outro</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#254c90] mb-1">Departamento</label>
                                        <select name="departamento" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                                            <option>Financeiro</option>
                                            <option>RH</option>
                                            <option>Marketing</option>
                                            <option>Operações</option>
                                            <option>TI</option>
                                            <option>Normas e Procedimentos</option>
                                            <option>Administrativo</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#254c90] mb-1">Nível de Acesso</label>
                                        <select name="nivel_acesso" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                                            <option>Público (Todos os colaboradores)</option>
                                            <option>Restrito (Apenas departamento)</option>
                                            <option>Confidencial (Apenas gestores)</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Campo de Setor (visível apenas para Normas e Procedimentos) -->
                                <div id="setor-field" class="hidden">
                                    <label class="block text-sm font-medium text-[#254c90] mb-1">Setor (Normas e Procedimentos)</label>
                                    <select name="setor_id" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                                        <option value="">Selecione um setor</option>
                                        <?php foreach ($setores as $setor): ?>
                                            <option value="<?php echo $setor['id']; ?>"><?php echo htmlspecialchars($setor['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-[#254c90] mb-1">Descrição</label>
                                    <textarea name="descricao" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90] placeholder-[#254c90]" rows="3" placeholder="Breve descrição do conteúdo do arquivo..."></textarea>
                                </div>
                                <div class="border-2 border-dashed border-[#1d3870] rounded-lg p-6 text-center" id="dropzone">
                                    <input type="file" name="arquivo" id="fileInput" class="hidden">
                                    <div class="space-y-2">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-white"></i>
                                        <p class="text-white">Arraste e solte arquivos aqui ou</p>
                                        <button type="button" id="browseButton" class="px-4 py-2 bg-white text-[#254c90] rounded-md hover:bg-[#e5e7eb] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                            Selecionar Arquivo
                                        </button>
                                        <p class="text-sm text-white">Tamanho máximo: 50MB</p>
                                    </div>
                                    <div id="filePreview" class="hidden mt-4">
                                        <div class="flex items-center justify-between bg-[#254c90] p-3 rounded-md">
                                            <div class="flex items-center">
                                                <i class="fas fa-file text-white mr-3"></i>
                                                <div>
                                                    <p class="text-sm font-medium text-white" id="fileName">arquivo.pdf</p>
                                                    <p class="text-xs text-white" id="fileSize">2.5 MB</p>
                                                </div>
                                            </div>
                                            <button type="button" id="removeFile" class="text-white hover:text-[#e5e7eb]">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="px-4 py-2 bg-white text-[#254c90] rounded-md mr-2 hover:bg-[#e5e7eb] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        Cancelar
                                    </button>
                                    <button type="submit" id="submitUpload" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                                        Enviar Arquivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Formulário para cadastrar imagem do carrossel -->
                    <div class="bg-white rounded-lg shadow p-6 mt-8">
        <h3 class="text-lg font-semibold text-[#254c90] mb-4">Adicionar Imagem ao Carrossel</h3>
        <form method="POST" enctype="multipart/form-data" action="cadastrar_carrossel.php">
            <div>
                <label class="block text-sm font-medium text-[#254c90] mb-1">Imagem</label>
                <input type="file" name="imagem" accept="image/*" required class="w-full border border-[#1d3870] rounded-md px-4 py-2 bg-white text-[#254c90]">
                <span class="text-xs text-gray-500">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</span>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Adicionar ao Carrossel</button>
            </div>
        </form>
    </div>
                </section>
                <!-- Normas e Procedimentos Section -->
                <section id="normas" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#254c90] mb-4">Normas e Procedimentos</h2>
                        <p class="text-[#254c90] mb-6">
                            Aqui você encontrará todas as normas, políticas e procedimentos importantes da Comercial Souza.
                        </p>
                        <?php if (count($normas_por_setor) > 0): ?>
                            <?php foreach ($normas_por_setor as $nome_setor => $arquivos_do_setor): ?>
                                <div class="setor-container" data-setor="<?php echo htmlspecialchars($nome_setor); ?>">
                                    <h3 class="text-xl font-bold text-[#1d3870] mb-4 border-b-2 border-[#1d3870] pb-2"><?php echo htmlspecialchars($nome_setor); ?></h3>
                                    <div class="space-y-4 mb-8">
                                        <?php foreach ($arquivos_do_setor as $arquivo): ?>
                                            <div class="border-l-4 border-blue-500 pl-4">
                                                <h4 class="font-semibold text-lg text-[#254c90]"><?php echo htmlspecialchars($arquivo['titulo']); ?></h4>
                                                <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($arquivo['descricao']); ?></p>
                                                <div class="mt-2">
                                                    <a href="uploads/<?php echo htmlspecialchars($arquivo['nome_arquivo']); ?>" download class="text-indigo-600 hover:text-indigo-800 text-sm inline-block">Baixar Documento &gt;</a>
                                                    <?php if (isset($arquivo['tipo']) && strtolower($arquivo['tipo']) === 'pdf'): ?>
                                                        <a href="uploads/<?php echo htmlspecialchars($arquivo['nome_arquivo']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm inline-block ml-4">Visualizar Online &gt;</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">Nenhum documento de normas e procedimentos foi cadastrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </section>
                <!-- Informações/Avisos Section -->
                <section id="info-upload" class="hidden space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-[#254c90]">
            <h3 class="text-lg font-semibold text-[#254c90]">Cadastrar Aviso/Informação</h3>
        </div>
        <div class="p-6">
            <form id="infoForm" class="space-y-6" method="POST" action="cadastrar_informacao.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-[#254c90] mb-1">Título</label>
                        <input type="text" name="titulo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#254c90] mb-1">Categoria</label>
                        <select name="categoria" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" required>
                            <option value="Comunicados Importantes">Comunicados Importantes</option>
                            <option value="Informações Úteis">Informações Úteis</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#254c90] mb-1">Exibir a partir de</label>
                        <input type="date" name="data_inicial" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#254c90] mb-1">Exibir até</label>
                        <input type="date" name="data_final" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#254c90] mb-1">Descrição</label>
                    <textarea name="descricao" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" rows="4" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#254c90] mb-1">Data de Publicação</label>
                    <input type="date" name="data_publicacao" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#254c90] mb-1">Cor da Barra (opcional)</label>
                    <select name="cor" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                        <option value="blue">Azul</option>
                        <option value="green">Verde</option>
                        <option value="orange">Laranja</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#254c90] mb-1">Imagem (opcional)</label>
                    <input type="file" name="imagem" accept="image/*" class="w-full border border-[#1d3870] rounded-md px-4 py-2 bg-white text-[#254c90]">
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
</section>
                <!-- Sugestões e Reclamações Section -->
                <section id="sugestoes" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#254c90] mb-2">Sugestões e Reclamações</h2>
                        <p class="text-[#254c90] mb-6">Sua opinião é muito importante para nós. Envie sua sugestão ou reclamação para ajudar a melhorar nosso ambiente de trabalho!</p>
                        
                        <form id="sugestaoForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[#254c90] mb-1">Tipo de Mensagem</label>
                                <select name="tipo" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" required>
                                    <option value="">Selecione...</option>
                                    <option value="sugestao">Sugestão</option>
                                    <option value="reclamacao">Reclamação</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-[#254c90] mb-1">Seu E-mail (Opcional)</label>
                                    <input type="email" name="email" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" placeholder="seunome@comercialsouza.com.br">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-[#254c90] mb-1">Seu Telefone (Opcional)</label>
                                    <input type="tel" name="telefone" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" placeholder="(XX) XXXXX-XXXX">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#254c90] mb-1">Sua Mensagem</label>
                                <textarea name="mensagem" rows="5" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]" placeholder="Digite sua mensagem aqui..." required></textarea>
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
        <h2 class="text-2xl font-bold text-[#254c90] mb-4">FAQ - Perguntas Frequentes</h2>
        <div class="space-y-4">
            <div class="faq-item">
                <div class="font-semibold text-[#254c90]">1. Como faço para resetar minha senha?</div>
                <div class="text-gray-700">Para resetar sua senha, vá até a página de login e clique em "Esqueci minha senha". Siga as instruções enviadas para seu e-mail.</div>
            </div>
            <div class="faq-item">
                <div class="font-semibold text-[#254c90]">2. Onde encontro os documentos da empresa?</div>
                <div class="text-gray-700">Os documentos da empresa estão disponíveis na seção "Documentos PDF" e "Planilhas" do sistema.</div>
            </div>
            <div class="faq-item">
                <div class="font-semibold text-[#254c90]">3. Como posso entrar em contato com o suporte?</div>
                <div class="text-gray-700">Para entrar em contato com o suporte, envie um e-mail para suporte@exemplo.com ou ligue para (11) 1234-5678.</div>
            </div>
        </div>
    </div>
</section>

<!-- Sobre Nós Section -->
<section id="about" class="hidden space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-[#254c90] mb-4">Sobre Nós</h2>
        <p class="text-[#254c90] mb-4">
            A Comercial Souza iniciou sua jornada somente com os dois irmãos que compravam, vendiam e entregavam, e desde então vem construindo uma história de sucesso. Ao longo dessa trajetória buscamos sempre o comprometimento com nossos clientes, colaboradores e fornecedores. Nossa equipe é composta por profissionais especializados e capacitados para oferecer o melhor serviço e atendimento, nosso relacionamento se dá de maneira consultiva, visitando o cliente e entendendo suas necessidades. Atualmente atendemos redes de supermercados e mercados de pequeno e médio porte com uma ampla linha de produtos, nos segmentos: Alimentar, bebidas, perfumaria, limpeza e bazar, com mais de 3.000 itens, 7 supervisores e mais de 150 representantes externos. Contamos com um amplo CD de armazenagem e tecnologia de ponta (ERP e Força de Vendas), a Comercial Souza vem se constituindo no mercado como uma empresa inovadora, temos agilidade em nossas entregas e com isso melhorando o abastecimento e rentabilidades em nossos clientes.
        </p>
        <div class="mb-4">
            <div class="font-bold text-white bg-blue-600 rounded-t px-4 py-2">Nossa Visão</div>
            <div class="bg-white border border-blue-600 rounded-b px-4 py-2 text-[#254c90]">
                Ser referência em distribuição na área de atuação com produtos de qualidade, buscando sempre excelência em logística, inovações e tecnologias para atender melhor às necessidades dos nossos clientes.
            </div>
        </div>
        <div class="mb-4">
            <div class="font-bold text-white bg-green-600 rounded-t px-4 py-2">Nossa Missão</div>
            <div class="bg-white border border-green-600 rounded-b px-4 py-2 text-[#254c90]">
                Entregar eficiência, qualidade no atendimento e agilidade em todos os processos. Agregar valores para nossos clientes, colaboradores e fornecedores.
            </div>
        </div>
        <div>
            <div class="font-bold text-white bg-cyan-600 rounded-t px-4 py-2">Nossos Valores</div>
            <div class="bg-white border border-cyan-600 rounded-b px-4 py-2 text-[#254c90]">
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

                <!-- Registros de Sugestões Section (Admin only) -->
                <section id="registros_sugestoes" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#254c90] mb-4">Registros de Sugestões e Reclamações</h2>
                        <p class="text-[#254c90] mb-6">Acompanhe e gerencie as mensagens enviadas pelos colaboradores.</p>
                        <div id="registros-container">
                            <!-- O conteúdo da tabela será carregado aqui via JavaScript -->
                        </div>
                    </div>
                </section>

                <!-- Settings Section (Admin only) -->
                <section id="settings" class="hidden space-y-6">
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <!-- Abas de Navegação -->
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="flex space-x-4" aria-label="Tabs">
                                <button class="settings-tab-btn px-3 py-2 font-medium text-sm rounded-t-lg border-b-2 border-[#254c90] text-[#254c90]" data-tab="menu">
                                    Menu/Sub-Menu
                                </button>
                                <button class="settings-tab-btn px-3 py-2 font-medium text-sm rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="users">
                                    Usuários/Permissões
                                </button>
                            </nav>
                        </div>

                        <!-- Conteúdo da Aba: Menu/Sub-Menu -->
                        <div id="settings-tab-menu" class="settings-tab-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Card para Adicionar Setor -->
                                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                                    <h3 class="text-lg font-semibold text-[#254c90] mb-4 border-b pb-2">Adicionar Novo Setor</h3>
                                    <form action="gerenciar_setores.php" method="POST" class="space-y-4">
                                        <input type="hidden" name="action" value="add">
                                        <div>
                                            <label for="nome_setor" class="block text-sm font-medium text-[#254c90]">Nome do Setor</label>
                                            <input type="text" id="nome_setor" name="nome_setor" required class="mt-1 w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]">Adicionar Setor</button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Card para Listar e Remover Setores -->
                                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                                    <h3 class="text-lg font-semibold text-[#254c90] mb-4 border-b pb-2">Setores Cadastrados</h3>
                                    <ul class="space-y-3 max-h-96 overflow-y-auto">
                                        <?php foreach ($setores as $setor): ?>
                                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded-md">
                                                <span class="text-[#254c90]"><?php echo htmlspecialchars($setor['nome']); ?></span>
                                                <form action="gerenciar_setores.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este setor? Os documentos associados não serão apagados, mas ficarão sem setor.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="setor_id" value="<?php echo $setor['id']; ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Excluir Setor">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Conteúdo da Aba: Usuários/Permissões -->
                        <div id="settings-tab-users" class="settings-tab-content hidden">
                            <h3 class="text-lg font-semibold text-[#254c90] mb-4 border-b pb-2">Gerenciar Usuários e Permissões</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Departamento</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Nível</th>
                                            <th class="py-2 px-4 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td class="py-3 px-4 text-sm text-gray-800 font-medium"><?= htmlspecialchars($usuario['username']) ?></td>
                                                <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($usuario['department']) ?></td>
                                                <td class="py-3 px-4 text-sm text-gray-600"><?= ucfirst(htmlspecialchars($usuario['role'])) ?></td>
                                                <td class="py-3 px-4 text-sm text-center"><button class="open-permissions-modal px-3 py-1 bg-[#254c90] text-white text-xs font-semibold rounded-md hover:bg-[#1d3870]" data-userid="<?= $usuario['id'] ?>" data-username="<?= htmlspecialchars($usuario['username']) ?>">Gerenciar</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-red-500">Acesso negado.</p>
                <?php endif; ?>
                </section>

                <!-- Matriz de Comunicação Section -->
                <section id="matriz_comunicacao" class="hidden space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-[#254c90] mb-4">Matriz de Comunicação</h2>
                        
                        <!-- Formulário de Filtros -->
                        <form action="index.php" method="GET" class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap items-end gap-4">
                            <input type="hidden" name="section" value="matriz_comunicacao">
                            <div>
                                <label for="nome" class="block text-sm font-medium text-gray-700">Nome:</label>
                                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                            </div>
                            <div>
                                <label for="setor" class="block text-sm font-medium text-gray-700">Setor:</label>
                                <select id="setor" name="setor" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-gray-700" style="min-width: 180px;">
                                    <option value="">Todos os setores</option>
                                    <?php
                                    // Busca os setores distintos diretamente da tabela para popular o filtro
                                    $result_setores_matriz = $conn->query("SELECT DISTINCT setor FROM matriz_comunicacao WHERE setor IS NOT NULL AND setor != '' ORDER BY setor ASC");
                                    if ($result_setores_matriz) {
                                        while ($setor_item = $result_setores_matriz->fetch_assoc()) {
                                            $nome_setor = htmlspecialchars($setor_item['setor']);
                                            $selected = (isset($_GET['setor']) && $_GET['setor'] === $setor_item['setor']) ? 'selected' : '';
                                            echo "<option value=\"{$nome_setor}\" {$selected}>{$nome_setor}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">E-mail:</label>
                                <input type="text" id="email" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                            </div>
                            <div>
                                <label for="ramal" class="block text-sm font-medium text-gray-700">Ramal:</label>
                                <input type="text" id="ramal" name="ramal" value="<?= htmlspecialchars($_GET['ramal'] ?? '') ?>" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870]" title="Aplicar filtros de busca"><i class="fas fa-search"></i> Pesquisar</button>
                                <a href="index.php?section=matriz_comunicacao" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700" title="Remover todos os filtros">Limpar</a>
                                <?php if (in_array($_SESSION['role'], ['admin', 'god'])): ?>
                                    <button type="button" id="btn-adicionar-funcionario" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" title="Adicionar novo registro"><i class="fas fa-plus"></i> Adicionar Novo</button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Formulário para Adicionar Novo Funcionário (oculto por padrão) -->
                        <div id="form-adicionar-funcionario" class="hidden bg-gray-100 p-6 rounded-lg border border-gray-300 my-6">
                            <h3 class="text-lg font-semibold text-[#254c90] mb-4">Adicionar Novo Funcionário</h3>
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
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (count($funcionarios_matriz) > 0): ?>
                                        <?php foreach ($funcionarios_matriz as $funcionario): ?>
                                            <?php $is_admin = in_array($_SESSION['role'], ['admin', 'god']); ?>
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
                        <div class="mt-6 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php
                                if ($total_paginas_matriz > 1):
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
                </section>
            </main>
        </div>
    </div>
    <!-- Modal de Permissões -->
    <div id="permissionsModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl transform transition-all scale-95 opacity-0">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-semibold text-[#254c90]">Gerenciar Permissões: <span id="modalUsername" class="font-bold"></span></h3>
                <button id="closePermissionsModal" class="text-gray-500 hover:text-gray-800">&times;</button>
            </div>
            <form id="permissionsForm" action="update_user_permissions.php" method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="space-y-6">
                    <!-- Nível de Acesso (Role) -->
                    <div>
                        <label class="block text-sm font-medium text-[#254c90] mb-2">Nível de Acesso Principal</label>
                        <select name="role" id="modalUserRole" class="w-full border border-[#1d3870] rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#254c90] bg-white text-[#254c90]">
                            <option value="user">Usuário</option>
                            <option value="admin">Admin</option>
                            <option value="god">God</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Admin e God têm acesso a todas as telas por padrão.</p>
                    </div>
                    <!-- Permissões de Tela (Sections) -->
                    <div id="sectionsPermissionsContainer">
                        <label class="block text-sm font-medium text-[#254c90] mb-2">Acesso às Telas (para nível "Usuário")</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 border p-4 rounded-md max-h-64 overflow-y-auto">
                            <?php foreach ($available_sections as $key => $label): ?>
                                <div>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="sections[]" value="<?= $key ?>" class="form-checkbox h-5 w-5 text-[#254c90] rounded border-gray-300 focus:ring-[#1d3870]">
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
    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-[#254c90] mb-2">Sucesso!</h3>
                <p class="text-[#254c90] mb-6">Seu arquivo foi enviado com sucesso e está disponível no sistema.</p>
                <button id="closeModal" class="w-full px-4 py-2 bg-[#254c90] text-white rounded-md hover:bg-[#1d3870] focus:outline-none focus:ring-2 focus:ring-[#254c90]">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    <div id="excel-viewer" class="w-full h-[700px] mb-6 hidden">
        <iframe id="excel-iframe" class="w-full h-full rounded-lg border" frameborder="0"></iframe>
        <div class="flex justify-end mt-2">
            <button id="close-excel-viewer" class="px-3 py-1 bg-white text-[#254c90] rounded hover:bg-[#e5e7eb]">Fechar</button>
        </div>
    </div>
    <div id="excel-table-container" class="w-full mb-6 hidden bg-[#1d3870] rounded-lg shadow p-4 overflow-auto text-white"></div>
    <script>
        document.getElementById('openSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });
        function showSection(sectionId, filter = null) {
            // Esconde todas as seções
            document.querySelectorAll('main > section').forEach(section => {
                section.classList.add('hidden');
            });
            document.getElementById(sectionId).classList.remove('hidden');
            // Atualiza o título
            const titles = {
                'dashboard': 'Página Inicial',
                'documents': 'Documentos PDF',
                'spreadsheets': 'Planilhas',
                'information': 'Informações',
                'matriz_comunicacao': 'Matriz de Comunicação',
                'sugestoes': 'Sugestões e Reclamações',
                'faq': 'FAQ',
                'normas': 'Normas e Procedimentos',
                'upload': 'Upload de Arquivos',
                'info-upload': 'Cadastrar Informação',
                'about': 'Sobre Nós',
                'registros_sugestoes': 'Registros de Sugestões',
                'settings': 'Configurações'
            };
            document.getElementById('pageTitle').textContent = titles[sectionId] || 'Página Inicial';

            // Remove destaque de todos os links
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('bg-[#1d3870]');
                // Fecha o submenu de normas se clicar em outro link
                if (link.id !== 'normas-menu-toggle') {
                    document.getElementById('normas-submenu').classList.add('hidden');
                    document.getElementById('normas-arrow').classList.remove('rotate-180');
                }
            });
            // Adiciona destaque ao link ativo
            const activeLink = document.querySelector('.sidebar-link[data-section="' + sectionId + '"]');
            if (activeLink) {
                activeLink.classList.add('bg-[#1d3870]');
            }

            // Filtra a seção de normas se um filtro for passado
            if (sectionId === 'normas' && filter) {
                document.querySelectorAll('#normas .setor-container').forEach(container => {
                    container.style.display = (filter === 'all' || container.dataset.setor === filter) ? 'block' : 'none';
                });
                // Adiciona destaque ao link do submenu clicado
                document.querySelector(`.sidebar-link[data-setor-filter="${filter}"]`)?.classList.add('bg-[#1d3870]');
            }

            // Carrega dinamicamente a lista de sugestões para admins
            if (sectionId === 'registros_sugestoes') {
                const container = document.getElementById('registros-container');
                container.innerHTML = '<p class="text-center text-[#254c90]">Carregando registros...</p>';
                fetch('registros_sugestoes.php')
                    .then(response => response.text())
                    .then(html => container.innerHTML = html)
                    .catch(() => container.innerHTML = '<p class="text-center text-red-500">Erro ao carregar os registros.</p>');
            }
        }
        document.getElementById('browseButton').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                showFilePreview(e.target.files[0]);
                const file = e.target.files[0];
                if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, {type: 'array'});
                        const sheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[sheetName];
                        const html = XLSX.utils.sheet_to_html(worksheet, {header: "<thead>", footer: "</tfoot>"});
                        document.getElementById('excel-table-container').innerHTML = html;
                        document.getElementById('excel-table-container').classList.remove('hidden');
                        document.getElementById('excel-viewer').classList.add('hidden');
                    };
                    reader.readAsArrayBuffer(file);
                }
            }
        });
        document.getElementById('removeFile').addEventListener('click', function() {
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').classList.add('hidden');
        });
        function showFilePreview(file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('filePreview').classList.remove('hidden');
        }
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        const dropzone = document.getElementById('dropzone');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        function highlight() {
            dropzone.classList.add('border-[#254c90]');
            dropzone.classList.add('bg-[#1d3870]');
        }
        function unhighlight() {
            dropzone.classList.remove('border-[#254c90]');
            dropzone.classList.remove('bg-[#1d3870]');
        }
        dropzone.addEventListener('drop', handleDrop, false);
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                document.getElementById('fileInput').files = files;
                showFilePreview(files[0]);
            }
        }
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('successModal').classList.add('hidden');
        });
        document.querySelectorAll('.view-excel').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const fileUrl = this.getAttribute('data-file');
                // Esconde os cards de planilhas
                document.querySelectorAll('#spreadsheets .document-card').forEach(card => {
                    card.classList.add('hidden');
                });
                // Carrega e exibe a tabela Excel
                fetch(fileUrl)
                    .then(res => res.arrayBuffer())
                    .then(buffer => {
                        const data = new Uint8Array(buffer);
                        const workbook = XLSX.read(data, {type: 'array'});
                        const sheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[sheetName];
                        const html = XLSX.utils.sheet_to_html(worksheet, {header: "<thead>", footer: "</tfoot>"});
                        document.getElementById('excel-table-container').innerHTML = html;
                        document.getElementById('excel-table-container').classList.remove('hidden');
                    });
            });
        });
        document.getElementById('close-excel-viewer').addEventListener('click', function() {
            document.getElementById('excel-viewer').classList.add('hidden');
            document.getElementById('excel-iframe').src = '';
            document.getElementById('excel-table-container').classList.remove('hidden');
        });
        // Garante que só o dashboard está selecionado ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('bg-[#1d3870]');
            });
            const dashboardLink = document.querySelector('.sidebar-link[data-section="dashboard"]');
            if (dashboardLink) dashboardLink.classList.add('bg-[#1d3870]');

            // Check for section in URL to auto-open
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            const tab = urlParams.get('tab');
            if (section) {
                showSection(section);
                // If it's the settings section and a tab is specified, click the tab button
                if (section === 'settings' && tab) {
                    const tabButton = document.querySelector(`.settings-tab-btn[data-tab="${tab}"]`);
                    tabButton?.click();
                }
            }
        });
        // Dropdown do perfil
        document.getElementById('profileDropdownBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('profileDropdown').classList.toggle('hidden');
});
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('profileDropdown');
    if (!dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
});
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successModal').classList.remove('hidden');
            form.reset();
            document.getElementById('filePreview').classList.add('hidden');
            // Aqui você pode atualizar a lista de uploads se quiser
        } else {
            alert(data.message || 'Erro ao enviar arquivo.');
        }
    })
    .catch(() => {
        alert('Erro ao enviar arquivo.');
    });
});

document.getElementById('sugestaoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    var statusDiv = document.getElementById('sugestaoStatus');

    statusDiv.innerHTML = '<p class="text-blue-600">Enviando...</p>';

    fetch('salvar_sugestao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = `<p class="text-green-600 font-semibold">${data.message}</p>`;
            form.reset();
        } else {
            statusDiv.innerHTML = `<p class="text-red-600 font-semibold">${data.message}</p>`;
        }
    })
    .catch(() => {
        statusDiv.innerHTML = '<p class="text-red-600 font-semibold">Ocorreu um erro de conexão. Tente novamente.</p>';
    });
});

// Event listener para a mudança de status da sugestão (usando delegação de evento)
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('status-sugestao')) {
        const selectElement = e.target;
        const sugestaoId = selectElement.dataset.id;
        const novoStatus = selectElement.value;
        const feedbackSpan = selectElement.nextElementSibling;

        feedbackSpan.textContent = 'Salvando...';

        const formData = new FormData();
        formData.append('sugestao_id', sugestaoId);
        formData.append('novo_status', novoStatus);

        fetch('atualizar_status_sugestao.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                feedbackSpan.textContent = 'Salvo!';
                setTimeout(() => { feedbackSpan.textContent = ''; }, 2000); // Limpa a mensagem após 2 segundos
            }
        });
    }
});

// Lógica para abas da seção de Configurações
const settingsTabBtns = document.querySelectorAll('.settings-tab-btn');
const settingsTabContents = document.querySelectorAll('.settings-tab-content');

settingsTabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        // Atualiza a aparência dos botões
        settingsTabBtns.forEach(b => {
            b.classList.remove('border-[#254c90]', 'text-[#254c90]');
            b.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        });
        btn.classList.add('border-[#254c90]', 'text-[#254c90]');
        btn.classList.remove('border-transparent', 'text-gray-500');

        // Mostra/esconde o conteúdo das abas
        settingsTabContents.forEach(content => {
            if (content.id === `settings-tab-${tab}`) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
    });
});
// Lógica para o menu de Normas e Procedimentos
document.getElementById('normas-menu-toggle').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('normas-submenu').classList.toggle('hidden');
    document.getElementById('normas-arrow').classList.toggle('rotate-180');
});

// Lógica do Modal de Permissões
const permissionsModal = document.getElementById('permissionsModal');
const modalContent = permissionsModal.querySelector('.transform');
const openModalBtns = document.querySelectorAll('.open-permissions-modal');
const closeModalBtn = document.getElementById('closePermissionsModal');
const cancelBtn = document.getElementById('cancelPermissions');
const modalUserId = document.getElementById('modalUserId');
const modalUsername = document.getElementById('modalUsername');
const modalUserRole = document.getElementById('modalUserRole');
const sectionsContainer = document.getElementById('sectionsPermissionsContainer');
const sectionCheckboxes = permissionsModal.querySelectorAll('input[name="sections[]"]');

function openPermissionsModal() {
    permissionsModal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closePermissionsModal() {
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        permissionsModal.classList.add('hidden');
    }, 200);
}

openModalBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const userId = btn.dataset.userid;
        const username = btn.dataset.username;

        modalUserId.value = userId;
        modalUsername.textContent = username;

        // Limpa o formulário antes de carregar novos dados
        sectionCheckboxes.forEach(cb => cb.checked = false);
        modalUserRole.value = 'user';

        // Busca as permissões atuais do usuário via AJAX
        fetch(`get_user_permissions.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) { alert(data.error); return; }
                
                modalUserRole.value = data.role;
                data.sections.forEach(sectionName => {
                    const checkbox = permissionsModal.querySelector(`input[value="${sectionName}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                sectionsContainer.style.display = (data.role === 'user') ? 'block' : 'none';
                openPermissionsModal();
            });
    });
});

modalUserRole.addEventListener('change', () => {
    sectionsContainer.style.display = (modalUserRole.value === 'user') ? 'block' : 'none';
});

closeModalBtn.addEventListener('click', closePermissionsModal);
cancelBtn.addEventListener('click', closePermissionsModal);

// Lógica para mostrar/esconder o campo de setor no formulário de upload
document.querySelector('select[name="departamento"]').addEventListener('change', function() {
    const setorField = document.getElementById('setor-field');
    if (this.value === 'Normas e Procedimentos') {
        setorField.classList.remove('hidden');
    } else {
        setorField.classList.add('hidden');
    }
});

// Lógica para mostrar/esconder formulário de adicionar funcionário
const btnAdicionar = document.getElementById('btn-adicionar-funcionario');
const formAdicionar = document.getElementById('form-adicionar-funcionario');
const btnCancelarAdicao = document.getElementById('btn-cancelar-adicao');

if (btnAdicionar && formAdicionar && btnCancelarAdicao) {
    btnAdicionar.addEventListener('click', () => {
        formAdicionar.classList.remove('hidden');
    });

    btnCancelarAdicao.addEventListener('click', () => {
        formAdicionar.classList.add('hidden');
    });
}

// Lógica para edição na Matriz de Comunicação com ícone de lápis
const matrizSection = document.getElementById('matriz_comunicacao');

matrizSection.addEventListener('click', function(e) {
    // Ativa a edição ao clicar no lápis
    if (e.target && e.target.classList.contains('edit-trigger')) {
        const wrapper = e.target.closest('.cell-content-wrapper');
        const contentSpan = wrapper.querySelector('.cell-content');

        contentSpan.setAttribute('contenteditable', 'true');
        contentSpan.focus();

        // Seleciona o texto para facilitar a edição
        const range = document.createRange();
        range.selectNodeContents(contentSpan);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }
});

matrizSection.addEventListener('blur', function(e) {
    // Salva a alteração quando o foco é perdido (blur)
    if (e.target && e.target.classList.contains('cell-content') && e.target.isContentEditable) {
        const contentSpan = e.target;
        const td = contentSpan.closest('td');
        const tr = contentSpan.closest('tr');

        const id = tr.dataset.id;
        const column = td.dataset.column;
        const value = contentSpan.textContent.trim();

        contentSpan.setAttribute('contenteditable', 'false');

        td.classList.remove('cell-success', 'cell-error');
        td.classList.add('cell-saving');

        const formData = new FormData();
        formData.append('id', id);
        formData.append('column', column);
        formData.append('value', value);

        fetch('atualizar_matriz.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                td.classList.remove('cell-saving');
                td.classList.add(data.success ? 'cell-success' : 'cell-error');
                if (!data.success) alert(data.message || 'Erro ao salvar.');
                setTimeout(() => td.classList.remove('cell-success', 'cell-error'), 2000);
            })
            .catch(() => alert('Erro de conexão.'));
    }
}, true); // Usa a fase de captura para garantir que o evento seja pego

function visualizarArquivo(url, tipo) {
    // Mostra o container do visualizador
    document.getElementById('excel-viewer').classList.remove('hidden');
    // Decide como abrir
    if (tipo.toLowerCase().includes('excel') || tipo.toLowerCase().includes('planilha') || url.endsWith('.xlsx') || url.endsWith('.xls')) {
        // Excel: usa Google Docs Viewer para melhor compatibilidade
        document.getElementById('excel-iframe').src = 'https://docs.google.com/gview?url=' + encodeURIComponent(window.location.origin + '/' + url) + '&embedded=true';
    } else if (tipo.toLowerCase().includes('pdf') || url.endsWith('.pdf')) {
        // PDF: abre direto no iframe
        document.getElementById('excel-iframe').src = url;
    } else {
        // Outros tipos: tenta abrir direto
        document.getElementById('excel-iframe').src = url;
    }
}
    </script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</body>
</html>
