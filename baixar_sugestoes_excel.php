<?php
session_start();
require_once 'conexao.php';

// 1. VERIFICAÇÃO DE ROBUSTEZ: Checa se o autoloader do Composer existe
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    http_response_code(500);
    die("ERRO: O autoloader do Composer não foi encontrado. Execute 'composer install' na pasta do projeto.");
}
require_once $autoloader;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Apenas admins ou 'god' podem baixar
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    http_response_code(403);
    echo "Acesso negado.";
    exit();
}

// --- Lógica para buscar os dados ---
$sql = "SELECT s.data_envio, u.username, s.tipo, s.mensagem, s.email, s.telefone, s.status
        FROM sugestoes s 
        JOIN users u ON s.usuario_id = u.id 
        ORDER BY s.data_envio DESC";

$result = $conn->query($sql);

// --- Geração do XLSX ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Cabeçalho
$header = ['Data', 'Usuário', 'Tipo', 'Mensagem', 'Email', 'Telefone', 'Status'];
$sheet->fromArray($header, null, 'A1');

// Estilo do cabeçalho
const HEADER_STYLE = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '254c90']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($header)) . '1')->applyFromArray(HEADER_STYLE);

// Processa linha por linha
$rowNum = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rowData = [
            date('d/m/Y H:i', strtotime($row['data_envio'])),
            $row['username'],
            ucfirst($row['tipo']),
            $row['mensagem'],
            $row['email'],
            $row['telefone'],
            ucfirst(str_replace('_', ' ', $row['status']))
        ];
        $sheet->fromArray($rowData, null, 'A' . $rowNum);
        $rowNum++;
    }
}

// Ajusta a largura das colunas automaticamente
foreach (range(1, count($header)) as $col) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
}

// Define o nome da planilha
$sheet->setTitle('Registro de Sugestões');

$filename = 'registro_sugestoes_' . date('Y-m-d') . '.xlsx';

// Envia o arquivo para o navegador
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit();
