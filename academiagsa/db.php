<?php
$host = "localhost";
$user = "root"; 
$pass = "1122334455"; // Sua senha do MYSQL
$db   = "sistema_treino";

try {
    // Usamos o charset utf8mb4 para aceitar acentos e emojis de treino
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // ATENÇÃO: Estas linhas abaixo fazem o erro aparecer na tela em vez de dar Erro 500
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Se a senha estiver errada, ele vai avisar aqui:
    die("Erro ao conectar: " . $e->getMessage());
}
?>