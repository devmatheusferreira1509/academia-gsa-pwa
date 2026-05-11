<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['nome'])) {
    header("Location: analytics_vip.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nome = $_GET['nome'];
$gramas = floatval($_GET['g']);

$fator = $gramas / 100;

$kcal = floatval($_GET['kcal']) * $fator;
$prot = floatval($_GET['prot']) * $fator;
$carb = floatval($_GET['carb']) * $fator;
$gord = floatval($_GET['gord']) * $fator;

$sql = "INSERT INTO consumo_diario (usuario_id, alimento_nome, quantidade_g, kcal, proteina, carbo, gordura) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
if ($stmt->execute([$user_id, $nome, $gramas, $kcal, $prot, $carb, $gord])) {
    header("Location: analytics_vip.php?sucesso=1");
} else {
    echo "Erro ao salvar: " . implode(" ", $stmt->errorInfo());
}
?>