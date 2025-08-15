<?php
session_start(); // Necessário para acessar $_SESSION
header('Content-Type: application/javascript');
?>
document.addEventListener('DOMContentLoaded', function () {
    const tour = new Shepherd.Tour({
        modal: true,
        defaultStepOptions: {
            cancelIcon: {
                enabled: true
            },
            classes: 'shepherd-custom',
            scrollTo: { behavior: 'smooth', block: 'center' }
        }
    });

    // Verifica se o usuário tem perfil de admin ou god
    const isAdmin = <?php echo (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god'])) ? 'true' : 'false'; ?>;

    // Passo 1: Boas-vindas
    tour.addStep({
        id: 'welcome',
        title: 'Bem-vindo à Intranet!',
        text: 'Vamos fazer um tour guiado pelas principais funcionalidades do sistema. Clique em "Próximo" para começar ou em "Sair" para explorar por conta própria.',
        buttons: [
            {
                action() { return this.cancel(); },
                classes: 'shepherd-button-secondary',
                text: 'Sair'
            },
            {
                action() { return this.next(); },
                text: 'Próximo'
            }
        ]
    });

    // Passo 2: Menu Lateral
    tour.addStep({
        id: 'sidebar',
        title: 'Navegação Principal',
        text: 'Aqui no menu lateral, você encontra acesso rápido a todas as seções da intranet. Use-o para navegar entre as telas.',
        attachTo: {
            element: '#sidebar',
            on: 'right'
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 3: Ferramentas do Cabeçalho
    tour.addStep({
        id: 'header-tools',
        title: 'Ferramentas Rápidas',
        text: 'No topo, você pode buscar por conteúdo, acessar o FAQ, ver suas notificações e gerenciar seu perfil de usuário.',
        attachTo: {
            element: '#profileDropdownBtn', // Anexa ao botão de perfil que é mais central
            on: 'bottom'
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 4: Dashboard
    tour.addStep({
        id: 'dashboard-content',
        title: 'Sua Página Inicial',
        text: 'Este é o seu Dashboard. Aqui você verá os últimos comunicados da empresa e outras informações importantes.',
        attachTo: {
            element: '#dashboard',
            on: 'bottom'
        },
        when: {
            show: () => {
                showSection('dashboard'); // Garante que a seção correta está visível
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 5: Apresentando a seção de Normas e Procedimentos
    tour.addStep({
        id: 'documents-link',
        title: 'Normas e Procedimentos',
        text: 'Agora, vamos conhecer a área onde ficam todos os documentos, normas e procedimentos da empresa.',
        attachTo: {
            element: 'a[data-section="documents"]',
            on: 'right'
        },
        when: {
            show: () => {
                showSection('dashboard'); // Volta para o dashboard para mostrar o link
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 6: Dentro da seção de Normas e Procedimentos
    tour.addStep({
        id: 'documents-inside',
        title: 'Consultando Documentos',
        text: 'Nesta tela, você pode usar os filtros para encontrar um procedimento específico. Clique em um card para visualizar o documento.',
        attachTo: {
            element: '#documents-grid',
            on: 'top'
        },
        when: {
            show: () => {
                showSection('documents'); // Navega para a seção de documentos
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 7: Apresentando a Matriz de Comunicação
    tour.addStep({
        id: 'matriz-link',
        title: 'Matriz de Comunicação',
        text: 'Precisa encontrar o contato de alguém? A Matriz de Comunicação centraliza os ramais e e-mails de todos.',
        attachTo: {
            element: 'a[data-section="matriz_comunicacao"]',
            on: 'right'
        },
        when: {
            show: () => {
                showSection('documents'); // Mantém na tela anterior para mostrar o link
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 8: Dentro da Matriz de Comunicação
    tour.addStep({
        id: 'matriz-inside',
        title: 'Encontrando Contatos',
        text: 'Use os filtros por setor para encontrar rapidamente quem você procura. Se for admin, você pode editar os dados diretamente na tabela.',
        attachTo: {
            element: '#matriz-filter-form',
            on: 'bottom'
        },
        when: {
            show: () => {
                showSection('matriz_comunicacao'); // Navega para a seção da matriz
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 9: Apresentando as Sugestões
    tour.addStep({
        id: 'sugestoes-link',
        title: 'Sugestões e Reclamações',
        text: 'Sua opinião é muito importante! Use esta seção para enviar sugestões de melhoria ou reclamações.',
        attachTo: {
            element: 'a[data-section="sugestoes"]',
            on: 'right'
        },
        when: {
            show: () => {
                showSection('matriz_comunicacao');
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 10: Dentro das Sugestões
    tour.addStep({
        id: 'sugestoes-inside',
        title: 'Enviando seu Feedback',
        text: 'Preencha o formulário para enviar sua mensagem. O envio é registrado e encaminhado para a área responsável.',
        attachTo: {
            element: '#sugestaoForm',
            on: 'top'
        },
        when: {
            show: () => {
                showSection('sugestoes');
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passo 11: Seção de Sistemas
    tour.addStep({
        id: 'sistemas-link',
        title: 'Acesso a Sistemas',
        text: 'Aqui você encontra atalhos para outros sistemas importantes utilizados na empresa, como o GLPI.',
        attachTo: {
            element: 'a[data-section="sistema"]',
            on: 'right'
        },
        when: {
            show: () => {
                showSection('sugestoes');
            }
        },
        buttons: [
            { text: 'Voltar', action: tour.back },
            { text: 'Próximo', action: tour.next }
        ]
    });

    // Passos exclusivos para Administradores
    if (isAdmin) {
        tour.addStep({
            id: 'admin-settings-link',
            title: 'Área Administrativa',
            text: 'Como administrador, você tem acesso à área de Configurações para gerenciar usuários, permissões e outros aspectos do sistema.',
            attachTo: {
                element: 'a[data-section="settings"]',
                on: 'right'
            },
            when: {
                show: () => {
                    showSection('dashboard'); // Volta ao dashboard para mostrar o link de admin
                }
            },
            buttons: [
                { text: 'Voltar', action: tour.back },
                { text: 'Próximo', action: tour.next }
            ]
        });

        tour.addStep({
            id: 'admin-settings-inside',
            title: 'Gerenciando o Sistema',
            text: 'Nesta tela, você pode criar novos usuários, definir o que cada um pode ver e gerenciar os atalhos de sistemas.',
            attachTo: {
                element: '#settings-tab-users', // Foca na aba de usuários
                on: 'top'
            },
            when: {
                show: () => {
                    showSection('settings'); // Navega para as configurações
                }
            },
            buttons: [
                { text: 'Voltar', action: tour.back },
                { text: 'Próximo', action: tour.next }
            ]
        });
    }

    // Passo Final
    tour.addStep({
        id: 'finish',
        title: 'Tour Concluído!',
        text: 'Você agora conhece as principais áreas da intranet. Explore à vontade e, se tiver dúvidas, pode refazer este tour a qualquer momento!',
        buttons: [
            { text: 'Voltar', action: tour.back },
            {
                text: 'Finalizar',
                action: tour.complete
            }
        ]
    });
 
    // Adiciona um evento que é disparado quando o tour começa.
    // Isso garante que, seja iniciado manual ou automaticamente, o status do usuário será atualizado.
    tour.on('start', () => {
        fetch('mark_tour_completed.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Status do tour atualizado para o usuário.');
                }
            })
            .catch(error => console.error('Não foi possível atualizar o status do tour:', error));
    });
 
    // Expor a instância do tour globalmente para que o botão possa iniciá-la.
    window.intranetTour = tour;
 
    // Verifica se o tour deve iniciar automaticamente.
    const shouldShowTour = <?php echo (isset($_SESSION['show_tour']) && $_SESSION['show_tour']) ? 'true' : 'false'; ?>;
    if (shouldShowTour && window.intranetTour) {
        window.intranetTour.start();
    }
});