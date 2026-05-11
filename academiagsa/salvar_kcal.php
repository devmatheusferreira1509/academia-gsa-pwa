<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) exit;

$kcal = $_POST['kcal'] ?? 0;
$data = date('Y-m-d');

$stmt = $pdo->prepare("
    INSERT INTO consumo_diario (usuario_id, kcal, data_registro)
    VALUES (?, ?, ?)
");
$stmt->execute([$user_id, $kcal, $data]);

echo json_encode(['status'=>'ok']);