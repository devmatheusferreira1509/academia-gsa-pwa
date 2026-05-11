<?php
require 'db.php';
session_start();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $peso_atual = $_POST['peso_atual'];
    $altura = $_POST['altura'];
    $data_nasc = $_POST['data_nasc'];
    $objetivo = $_POST['objetivo'];

    $stmt = $pdo->prepare("UPDATE usuarios SET peso_atual = ?, altura = ?, data_nascimento = ?, objetivo = ? WHERE id = ?");
    $stmt->execute([$peso_atual, $altura, $data_nasc, $objetivo, $user_id]);

    $peso_meta = $_POST['peso_meta'];
    $ritmo = $_POST['ritmo_semanal'];

    $stmt2 = $pdo->prepare("INSERT INTO metas_usuario (usuario_id, peso_inicial, peso_meta, ritmo_semanal, data_inicio) VALUES (?, ?, ?, ?, CURDATE())");
    $stmt2->execute([$user_id, $peso_atual, $peso_meta, $ritmo]);

    header("Location: analytics_vip.php");
}