<?php
session_start();

// 1. Destruir todas as variáveis de sessão
$_SESSION = array();

// 2. "Matar" os cookies do "Manter Conectado"
// Definimos o tempo de expiração para uma hora atrás (time() - 3600)
if (isset($_COOKIE['user_matricula'])) {
    setcookie('user_matricula', '', time() - 3600, "/");
}

if (isset($_COOKIE['user_senha'])) {
    setcookie('user_senha', '', time() - 3600, "/");
}

// 3. Destruir a sessão no servidor
session_destroy();

// 4. Redirecionar para o login
header("Location: index.php");
exit;
?>