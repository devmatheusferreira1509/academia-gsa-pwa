<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$data = $_GET['data'] ?? date('Y-m-d');

// 1. Busca Gasto de Treino
$st = $pdo->prepare("SELECT SUM(kcal_gasta) as k, SUM(duracao_segundos) as s FROM historico_atividades WHERE usuario_id = ? AND DATE(data_registro) = ?");
$st->execute([$user_id, $data]);
$treino = $st->fetch();

// 2. Busca Consumo Total do Dia
$st2 = $pdo->prepare("SELECT SUM(kcal) as total_c FROM consumo_diario WHERE usuario_id = ? AND DATE(data_registro) = ?");
$st2->execute([$user_id, $data]);
$consumo = $st2->fetch()['total_c'] ?? 0;

// 3. Busca Meta Base do Usuário (Para calcular o saldo)
$st3 = $pdo->prepare("SELECT peso_atual, objetivo FROM usuarios WHERE id = ?");
$st3->execute([$user_id]);
$u = $st3->fetch();

$meta_base = 2000; 
if($u) {
    $meta_base = (10 * $u['peso_atual']) + 600;
}

$kcal_treino = $treino['k'] ?? 0;
$saldo = ($meta_base + $kcal_treino) - $consumo;

echo json_encode([
    "treinou" => ($kcal_treino > 0),
    "tempo_min" => round(($treino['s'] ?? 0) / 60),
    "kcal_treino" => round($kcal_treino),
    "saldo_dia" => $saldo
]);