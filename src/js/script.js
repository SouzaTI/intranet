document.addEventListener('DOMContentLoaded', function() {
    const defaultSection = 'dashboard';

    // Variáveis da FAQ movidas para o topo para evitar 'ReferenceError'
    const chatArea = document.getElementById('faq-chat-area');
    const suggestionsArea = document.getElementById('faq-suggestions-area');
    const resetArea = document.getElementById('faq-reset-area');
    const resetButton = document.getElementById('faq-reset-btn');

    function showSection(sectionId, updateUrl = false) {
        // Esconde todas as seções
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('hidden');
        });

        const sectionElement = document.getElementById(sectionId);
        if (sectionElement) {
            sectionElement.classList.remove('hidden');
        }

        // Atualiza o título
        const titles = {
            'dashboard': 'Página Inicial',
            'documents': 'Normas e Procedimentos',
            'spreadsheets': 'Planilhas',
            'information': 'Informações',
            'matriz_comunicacao': 'Matriz de Comunicação',
            'sugestoes': 'Sugestões e Reclamações',
            'faq': 'FAQ',
            'profile': 'Meu Perfil',
            'create_procedure': 'Criar Procedimento',
            'info-upload': 'Cadastrar Informação',
            'sistema': 'Sistemas',
            'about': 'Sobre Nós',
            'registros_sugestoes': 'Registros de Sugestões',
            'settings': 'Configurações',
        };
        document.getElementById('pageTitle').textContent = titles[sectionId] || 'Página Inicial';

        // Remove destaque de todos os links
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active-sidebar-link');
        });
        // Adiciona destaque ao link ativo
        const activeLink = document.querySelector(`.sidebar-link[data-section="${sectionId}"]`);
        if (activeLink) {
            activeLink.classList.add('active-sidebar-link');
        }

        // Atualiza a URL para refletir a seção atual
        if (updateUrl && window.history.pushState) {
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?section=' + sectionId;
            window.history.pushState({path: newUrl}, '', newUrl);
        }

        // Carrega dinamicamente a lista de sugestões para admins
        if (sectionId === 'registros_sugestoes') {
            const container = document.getElementById('registros-container');
            if(container){
                container.innerHTML = '<p class="text-center text-[#4A90E2]">Carregando registros...</p>';
                fetch('registros_sugestoes.php')
                    .then(response => response.text())
                    .then(html => container.innerHTML = html)
                    .catch(() => container.innerHTML = '<p class="text-center text-red-500">Erro ao carregar os registros.</p>');
            }
        }

        // Inicializa o chat da FAQ quando a seção é mostrada
        if (sectionId === 'faq') {
            if(typeof setupFaqChat === 'function'){
                setupFaqChat();
            }
        }
    }
    window.showSection = showSection;

    // Exibe a seção correta ao carregar a página
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section && document.getElementById(section)) {
        showSection(section, false);
    } else {
        showSection(defaultSection, false);
    }

    // Exibe a seção correta ao clicar nos links da sidebar
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-section');
            showSection(sectionId, true);
        });
    });

    // Exibe a seção correta ao usar o histórico do navegador
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        if (section && document.getElementById(section)) {
            showSection(section, false);
        } else {
            showSection(defaultSection, false);
        }
    });

    // --- Rest of the script ---
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
    if(document.getElementById('prevCarrosselImg')) {
        document.getElementById('prevCarrosselImg').onclick = function() {
            imgCurrent = (imgCurrent - 1 + imgItems.length) % imgItems.length;
            showCarrosselImg(imgCurrent, -1);
        };
    }
    if(document.getElementById('nextCarrosselImg')) {
        document.getElementById('nextCarrosselImg').onclick = function() {
            imgCurrent = (imgCurrent + 1) % imgItems.length;
            showCarrosselImg(imgCurrent, 1);
        };
    }
    // Passa automaticamente a cada 4 segundos com animação suave
    if(imgItems.length > 0) {
        setInterval(function() {
            imgCurrent = (imgCurrent + 1) % imgItems.length;
            showCarrosselImg(imgCurrent, 1);
        }, 4000);
        // Inicializa
        showCarrosselImg(imgCurrent, 1);
    }

    if(document.getElementById('openSidebar')){
        document.getElementById('openSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });
    }

    if(document.getElementById('closeSidebar')){
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });
    }

    if(document.getElementById('closeModal')){
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('successModal').classList.add('hidden');
        });
    }

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

    if(document.getElementById('close-excel-viewer')){
        document.getElementById('close-excel-viewer').addEventListener('click', function() {
            document.getElementById('excel-viewer').classList.add('hidden');
            document.getElementById('excel-iframe').src = '';
            document.getElementById('excel-table-container').classList.remove('hidden');
        });
    }

    // --- Lógica de Notificações ---
    const notificationsBell = document.getElementById('notificationsBell');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');
    const notificationBadge = document.getElementById('notification-count-badge');
    const markAllAsReadBtn = document.getElementById('mark-all-as-read');

    async function fetchNotifications() {
        try {
            const response = await fetch('get_notificacoes.php');
            const data = await response.json();
            if (data.success) {
                renderNotifications(data.notifications);
            } else {
                console.error('Erro ao buscar notificações:', data.error);
                if(notificationsList) {
                    notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Erro ao carregar.</div>';
                }
            }
        } catch (error) {
            console.error('Erro de rede ao buscar notificações:', error);
            if(notificationsList) {
                notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Erro de conexão.</div>';
            }
        }
    }

    function renderNotifications(notifications) {
        if(!notificationsList) return;
        notificationsList.innerHTML = ''; // Limpa a lista atual
        let unreadCount = 0;

        if (notifications.length === 0) {
            notificationsList.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Nenhuma notificação nova.</div>';
            notificationBadge.classList.add('hidden');
            notificationBadge.textContent = '';
            return;
        }

        notifications.forEach(notif => {
            if (notif.lida == 0) {
                unreadCount++;
            }

            const item = document.createElement('a');
            item.href = '#';
            item.classList.add('notification-item', 'block', 'px-4', 'py-3', 'hover:bg-gray-100', 'transition', 'duration-150', 'ease-in-out');
            item.dataset.id = notif.id;
            item.dataset.link = notif.link || '#';

            if (notif.lida == 0) {
                item.classList.add('unread');
            }

            const date = new Date(notif.data_criacao);
            const formattedDate = `${date.toLocaleDateString('pt-BR')} às ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`;

            item.innerHTML = `
                <div class="flex items-start space-x-3 pointer-events-none">
                    <div class="flex-shrink-0 pt-1">
                        <div class="w-3 h-3 rounded-full ${notif.lida == 0 ? 'bg-blue-500' : 'bg-gray-300'}"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-800">${notif.mensagem}</p>
                        <p class="text-xs text-gray-500 mt-1">${formattedDate}</p>
                    </div>
                </div>
            `;
            notificationsList.appendChild(item);
        });

        if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.classList.remove('hidden');
        } else {
            notificationBadge.classList.add('hidden');
        }
    }

    async function markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('id', notificationId);

        try {
            const response = await fetch('marcar_notificacao_lida.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                fetchNotifications();
            } else {
                console.error('Falha ao marcar como lida:', data.error);
            }
        } catch (error) {
            console.error('Erro de rede ao marcar como lida:', error);
        }
    }

    if (notificationsBell) {
        notificationsBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('hidden');
            if (!notificationsDropdown.classList.contains('hidden')) {
                fetchNotifications();
            }
        });
    }

    if (markAllAsReadBtn) {
        markAllAsReadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            markAsRead('all');
        });
    }

    if (notificationsList) {
        notificationsList.addEventListener('click', (e) => {
            e.preventDefault();
            const targetItem = e.target.closest('.notification-item');
            if (targetItem) {
                const notificationId = targetItem.dataset.id;
                const link = targetItem.dataset.link;
                
                markAsRead(notificationId).then(() => {
                    if (link && link !== '#') {
                        window.location.href = link;
                    }
                });
            }
        });
    }
    
    if(notificationsBell){
        fetchNotifications();
        setInterval(fetchNotifications, 60000);
    }

    document.addEventListener('click', (e) => {
        if (notificationsDropdown && !notificationsBell.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.add('hidden');
        }
    });

    document.querySelectorAll('.faq-accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('i.fa-chevron-down');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        });
    });

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

    if(document.getElementById('sugestaoForm')){
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
    }

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
                    setTimeout(() => { feedbackSpan.textContent = ''; }, 2000);
                }
            });
        }
    });

    const infoTabBtns = document.querySelectorAll('#information .folder-tab');
    const infoTabContents = document.querySelectorAll('#information .info-tab-content');

    infoTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;

            infoTabBtns.forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            infoTabContents.forEach(content => {
                content.classList.toggle('hidden', content.id !== `info-tab-${tabName}`);
            });
        });
    });

    const settingsTabBtns = document.querySelectorAll('#settings .folder-tab');
    const settingsTabContents = document.querySelectorAll('#settings .settings-tab-content');

    settingsTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;

            settingsTabBtns.forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            settingsTabContents.forEach(content => {
                content.classList.toggle('hidden', content.id !== `settings-tab-${tab}`);
            });
        });
    });

    const permissionsModal = document.getElementById('permissionsModal');
    if(permissionsModal) {
        const modalContent = permissionsModal.querySelector('.transform');
        const openModalBtns = document.querySelectorAll('.open-permissions-modal');
        const closeModalBtn = document.getElementById('closePermissionsModal');
        const cancelBtn = document.getElementById('cancelPermissions');
        const modalUserId = document.getElementById('modalUserId');
        const modalUsername = document.getElementById('modalUsername');
        const modalUserRole = document.getElementById('modalUserRole');
        const modalUserSetor = document.getElementById('modalUserSetor');
        const modalUserEmpresa = document.getElementById('modalUserEmpresa');
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

                sectionCheckboxes.forEach(cb => cb.checked = false);
                modalUserSetor.value = '';
                modalUserEmpresa.value = 'Comercial Souza';
                modalUserRole.value = 'user';

                fetch(`get_user_permissions.php?user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) { alert(data.error); return; }
                        
                        modalUserRole.value = data.role;
                        modalUserEmpresa.value = data.empresa || 'Comercial Souza';
                        modalUserSetor.value = data.setor_id || '';
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
    }

    const createUserModal = document.getElementById('createUserModal');
    if (createUserModal) {
        const createUserModalContent = createUserModal.querySelector('.transform');
        const openCreateUserModalBtn = document.getElementById('openCreateUserModalBtn');
        const closeCreateUserModalBtn = document.getElementById('closeCreateUserModal');
        const cancelCreateUserBtn = document.getElementById('cancelCreateUser');

        if (openCreateUserModalBtn) {
            openCreateUserModalBtn.addEventListener('click', () => {
                createUserModal.classList.remove('hidden');
                setTimeout(() => {
                    createUserModalContent.classList.remove('scale-95', 'opacity-0');
                    createUserModalContent.classList.add('scale-100', 'opacity-100');
                }, 10);
            });
        }

        function closeCreateUserModal() {
            createUserModalContent.classList.remove('scale-100', 'opacity-100');
            createUserModalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                createUserModal.classList.add('hidden');
            }, 200);
        }

        if (closeCreateUserModalBtn) closeCreateUserModalBtn.addEventListener('click', closeCreateUserModal);
        if (cancelCreateUserBtn) cancelCreateUserBtn.addEventListener('click', closeCreateUserModal);
    }

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

    const btnAdicionarTab = document.getElementById('btn-adicionar-funcionario-tab');
    const formAdicionarTab = document.getElementById('form-adicionar-funcionario-tab');
    const btnCancelarAdicaoTab = document.getElementById('btn-cancelar-adicao-tab');

    if (btnAdicionarTab && formAdicionarTab && btnCancelarAdicaoTab) {
        btnAdicionarTab.addEventListener('click', () => {
            formAdicionarTab.classList.remove('hidden');
        });

        btnCancelarAdicaoTab.addEventListener('click', () => {
            formAdicionarTab.classList.add('hidden');
        });
    }

    const matrizSection = document.getElementById('matriz_comunicacao');

    if(matrizSection) {
        matrizSection.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('edit-trigger')) {
                const wrapper = e.target.closest('.cell-content-wrapper');
                const contentSpan = wrapper.querySelector('.cell-content');

                contentSpan.setAttribute('contenteditable', 'true');
                contentSpan.focus();

                const range = document.createRange();
                range.selectNodeContents(contentSpan);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        });

        matrizSection.addEventListener('blur', function(e) {
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
        }, true);
    }

    const informationSection = document.getElementById('information');

    if (informationSection) {
        informationSection.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('edit-trigger')) {
                const wrapper = e.target.closest('.cell-content-wrapper');
                const contentSpan = wrapper.querySelector('.cell-content');

                contentSpan.setAttribute('contenteditable', 'true');
                contentSpan.focus();

                const range = document.createRange();
                range.selectNodeContents(contentSpan);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        });

        informationSection.addEventListener('blur', function(e) {
            if (e.target && e.target.classList.contains('cell-content') && e.target.isContentEditable) {
                const contentSpan = e.target;
                const td = contentSpan.closest('td');
                const tr = contentSpan.closest('tr');

                const id = tr.dataset.id;
                const column = td.dataset.column;
                const value = contentSpan.textContent.trim();

                contentSpan.setAttribute('contenteditable', 'false');
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
                    .catch(() => {
                        td.classList.remove('cell-saving');
                        td.classList.add('cell-error');
                        alert('Erro de conexão.');
                        setTimeout(() => td.classList.remove('cell-error'), 2000);
                    });
            }
        }, true);
    }

    // --- Lógica Unificada para Matriz de Comunicação (Filtro e Paginação com AJAX) ---
    const matrizSectionEl = document.getElementById('matriz_comunicacao');

    function fetchMatrizContent(url) {
        const tbody = document.getElementById('matriz-comunicacao-tbody-main');
        const paginationContainer = document.getElementById('matriz-comunicacao-pagination-main');

        // Mostra feedback de carregamento
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="4" class="py-4 px-4 text-center text-gray-500">Carregando...</td></tr>';
        }
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }

        // Constrói a URL para o endpoint AJAX
        const ajaxUrl = new URL('/intranet/filtrar_matriz_ajax.php', window.location.origin);
        ajaxUrl.search = new URL(url).search; // Usa os parâmetros da URL de destino

        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (tbody) {
                    tbody.innerHTML = data.table_html;
                }
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination_html;
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar a Matriz de Comunicação:', error);
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="4" class="py-4 px-4 text-center text-red-500">Erro ao carregar os dados. Tente novamente.</td></tr>';
                }
            });
    }

    if (matrizSectionEl) {
        matrizSectionEl.addEventListener('click', function(e) {
            const filterBtn = e.target.closest('.matriz-filter-btn');
            const paginationLink = e.target.closest('#matriz-comunicacao-pagination-main a');

            if (filterBtn) {
                e.preventDefault();
                const setor = filterBtn.dataset.setor;

                // Atualiza a URL no navegador
                const url = new URL(window.location);
                url.searchParams.set('section', 'matriz_comunicacao');
                url.searchParams.delete('pagina'); // Volta para a primeira página ao aplicar filtro

                if (setor) {
                    url.searchParams.set('setor', setor);
                } else {
                    url.searchParams.delete('setor');
                }
                window.history.pushState({}, '', url);

                // Atualiza o estado visual dos botões
                matrizSectionEl.querySelectorAll('.matriz-filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                    btn.classList.add('inactive');
                });
                filterBtn.classList.add('active');
                filterBtn.classList.remove('inactive');

                // Busca o conteúdo
                fetchMatrizContent(url.toString());

            } else if (paginationLink) {
                e.preventDefault();
                const destinationUrl = paginationLink.href;
                
                // Atualiza a URL no navegador
                window.history.pushState({}, '', destinationUrl);

                // Busca o conteúdo
                fetchMatrizContent(destinationUrl);
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target && (e.target.id === 'btn-copiar-emails' || e.target.closest('#btn-copiar-emails'))) {
            const button = e.target.id === 'btn-copiar-emails' ? e.target : e.target.closest('#btn-copiar-emails');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copiando...';
            button.disabled = true;

            const urlParams = new URLSearchParams(window.location.search);
            const setor = urlParams.get('setor');

            let fetchUrl = 'get_all_emails.php';
            if (setor) {
                fetchUrl += `?setor=${encodeURIComponent(setor)}`;
            }

            fetch(fetchUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.error) { throw new Error(data.error); }
                    if (data.emails && data.emails.length > 0) {
                        navigator.clipboard.writeText(data.emails).then(() => {
                            button.innerHTML = '<i class="fas fa-check"></i> E-mails Copiados!';
                        }, () => { throw new Error('Falha ao copiar.'); });
                    } else {
                        button.innerHTML = 'Nenhum e-mail encontrado.';
                    }
                })
                .catch(error => {
                    console.error('Erro ao copiar e-mails:', error);
                    button.innerHTML = '<i class="fas fa-times"></i> Erro ao Copiar';
                })
                .finally(() => setTimeout(() => { button.innerHTML = originalHtml; button.disabled = false; }, 2500));
        }
    });

    const departmentFilterDocs = document.getElementById('department-filter-docs');
    const searchInputDocs = document.getElementById('search-input-docs');

    function filterDocuments() {
        if (!departmentFilterDocs || !searchInputDocs) return; 
        
        const selectedDepartment = departmentFilterDocs.value;
        const searchTerm = searchInputDocs.value.toLowerCase();
        const documentCards = document.querySelectorAll('#documents-grid .document-card');

        documentCards.forEach(card => {
            const cardDepartment = card.dataset.department;
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('p').textContent.toLowerCase();

            const departmentMatch = (selectedDepartment === 'all' || cardDepartment === selectedDepartment);
            const textMatch = (title.includes(searchTerm) || description.includes(searchTerm));

            if (departmentMatch && textMatch) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
    if (departmentFilterDocs) departmentFilterDocs.addEventListener('change', filterDocuments);
    if (searchInputDocs) searchInputDocs.addEventListener('input', filterDocuments);

    function visualizarArquivo(url, tipo) {
        document.getElementById('excel-viewer').classList.remove('hidden');
        if (tipo.toLowerCase().includes('excel') || tipo.toLowerCase().includes('planilha') || url.endsWith('.xlsx') || url.endsWith('.xls')) {
            document.getElementById('excel-iframe').src = 'https://docs.google.com/gview?url=' + encodeURIComponent(window.location.origin + '/' + url) + '&embedded=true';
        } else if (tipo.toLowerCase().includes('pdf') || url.endsWith('.pdf')) {
            document.getElementById('excel-iframe').src = url;
        } else {
            document.getElementById('excel-iframe').src = url;
        }
    }

    const procedureForm = document.getElementById('createProcedureForm');
    if (procedureForm) {
        procedureForm.addEventListener('submit', function(e) {
            tinymce.triggerSave();

            const objetivoTextarea = procedureForm.querySelector('textarea[name="objetivo"]');
            
            if (!objetivoTextarea.value.trim()) {
                e.preventDefault();
                
                alert('O campo "Objetivo" é obrigatório.');
                
                const editorInstance = tinymce.get(objetivoTextarea.id);
                if (editorInstance) {
                    const editorContainer = editorInstance.getContainer();
                    editorContainer.style.border = '2px solid red';
                    editorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        editorContainer.style.border = '';
                    }, 3000);
                }
            }
        });
    }

    const linkTypeSelect = document.getElementById('link_type');
    const internalDestSelect = document.getElementById('link_destination_internal');
    const externalDestInput = document.getElementById('link_destination_external');

    if (linkTypeSelect) {
        linkTypeSelect.addEventListener('change', () => {
            if (linkTypeSelect.value === 'internal') {
                internalDestSelect.classList.remove('hidden');
                externalDestInput.classList.add('hidden');
            } else {
                internalDestSelect.classList.add('hidden');
                externalDestInput.classList.remove('hidden');
            }
        });
    }

    document.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'insert_link_btn') {
            const linkText = document.getElementById('link_text').value.trim();
            if (!linkText) {
                alert('Por favor, insira o texto que será exibido para o link.');
                return;
            }

            let linkPlaceholder = '';
            if (linkTypeSelect.value === 'internal') {
                const section = document.getElementById('link_internal_page').value;
                linkPlaceholder = `[[${linkText}|internal:${section}]]`;
            } else {
                const url = document.getElementById('link_external_url').value.trim();
                if (!url.startsWith('http://') && !url.startsWith('https://')) {
                    alert('Por favor, insira uma URL externa válida, começando com http:// ou https://.');
                    return;
                }
                linkPlaceholder = `[[${linkText}|external:${url}]]`;
            }

            const answerTextarea = document.getElementById('answer');
            if (answerTextarea) {
                const cursorPos = answerTextarea.selectionStart;
                const textBefore = answerTextarea.value.substring(0, cursorPos);
                const textAfter = answerTextarea.value.substring(cursorPos);
                answerTextarea.value = textBefore + linkPlaceholder + textAfter;
            }
        }
    });

    if(typeof tinymce !== 'undefined'){
        tinymce.init({
            selector: 'textarea.procedure-editor',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 300,
            menubar: false,
            readonly: false,
            language: 'pt_BR',
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

    async function fetchFaqList() {
        const faqListContainer = document.querySelector('#manage_faq_section .space-y-3.max-h-[500px]');
        if (!faqListContainer) return;

        faqListContainer.innerHTML = '<p class=\'text-center text-gray-500\'>Carregando FAQs...</p>';

        try {
            const response = await fetch('index.php?section=manage_faq_section&fetch_faqs=true', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            if (data.success) {
                if (data.faqs && data.faqs.length > 0) {
                    let faqHtml = '';
                    data.faqs.forEach(faq => {
                        faqHtml += `
                            <div class='border border-gray-200 rounded-lg shadow-sm overflow-hidden'>
                                <button class='faq-accordion-header w-full flex justify-between items-center p-4 bg-gray-100 hover:bg-gray-200 focus:outline-none transition duration-200 ease-in-out'>
                                    <span class='font-semibold text-[#4A90E2] text-left text-lg'>${faq.question}</span>
                                    <i class='fas fa-chevron-down text-gray-600 transform transition-transform duration-300 text-xl'></i>
                                </button>
                                <div class='faq-accordion-content hidden p-4 bg-white border-t border-gray-200'>
                                    <p class='text-gray-700 mb-4 leading-relaxed'>${faq.answer.replace(/\n/g, '<br>')}</p>
                                    <div class='flex space-x-3'>
                                        <a href='index.php?section=manage_faq_section&faq_action=edit&id=${faq.id}' class='inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 ease-in-out'>
                                            <i class='fas fa-edit mr-2'></i> Editar
                                        </a>
                                        <form action='index.php?section=manage_faq_section' method='POST' class='inline-block delete-faq-form' onsubmit='return confirm("Tem certeza que deseja excluir esta FAQ?");'>
                                            <input type='hidden' name='faq_action' value='delete'>
                                            <input type='hidden' name='id' value='${faq.id}'/>
                                            <button type='submit' class='inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200 ease-in-out'>
                                                <i class='fas fa-trash-alt mr-2'></i> Excluir
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    faqListContainer.innerHTML = faqHtml;
                    attachAccordionListeners();
                    attachDeleteFaqListeners();
                } else {
                    faqListContainer.innerHTML = '<p class=\'text-gray-600 p-4 bg-gray-50 rounded-md border border-gray-200\'>Nenhuma FAQ encontrada. Adicione uma nova FAQ acima.</p>';
                }
            } else {
                faqListContainer.innerHTML = '<p class=\'text-red-500 p-4 bg-red-50 rounded-md border border-red-200\'>Erro ao carregar FAQs: ' + data.message + '</p>';
            }
        } catch (error) {
            console.error('Erro ao buscar lista de FAQ:', error);
            faqListContainer.innerHTML = '<p class=\'text-red-500 p-4 bg-red-50 rounded-md border border-red-200\'>Erro de conexão ao carregar FAQs.</p>';
        }
    }

    function attachAccordionListeners() {
        document.querySelectorAll('.faq-accordion-header').forEach(header => {
                    header.addEventListener('click', () => {
                        const content = header.nextElementSibling;
                        const icon = header.querySelector('i.fa-chevron-down');

                        if (content.classList.contains('hidden')) {
                            content.classList.remove('hidden');
                            icon.classList.add('rotate-180');
                        } else {
                            content.classList.add('hidden');
                            icon.classList.remove('rotate-180');
                        }
                    });
                });
    }

    function attachDeleteFaqListeners() {
        document.querySelectorAll('.delete-faq-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!confirm('Tem certeza que deseja excluir esta FAQ?')) {
                    return;
                }

                const formData = new FormData(this);
                const manageFaqMessageDiv = document.querySelector('#manage_faq_section .alert');

                if (manageFaqMessageDiv) {
                    manageFaqMessageDiv.innerHTML = '<div class=\'bg-blue-100 border-blue-500 text-blue-700 border-l-4 p-4 mb-4 rounded-lg shadow-sm\' role=\'alert\'>Excluindo...</div>';
                    manageFaqMessageDiv.classList.remove('hidden');
                }

                try {
                    const response = await fetch('index.php?section=manage_faq_section', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();

                    if (manageFaqMessageDiv) {
                        if (data.success) {
                            manageFaqMessageDiv.innerHTML = `<div class='bg-green-100 border-green-500 text-green-700 border-l-4 p-4 mb-4 rounded-lg shadow-sm\' role=\'alert\'>${data.message}</div>`;
                        } else {
                            manageFaqMessageDiv.innerHTML = `<div class='bg-red-100 border-red-500 text-red-700 border-l-4 p-4 mb-4 rounded-lg shadow-sm\' role=\'alert\'>${data.message}</div>`;
                        }
                        setTimeout(() => {
                            manageFaqMessageDiv.classList.add('hidden');
                        }, 5000);
                    }
                    await fetchFaqList();
                } catch (error) {
                    console.error('Erro ao excluir FAQ:', error);
                    if (manageFaqMessageDiv) {
                        manageFaqMessageDiv.innerHTML = '<div class=\'bg-red-100 border-red-500 text-red-700 border-l-4 p-4 mb-4 rounded-lg shadow-sm\' role=\'alert\'>Erro de conexão ao excluir FAQ.</div>';
                        manageFaqMessageDiv.classList.remove('hidden');
                        setTimeout(() => {
                            manageFaqMessageDiv.classList.add('hidden');
                        }, 5000);
                    }
                }
            });
        });
    }

    function setupFaqChat() {
        if(!chatArea) return;

        // Novo: Pega o container da lista de perguntas
        const questionsList = document.getElementById('faq-questions-list');

        chatArea.innerHTML = `
            <div class="flex justify-start items-end gap-3 animate-fade-in-up">
                ${samAvatarHtml}
                <div class="chat-bubble chat-bubble-answer">
                    <p>Olá! Eu sou o ${virtualAssistantName}, seu assistente virtual da ${companyDisplayName}. Como posso ajudar hoje? Escolha uma pergunta da lista ao lado.</p>
                </div>
            </div>`;
        
        // Limpa o conteúdo anterior
        if (questionsList) {
            questionsList.innerHTML = '';
        }
        if (suggestionsArea) {
            suggestionsArea.innerHTML = ''; // Mantido por segurança, embora esteja oculto
        }
        resetArea.classList.add('hidden');

        // Popula a nova lista de perguntas
        if (questionsList) {
            faqsData.forEach(faq => {
                const button = document.createElement('button');
                button.className = 'faq-question-item'; // Nova classe CSS
                button.textContent = faq.question;
                button.dataset.faqId = faq.id;
                button.addEventListener('click', handleFaqSuggestionClick);
                questionsList.appendChild(button);
            });
        }
    }
    window.setupFaqChat = setupFaqChat;

    function processAnswerText(text) {
        // Match the custom link format [[link text|type:destination]]
        const linkRegex = /\\\[\\\[(.*?)\\|(.*?):(.*?)\\\]\\]/g;

        // First, replace the custom link format with proper HTML <a> tags
        let processedText = text.replace(linkRegex, (match, linkText, linkType, linkDest) => {
            let url = '#';
            let targetAttr = '';
            let iconHtml = '';

            // Trim whitespace from captured groups
            const type = linkType.trim();
            const destination = linkDest.trim();

            if (type === 'internal') {
                url = `index.php?section=${destination}`;
                iconHtml = '<i class="fas fa-arrow-circle-right mr-1"></i>';
            } else if (type === 'external') {
                url = destination;
                targetAttr = ' target="_blank" rel="noopener noreferrer"';
                iconHtml = '<i class="fas fa-external-link-alt mr-1"></i>';
            }

            // Only build the link if the type was valid
            if (iconHtml) {
                return `<a href="${url}" class="font-bold hover:underline inline-flex items-center" style="color: #254c90;"${targetAttr}>${iconHtml}${linkText}</a>`;
            }
            
            // If the tag is malformed (e.g., wrong type), just return the link text as fallback.
            return linkText;
        });

        // After processing links, replace newline characters with <br> for proper HTML rendering.
                // After processing links, replace newline characters with <br> for proper HTML rendering.
        return processedText.replace(/\n/g, '<br>');
    }

    // Filtro de busca para a lista de perguntas da FAQ
    const faqSearchInput = document.getElementById('faq-search-input');
    if (faqSearchInput) {
        faqSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const questions = document.querySelectorAll('#faq-questions-list .faq-question-item');
            questions.forEach(q => {
                q.style.display = q.textContent.toLowerCase().includes(searchTerm) ? 'block' : 'none';
            });
        });
    }

    function handleFaqSuggestionClick(event) {
        const button = event.currentTarget;
        const faqId = button.dataset.faqId;
        const faq = faqsData.find(f => f.id == faqId);

        if (!faq) return;

        // Novo: Destaca a pergunta ativa na lista
        const questionsList = document.getElementById('faq-questions-list');
        if (questionsList) {
            questionsList.querySelectorAll('.faq-question-item').forEach(btn => btn.classList.remove('active'));
        }
        button.classList.add('active');

        if (resetArea.classList.contains('hidden')) {
            resetArea.classList.remove('hidden');
        }

        const questionBubble = document.createElement('div');
        questionBubble.className = 'flex justify-end items-end gap-3 animate-fade-in-up';
        questionBubble.innerHTML = `
            <div class="chat-bubble chat-bubble-question">
                <p class="font-semibold">${faq.question}</p>
            </div>
            ${userAvatarHtml}`;
        chatArea.appendChild(questionBubble);

        chatArea.scrollTop = chatArea.scrollHeight;

        const typingBubble = document.createElement('div');
        typingBubble.id = 'typing-indicator-bubble';
        typingBubble.className = 'flex justify-start items-end gap-3 animate-fade-in-up';
        typingBubble.innerHTML = `
            ${samAvatarHtml}
            <div class="chat-bubble chat-bubble-answer">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>`;
        chatArea.appendChild(typingBubble);
        chatArea.scrollTop = chatArea.scrollHeight;

        setTimeout(() => {
            const typingIndicatorToRemove = document.getElementById('typing-indicator-bubble');
            if (typingIndicatorToRemove) typingIndicatorToRemove.remove();

            const answerBubble = document.createElement('div');
            answerBubble.className = 'flex justify-start items-end gap-3 animate-fade-in-up';

            // Pass the raw answer text to the processing function.
            // The function will handle both link processing and newline conversion.
            const processedAnswer = processAnswerText(faq.answer);

            answerBubble.innerHTML = `
                ${samAvatarHtml}
                <div class="chat-bubble chat-bubble-answer">
                    <p>${processedAnswer}</p>
                </div>`;
            chatArea.appendChild(answerBubble);

            chatArea.scrollTop = chatArea.scrollHeight;

            // Verifica se todas as perguntas foram clicadas (agora verificando a classe 'active')
            const activeButtons = document.querySelectorAll('#faq-questions-list .faq-question-item.active');
            const allButtons = document.querySelectorAll('#faq-questions-list .faq-question-item');

            if (activeButtons.length === allButtons.length) {
                setTimeout(() => {
                    const endMessage = document.createElement('div');
                    endMessage.className = 'flex justify-start items-end gap-3 animate-fade-in-up';
                    endMessage.innerHTML = `
                        ${samAvatarHtml}
                        <div class="chat-bubble chat-bubble-answer">
                            <p>Espero ter ajudado! Se tiver outra dúvida, clique em "Reiniciar Conversa". 😊</p>
                        </div>`;
                    chatArea.appendChild(endMessage);
                    chatArea.scrollTop = chatArea.scrollHeight;
                }, 800);
            }
        }, 1200);
    }

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            // Limpa o campo de busca ao reiniciar
            if (faqSearchInput) {
                faqSearchInput.value = '';
            }
            setupFaqChat();
        });
    }

    const startTourBtn = document.getElementById('start-tour-btn');
    if (startTourBtn && window.intranetTour) {
        startTourBtn.addEventListener('click', () => {
            window.intranetTour.start();
        });
    }

    
});