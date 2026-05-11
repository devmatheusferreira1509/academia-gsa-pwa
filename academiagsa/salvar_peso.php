<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sessão expirada"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$peso = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);

if ($peso === false || $peso <= 0) {
    echo json_encode(["status" => "error", "message" => "Peso inválido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO progresso_usuario (usuario_id, peso, data_registro) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $peso]);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro no servidor"]);
}