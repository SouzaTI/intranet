<?php
require_once 'config.php';
require_once __DIR__ . '/api/auth_check.php';
require_once __DIR__ . '/api/ContratoAuth.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$ehAdmin = !empty($_SESSION['is_admin']);
$auth = new ContratoAuth($pdo_intra, $userId, $ehAdmin);

$versao = method_exists($auth, 'versao') ? $auth->versao() : 'AUTH ANTIGO / SEM MARCADOR';
$setores = method_exists($auth, 'setoresUsuario') ? $auth->setoresUsuario() : [];

[$filtro, $params] = $auth->filtroContratosSql();

$stmt = $pdo_intra->prepare("SELECT COUNT(*) FROM contratos c WHERE {$filtro}");
$stmt->execute($params);
$totalVisiveis = (int) $stmt->fetchColumn();

$stmt = $pdo_intra->prepare("
    SELECT c.id, c.fornecedor, c.setor, c.gestor_id, c.status_fluxo
      FROM contratos c
     WHERE {$filtro}
     ORDER BY c.setor, c.fornecedor
     LIMIT 50
");
$stmt->execute($params);
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo_intra->prepare("
    SELECT g.id, g.nome, g.setor_vinculado
      FROM usuarios_grupos ug
      JOIN grupos_intranet g ON g.id = ug.grupo_id
     WHERE ug.usuario_id = ?
     ORDER BY g.nome
");
$stmt->execute([$userId]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Diagnóstico ContratoAuth</title>
<style>
body{font-family:Arial,sans-serif;background:#f3f6fb;color:#0f172a;padding:24px}
.box{max-width:1100px;margin:auto;background:white;border:1px solid #dbe3ee;border-radius:16px;padding:22px}
table{width:100%;border-collapse:collapse;margin:12px 0 24px}
th,td{border-bottom:1px solid #e5e7eb;padding:9px;text-align:left;font-size:14px}
.ok{color:#047857;font-weight:700}.erro{color:#be123c;font-weight:700}
code,pre{background:#f1f5f9;border-radius:8px;padding:8px;display:block;overflow:auto}
</style>
</head>
<body>
<div class="box">
<h2>Diagnóstico Gestão de Contratos</h2>

<table>
<tr><th>Usuário</th><td><?= h($userId) ?></td></tr>
<tr><th>ContratoAuth carregado</th><td class="<?= $versao === 'GRUPO_SETOR_V2' ? 'ok':'erro' ?>"><?= h($versao) ?></td></tr>
<tr><th>Setores reconhecidos pelo Auth</th><td class="<?= $setores ? 'ok':'erro' ?>"><?= h($setores ? implode(' | ', $setores) : 'NENHUM') ?></td></tr>
<tr><th>acessar_modulo</th><td><?= $auth->pode('acessar_modulo') ? 'SIM' : 'NÃO' ?></td></tr>
<tr><th>visualizar</th><td><?= $auth->pode('visualizar') ? 'SIM' : 'NÃO' ?></td></tr>
<tr><th>Total visível pelo filtro real</th><td class="<?= $totalVisiveis > 0 ? 'ok':'erro' ?>"><?= h($totalVisiveis) ?></td></tr>
</table>

<h3>Grupos do usuário</h3>
<table>
<tr><th>ID</th><th>Grupo</th><th>Setor vinculado</th></tr>
<?php foreach ($grupos as $g): ?>
<tr>
<td><?= h($g['id']) ?></td>
<td><?= h($g['nome']) ?></td>
<td><?= h($g['setor_vinculado'] ?? 'NULL') ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>Contratos retornados pelo mesmo filtro do contratos.php</h3>
<table>
<tr><th>ID</th><th>Fornecedor</th><th>Setor</th><th>Gestor</th><th>Status</th></tr>
<?php foreach ($contratos as $c): ?>
<tr>
<td><?= h($c['id']) ?></td>
<td><?= h($c['fornecedor']) ?></td>
<td><?= h($c['setor']) ?></td>
<td><?= h($c['gestor_id']) ?></td>
<td><?= h($c['status_fluxo']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$contratos): ?>
<tr><td colspan="5" class="erro">Nenhum contrato retornado.</td></tr>
<?php endif; ?>
</table>

<h3>Filtro SQL gerado</h3>
<pre><?= h($filtro) ?></pre>
<h3>Parâmetros</h3>
<pre><?= h(print_r($params, true)) ?></pre>

<p><strong>Depois do teste, remova este arquivo do servidor.</strong></p>
</div>
</body>
</html>
