<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $peso_meta = $_POST['peso_meta'];
    $acao = $_POST['acao'];

    if ($acao == 'criar') {
        $peso_inicial = $_POST['peso_inicial'];
        $ritmo = $_POST['ritmo'] ?? 1.0;
        
        $stmt = $pdo->prepare("INSERT INTO metas_usuario (usuario_id, objetivo, peso_inicial, peso_meta, ritmo_semanal, data_inicio, status) VALUES (?, 'emagrecer', ?, ?, ?, NOW(), 'pendente')");
        $stmt->execute([$user_id, $peso_inicial, $peso_meta, $ritmo]);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET peso_atual = ? WHERE id = ?");
        $stmt->execute([$peso_inicial, $user_id]);
    }
}