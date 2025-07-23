<?php
$conn = new mysqli("localhost", "root", "", "intranet");
$id = intval($_GET['id']);
$res = $conn->query("SELECT id, titulo, conteudo FROM sidebar_botoes WHERE id=$id");
echo json_encode($res->fetch_assoc());