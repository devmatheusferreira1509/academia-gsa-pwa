<?php
session_start();
require 'db.php';

// Verifica se o usuário está logado e é VIP
if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'VIP') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_registro = $_GET['id'] ?? null;

if ($id_registro) {
    // Isso impede que um usuário apague os dados de outro mudando o ID na URL
    $stmt = $pdo->prepare("DELETE FROM consumo_diario WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id_registro, $user_id]);
}

// Retorna para o dashboard com o filtro que estava usando
$origem = $_SERVER['HTTP_REFERER'] ?? 'analytics_vip.php';
header("Location: " . $origem);
exit;