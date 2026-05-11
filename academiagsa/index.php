<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_matricula']) && isset($_COOKIE['user_senha'])) {
    $c_matricula = $_COOKIE['user_matricula'];
    $c_senha     = $_COOKIE['user_senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND senha = ?");
    $stmt->execute([$c_matricula, $c_senha]);
    $user = $stmt->fetch();

    if ($user) {
        logarUsuario($user, $pdo);
    }
}

function logarUsuario($user, $pdo) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nome']    = $user['nome'];
    $hoje = date('Y-m-d');
    
    $stmt_meta = $pdo->prepare("SELECT peso_meta FROM metas_usuario WHERE usuario_id = ? LIMIT 1");
    $stmt_meta->execute([$user['id']]);
    $tem_meta = $stmt_meta->fetch();

    $perfil_basico_vazio = (empty($user['peso_atual']) || empty($user['altura']) || empty($user['data_nascimento']));
    $perfil_vip_vazio    = ($perfil_basico_vazio || empty($user['objetivo']) || !$tem_meta);

    if ($user['nivel_acesso'] === 'VIP') {
        if ($user['vip_ativo'] == 1 && $hoje <= $user['vip_expiracao']) {
            $_SESSION['nivel'] = 'VIP';
            
            if ($perfil_vip_vazio) {
                header("Location: meus_dados_vip.php?aviso=obrigatorio");
            } else {
                header("Location: painel_vip.php");
            }
        } else {
            $_SESSION['nivel'] = 'Aluno';
            if ($perfil_basico_vazio) {
                header("Location: meus_dados.php?aviso=obrigatorio");
            } else {
                header("Location: painel_aluno.php");
            }
        }
    } 
    elseif ($user['nivel_acesso'] === 'Master') {
        $_SESSION['nivel'] = 'Master';
        header("Location: painel_master.php");
    } 
    else {
        $_SESSION['nivel'] = 'Aluno';
        if ($perfil_basico_vazio) {
            header("Location: meus_dados.php?aviso=obrigatorio");
        } else {
            header("Location: painel_aluno.php");
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $senha     = $_POST['senha'];
    $lembrar   = isset($_POST['lembrar']); 

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND senha = ?");
    $stmt->execute([$matricula, $senha]);
    $user = $stmt->fetch();

    if ($user) {
        if ($lembrar) {
            setcookie('user_matricula', $matricula, time() + (86400 * 30), "/");
            setcookie('user_senha', $senha, time() + (86400 * 30), "/");
        }
        logarUsuario($user, $pdo);
    } else {
        $erro = "Matrícula ou Senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>GSA | Sistema de Treino</title>
    
    <meta name="theme-color" content="#0b1315">
    <link rel="manifest" href="manifest.json">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GSA Treino">
    <link rel="apple-touch-icon" href="logo_ios.png">

    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --text: #ffffff; --border: #2a3b44; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background-color: var(--bg); color: var(--text); display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        
        .login-card { background: var(--card); padding: 40px 30px; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.7); width: 100%; max-width: 320px; text-align: center; border: 1px solid var(--border); }
        
        .logo-gsa { font-size: 3rem; font-weight: 900; letter-spacing: -2px; margin: 0; color: var(--text); line-height: 1; }
        .logo-gsa span { color: var(--accent); }
        .subtitle { color: #556670; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; }

        input { width: 100%; padding: 16px; margin: 10px 0; border-radius: 15px; border: 1px solid var(--border); background: #050a0c; color: white; font-size: 1.1rem; text-align: center; box-sizing: border-box; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--accent); box-shadow: 0 0 15px rgba(46, 204, 113, 0.15); }
        
        button { width: 100%; padding: 16px; background: var(--accent); border: none; color: black; font-weight: 900; border-radius: 15px; cursor: pointer; font-size: 1rem; margin-top: 15px; text-transform: uppercase; transition: 0.3s; }
        button:active { transform: scale(0.98); }
        
        .install-area { margin-top: 25px; width: 100%; max-width: 320px; display: flex; flex-direction: column; gap: 10px; }
        .btn-install { background: transparent; border: 1px solid var(--border); color: #8899a6; padding: 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; text-transform: none; margin-top: 0; width: 100%; }

        .erro { background: rgba(255, 71, 87, 0.1); color: #ff4757; padding: 10px; border-radius: 10px; font-size: 0.8rem; margin-bottom: 20px; border: 1px solid rgba(255, 71, 87, 0.2); }
    </style>
</head>
<body>

<div class="login-card">
    <h1 class="logo-gsa">G<span>SA</span></h1>
    <div class="subtitle">SISTEMA DE TREINO</div>
    
    <?php if(isset($erro)) echo "<div class='erro'>⚠️ $erro</div>"; ?>
    
    <form method="POST">
        <input type="text" name="matricula" placeholder="Nº MATRÍCULA" inputmode="numeric" required>
        <input type="password" name="senha" placeholder="SENHA" required>
        
        <label style="display:flex; align-items:center; justify-content:center; gap:8px; font-size:0.8rem; color:#556670; margin-top:15px; cursor:pointer">
            <input type="checkbox" name="lembrar" style="width:16px; margin:0; accent-color: var(--accent);" checked> Manter conectado
        </label>

        <button type="submit">ENTRAR NA MINHA CONTA</button>
    </form>
</div>

<div class="install-area" id="installArea">
    <button id="btnAndroid" class="btn-install" style="display: none;">
        <span>🤖 Instalar no Android</span>
    </button>
    <button id="btnIOS" class="btn-install" onclick="showIosInstructions()">
        <span>🍎 Adicionar ao iPhone (iOS)</span>
    </button>
</div>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(function(err) {
            console.log('Erro ao registrar ServiceWorker: ', err);
        });
    }

    let deferredPrompt;
    const btnAndroid = document.getElementById('btnAndroid');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        btnAndroid.style.display = 'flex';
    });

    btnAndroid.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                btnAndroid.style.display = 'none';
            }
            deferredPrompt = null;
        }
    });

    function showIosInstructions() {
        alert("Para instalar no seu iPhone:\n\n1. Clique no botão de 'Compartilhar' (ícone de quadrado com seta no rodapé do Safari).\n2. Role a lista e selecione 'Adicionar à Tela de Início'.\n3. Clique em 'Adicionar' no canto superior.");
    }

    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        document.getElementById('installArea').style.display = 'none';
    }
</script>

</body>
</html>