<?php
session_start();
require_once 'conexao.php'; // ajuste o nome se for diferente
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
                <a href="#" data-section="dashboard" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('dashboard'); return false;">
                    <i class="fas fa-home w-6"></i>
                    <span>Página Inicial</span>
                </a>
                <a href="#" data-section="documents" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('documents'); return false;">
                    <i class="fas fa-file-pdf w-6"></i>
                    <span>Documentos PDF</span>
                </a>
                <a href="#" data-section="spreadsheets" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('spreadsheets'); return false;">
                    <i class="fas fa-file-excel w-6"></i>
                    <span>Planilhas</span>
                </a>
                <a href="#" data-section="information" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('information'); return false;">
                    <i class="fas fa-info-circle w-6"></i>
                    <span>Informações</span>
                </a>
                <a href="#" data-section="sugestoes" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('sugestoes'); return false;">
                    <i class="fas fa-comment-dots w-6"></i>
                    <span>Sugestões e Reclamações</span>
                </a>
                <a href="#" data-section="faq" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('faq'); return false;">
                    <i class="fas fa-question-circle w-6"></i>
                    <span>FAQ</span>
                </a>
                <div class="px-4 py-2 mt-8 uppercase text-xs font-semibold">Administração</div>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="#" data-section="upload" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('upload'); return false;">
                        <i class="fas fa-upload w-6"></i>
                        <span>Upload de Arquivos</span>
                    </a>
                    <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2">
                        <i class="fas fa-cog w-6"></i>
                        <span>Configurações</span>
                    </a>
                <?php endif; ?>
                <a href="#" data-section="info-upload" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('info-upload'); return false;">
                    <i class="fas fa-bullhorn w-6"></i>
                    <span>Cadastrar Informação</span>
                </a>
                <a href="#" data-section="about" class="sidebar-link block py-2.5 px-4 rounded transition duration-200 hover:bg-[#1d3870] text-white flex items-center space-x-2" onclick="showSection('about'); return false;">
                    <i class="fas fa-users w-6"></i>
                    <span>Sobre Nós</span>
                </a>
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
        <h2 class="text-2xl font-bold text-[#254c90] mb-4">Sugestões e Reclamações</h2>
        <p class="text-[#254c90] mb-4">Envie sua sugestão ou reclamação para ajudar a melhorar nosso ambiente de trabalho!</p>
        <!-- Formulário ou instruções aqui -->
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
            </main>
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
        function showSection(sectionId) {
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
                'upload': 'Upload de Arquivos'
            };
            document.getElementById('pageTitle').textContent = titles[sectionId];

            // Remove destaque de todos os links
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('bg-[#1d3870]');
            });
            // Adiciona destaque ao link ativo
            const activeLink = document.querySelector('.sidebar-link[data-section="' + sectionId + '"]');
            if (activeLink) {
                activeLink.classList.add('bg-[#1d3870]');
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
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9613e4dce2355e0f',t:'MTc1Mjg2MTc4Ny4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script>
</body>
</html>
