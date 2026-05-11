<?php
// busca_exercicios_api.php
require 'db.php';
$musculo = $_GET['musculo'] ?? '';

$stmt = $pdo->prepare("SELECT nome, gif_url FROM api_exercicios WHERE corpo_alvo = ? LIMIT 50");
$stmt->execute([$musculo]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));