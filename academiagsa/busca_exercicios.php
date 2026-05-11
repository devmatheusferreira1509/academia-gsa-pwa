<?php
require 'db.php';
header('Content-Type: application/json');

$musculos = isset($_GET['musculos']) ? $_GET['musculos'] : '';

if(!empty($musculos)) {
    $arrayMusculos = array_map('trim', explode(',', $musculos));
    $inQuery = implode(',', array_fill(0, count($arrayMusculos), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM biblioteca_exercicios WHERE grupo_muscular IN ($inQuery) ORDER BY nome_exercicio ASC");
    $stmt->execute($arrayMusculos);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}