<?php
require_once 'conexao.php';
header('Content-Type: application/json');

$eventos = [];
// Seleciona os campos que o FullCalendar espera (title, start, end, color)
// e passa a descrição e o id via extendedProps
$result = $conn->query("SELECT id, titulo as title, data_inicio as start, data_fim as end, cor as color, descricao FROM eventos");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['extendedProps'] = [
            'description' => $row['descricao'],
            'id' => $row['id']
        ];
        unset($row['descricao']); // Remove a chave original para não duplicar
        unset($row['id']);
        $eventos[] = $row;
    }
}

// Adicionar aniversários
$current_year = date('Y');
$result_birthdays = $conn->query("SELECT username, data_nascimento FROM users WHERE data_nascimento IS NOT NULL");

if ($result_birthdays) {
    while ($user = $result_birthdays->fetch_assoc()) {
        $birth_date = $user['data_nascimento'];
        // Extrai mês e dia da data de nascimento
        $month_day = date('m-d', strtotime($birth_date));
        
        // Cria a data do aniversário para o ano atual
        $birthday_this_year = $current_year . '-' . $month_day;

        $eventos[] = [
            'title' => 'Aniversário de ' . $user['username'],
            'start' => $birthday_this_year,
            'allDay' => true,
            'color' => '#ADD8E6', // Light Blue for birthdays
            'extendedProps' => [
                'description' => 'Aniversário de ' . $user['username'],
                'type' => 'birthday' // Adiciona um tipo para diferenciar
            ]
        ];
    }
}

echo json_encode($eventos);
$conn->close();
?>
