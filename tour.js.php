<?php
session_start();
header('Content-Type: application/javascript');
?>
document.addEventListener('DOMContentLoaded', function () {
    const tour = new Shepherd.Tour({
        modal: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: { enabled: true },
            canClickTarget: false
        }
    });

    const samImageHtml = '<div style="text-align: center; margin-bottom: 10px;"><img src="img/SAM-ACENANDO.png" alt="SAM - Assistente Virtual" style="width: 80px; height: auto; border: none;"></div>';
    const samFinalImageHtml = '<div style="text-align: center; margin-bottom: 10px;"><img src="img/SAM-FIM.png" alt="SAM - Assistente Virtual" style="width: 80px; height: auto; border: none;"></div>';

    const navigateTo = (tourStep, url) => {
        const section = new URLSearchParams(url.split('?')[1]).get('section');
        localStorage.setItem('tourStep', tourStep.id);
        if (window.showSection && typeof window.showSection === 'function') {
            const currentSection = document.querySelector('main > section:not(.hidden)');
            if (!currentSection || currentSection.id !== section) {
                window.showSection(section, true);
            }
        } else {
            if (!window.location.href.includes(url)) {
                window.location.href = url;
            }
        }
    };

    fetch('get_current_user_permissions.php')
        .then(response => response.json())
        .then(permissions => {
            if (permissions.error) {
                console.error('Erro ao buscar permissões:', permissions.error);
                return;
            }

            const allowedSections = permissions.sections || [];
            const isAdmin = permissions.role === 'admin' || permissions.role === 'god';

            // Passo de Boas-vindas
            tour.addStep({
                id: 'welcome',
                title: 'Bem-vindo à Intranet!',
                text: samImageHtml + 'Vamos fazer um tour guiado pelas principais funcionalidades do sistema. Clique em "Próximo" para começar ou em "Sair" para explorar por conta própria.',
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

            if (isAdmin || allowedSections.includes('dashboard')) {
                tour.addStep({
                    id: 'dashboard',
                    title: 'Página Inicial',
                    text: samImageHtml + 'Bem-vindo! Esta é a página inicial da intranet.',
                    attachTo: { element: 'a[data-section="dashboard"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=dashboard');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('documents')) {
                tour.addStep({
                    id: 'documents',
                    title: 'Normas e Procedimentos',
                    text: samImageHtml + 'Aqui você encontra todos os documentos, normas e procedimentos da empresa.',
                    attachTo: { element: 'a[data-section="documents"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=documents');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (isAdmin || allowedSections.includes('information')) {
                tour.addStep({
                    id: 'information',
                    title: 'Informações',
                    text: samImageHtml + 'Veja comunicados e informações importantes.',
                    attachTo: { element: 'a[data-section="information"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=information');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('matriz_comunicacao')) {
                tour.addStep({
                    id: 'matriz_comunicacao',
                    title: 'Matriz de Comunicação',
                    text: samImageHtml + 'Consulte a matriz de comunicação entre setores.',
                    attachTo: { element: 'a[data-section="matriz_comunicacao"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=matriz_comunicacao');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('create_procedure')) {
                tour.addStep({
                    id: 'create_procedure',
                    title: 'Criar Procedimento',
                    text: samImageHtml + 'Crie novos procedimentos e envie documentos em PDF.',
                    attachTo: { element: 'a[data-section="create_procedure"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=create_procedure');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('sugestoes')) {
                tour.addStep({
                    id: 'sugestoes',
                    title: 'Sugestões e Reclamações',
                    text: samImageHtml + 'Envie sugestões ou reclamações para a administração.',
                    attachTo: { element: 'a[data-section="sugestoes"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=sugestoes');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('sistema')) {
                tour.addStep({
                    id: 'sistema',
                    title: 'Sistemas',
                    text: samImageHtml + 'Acesse sistemas integrados da empresa.',
                    attachTo: { element: 'a[data-section="sistema"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=sistema');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin) {
                tour.addStep({
                    id: 'settings',
                    title: 'Configurações',
                    text: samImageHtml + 'Configure preferências e permissões do sistema.',
                    attachTo: { element: 'a[data-section="settings"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=settings');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin) {
                tour.addStep({
                    id: 'registros_sugestoes',
                    title: 'Registros de Sugestões',
                    text: samImageHtml + 'Veja todas as sugestões enviadas pelos colaboradores.',
                    attachTo: { element: 'a[data-section="registros_sugestoes"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=registros_sugestoes');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('info-upload')) {
                tour.addStep({
                    id: 'info_upload',
                    title: 'Cadastrar Informação',
                    text: samImageHtml + 'Cadastre novos comunicados e informações.',
                    attachTo: { element: 'a[data-section="info-upload"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=info-upload');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('about')) {
                tour.addStep({
                    id: 'about',
                    title: 'Sobre Nós',
                    text: samImageHtml + 'Conheça mais sobre a empresa e a equipe.',
                    attachTo: { element: 'a[data-section="about"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=about');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('faq')) {
                tour.addStep({
                    id: 'faq',
                    title: 'FAQ',
                    text: samImageHtml + 'Perguntas frequentes para tirar suas dúvidas.',
                    attachTo: { element: 'a[data-section="faq"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=faq');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin && allowedSections.includes('manage_faq_section')) {
                tour.addStep({
                    id: 'manage_faq_section',
                    title: 'Gerenciar FAQ',
                    text: samImageHtml + 'Adicione, edite ou remova perguntas frequentes.',
                    attachTo: { element: 'a[data-section="manage_faq_section"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=manage_faq_section');
                        }
                    },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }

            if (isAdmin || allowedSections.includes('profile')) {
                tour.addStep({
                    id: 'profile',
                    title: 'Perfil do Usuário',
                    text: samImageHtml + 'Veja e edite seus dados pessoais e senha.',
                    attachTo: { element: 'a[data-section="profile"]', on: 'right' },
                    when: {
                        show: function() {
                            navigateTo(this, 'index.php?section=profile');
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
                text: samFinalImageHtml + 'Você agora conhece as principais áreas da intranet. Explore à vontade e, se tiver dúvidas, pode refazer este tour a qualquer momento!',
                buttons: [
                    { text: 'Voltar', action: tour.back },
                    {
                        text: 'Finalizar',
                        action: tour.complete
                    }
                ]
            });

            tour.on('complete', () => {
                fetch('mark_tour_completed.php', { method: 'POST' });
                localStorage.removeItem('tourStep');
            });

            tour.on('cancel', () => {
                localStorage.removeItem('tourStep');
            });

            window.intranetTour = tour;

            const savedStepId = localStorage.getItem('tourStep');
            if (savedStepId) {
                setTimeout(() => {
                    const stepExists = tour.steps.some(step => step.id === savedStepId);
                    if (stepExists) {
                        tour.start();
                        tour.show(savedStepId);
                    } else {
                        localStorage.removeItem('tourStep');
                    }
                }, 500);
            }

            const startTourButton = document.getElementById('startTourButton');
            if (startTourButton) {
                startTourButton.addEventListener('click', function () {
                    localStorage.removeItem('tourStep');
                    fetch('reset_tour_status.php', { method: 'POST' })
                        .then(() => tour.start())
                        .catch(() => tour.start());
                });
            }

            const shouldShowTour = <?php echo (isset($_SESSION['show_tour']) && $_SESSION['show_tour']) ? 'true' : 'false'; ?>;
            if (shouldShowTour && !savedStepId) {
                tour.start();
            }
        })
        .catch(error => {
            console.error('Erro ao inicializar o tour:', error);
        });
});
