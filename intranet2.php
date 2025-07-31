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
            background-color: #f5f7fa;
        }
        .sidebar {
            transition: all 0.3s ease;
        }
        .document-card {
            transition: all 0.2s ease;
        }
        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar bg-indigo-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform md:relative md:translate-x-0 transition duration-200 ease-in-out z-20">
            <div class="flex items-center justify-between px-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-building text-2xl"></i>
                    <span class="text-xl font-semibold">Intranet</span>
                </div>
                <button id="closeSidebar" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-10">
                <div class="px-4 py-2 text-gray-300 uppercase text-xs font-semibold">Menu Principal</div>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-900 text-white flex items-center space-x-2">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 text-gray-100 flex items-center space-x-2" onclick="showSection('documents')">
                    <i class="fas fa-file-pdf w-6"></i>
                    <span>Documentos PDF</span>
                </a>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 text-gray-100 flex items-center space-x-2" onclick="showSection('spreadsheets')">
                    <i class="fas fa-file-excel w-6"></i>
                    <span>Planilhas</span>
                </a>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 text-gray-100 flex items-center space-x-2" onclick="showSection('information')">
                    <i class="fas fa-info-circle w-6"></i>
                    <span>Informações</span>
                </a>
                
                <div class="px-4 py-2 mt-8 text-gray-300 uppercase text-xs font-semibold">Administração</div>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 text-gray-100 flex items-center space-x-2" onclick="showSection('upload')">
                    <i class="fas fa-upload w-6"></i>
                    <span>Upload de Arquivos</span>
                </a>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700 text-gray-100 flex items-center space-x-2">
                    <i class="fas fa-cog w-6"></i>
                    <span>Configurações</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-3">
                        <button id="openSidebar" class="md:hidden focus:outline-none">
                            <i class="fas fa-bars text-gray-700"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-gray-800" id="pageTitle">Dashboard</h2>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Buscar..." class="search-input py-2 pl-10 pr-4 rounded-md border border-gray-300 focus:outline-none focus:border-indigo-500 w-64">
                            <i class="fas fa-search text-gray-400 absolute left-3 top-3"></i>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <button class="relative p-1 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none">
                                <i class="fas fa-bell text-lg"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                                    U
                                </div>
                                <span class="text-sm font-medium text-gray-700">Usuário</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4">
                <!-- Dashboard Section -->
                <section id="dashboard" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4">
                            <div class="rounded-full bg-blue-100 p-3">
                                <i class="fas fa-file-pdf text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Documentos PDF</h3>
                                <p class="text-2xl font-bold text-gray-900">24</p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4">
                            <div class="rounded-full bg-green-100 p-3">
                                <i class="fas fa-file-excel text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Planilhas</h3>
                                <p class="text-2xl font-bold text-gray-900">18</p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4">
                            <div class="rounded-full bg-purple-100 p-3">
                                <i class="fas fa-info-circle text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Informações</h3>
                                <p class="text-2xl font-bold text-gray-900">12</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Documentos Recentes</h3>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-900">Relatório Financeiro Q2</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PDF</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10/06/2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Visualizar</a>
                                                <a href="#" class="text-gray-600 hover:text-gray-900">Baixar</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-excel text-green-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-900">Orçamento 2023</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Excel</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05/06/2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Visualizar</a>
                                                <a href="#" class="text-gray-600 hover:text-gray-900">Baixar</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-900">Manual de Procedimentos</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PDF</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">01/06/2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Visualizar</a>
                                                <a href="#" class="text-gray-600 hover:text-gray-900">Baixar</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Documents Section -->
                <section id="documents" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Documentos PDF</h2>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" placeholder="Filtrar documentos..." class="search-input py-2 pl-10 pr-4 rounded-md border border-gray-300 focus:outline-none focus:border-indigo-500 w-64">
                                <i class="fas fa-search text-gray-400 absolute left-3 top-3"></i>
                            </div>
                            <select class="border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:border-indigo-500">
                                <option>Todos os departamentos</option>
                                <option>Financeiro</option>
                                <option>RH</option>
                                <option>Marketing</option>
                                <option>Operações</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Document Card -->
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-red-50 flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Relatório Anual 2022</h3>
                                <p class="text-gray-600 text-sm mt-1">Relatório completo de resultados financeiros e operacionais do ano de 2022.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 15/01/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-red-50 flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Manual de Procedimentos</h3>
                                <p class="text-gray-600 text-sm mt-1">Guia completo de procedimentos internos e políticas da empresa.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 05/03/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-red-50 flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Política de Segurança</h3>
                                <p class="text-gray-600 text-sm mt-1">Documento com as diretrizes de segurança da informação e procedimentos.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 20/04/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-red-50 flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Relatório Trimestral Q1</h3>
                                <p class="text-gray-600 text-sm mt-1">Resultados financeiros do primeiro trimestre de 2023.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 10/04/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Spreadsheets Section -->
                <section id="spreadsheets" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Planilhas</h2>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" placeholder="Filtrar planilhas..." class="search-input py-2 pl-10 pr-4 rounded-md border border-gray-300 focus:outline-none focus:border-indigo-500 w-64">
                                <i class="fas fa-search text-gray-400 absolute left-3 top-3"></i>
                            </div>
                            <select class="border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:border-indigo-500">
                                <option>Todos os departamentos</option>
                                <option>Financeiro</option>
                                <option>RH</option>
                                <option>Marketing</option>
                                <option>Operações</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Spreadsheet Card -->
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-green-50 flex items-center justify-center">
                                <i class="fas fa-file-excel text-green-600 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Orçamento 2023</h3>
                                <p class="text-gray-600 text-sm mt-1">Planilha detalhada com o orçamento anual de 2023 por departamento.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 05/01/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-green-50 flex items-center justify-center">
                                <i class="fas fa-file-excel text-green-600 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Controle de Estoque</h3>
                                <p class="text-gray-600 text-sm mt-1">Planilha para controle e gestão de estoque com atualizações automáticas.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 15/05/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 bg-green-50 flex items-center justify-center">
                                <i class="fas fa-file-excel text-green-600 text-5xl"></i>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-lg text-gray-800">Indicadores de Desempenho</h3>
                                <p class="text-gray-600 text-sm mt-1">KPIs e métricas de desempenho por equipe e departamento.</p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-xs text-gray-500">Atualizado: 01/06/2023</span>
                                    <div class="flex space-x-2">
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-1 text-gray-500 hover:text-indigo-600" title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Information Section -->
                <section id="information" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Informações</h2>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" placeholder="Buscar informações..." class="search-input py-2 pl-10 pr-4 rounded-md border border-gray-300 focus:outline-none focus:border-indigo-500 w-64">
                                <i class="fas fa-search text-gray-400 absolute left-3 top-3"></i>
                            </div>
                            <select class="border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:border-indigo-500">
                                <option>Todas as categorias</option>
                                <option>Notícias</option>
                                <option>Comunicados</option>
                                <option>Procedimentos</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Comunicados Importantes</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <h4 class="font-semibold text-gray-800">Manutenção do Sistema</h4>
                                <p class="text-gray-600 mt-1">O sistema estará em manutenção no dia 15/06/2023 das 22h às 02h. Por favor, salve seus trabalhos antes deste período.</p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <span>Publicado em: 10/06/2023</span>
                                </div>
                            </div>
                            
                            <div class="border-l-4 border-green-500 pl-4 py-2">
                                <h4 class="font-semibold text-gray-800">Nova Política de Home Office</h4>
                                <p class="text-gray-600 mt-1">A partir de 01/07/2023, todos os colaboradores poderão trabalhar remotamente até 3 dias por semana. Consulte o documento completo para mais detalhes.</p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <span>Publicado em: 05/06/2023</span>
                                </div>
                            </div>
                            
                            <div class="border-l-4 border-yellow-500 pl-4 py-2">
                                <h4 class="font-semibold text-gray-800">Treinamento Obrigatório</h4>
                                <p class="text-gray-600 mt-1">Todos os colaboradores devem completar o treinamento de segurança da informação até 30/06/2023. O link para o treinamento foi enviado por e-mail.</p>
                                <div class="flex items-center mt-2 text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <span>Publicado em: 01/06/2023</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Informações Úteis</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <h4 class="font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-phone-alt text-indigo-500 mr-2"></i>
                                        Ramais Internos
                                    </h4>
                                    <p class="text-gray-600 mt-2">Lista completa de ramais internos por departamento e colaborador.</p>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center mt-3">
                                        Ver detalhes
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                                
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <h4 class="font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>
                                        Calendário Corporativo
                                    </h4>
                                    <p class="text-gray-600 mt-2">Calendário com feriados, eventos e datas importantes da empresa.</p>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center mt-3">
                                        Ver detalhes
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                                
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <h4 class="font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-building text-indigo-500 mr-2"></i>
                                        Estrutura Organizacional
                                    </h4>
                                    <p class="text-gray-600 mt-2">Organograma completo da empresa com departamentos e lideranças.</p>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center mt-3">
                                        Ver detalhes
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                                
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <h4 class="font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-question-circle text-indigo-500 mr-2"></i>
                                        Perguntas Frequentes
                                    </h4>
                                    <p class="text-gray-600 mt-2">Respostas para as dúvidas mais comuns sobre procedimentos e sistemas.</p>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center mt-3">
                                        Ver detalhes
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Upload Section -->
                <section id="upload" class="hidden space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Upload de Arquivos</h2>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Enviar Novo Arquivo</h3>
                        </div>
                        <div class="p-6">
                            <form id="uploadForm" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Título do Arquivo</label>
                                        <input type="text" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ex: Relatório Financeiro Q2">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                                        <select class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option>PDF</option>
                                            <option>Planilha Excel</option>
                                            <option>Documento Word</option>
                                            <option>Apresentação PowerPoint</option>
                                            <option>Outro</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                        <select class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option>Financeiro</option>
                                            <option>RH</option>
                                            <option>Marketing</option>
                                            <option>Operações</option>
                                            <option>TI</option>
                                            <option>Administrativo</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Acesso</label>
                                        <select class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option>Público (Todos os colaboradores)</option>
                                            <option>Restrito (Apenas departamento)</option>
                                            <option>Confidencial (Apenas gestores)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                    <textarea class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3" placeholder="Breve descrição do conteúdo do arquivo..."></textarea>
                                </div>
                                
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center" id="dropzone">
                                    <input type="file" id="fileInput" class="hidden">
                                    <div class="space-y-2">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                        <p class="text-gray-700">Arraste e solte arquivos aqui ou</p>
                                        <button type="button" id="browseButton" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            Selecionar Arquivo
                                        </button>
                                        <p class="text-sm text-gray-500">Tamanho máximo: 50MB</p>
                                    </div>
                                    
                                    <div id="filePreview" class="hidden mt-4">
                                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-md">
                                            <div class="flex items-center">
                                                <i class="fas fa-file text-indigo-500 mr-3"></i>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700" id="fileName">arquivo.pdf</p>
                                                    <p class="text-xs text-gray-500" id="fileSize">2.5 MB</p>
                                                </div>
                                            </div>
                                            <button type="button" id="removeFile" class="text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                        Cancelar
                                    </button>
                                    <button type="button" id="submitUpload" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        Enviar Arquivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Uploads Recentes</h3>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-900">Relatório de Vendas Maio</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PDF</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Comercial</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05/06/2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Publicado
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-excel text-green-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-900">Planilha de Férias 2023</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Excel</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">RH</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">01/06/2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Publicado
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">Sucesso!</h3>
                <p class="text-gray-600 mb-6">Seu arquivo foi enviado com sucesso e está disponível no sistema.</p>
                <button id="closeModal" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Container para exibir a planilha -->
    <div id="excel-viewer" class="w-full h-[700px] mb-6 hidden">
        <iframe id="excel-iframe" class="w-full h-full rounded-lg border" frameborder="0"></iframe>
        <div class="flex justify-end mt-2">
            <button id="close-excel-viewer" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Fechar</button>
        </div>
    </div>
    
    <!-- Container para exibir a tabela do Excel -->
    <div id="excel-table-container" class="w-full mb-6 hidden bg-white rounded-lg shadow p-4 overflow-auto"></div>
    
    <script>
        // Toggle sidebar on mobile
        document.getElementById('openSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });
        
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });
        
        // Section navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('main > section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show the selected section
            document.getElementById(sectionId).classList.remove('hidden');
            
            // Update page title
            const titles = {
                'dashboard': 'Dashboard',
                'documents': 'Documentos PDF',
                'spreadsheets': 'Planilhas',
                'information': 'Informações',
                'upload': 'Upload de Arquivos'
            };
            
            document.getElementById('pageTitle').textContent = titles[sectionId];
        }
        
        // File upload functionality
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
                        // Esconde o viewer de iframe se estiver aberto
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
        
        // Drag and drop functionality
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
            dropzone.classList.add('border-indigo-500');
            dropzone.classList.add('bg-indigo-50');
        }
        
        function unhighlight() {
            dropzone.classList.remove('border-indigo-500');
            dropzone.classList.remove('bg-indigo-50');
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
        
        // Submit upload
        document.getElementById('submitUpload').addEventListener('click', function() {
            // Show success modal
            document.getElementById('successModal').classList.remove('hidden');
        });
        
        // Close modal
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('successModal').classList.add('hidden');
        });
        
        // Visualização de planilhas Excel no espaço branco
        document.querySelectorAll('.view-excel').forEach(btn => {
            btn.addEventListener('click', function() {
                const fileUrl = this.getAttribute('data-file');
                // Monta a URL do Office Online Viewer
                const viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + fileUrl);
                document.getElementById('excel-iframe').src = viewerUrl;
                document.getElementById('excel-viewer').classList.remove('hidden');
                // Esconde os cards de planilhas
                document.querySelector('#spreadsheets .grid').classList.add('hidden');
            });
        });
        document.getElementById('close-excel-viewer').addEventListener('click', function() {
            document.getElementById('excel-viewer').classList.add('hidden');
            document.getElementById('excel-iframe').src = '';
            document.querySelector('#spreadsheets .grid').classList.remove('hidden');
        });
    </script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</html>
