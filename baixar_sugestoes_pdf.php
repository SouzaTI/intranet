<?php
session_start();
require_once 'conexao.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Apenas admins ou 'god' podem ver esta página
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo '<p class="text-red-500">Acesso negado. Você não tem permissão para visualizar esta página.</p>';
    exit();
}

// Busca dos dados
$sql = "SELECT s.*, u.username 
        FROM sugestoes s 
        JOIN users u ON s.usuario_id = u.id 
        ORDER BY s.data_envio DESC";
$result = $conn->query($sql);
$registros = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Monta o conteúdo HTML para o PDF
$html = '
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Sugestões e Reclamações</title>
    <style>
        @page { margin: 20px; }
        body { font-family: "Helvetica", sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #254c90; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; word-wrap: break-word; }
        thead th {
            background-color: #254c90;
            color: white;
            text-align: center;
        }
        tbody tr:nth-child(even) { background-color: #f2f2f2; }
        .status-nova { color: #31708f; background-color: #d9edf7; }
        .status-em_analise { color: #8a6d3b; background-color: #fcf8e3; }
        .status-concluida { color: #3c763d; background-color: #dff0d8; }
        .tipo-sugestao { color: #31708f; }
        .tipo-reclamacao { color: #a94442; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Sugestões e Reclamações</h1>
    </div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Usuário</th>
                <th>Tipo</th>
                <th>Mensagem</th>
                <th>Contato</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

if (count($registros) > 0) {
    foreach ($registros as $row) {
        $contato = '';
        if($row['email']) { $contato .= 'Email: ' . htmlspecialchars($row['email']) . '<br>'; }
        if($row['telefone']) { $contato .= 'Tel: ' . htmlspecialchars($row['telefone']); }

        $html .= '<tr>';
        $html .= '<td style="width: 11%;">' . date('d/m/Y H:i', strtotime($row['data_envio'])) . '</td>';
        $html .= '<td style="width: 12%;">' . htmlspecialchars($row['username']) . '</td>';
        $html .= '<td style="width: 8%;" class="tipo-' . $row['tipo'] . '">' . ucfirst($row['tipo']) . '</td>';
        $html .= '<td style="width: 45%;">' . nl2br(htmlspecialchars($row['mensagem'])) . '</td>';
        $html .= '<td style="width: 12%;">' . $contato . '</td>';
        $html .= '<td style="width: 12%;" class="status-' . $row['status'] . '">' . ucfirst(str_replace('_', ' ', $row['status'])) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="6" style="text-align: center;">Nenhum registro encontrado.</td></tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Instancia e usa o Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'registro_sugestoes_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);

exit();
