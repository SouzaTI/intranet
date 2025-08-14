<?php
session_start();
require_once 'log_activity.php'; // Inclui o arquivo de log
// Permite admin OU god adicionar botões
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: intranet.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "intranet");

$titulo = trim($_POST['titulo']);
$tipo = $_POST['tipo'];
$pdf_path = null;

// Upload do PDF se for o caso
if ($tipo === 'pdf' && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        $nome_arquivo = uniqid('pdf_') . '.pdf';
        $destino = 'uploads/' . $nome_arquivo;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        if (move_uploaded_file($_FILES['pdf']['tmp_name'], $destino)) {
            $pdf_path = $destino;
        }
    }
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($tipo === 'tabela') {
    if (!empty($_POST['iframe'])) {
        // Se o usuário colou um iframe, salva ele como conteúdo
        $conteudo = $_POST['iframe'];
    } elseif (isset($_FILES['excel']) && $_FILES['excel']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['excel']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['excel']['name'], PATHINFO_EXTENSION));
        if ($ext === 'xls') {
            $reader = IOFactory::createReader('Xls');
        } else {
            $reader = IOFactory::createReader('Xlsx');
        }
        $spreadsheet = $reader->load($tmpFile);
        $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
        ob_start();
        $htmlWriter->save('php://output');
        $tabelaHtml = ob_get_clean();
        $conteudo = $tabelaHtml;
    } else {
        $conteudo = '';
    }
} else {
    $conteudo = $_POST['conteudo'] ?? '';
}

// Para tipo tabela, o admin/god pode colar o HTML da tabela no campo conteudo
$stmt = $conn->prepare("INSERT INTO sidebar_botoes (titulo, conteudo, pdf_path, parent_id, tipo) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssds", $titulo, $conteudo, $pdf_path, $parent_id, $tipo);
$stmt->execute();

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'N/A';
logActivity($userId, "Botão Adicionado", "Usuário {$username} adicionou um botão de tipo '{$tipo}' com título '{$titulo}'.");

header("Location: intranet.php");
exit();