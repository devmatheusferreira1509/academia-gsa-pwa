<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

$dados = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$dados->execute([$user_id]);
$u = $dados->fetch();

function calcularIdade($dataNascimento) {
    if (!$dataNascimento) return "--";
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

$idade = calcularIdade($u['data_nascimento'] ?? null);
$peso = $u['peso_atual'] ?? 0;
$altura = $u['altura'] ?? 0; 

if ($peso > 0 && $altura > 0 && $idade > 0) {
    $tmb = round(66 + (13.7 * $peso) + (5 * $altura) - (6.8 * $idade));
    $agua = round($peso * 35) / 1000;
} else {
    $tmb = $agua = "--";
}

if ($peso > 0 && $altura > 0) {
    $altura_metros = $altura / 100; 
    $imc = round($peso / ($altura_metros * $altura_metros), 1);
    
    if ($imc < 18.5) {
        $imc_info = "Abaixo do peso.";
        $imc_cor = "#f1c40f";
    } elseif ($imc >= 18.5 && $imc <= 24.9) {
        $imc_info = "Peso Normal.";
        $imc_cor = "var(--accent)";
    } elseif ($imc >= 25.0 && $imc <= 29.9) {
        $imc_info = "Sobrepeso.";
        $imc_cor = "#f39c12";
    } else {
        $imc_info = "Obesidade.";
        $imc_cor = "#e74c3c";
    }
} else {
    $imc = $imc_info = "--";
    $imc_cor = "var(--gray)";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GSA | Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --gold: #f1c40f; --text: #ffffff; --gray: #8899a6; --border: #2a3b44; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 15px; padding-bottom: 110px; overflow-x: hidden; }
        
        .perfil-header { text-align: center; margin: 20px 0 30px 0; }
        .avatar { width: 85px; height: 85px; background: linear-gradient(135deg, var(--accent), #27ae60); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #000; font-weight: 900; margin-bottom: 12px; border: 4px solid var(--card); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }

        .dashboard-saude { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 25px; }
        .dashboard-item { background: var(--card); border-radius: 20px; padding: 15px; text-align: center; border: 1px solid var(--border); }
        .dashboard-label { color: var(--gray); text-transform: uppercase; font-size: 0.6rem; font-weight: 900; letter-spacing: 0.5px; margin-bottom: 5px; }
        .dashboard-value { font-size: 1.4rem; font-weight: 900; color: var(--accent); }
        .dashboard-unit { font-size: 0.75rem; color: var(--text); font-weight: 400; }

        .form-section { background: var(--card); padding: 20px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 20px; }
        .section-title { margin-top: 0; color: var(--accent); font-weight: 900; font-size: 1rem; text-transform: uppercase; margin-bottom: 15px; }
        
        .input-group { margin-bottom: 15px; }
        label { display: block; color: var(--gray); font-size: 0.7rem; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; }
        input { width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 10px; color: #fff; box-sizing: border-box; font-size: 0.95rem; font-family: inherit; }
        input:focus { border-color: var(--accent); outline: none; background: rgba(0,0,0,0.3); }
        
        .btn-save { width: 100%; padding: 16px; background: var(--accent); color: #000; border: none; border-radius: 12px; font-weight: 900; cursor: pointer; font-size: 1rem; text-transform: uppercase; margin-top: 10px; }
        
        .bottom-nav { 
            position: fixed; bottom: 0; left: 0; width: 100%; 
            background: #162127; display: flex; justify-content: space-around; 
            padding: 12px 0 25px 0; border-top: 1px solid var(--border); z-index: 1000; 
        }
        .nav-item { text-decoration: none; display: flex; flex-direction: column; align-items: center; color: var(--gray); flex: 1; }
        .nav-item span:first-child { font-size: 1.5rem; margin-bottom: 4px; }
        .nav-item span:last-child { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; }
        .nav-item.active { color: var(--accent); }
        .nav-item.sair { color: #e74c3c; }
    </style>
</head>
<body>

    <div class="perfil-header">
        <div class="avatar"><?= substr($u['nome'], 0, 1) ?></div>
        <h2 style="margin: 5px 0; font-weight: 900; letter-spacing: -0.5px;"><?= $u['nome'] ?></h2>
        <div style="background: rgba(46,204,113,0.1); display: inline-block; padding: 4px 12px; border-radius: 20px;">
            <small style="color: var(--accent); font-weight: 800; font-size: 0.7rem;">MATRÍCULA: #<?= $u['matricula'] ?></small>
        </div>
    </div>

    <div class="dashboard-saude">
        <div class="dashboard-item">
            <div class="dashboard-label">Peso</div>
            <div class="dashboard-value"><?= $peso ?><small class="dashboard-unit"> kg</small></div>
        </div>
        <div class="dashboard-item">
            <div class="dashboard-label">Altura</div>
            <div class="dashboard-value"><?= $altura ?><small class="dashboard-unit"> cm</small></div>
        </div>
        <div class="dashboard-item">
            <div class="dashboard-label">Idade</div>
            <div class="dashboard-value"><?= $idade ?><small class="dashboard-unit"> anos</small></div>
        </div>
        <div class="dashboard-item">
            <div class="dashboard-label">Água/Dia</div>
            <div class="dashboard-value" style="color: #3498db;"><?= $agua ?><small class="dashboard-unit"> L</small></div>
        </div>
        
        <div class="dashboard-item" style="grid-column: span 2; display: flex; justify-content: space-between; align-items: center; text-align: left;">
            <div>
                <div class="dashboard-label">Metabolismo (TMB)</div>
                <div class="dashboard-value" style="color: var(--gold);"><?= $tmb ?> <small class="dashboard-unit">kcal/dia</small></div>
            </div>
            <span style="font-size: 1.5rem;">🔥</span>
        </div>

        <div class="dashboard-item" style="grid-column: span 2; border: 1px solid <?= $imc_cor ?>; background: rgba(0,0,0,0.1);">
            <div class="dashboard-label">Status do IMC</div>
            <div class="dashboard-value" style="color: <?= $imc_cor ?>;"><?= $imc ?></div>
            <p style="font-size: 0.75rem; color: var(--gray); margin: 5px 0 0 0; font-weight: 700;"><?= $imc_info ?></p>
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Editar Perfil</h3>
        <form action="atualizar_perfil.php" method="POST" id="perfilForm">
            <div class="input-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" value="<?= $u['data_nascimento'] ?>">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex:1;">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.1" name="peso_atual" value="<?= $peso ?>" required>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>Altura (cm)</label>
                    <input type="number" name="altura" value="<?= $altura ?>" required>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 15px; margin-top: 5px;">
                <label style="color: var(--gold)">Alterar Senha (Opcional)</label>
                <div class="input-group">
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova senha">
                </div>
                <div class="input-group">
                    <input type="password" id="confirma_senha" placeholder="Confirmar nova senha">
                </div>
            </div>
            
            <button type="submit" class="btn-save">ATUALIZAR DADOS</button>
        </form>
    </div>

    <nav class="bottom-nav">
        <a href="painel_aluno.php" class="nav-item">
            <span>🏠</span>
            <span>Início</span>
        </a>
        <a href="meus_dados.php" class="nav-item active">
            <span>👤</span>
            <span>Perfil</span>
        </a>
        <a href="logout.php" class="nav-item sair">
            <span>🚪</span>
            <span>Sair</span>
        </a>        
    </nav>

    <script>
    document.getElementById('perfilForm').onsubmit = function() {
        var senha = document.getElementById('nova_senha').value;
        var confirma = document.getElementById('confirma_senha').value;

        if (senha !== "" && senha !== confirma) {
            alert("As senhas não coincidem!");
            return false;
        }
        return true;
    };
    </script>

</body>
</html>