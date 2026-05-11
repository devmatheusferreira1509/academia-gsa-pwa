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
    $objetivo = $_POST['objetivo'] ?? null;
    $peso_meta = $_POST['peso_meta'] ?? null;
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    try {
        $pdo->beginTransaction();

        // 1. ATUALIZA DADOS NA TABELA 'usuarios'
        $stmt = $pdo->prepare("UPDATE usuarios SET peso_atual = ?, altura = ?, data_nascimento = ?, objetivo = ? WHERE id = ?");
        $stmt->execute([$peso, $altura, $data_nasc, $objetivo, $user_id]);

        // 2. ATUALIZA OU INSERE NA TABELA 'metas_usuario' COM LÓGICA DE PESO INICIAL
        $check_meta = $pdo->prepare("SELECT id, peso_inicial FROM metas_usuario WHERE usuario_id = ? LIMIT 1");
        $check_meta->execute([$user_id]);
        $meta_atual = $check_meta->fetch();
        
        if ($meta_atual) {
            if (empty($meta_atual['peso_inicial']) || $meta_atual['peso_inicial'] <= 0) {
                $stmt_meta = $pdo->prepare("UPDATE metas_usuario SET peso_meta = ?, peso_inicial = ?, ritmo_semanal = 1.0, objetivo = ? WHERE usuario_id = ?");
                $stmt_meta->execute([$peso_meta, $peso, $objetivo, $user_id]);
            } else {
                $stmt_meta = $pdo->prepare("UPDATE metas_usuario SET peso_meta = ?, ritmo_semanal = 1.0, objetivo = ? WHERE usuario_id = ?");
                $stmt_meta->execute([$peso_meta, $objetivo, $user_id]);
            }
        } else {
            $stmt_meta = $pdo->prepare("INSERT INTO metas_usuario (usuario_id, peso_meta, peso_inicial, ritmo_semanal, objetivo, data_inicio) VALUES (?, ?, ?, 1.0, ?, NOW())");
            $stmt_meta->execute([$user_id, $peso_meta, $peso, $objetivo]);
        }

        // 3. ATUALIZA SENHA SE FOR INFORMADA
        if (!empty($nova_senha)) {
            $stmt_senha = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt_senha->execute([$nova_senha, $user_id]);
        }

        $pdo->commit();
        
        header("Location: meus_dados_vip.php?sucesso=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erro ao atualizar perfil: " . $e->getMessage());
    }
} else {
    header("Location: meus_dados_vip.php");
    exit;
}