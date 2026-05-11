<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];
$data = $_GET['data'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT id, alimento_nome, quantidade_g, kcal FROM consumo_diario WHERE usuario_id = ? AND DATE(data_registro) = ? ORDER BY data_registro DESC");
$stmt->execute([$user_id, $data]);
$refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($refeicoes);