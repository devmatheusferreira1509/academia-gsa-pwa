<?php
session_start();
require 'db.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM alimentos WHERE id = ? AND usuario_id = ?");
    
    if ($stmt->execute([$id, $user_id])) {
        header("Location: visual_vip.php?status=sucesso_excluir");
    } else {
        header("Location: visual_vip.php?status=erro_sql");
    }
} else {
    header("Location: visual_vip.php?status=erro_parametros");
}
exit;