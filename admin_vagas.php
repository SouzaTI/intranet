<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem ver esta página
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    // Se for uma requisição AJAX, retorna erro JSON, senão, HTML
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    } else {
        echo '<p class="text-red-500">Acesso negado. Você não tem permissão para visualizar esta página.</p>';
    }
    exit();
}

// Lógica para adicionar/editar/deletar vaga (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;

    if ($action === 'add' || $action === 'edit') {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $requisitos = $_POST['requisitos'] ?? '';
        $setor = $_POST['setor'] ?? '';
        $status = $_POST['status'] ?? 'aberta';

        if (empty($titulo) || empty($descricao) || empty($requisitos) || empty($setor)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
            exit();
        }

        $stmt = null;
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO vagas_internas (titulo, descricao, requisitos, setor, status, data_publicacao) VALUES (?, ?, ?, ?, ?, CURDATE())");
            if($stmt) $stmt->bind_param("sssss", $titulo, $descricao, $requisitos, $setor, $status);
        } elseif ($action === 'edit' && $id) {
            $stmt = $conn->prepare("UPDATE vagas_internas SET titulo = ?, descricao = ?, requisitos = ?, setor = ?, status = ? WHERE id = ?");
            if($stmt) $stmt->bind_param("sssssi", $titulo, $descricao, $requisitos, $setor, $status, $id);
        }

        if ($stmt && $stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vaga salva com sucesso!']);
        } else {
            $error = $stmt ? $stmt->error : $conn->error;
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados: ' . $error]);
        }

    } elseif ($action === 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM vagas_internas WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Vaga excluída com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir a vaga: ' . $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a exclusão: ' . $conn->error]);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida ou ID ausente.']);
    }
    exit();
}

// Se for um GET normal, busca os dados e renderiza o HTML
$vagas = $conn->query("SELECT * FROM vagas_internas ORDER BY data_publicacao DESC");
$setores = [];
$result_setores = $conn->query("SELECT nome FROM setores ORDER BY nome ASC");
if ($result_setores) {
    while ($setor = $result_setores->fetch_assoc()) {
        $setores[] = $setor['nome'];
    }
}
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-[#4A90E2] mb-6">Gerenciar Vagas</h2>

    <!-- Formulário para Adicionar/Editar Vaga -->
    <form id="vagas-form" action="admin_vagas.php" method="POST" class="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div id="form-feedback-vagas" class="mb-4"></div>
        <input type="hidden" name="action" id="form-action" value="add">
        <input type="hidden" name="id" id="form-id" value="">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título da Vaga</label>
                <input type="text" name="titulo" id="form-titulo" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="setor" class="block text-sm font-medium text-gray-700">Setor</label>
                <select name="setor" id="form-setor" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="" disabled selected>Selecione um setor</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea name="descricao" id="form-descricao" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required></textarea>
        </div>
        <div class="mt-4">
            <label for="requisitos" class="block text-sm font-medium text-gray-700">Requisitos</label>
            <textarea name="requisitos" id="form-requisitos" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required></textarea>
        </div>
        <div class="mt-4">
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" id="form-status" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                <option value="aberta">Aberta</option>
                <option value="fechada">Fechada</option>
                <option value="em_andamento">Em Andamento</option>
            </select>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" id="cancel-edit-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md mr-2 hidden">Cancelar Edição</button>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Salvar Vaga</button>
        </div>
    </form>

    <!-- Tabela de Vagas -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-[#254c90] text-white">
                <tr>
                    <th class="py-2 px-4 text-left">Título</th>
                    <th class="py-2 px-4 text-left">Setor</th>
                    <th class="py-2 px-4 text-left">Status</th>
                    <th class="py-2 px-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($vagas && $vagas->num_rows > 0): ?>
                    <?php while($vaga = $vagas->fetch_assoc()): ?>
                        <tr>
                            <td class="py-3 px-4"><?= htmlspecialchars($vaga['titulo']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($vaga['setor']) ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?= $vaga['status'] == 'aberta' ? 'bg-green-100 text-green-800' : '' ?>
                                    <?= $vaga['status'] == 'fechada' ? 'bg-red-100 text-red-800' : '' ?>
                                    <?= $vaga['status'] == 'em_andamento' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                ">
                                    <?= ucfirst(str_replace('_', ' ', $vaga['status'])) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button class="text-gray-500 hover:text-gray-700 mr-2" 
                                        onclick='showVagaDetails(<?= json_encode($vaga) ?>)'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="edit-vaga-btn text-blue-500 hover:text-blue-700"
                                        data-id="<?= $vaga['id'] ?>"
                                        data-titulo="<?= htmlspecialchars($vaga['titulo']) ?>"
                                        data-descricao="<?= htmlspecialchars($vaga['descricao']) ?>"
                                        data-requisitos="<?= htmlspecialchars($vaga['requisitos']) ?>"
                                        data-setor="<?= htmlspecialchars($vaga['setor']) ?>"
                                        data-status="<?= $vaga['status'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-vaga-btn text-red-500 hover:text-red-700 ml-2" data-id="<?= $vaga['id'] ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="py-4 px-4 text-center text-gray-500">Nenhuma vaga cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Detalhes da Vaga -->
<div id="vaga-details-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 id="modal-title" class="text-2xl font-bold text-[#4A90E2]">Detalhes da Vaga</h3>
            <button id="modal-close-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <div class="mt-4 space-y-4">
            <div>
                <h4 class="font-semibold text-gray-700">Setor:</h4>
                <p id="modal-setor" class="text-gray-800"></p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-700">Descrição:</h4>
                <p id="modal-descricao" class="text-gray-800 whitespace-pre-wrap"></p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-700">Requisitos:</h4>
                <p id="modal-requisitos" class="text-gray-800 whitespace-pre-wrap"></p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-700">Status:</h4>
                <p id="modal-status" class="text-gray-800"></p>
            </div>
             <div>
                <h4 class="font-semibold text-gray-700">Data de Publicação:</h4>
                <p id="modal-data" class="text-gray-800"></p>
            </div>
        </div>
    </div>
</div>

<!-- O script de edição E SUBMISSÃO agora está aqui -->
<script>
// Como este script é injetado dinamicamente, não usamos DOMContentLoaded
const container = document.getElementById('admin_vagas');
if (container) {
    const modal = document.getElementById('vaga-details-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    // Função para mostrar detalhes da vaga no modal
    window.showVagaDetails = (vaga) => {
        if (!modal) return;

        document.getElementById('modal-title').innerText = vaga.titulo;
        document.getElementById('modal-setor').innerText = vaga.setor;
        document.getElementById('modal-descricao').innerText = vaga.descricao;
        document.getElementById('modal-requisitos').innerText = vaga.requisitos;
        
        const statusSpan = document.createElement('span');
        statusSpan.className = 'px-2 py-1 text-xs font-semibold rounded-full';
        let statusText = 'Desconhecido';
        
        switch(vaga.status) {
            case 'aberta':
                statusSpan.classList.add('bg-green-100', 'text-green-800');
                statusText = 'Aberta';
                break;
            case 'fechada':
                statusSpan.classList.add('bg-red-100', 'text-red-800');
                statusText = 'Fechada';
                break;
            case 'em_andamento':
                statusSpan.classList.add('bg-yellow-100', 'text-yellow-800');
                statusText = 'Em Andamento';
                break;
        }
        statusSpan.innerText = statusText;
        const statusP = document.getElementById('modal-status');
        statusP.innerHTML = ''; // Limpa o conteúdo anterior
        statusP.appendChild(statusSpan);

        // Formata a data
        const dataObj = new Date(vaga.data_publicacao + 'T00:00:00');
        document.getElementById('modal-data').innerText = dataObj.toLocaleDateString('pt-BR');

        modal.classList.remove('hidden');
    };

    // Evento para fechar o modal
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', () => modal.classList.add('hidden'));
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) { // Fecha apenas se clicar no fundo escuro
                modal.classList.add('hidden');
            }
        });
    }

    // Lógica para preencher o formulário de edição
    const setupEditButtons = () => {
        const editButtons = container.querySelectorAll('.edit-vaga-btn');
        const form = container.querySelector('#vagas-form');
        if (!form) return;

        const actionInput = form.querySelector('#form-action');
        const idInput = form.querySelector('#form-id');
        const tituloInput = form.querySelector('#form-titulo');
        const descricaoInput = form.querySelector('#form-descricao');
        const requisitosInput = form.querySelector('#form-requisitos');
        const setorInput = form.querySelector('#form-setor');
        const statusInput = form.querySelector('#form-status');
        const cancelBtn = form.querySelector('#cancel-edit-btn');
        const feedbackDiv = form.querySelector('#form-feedback-vagas');

        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                actionInput.value = 'edit';
                idInput.value = btn.dataset.id;
                tituloInput.value = btn.dataset.titulo;
                descricaoInput.value = btn.dataset.descricao;
                requisitosInput.value = btn.dataset.requisitos;
                setorInput.value = btn.dataset.setor;
                statusInput.value = btn.dataset.status;
                
                cancelBtn.classList.remove('hidden');
                form.scrollIntoView({ behavior: 'smooth' });
            });
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                actionInput.value = 'add';
                idInput.value = '';
                form.reset();
                cancelBtn.classList.add('hidden');
                if(feedbackDiv) feedbackDiv.innerHTML = '';
            });
        }
    };

    // Lógica para submissão do formulário via AJAX
    const setupFormSubmit = () => {
        const form = container.querySelector('#vagas-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            let feedbackDiv = form.querySelector('#form-feedback-vagas');

            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitButton.disabled = true;
            feedbackDiv.innerHTML = '';

            fetch('admin_vagas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => { 
                        throw new Error("O servidor enviou uma resposta inesperada. Verifique o console para detalhes.");
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    feedbackDiv.innerHTML = `<div class="p-3 mb-4 bg-green-100 text-green-800 rounded-md">${data.message}</div>`;
                    form.reset();
                    form.querySelector('#form-action').value = 'add';
                    form.querySelector('#cancel-edit-btn').classList.add('hidden');
                    
                    setTimeout(() => {
                        if(window.showSection) {
                           window.showSection('admin_vagas');
                        } else {
                           window.location.href = 'index.php?section=admin_vagas&status=success';
                        }
                    }, 1500);
                } else {
                    feedbackDiv.innerHTML = `<div class="p-3 mb-4 bg-red-100 text-red-800 rounded-md">${data.message || 'Ocorreu um erro desconhecido.'}</div>`;
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                feedbackDiv.innerHTML = `<div class="p-3 mb-4 bg-red-100 text-red-800 rounded-md"><b>Erro de comunicação:</b> ${error.message}</div>`;
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            });
        });
    };

    // Lógica para exclusão de vaga via AJAX
    const setupDeleteButtons = () => {
        const deleteButtons = container.querySelectorAll('.delete-vaga-btn');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Tem certeza que deseja excluir esta vaga?')) {
                    return;
                }

                const id = btn.dataset.id;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('admin_vagas.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recarrega a seção para mostrar a lista atualizada
                        if(window.showSection) {
                           window.showSection('admin_vagas');
                        } else {
                           window.location.href = 'index.php?section=admin_vagas&delete_status=success';
                        }
                    } else {
                        alert(data.message || 'Ocorreu um erro ao excluir a vaga.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro de comunicação ao tentar excluir a vaga.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                });
            });
        });
    };

    // Inicializa tudo
    setupEditButtons();
    setupFormSubmit();
    setupDeleteButtons();
}
</script>