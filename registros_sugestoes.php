<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem ver esta página
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo '<p class="text-red-500">Acesso negado. Você não tem permissão para visualizar esta página.</p>';
    exit();
}

$sql = "SELECT s.*, u.username 
        FROM sugestoes s 
        JOIN users u ON s.usuario_id = u.id 
        ORDER BY s.data_envio DESC";

$result = $conn->query($sql);

$status_options = ['nova', 'em_analise', 'concluida'];
$status_classes = [
    'nova' => 'bg-blue-100 text-blue-800',
    'em_analise' => 'bg-yellow-100 text-yellow-800',
    'concluida' => 'bg-green-100 text-green-800'
];
?>

<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-lg shadow">
        <thead class="bg-[#254c90] text-white">
            <tr>
                <th class="py-3 px-4 text-left">Data</th>
                <th class="py-3 px-4 text-left">Usuário</th>
                <th class="py-3 px-4 text-left">Tipo</th>
                <th class="py-3 px-4 text-left">Mensagem</th>
                <th class="py-3 px-4 text-left">Contato</th>
                <th class="py-3 px-4 text-left">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-blue-50 transition-colors duration-150">
                        <td class="py-3 px-4 text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($row['data_envio'])) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-800 font-medium"><?= htmlspecialchars($row['username']) ?></td>
                        <td class="py-3 px-4 text-sm"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $row['tipo'] == 'sugestao' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>"><?= ucfirst($row['tipo']) ?></span></td>
                        <td class="py-3 px-4 text-sm text-gray-700 max-w-md break-words"><?= nl2br(htmlspecialchars($row['mensagem'])) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?php if($row['email']): ?> <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($row['email']) ?><br> <?php endif; ?>
                            <?php if($row['telefone']): ?> <i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($row['telefone']) ?> <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-sm">
                            <select data-id="<?= $row['id'] ?>" class="status-sugestao w-full border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#254c90] <?= $status_classes[$row['status']] ?>">
                                <?php foreach($status_options as $status): ?>
                                    <option value="<?= $status ?>" <?= $row['status'] == $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="status-feedback text-xs text-green-600 ml-1"></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="py-4 px-4 text-center text-gray-500">Nenhum registro encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>