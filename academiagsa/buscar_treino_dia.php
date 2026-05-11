<?php
require 'db.php';
session_start();
$data = $_GET['data'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT SUM(duracao_segundos) as tempo, SUM(kcal_gasta) as kcal FROM historico_atividades WHERE usuario_id = ? AND DATE(data_registro) = ?");
$stmt->execute([$user_id, $data]);
$t = $stmt->fetch();

echo json_encode([
    'treinou' => ($t['tempo'] > 0),
    'tempo' => $t['tempo'] ?? 0,
    'kcal' => $t['kcal'] ?? 0
]);