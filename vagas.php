<?php
// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao.php';

// Verifica se o usuário está logado e se tem permissão de admin para gerenciar vagas
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god']);

$message = '';

// Lógica para adicionar/editar vaga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($is_admin) {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $requisitos = $_POST['requisitos'] ?? '';
        $contato = $_POST['contato'] ?? '';
        $data_publicacao = $_POST['data_publicacao'] ?? date('Y-m-d');
        $data_expiracao = $_POST['data_expiracao'] ?? null;
        $status = $_POST['status'] ?? 'ativa';

        if (empty($titulo) || empty($descricao)) {
            $message = '<div class="alert alert-danger">Título e descrição são obrigatórios.</div>';
        } else {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO vagas (titulo, descricao, requisitos, contato, data_publicacao, data_expiracao, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $titulo, $descricao, $requisitos, $contato, $data_publicacao, $data_expiracao, $status);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Vaga adicionada com sucesso!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Erro ao adicionar vaga: ' . $conn->error . '</div>';
                }
            } elseif ($_POST['action'] === 'edit') {
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $conn->prepare("UPDATE vagas SET titulo = ?, descricao = ?, requisitos = ?, contato = ?, data_publicacao = ?, data_expiracao = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssssssi", $titulo, $descricao, $requisitos, $contato, $data_publicacao, $data_expiracao, $status, $id);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Vaga atualizada com sucesso!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Erro ao atualizar vaga: ' . $conn->error . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">ID da vaga não fornecido para edição.</div>';
                }
            }
            $stmt->close();
        }
    } else {
        $message = '<div class="alert alert-danger">Você não tem permissão para realizar esta ação.</div>';
    }
}

// Lógica para excluir vaga
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ($is_admin) {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM vagas WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Vaga excluída com sucesso!</div>';
            } else {
                $message = '<div class="alert alert-danger">Erro ao excluir vaga: ' . $conn->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">ID da vaga não fornecido para exclusão.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Você não tem permissão para realizar esta ação.</div>';
    }
}

// Obter vagas para exibição
$vagas = [];
$result_vagas = $conn->query("SELECT * FROM vagas ORDER BY data_publicacao DESC");
if ($result_vagas) {
    while ($row = $result_vagas->fetch_assoc()) {
        $vagas[] = $row;
    }
}

// Obter vaga para edição (se houver um ID na URL)
$vaga_to_edit = null;
if ($is_admin && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM vagas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vaga_to_edit = $result->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-[#4A90E2] mb-4">Vagas Disponíveis</h2>

    <?php if (!empty($message)): ?>
        <?php
            $status_class = strpos($message, 'alert-success') !== false
                ? 'bg-green-100 border-green-500 text-green-700'
                : 'bg-red-100 border-red-500 text-red-700';
            echo '<div class="' . $status_class . ' border-l-4 p-4 mb-4 rounded-lg shadow-sm" role="alert">' . str_replace(['<div class="alert alert-success">', '<div class="alert alert-danger">', '</div>'], '', $message) . '</div>';
        ?>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-xl font-semibold text-[#4A90E2] mb-4"><?php echo $vaga_to_edit ? 'Editar Vaga' : 'Adicionar Nova Vaga'; ?></h3>
            <form action="index.php?section=vagas" method="POST" class="space-y-4">
                <?php if ($vaga_to_edit): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($vaga_to_edit['id']); ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>

                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700">Título da Vaga</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($vaga_to_edit['titulo'] ?? ''); ?>" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="5" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"><?php echo htmlspecialchars($vaga_to_edit['descricao'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="requisitos" class="block text-sm font-medium text-gray-700">Requisitos (Opcional)</label>
                    <textarea id="requisitos" name="requisitos" rows="3" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2"><?php echo htmlspecialchars($vaga_to_edit['requisitos'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="contato" class="block text-sm font-medium text-gray-700">Contato (E-mail/Telefone)</label>
                    <input type="text" id="contato" name="contato" value="<?php echo htmlspecialchars($vaga_to_edit['contato'] ?? ''); ?>" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="data_publicacao" class="block text-sm font-medium text-gray-700">Data de Publicação</label>
                        <input type="date" id="data_publicacao" name="data_publicacao" value="<?php echo htmlspecialchars($vaga_to_edit['data_publicacao'] ?? date('Y-m-d')); ?>" required class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label for="data_expiracao" class="block text-sm font-medium text-gray-700">Data de Expiração (Opcional)</label>
                        <input type="date" id="data_expiracao" name="data_expiracao" value="<?php echo htmlspecialchars($vaga_to_edit['data_expiracao'] ?? ''); ?>" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                    </div>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="mt-1 w-full border border-[#1d3870] rounded-md px-3 py-2">
                        <option value="ativa" <?php echo ($vaga_to_edit['status'] ?? 'ativa') === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                        <option value="inativa" <?php echo ($vaga_to_edit['status'] ?? '') === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                        <option value="preenchida" <?php echo ($vaga_to_edit['status'] ?? '') === 'preenchida' ? 'selected' : ''; ?>>Preenchida</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <?php if ($vaga_to_edit): ?>
                        <a href="index.php?section=vagas" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar Edição</a>
                    <?php endif; ?>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <?php echo $vaga_to_edit ? 'Atualizar Vaga' : 'Adicionar Vaga'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <?php if (count($vagas) > 0): ?>
            <?php foreach ($vagas as $vaga): ?>
                <div class="bg-gray-50 rounded-lg shadow-md p-6 border-l-4 
                    <?php 
                        if ($vaga['status'] === 'ativa') echo 'border-green-500';
                        else if ($vaga['status'] === 'preenchida') echo 'border-blue-500';
                        else echo 'border-red-500';
                    ?>">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($vaga['titulo']) ?></h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?php 
                                if ($vaga['status'] === 'ativa') echo 'bg-green-100 text-green-800';
                                else if ($vaga['status'] === 'preenchida') echo 'bg-blue-100 text-blue-800';
                                else echo 'bg-red-100 text-red-800';
                            ?>">
                            <?= ucfirst($vaga['status']) ?>
                        </span>
                    </div>
                    <p class="text-gray-700 mb-3"><?= nl2br(htmlspecialchars($vaga['descricao'])) ?></p>
                    <?php if (!empty($vaga['requisitos'])): ?>
                        <div class="mb-3">
                            <h4 class="font-semibold text-gray-600">Requisitos:</h4>
                            <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($vaga['requisitos'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($vaga['contato'])): ?>
                        <div class="mb-3">
                            <h4 class="font-semibold text-gray-600">Contato:</h4>
                            <p class="text-gray-600 text-sm"><?= htmlspecialchars($vaga['contato']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="text-sm text-gray-500 flex justify-between items-center">
                        <span>Publicado em: <?= date('d/m/Y', strtotime($vaga['data_publicacao'])) ?></span>
                        <?php if (!empty($vaga['data_expiracao'])): ?>
                            <span>Expira em: <?= date('d/m/Y', strtotime($vaga['data_expiracao'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_admin): ?>
                        <div class="mt-4 flex justify-end space-x-2">
                            <a href="index.php?section=vagas&action=edit&id=<?= $vaga['id'] ?>" class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm">Editar</a>
                            <a href="index.php?section=vagas&action=delete&id=<?= $vaga['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta vaga?');" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 text-sm">Excluir</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-gray-500 py-10">
                <i class="fas fa-briefcase text-5xl mb-4"></i>
                <p class="text-lg">Nenhuma vaga disponível no momento.</p>
                <?php if ($is_admin): ?>
                    <p class="mt-2">Use o formulário acima para adicionar uma nova vaga.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>