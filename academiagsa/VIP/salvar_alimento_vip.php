<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];

$nome  = $_GET['label'] ?? 'Alimento';
$kcal  = (float)($_GET['kcal'] ?? 0);
$prot  = (float)($_GET['prot'] ?? 0);
$carb  = (float)($_GET['carb'] ?? 0);
$gord  = (float)($_GET['gord'] ?? 0);

$quantidade = (float)($_GET['quantidade'] ?? 100);
if ($quantidade <= 0) $quantidade = 100;

$fator = $quantidade / 100;

$kcal_final = $kcal * $fator;
$prot_final = $prot * $fator;
$carb_final = $carb * $fator;
$gord_final = $gord * $fator;

try {
    $sql = "INSERT INTO alimentos (nome, porcao_referencia, kcal, proteina, carbo, gordura, usuario_id, data_registro) 
            VALUES (:nome, :porcao, :kcal, :prot, :carb, :gord, :user_id, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'    => $nome,
        ':porcao'  => $quantidade,
        ':kcal'    => $kcal_final,
        ':prot'    => $prot_final,
        ':carb'    => $carb_final,
        ':gord'    => $gord_final,
        ':user_id' => $usuario_id
    ]);

    header("Location: visual_vip.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar no banco: " . $e->getMessage());
}