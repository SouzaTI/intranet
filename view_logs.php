<?php
session_start();
require_once 'conexao.php';
require_once 'count_logged_in_users.php';

// Atualiza o status do usuário atual como "online"
// A sessão já deve ter sido iniciada, então podemos chamar a função diretamente.
update_user_status();

// Conta o número de usuários online
$online_users = count_online_users();

// Lógica de Filtragem
$filter_user_id = $_GET['user_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$sql = "SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_user_id)) {
    $where_clauses[] = "l.user_id = ?";
    $params[] = $filter_user_id;
    $types .= 'i';
}
if (!empty($filter_status)) {
    $where_clauses[] = "l.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if (!empty($filter_date_from)) {
    $where_clauses[] = "l.timestamp >= ?";
    $params[] = $filter_date_from . " 00:00:00";
    $types .= 's';
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "l.timestamp <= ?";
    $params[] = $filter_date_to . " 23:59:59";
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY l.timestamp DESC";

$stmt = $conn->prepare($sql);
if (!empty($types) && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$logs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Buscar todos os usuários para o dropdown
$users = [];
$user_result = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
if ($user_result) {
    while ($user = $user_result->fetch_assoc()) {
        $users[] = $user;
    }
}

function get_log_visuals($action, $status) {
    $visuals = ['icon' => '<i class="fas fa-info-circle"></i>', 'class' => 'log-info'];
    if ($status === 'error') {
        $visuals['icon'] = '<i class="fas fa-exclamation-triangle"></i>';
        $visuals['class'] = 'log-error';
        return $visuals;
    }
    $action_lower = strtolower($action);
    if (str_contains($action_lower, 'add') || str_contains($action_lower, 'criou') || str_contains($action_lower, 'upload') || str_contains($action_lower, 'enviada')) {
        $visuals['icon'] = '<i class="fas fa-plus"></i>';
        $visuals['class'] = 'log-add';
    } elseif (str_contains($action_lower, 'excluiu') || str_contains($action_lower, 'delete')) {
        $visuals['icon'] = '<i class="fas fa-times"></i>';
        $visuals['class'] = 'log-delete';
    } elseif (str_contains($action_lower, 'editou') || str_contains($action_lower, 'atualiza')) {
        $visuals['icon'] = '<i class="fas fa-pencil-alt"></i>';
        $visuals['class'] = 'log-edit';
    } elseif (str_contains($action_lower, 'login')) {
        $visuals['icon'] = '<i class="fas fa-sign-in-alt"></i>';
        $visuals['class'] = 'log-auth';
    } elseif (str_contains($action_lower, 'logout')) {
        $visuals['icon'] = '<i class="fas fa-sign-out-alt"></i>';
        $visuals['class'] = 'log-auth';
    } elseif (str_contains($action_lower, 'senha')) {
        $visuals['icon'] = '<i class="fas fa-key"></i>';
        $visuals['class'] = 'log-auth'; // Reutilizando a classe de autenticação
    }
    return $visuals;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Atividade do Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; background-color: #0d1117; color: #c9d1d9; }
        .container { max-width: 980px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 24px; font-weight: 600; padding-bottom: 16px; border-bottom: 1px solid #30363d; margin-bottom: 10px; color: #c9d1d9; }
        
        .online-users-indicator { 
            margin-bottom: 20px; 
            color: #8b949e; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background-color: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            width: fit-content;
        }
        .online-users-indicator .online-icon {
            color: #238636; /* Verde para indicar online */
            font-size: 10px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(35, 134, 54, 0); }
            100% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0); }
        }

        /* Estilos do Filtro */
        .filter-panel { background-color: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 20px; margin-bottom: 30px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #8b949e; margin-bottom: 8px; }
        .filter-group input[type="date"], .filter-group select { background-color: #0d1117; color: #c9d1d9; border: 1px solid #30363d; border-radius: 6px; padding: 8px 12px; font-size: 14px; }
        
        /* Estilo das Pílulas de Status */
        .pill-filter { display: flex; border: 1px solid #30363d; border-radius: 6px; overflow: hidden; }
        .pill-filter a { padding: 8px 16px; color: #8b949e; text-decoration: none; font-size: 14px; font-weight: 600; border-right: 1px solid #30363d; background-color: #161b22; }
        .pill-filter a:last-child { border-right: none; }
        .pill-filter a.active { color: #fff; background-color: #007bff; }
        .pill-filter a:hover:not(.active) { background-color: #21262d; }

        .timeline { position: relative; padding-left: 40px; }
        .timeline::before { content: ''; position: absolute; left: 19px; top: 5px; bottom: 0; width: 2px; background-color: #30363d; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-icon { position: absolute; left: -21px; top: 0; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; border: 2px solid #0d1117; font-size: 16px; }
        .log-add .timeline-icon { background-color: #238636; } .log-delete .timeline-icon { background-color: #da3633; } .log-edit .timeline-icon { background-color: #388bfd; } .log-auth .timeline-icon { background-color: #8b949e; } .log-error .timeline-icon { background-color: #f0b623; } .log-info .timeline-icon { background-color: #31b5c7; }
        .timeline-content { position: relative; padding: 15px; padding-bottom: 40px; background-color: #161b22; border: 1px solid #30363d; border-radius: 6px; margin-left: 20px; }
        .timeline-header { display: flex; align-items: center; flex-wrap: wrap; font-size: 14px; margin-bottom: 8px; }
        .timeline-header .action { font-weight: 600; color: #58a6ff; margin-right: 8px; }
        .timeline-header .user { color: #8b949e; margin-right: 8px; }
        .timeline-header .user strong { color: #c9d1d9; font-weight: 600; }
        .timeline-header .timestamp { color: #8b949e; font-size: 12px; }
        .timeline-body { font-size: 14px; color: #c9d1d9; padding-bottom: 10px; }
        .timeline-body p { margin: 0 0 5px 0; }
        .timeline-body .ip { font-size: 12px; color: #8b949e; font-family: monospace; }
        .timeline-footer { position: absolute; bottom: 10px; right: 15px; }
        .status-indicator { display: inline-flex; align-items: center; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .status-indicator.success { color: #3fb950; background-color: rgba(56, 139, 69, 0.15); border: 1px solid rgba(56, 139, 69, 0.4); }
        .status-indicator.error { color: #f85149; background-color: rgba(248, 81, 73, 0.15); border: 1px solid rgba(248, 81, 73, 0.4); }
        .status-indicator i { margin-right: 5px; }
        .no-logs { text-align: center; color: #8b949e; padding: 40px; background-color: #161b22; border: 1px solid #30363d; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Logs de Atividade do Sistema</h1>

        <div class="online-users-indicator">
            <i class="fas fa-circle online-icon"></i>
            <strong><?php echo $online_users; ?></strong> usuário(s) online
        </div>

        <form id="log-filters" method="GET" action="">
            <div class="filter-panel">
                <div class="filter-group">
                    <label for="user_id">Usuário</label>
                    <select id="user_id" name="user_id">
                        <option value="">Todos</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($filter_user_id == $user['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <div class="pill-filter">
                        <a href="#" data-status="" class="pill-status <?php echo empty($filter_status) ? 'active' : ''; ?>">Todos</a>
                        <a href="#" data-status="success" class="pill-status <?php echo ($filter_status === 'success') ? 'active' : ''; ?>">Success</a>
                        <a href="#" data-status="error" class="pill-status <?php echo ($filter_status === 'error') ? 'active' : ''; ?>">Error</a>
                    </div>
                    <input type="hidden" name="status" id="status-input" value="<?php echo htmlspecialchars($filter_status); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_from">De</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">Até</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
            </div>
        </form>

        <div class="timeline">
            <?php if (empty($logs)): ?>
                <p class="no-logs">Nenhum log encontrado para os filtros selecionados.</p>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $visuals = get_log_visuals($log['action'], $log['status']); ?>
                    <div class="timeline-item <?php echo $visuals['class']; ?>">
                        <div class="timeline-icon"><?php echo $visuals['icon']; ?></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="action"><?php echo htmlspecialchars($log['action']); ?></span>
                                <span class="user">por <strong><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?></strong></span>
                                <span class="timestamp">em <?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></span>
                            </div>
                            <div class="timeline-body">
                                <?php if (!empty($log['details'])) : ?><p><?php echo htmlspecialchars($log['details']); ?></p><?php endif; ?>
                                <span class="ip">IP: <?php echo htmlspecialchars($log['ip_address']); ?></span>
                            </div>
                            <div class="timeline-footer">
                                <span class="status-indicator <?php echo htmlspecialchars($log['status']); ?>">
                                    <i class="fas <?php echo ($log['status'] === 'success') ? 'fa-check' : 'fa-times'; ?>"></i>
                                    <span><?php echo htmlspecialchars(ucfirst($log['status'])); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('log-filters');
            const inputs = form.querySelectorAll('select, input[type="date"]');
            const statusPills = form.querySelectorAll('.pill-status');
            const statusInput = document.getElementById('status-input');

            // Auto-submit para selects e datas
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    form.submit();
                });
            });

            // Lógica para as pílulas de status
            statusPills.forEach(pill => {
                pill.addEventListener('click', function(e) {
                    e.preventDefault(); // Previne a navegação do link
                    statusInput.value = this.getAttribute('data-status');
                    form.submit();
                });
            });
        });
    </script>

</body>
</html>