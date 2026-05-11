<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peso = $_POST['peso_atual'] ?? null;
    $altura = $_POST['altura'] ?? null;
    $data_nasc = $_POST['data_nascimento'] ?? null;
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE usuarios SET peso_atual = ?, altura = ?, data_nascimento = ? WHERE id = ?");
        $stmt->execute([$peso, $altura, $data_nasc, $user_id]);

        if (!empty($nova_senha)) {
            $stmt_senha = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt_senha->execute([$nova_senha, $user_id]);
        }

        $pdo->commit();
        
        header("Location: meus_dados.php?sucesso=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erro ao atualizar: " . $e->getMessage());
    }
} else {
    header("Location: meus_dados.php");
    exit;
}