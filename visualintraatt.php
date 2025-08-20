<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intranet Corporativa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-item:hover {
            background-color: #1e40af;
            transform: translateX(4px);
            transition: all 0.3s ease;
        }
        .content-fade {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mixkar Theme Styles */
        .mixkar-theme .sidebar-item:hover {
            background-color: #d97706;
        }
        .mixkar-theme .sidebar-item.active {
            background-color: #f59e0b !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans" id="mainBody">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-80 bg-blue-900 text-white shadow-xl">
            <div class="p-6 border-b border-blue-800">
                <h1 class="text-2xl font-bold text-center">Intranet Corporativa</h1>
            </div>
            <nav class="mt-6">
                <ul class="space-y-2 px-4">
                    <li><button onclick="showContent('home')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg bg-blue-800 transition-all duration-300">🏠 Página Inicial</button></li>
                    <li><button onclick="showContent('normas')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">📋 Normas e Procedimentos</button></li>
                    <li><button onclick="showContent('informacoes')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">ℹ️ Informações</button></li>
                    <li><button onclick="showContent('matriz')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">📞 Matriz de Comunicação</button></li>
                    <li><button onclick="showContent('sugestoes')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">💡 Sugestões e Reclamações</button></li>
                    <li><button onclick="showContent('sistemas')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">💻 Sistemas</button></li>
                    <li><button onclick="showContent('upload')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">📁 Upload de Arquivos</button></li>
                    <li><button onclick="showContent('configuracoes')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">⚙️ Configurações</button></li>
                    <li><button onclick="showContent('registro')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">📝 Registro de Sugestões</button></li>
                    <li><button onclick="showContent('criar')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">➕ Criar Procedimentos</button></li>
                    <li><button onclick="showContent('cadastrar')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">📊 Cadastrar Informação</button></li>
                    <li><button onclick="showContent('sobre')" class="sidebar-item w-full text-left px-4 py-3 rounded-lg hover:bg-blue-800 transition-all duration-300">🏢 Sobre Nós</button></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-6">
                <div class="flex justify-between items-center">
                    <h2 id="pageTitle" class="text-3xl font-bold text-blue-900">Página Inicial</h2>
                    <div class="flex items-center space-x-4">
                        <select id="profileSelector" onchange="changeProfile()" class="px-3 py-1 border border-gray-300 rounded text-sm">
                            <option value="default">Perfil Padrão</option>
                            <option value="mixkar">Perfil Mixkar</option>
                        </select>
                        <span class="text-gray-600">Bem-vindo, Usuário</span>
                        <div id="userAvatar" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">U</div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="p-6">
                <!-- Página Inicial -->
                <div id="home" class="content-section content-fade">
                    <!-- Carrossel de Banners -->
                    <div class="bg-white rounded-xl shadow-lg mb-6 overflow-hidden">
                        <div class="relative h-64">
                            <div id="carousel" class="flex transition-transform duration-500 ease-in-out h-full">
                                <!-- Banner 1 -->
                                <div class="min-w-full bg-gradient-to-r from-blue-600 to-blue-800 flex items-center justify-between px-8 text-white">
                                    <div>
                                        <h2 class="text-3xl font-bold mb-2">🎯 Metas 2024</h2>
                                        <p class="text-lg">Juntos rumo ao crescimento sustentável</p>
                                        <button class="mt-4 bg-white text-blue-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100">Saiba Mais</button>
                                    </div>
                                    <div class="text-6xl opacity-20">📊</div>
                                </div>
                                
                                <!-- Banner 2 -->
                                <div class="min-w-full bg-gradient-to-r from-green-600 to-green-800 flex items-center justify-between px-8 text-white">
                                    <div>
                                        <h2 class="text-3xl font-bold mb-2">🌱 Sustentabilidade</h2>
                                        <p class="text-lg">Compromisso com o meio ambiente</p>
                                        <button class="mt-4 bg-white text-green-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100">Participe</button>
                                    </div>
                                    <div class="text-6xl opacity-20">🌍</div>
                                </div>
                                
                                <!-- Banner 3 -->
                                <div class="min-w-full bg-gradient-to-r from-purple-600 to-purple-800 flex items-center justify-between px-8 text-white">
                                    <div>
                                        <h2 class="text-3xl font-bold mb-2">🚀 Inovação</h2>
                                        <p class="text-lg">Transformação digital em andamento</p>
                                        <button class="mt-4 bg-white text-purple-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100">Explore</button>
                                    </div>
                                    <div class="text-6xl opacity-20">💡</div>
                                </div>
                                
                                <!-- Banner 4 -->
                                <div class="min-w-full bg-gradient-to-r from-orange-600 to-orange-800 flex items-center justify-between px-8 text-white">
                                    <div>
                                        <h2 class="text-3xl font-bold mb-2">👥 Bem-estar</h2>
                                        <p class="text-lg">Cuidando da nossa equipe</p>
                                        <button class="mt-4 bg-white text-orange-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100">Inscreva-se</button>
                                    </div>
                                    <div class="text-6xl opacity-20">❤️</div>
                                </div>
                            </div>
                            
                            <!-- Controles do Carrossel -->
                            <button onclick="previousSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-2 rounded-full transition-all">
                                ←
                            </button>
                            <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-2 rounded-full transition-all">
                                →
                            </button>
                            
                            <!-- Indicadores -->
                            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                                <button onclick="goToSlide(0)" class="carousel-dot w-3 h-3 bg-white bg-opacity-50 rounded-full transition-all"></button>
                                <button onclick="goToSlide(1)" class="carousel-dot w-3 h-3 bg-white bg-opacity-50 rounded-full transition-all"></button>
                                <button onclick="goToSlide(2)" class="carousel-dot w-3 h-3 bg-white bg-opacity-50 rounded-full transition-all"></button>
                                <button onclick="goToSlide(3)" class="carousel-dot w-3 h-3 bg-white bg-opacity-50 rounded-full transition-all"></button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Comunicados -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-blue-900 flex items-center">
                                    📢 Comunicados Recentes
                                </h3>
                                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver todos</button>
                            </div>
                            <div class="space-y-4">
                                <div class="border-l-4 border-red-500 pl-4 py-3 bg-red-50 rounded-r-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-gray-800">🚨 Manutenção Programada - Sistema ERP</h4>
                                            <p class="text-gray-600 text-sm">Publicado em 20/01/2024 • TI</p>
                                            <p class="text-gray-700 mt-2">Sistema indisponível sábado das 22h às 6h de domingo para atualizações de segurança.</p>
                                        </div>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">Urgente</span>
                                    </div>
                                </div>
                                
                                <div class="border-l-4 border-blue-600 pl-4 py-3 bg-blue-50 rounded-r-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-gray-800">📋 Nova Política de Home Office</h4>
                                            <p class="text-gray-600 text-sm">Publicado em 18/01/2024 • RH</p>
                                            <p class="text-gray-700 mt-2">Diretrizes atualizadas para trabalho híbrido. Consulte o manual completo na seção de normas.</p>
                                        </div>
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">Novo</span>
                                    </div>
                                </div>
                                
                                <div class="border-l-4 border-green-600 pl-4 py-3 bg-green-50 rounded-r-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-gray-800">🎓 Treinamento: Segurança da Informação</h4>
                                            <p class="text-gray-600 text-sm">Publicado em 15/01/2024 • Segurança</p>
                                            <p class="text-gray-700 mt-2">Inscrições abertas até 25/01. Curso obrigatório para todos os colaboradores.</p>
                                        </div>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">Ação Req.</span>
                                    </div>
                                </div>
                                
                                <div class="border-l-4 border-yellow-600 pl-4 py-3 bg-yellow-50 rounded-r-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-gray-800">🍕 Confraternização de Janeiro</h4>
                                            <p class="text-gray-600 text-sm">Publicado em 12/01/2024 • Eventos</p>
                                            <p class="text-gray-700 mt-2">Pizza no refeitório na sexta-feira às 18h. Confirme sua presença até quarta-feira.</p>
                                        </div>
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">Evento</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acesso Rápido e Estatísticas -->
                        <div class="space-y-6">
                            <!-- Acesso Rápido -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-blue-900 mb-4 flex items-center">
                                    ⚡ Acesso Rápido
                                </h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="showContent('sistemas')" class="bg-blue-100 hover:bg-blue-200 p-4 rounded-lg text-center transition-colors">
                                        <div class="text-2xl mb-2">💼</div>
                                        <span class="text-sm font-medium text-blue-800">ERP</span>
                                    </button>
                                    <button onclick="showContent('upload')" class="bg-green-100 hover:bg-green-200 p-4 rounded-lg text-center transition-colors">
                                        <div class="text-2xl mb-2">📁</div>
                                        <span class="text-sm font-medium text-green-800">Upload</span>
                                    </button>
                                    <button onclick="showContent('sugestoes')" class="bg-purple-100 hover:bg-purple-200 p-4 rounded-lg text-center transition-colors">
                                        <div class="text-2xl mb-2">💡</div>
                                        <span class="text-sm font-medium text-purple-800">Sugestões</span>
                                    </button>
                                    <button onclick="showContent('matriz')" class="bg-orange-100 hover:bg-orange-200 p-4 rounded-lg text-center transition-colors">
                                        <div class="text-2xl mb-2">📞</div>
                                        <span class="text-sm font-medium text-orange-800">Contatos</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Aniversariantes do Mês -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-blue-900 mb-4 flex items-center">
                                    🎂 Aniversariantes de Janeiro
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-3 p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                                        <div class="w-10 h-10 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            MS
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">Maria Silva</h4>
                                            <p class="text-sm text-gray-600">RH • 25/01</p>
                                        </div>
                                        <div class="text-2xl">🎉</div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            JS
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">João Santos</h4>
                                            <p class="text-sm text-gray-600">TI • 28/01</p>
                                        </div>
                                        <div class="text-2xl">🎈</div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg border-l-4 border-green-400">
                                        <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            AC
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">Ana Costa</h4>
                                            <p class="text-sm text-gray-600">Financeiro • 30/01</p>
                                        </div>
                                        <div class="text-2xl">🎁</div>
                                    </div>
                                    
                                    <div class="text-center pt-2">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Ver todos os aniversários →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Normas e Procedimentos -->
                <div id="normas" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Normas e Procedimentos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-semibold text-blue-800">Código de Conduta</h4>
                                <p class="text-gray-600 mt-2">Diretrizes éticas e comportamentais</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-semibold text-blue-800">Política de Segurança</h4>
                                <p class="text-gray-600 mt-2">Normas de segurança da informação</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-semibold text-blue-800">Procedimentos de RH</h4>
                                <p class="text-gray-600 mt-2">Processos de recursos humanos</p>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-semibold text-blue-800">Manual de Qualidade</h4>
                                <p class="text-gray-600 mt-2">Padrões e processos de qualidade</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações -->
                <div id="informacoes" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Informações Gerais</h3>
                        <div class="space-y-6">
                            <div class="border-l-4 border-blue-600 pl-6">
                                <h4 class="text-lg font-semibold text-gray-800">Horário de Funcionamento</h4>
                                <p class="text-gray-600">Segunda a Sexta: 8h às 18h</p>
                            </div>
                            <div class="border-l-4 border-blue-600 pl-6">
                                <h4 class="text-lg font-semibold text-gray-800">Contatos Importantes</h4>
                                <p class="text-gray-600">TI: ramal 1001 | RH: ramal 1002 | Recepção: ramal 1000</p>
                            </div>
                            <div class="border-l-4 border-blue-600 pl-6">
                                <h4 class="text-lg font-semibold text-gray-800">Benefícios</h4>
                                <p class="text-gray-600">Vale alimentação, plano de saúde, vale transporte</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matriz de Comunicação -->
                <div id="matriz" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Matriz de Comunicação</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-blue-100">
                                        <th class="border border-gray-300 px-4 py-2 text-left">Departamento</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Responsável</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Email</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Ramal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2">Recursos Humanos</td>
                                        <td class="border border-gray-300 px-4 py-2">Maria Silva</td>
                                        <td class="border border-gray-300 px-4 py-2">rh@empresa.com</td>
                                        <td class="border border-gray-300 px-4 py-2">1002</td>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <td class="border border-gray-300 px-4 py-2">Tecnologia da Informação</td>
                                        <td class="border border-gray-300 px-4 py-2">João Santos</td>
                                        <td class="border border-gray-300 px-4 py-2">ti@empresa.com</td>
                                        <td class="border border-gray-300 px-4 py-2">1001</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2">Financeiro</td>
                                        <td class="border border-gray-300 px-4 py-2">Ana Costa</td>
                                        <td class="border border-gray-300 px-4 py-2">financeiro@empresa.com</td>
                                        <td class="border border-gray-300 px-4 py-2">1003</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sugestões e Reclamações -->
                <div id="sugestoes" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Sugestões e Reclamações</h3>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option>Sugestão</option>
                                    <option>Reclamação</option>
                                    <option>Elogio</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assunto</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Digite o assunto">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem</label>
                                <textarea rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Descreva sua sugestão ou reclamação"></textarea>
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Enviar</button>
                        </form>
                    </div>
                </div>

                <!-- Sistemas -->
                <div id="sistemas" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Sistemas Corporativos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="border border-gray-200 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                <div class="text-3xl mb-2">💼</div>
                                <h4 class="font-semibold text-blue-800">ERP</h4>
                                <p class="text-gray-600 text-sm mt-2">Sistema de gestão empresarial</p>
                                <button class="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Acessar</button>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                <div class="text-3xl mb-2">📊</div>
                                <h4 class="font-semibold text-blue-800">BI</h4>
                                <p class="text-gray-600 text-sm mt-2">Business Intelligence</p>
                                <button class="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Acessar</button>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                <div class="text-3xl mb-2">👥</div>
                                <h4 class="font-semibold text-blue-800">RH</h4>
                                <p class="text-gray-600 text-sm mt-2">Sistema de recursos humanos</p>
                                <button class="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Acessar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload de Arquivos -->
                <div id="upload" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Upload de Arquivos</h3>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <div class="text-4xl mb-4">📁</div>
                            <p class="text-gray-600 mb-4">Arraste arquivos aqui ou clique para selecionar</p>
                            <input type="file" multiple class="hidden" id="fileInput">
                            <button onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Selecionar Arquivos</button>
                        </div>
                    </div>
                </div>

                <!-- Configurações -->
                <div id="configuracoes" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Configurações</h3>
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Preferências de Notificação</h4>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" checked class="mr-2">
                                        <span>Receber notificações por email</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" class="mr-2">
                                        <span>Notificações push</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Tema</h4>
                                <select class="px-3 py-2 border border-gray-300 rounded-lg">
                                    <option>Claro</option>
                                    <option>Escuro</option>
                                    <option>Automático</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registro de Sugestões -->
                <div id="registro" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Registro de Sugestões</h3>
                        <div class="space-y-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Melhoria no Sistema</h4>
                                        <p class="text-gray-600 text-sm">Enviado em 15/01/2024</p>
                                        <p class="text-gray-700 mt-2">Sugestão para otimizar o processo de login...</p>
                                    </div>
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">Em análise</span>
                                </div>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Nova Funcionalidade</h4>
                                        <p class="text-gray-600 text-sm">Enviado em 12/01/2024</p>
                                        <p class="text-gray-700 mt-2">Implementar chat interno para comunicação...</p>
                                    </div>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Aprovado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Criar Procedimentos -->
                <div id="criar" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Criar Procedimentos</h3>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Título do Procedimento</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Digite o título">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option>Administrativo</option>
                                    <option>Operacional</option>
                                    <option>Segurança</option>
                                    <option>Qualidade</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                                <textarea rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Descreva o procedimento detalhadamente"></textarea>
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Criar Procedimento</button>
                        </form>
                    </div>
                </div>

                <!-- Cadastrar Informação -->
                <div id="cadastrar" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Cadastrar Informação</h3>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Informação</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option>Comunicado</option>
                                    <option>Notícia</option>
                                    <option>Aviso</option>
                                    <option>Evento</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Digite o título">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo</label>
                                <textarea rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Digite o conteúdo da informação"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Publicação</label>
                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Cadastrar</button>
                        </form>
                    </div>
                </div>

                <!-- Sobre Nós -->
                <div id="sobre" class="content-section hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-2xl font-bold text-blue-900 mb-6">Sobre Nós</h3>
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Nossa Missão</h4>
                                <p class="text-gray-700">Fornecer soluções inovadoras e de qualidade, contribuindo para o crescimento sustentável de nossos clientes e colaboradores.</p>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Nossa Visão</h4>
                                <p class="text-gray-700">Ser reconhecida como líder em nosso segmento, destacando-nos pela excelência em atendimento e inovação tecnológica.</p>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-3">Nossos Valores</h4>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>Integridade e transparência</li>
                                    <li>Compromisso com a qualidade</li>
                                    <li>Respeito às pessoas</li>
                                    <li>Inovação constante</li>
                                    <li>Responsabilidade social</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        let currentSlide = 0;
        const totalSlides = 4;

        function showContent(section) {
            // Hide all content sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(s => s.classList.add('hidden'));
            
            // Show selected section
            document.getElementById(section).classList.remove('hidden');
            
            // Update sidebar active state
            const buttons = document.querySelectorAll('.sidebar-item');
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-800');
                btn.classList.add('hover:bg-blue-800');
            });
            event.target.classList.add('bg-blue-800');
            event.target.classList.remove('hover:bg-blue-800');
            
            // Update page title
            const titles = {
                'home': 'Página Inicial',
                'normas': 'Normas e Procedimentos',
                'informacoes': 'Informações',
                'matriz': 'Matriz de Comunicação',
                'sugestoes': 'Sugestões e Reclamações',
                'sistemas': 'Sistemas',
                'upload': 'Upload de Arquivos',
                'configuracoes': 'Configurações',
                'registro': 'Registro de Sugestões',
                'criar': 'Criar Procedimentos',
                'cadastrar': 'Cadastrar Informação',
                'sobre': 'Sobre Nós'
            };
            document.getElementById('pageTitle').textContent = titles[section];
        }

        function updateCarousel() {
            const carousel = document.getElementById('carousel');
            const dots = document.querySelectorAll('.carousel-dot');
            
            carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            dots.forEach((dot, index) => {
                if (index === currentSlide) {
                    dot.classList.remove('bg-opacity-50');
                    dot.classList.add('bg-opacity-100');
                } else {
                    dot.classList.remove('bg-opacity-100');
                    dot.classList.add('bg-opacity-50');
                }
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }

        function previousSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        }

        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            updateCarousel();
        }

        // Auto-advance carousel
        function startCarousel() {
            setInterval(nextSlide, 5000); // Change slide every 5 seconds
        }

        function changeProfile() {
            const profile = document.getElementById('profileSelector').value;
            const body = document.getElementById('mainBody');
            const sidebar = document.querySelector('.w-80');
            const pageTitle = document.getElementById('pageTitle');
            const userAvatar = document.getElementById('userAvatar');
            
            if (profile === 'mixkar') {
                // Apply Mixkar theme
                body.classList.add('mixkar-theme');
                
                // Change sidebar colors
                sidebar.className = sidebar.className.replace('bg-blue-900', 'bg-black');
                
                // Change page title color
                pageTitle.className = pageTitle.className.replace('text-blue-900', 'text-yellow-600');
                
                // Change user avatar
                userAvatar.className = userAvatar.className.replace('bg-blue-600', 'bg-yellow-600');
                
                // Update all blue elements to yellow/black theme
                updateThemeColors('mixkar');
                
            } else {
                // Apply default theme
                body.classList.remove('mixkar-theme');
                
                // Restore sidebar colors
                sidebar.className = sidebar.className.replace('bg-black', 'bg-blue-900');
                
                // Restore page title color
                pageTitle.className = pageTitle.className.replace('text-yellow-600', 'text-blue-900');
                
                // Restore user avatar
                userAvatar.className = userAvatar.className.replace('bg-yellow-600', 'bg-blue-600');
                
                // Restore all colors to default theme
                updateThemeColors('default');
            }
        }
        
        function updateThemeColors(theme) {
            const elements = document.querySelectorAll('*');
            
            elements.forEach(element => {
                if (theme === 'mixkar') {
                    // Convert blue colors to yellow/black
                    element.className = element.className
                        .replace(/bg-blue-(\d+)/g, (match, num) => {
                            if (parseInt(num) >= 700) return 'bg-black';
                            return `bg-yellow-${num}`;
                        })
                        .replace(/text-blue-(\d+)/g, (match, num) => {
                            if (parseInt(num) >= 700) return 'text-black';
                            return `text-yellow-${num}`;
                        })
                        .replace(/border-blue-(\d+)/g, (match, num) => {
                            if (parseInt(num) >= 700) return 'border-black';
                            return `border-yellow-${num}`;
                        })
                        .replace(/hover:bg-blue-(\d+)/g, (match, num) => {
                            if (parseInt(num) >= 700) return 'hover:bg-gray-800';
                            return `hover:bg-yellow-${num}`;
                        })
                        .replace(/focus:ring-blue-(\d+)/g, 'focus:ring-yellow-500');
                } else {
                    // Convert back to blue colors
                    element.className = element.className
                        .replace(/bg-yellow-(\d+)/g, 'bg-blue-$1')
                        .replace(/bg-black/g, 'bg-blue-900')
                        .replace(/text-yellow-(\d+)/g, 'text-blue-$1')
                        .replace(/text-black/g, 'text-blue-900')
                        .replace(/border-yellow-(\d+)/g, 'border-blue-$1')
                        .replace(/border-black/g, 'border-blue-900')
                        .replace(/hover:bg-yellow-(\d+)/g, 'hover:bg-blue-$1')
                        .replace(/hover:bg-gray-800/g, 'hover:bg-blue-700')
                        .replace(/focus:ring-yellow-(\d+)/g, 'focus:ring-blue-500');
                }
            });
            
            // Update active sidebar item
            const activeButton = document.querySelector('.sidebar-item.bg-blue-800, .sidebar-item.bg-yellow-800, .sidebar-item.bg-black');
            if (activeButton) {
                if (theme === 'mixkar') {
                    activeButton.classList.remove('bg-blue-800');
                    activeButton.classList.add('bg-yellow-600', 'active');
                } else {
                    activeButton.classList.remove('bg-yellow-600', 'active');
                    activeButton.classList.add('bg-blue-800');
                }
            }
        }

        // Initialize with home page and start carousel
        document.addEventListener('DOMContentLoaded', function() {
            showContent('home');
            updateCarousel();
            startCarousel();
        });
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'971a16ddd6c6f18d',t:'MTc1NTYxMTEwNS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
