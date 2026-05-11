<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

// 1. Busca dados do Aluno
$dados = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$dados->execute([$user_id]);
$u = $dados->fetch();

// 2. Busca última meta para preencher o formulário
$stmt_meta = $pdo->prepare("SELECT peso_meta FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt_meta->execute([$user_id]);
$meta = $stmt_meta->fetch();

// 3. Lógica para cálculo de Idade e Água
function calcularIdade($dataNascimento) {
    if (!$dataNascimento) return "--";
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

$idade = calcularIdade($u['data_nascimento'] ?? null);
$peso = $u['peso_atual'] ?? 0;
$altura = $u['altura'] ?? 0; 
$agua = ($peso > 0) ? (round($peso * 35) / 1000) : "--";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GSA | Perfil VIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --gold: #f1c40f; --text: #ffffff; --gray: #8899a6; --border: #2a3b44; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 15px; padding-bottom: 120px; overflow-x: hidden; }
        
        .perfil-header { text-align: center; margin: 20px 0 30px 0; }
        .avatar { width: 85px; height: 85px; background: linear-gradient(135deg, var(--accent), #27ae60); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #000; font-weight: 900; margin-bottom: 12px; border: 4px solid var(--card); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }

        .dashboard-saude { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 25px; }
        .dashboard-item { background: var(--card); border-radius: 15px; padding: 12px; text-align: center; border: 1px solid var(--border); }
        .dashboard-label { color: var(--gray); text-transform: uppercase; font-size: 0.55rem; font-weight: 900; margin-bottom: 5px; }
        .dashboard-value { font-size: 1.1rem; font-weight: 900; color: var(--accent); }
        .dashboard-unit { font-size: 0.65rem; color: var(--text); font-weight: 400; }

        .form-section { background: var(--card); padding: 20px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 20px; }
        .section-title { margin-top: 0; color: var(--accent); font-weight: 900; font-size: 1rem; text-transform: uppercase; margin-bottom: 15px; }
        
        .input-group { margin-bottom: 15px; }
        label { display: block; color: var(--gray); font-size: 0.7rem; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; }
        input, select { width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 10px; color: #fff; box-sizing: border-box; font-size: 0.95rem; font-family: inherit; outline: none; }
        input:focus, select:focus { border-color: var(--accent); }
        
        .btn-save { width: 100%; padding: 16px; background: var(--accent); color: #000; border: none; border-radius: 12px; font-weight: 900; cursor: pointer; font-size: 1rem; text-transform: uppercase; margin-top: 10px; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #162127; display: flex; justify-content: space-around; padding: 15px 0 30px 0; border-top: 1px solid var(--border); z-index: 1000; }
        .nav-item { text-decoration: none; display: flex; flex-direction: column; align-items: center; color: var(--gray); flex: 1; font-size: 0.65rem; font-weight: 700; gap: 5px; }
        .nav-item.active { color: var(--accent); }
    </style>
</head>
<body>

    <div class="perfil-header">
        <div class="avatar"><?= substr($u['nome'] ?? 'U', 0, 1) ?></div>
        
        <h2 style="margin: 5px 0; font-weight: 900; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <?= htmlspecialchars($u['nome']) ?>
            <span style="background: var(--gold); color: #000; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; letter-spacing: 1px;">VIP</span>
        </h2>

        <div style="display: flex; flex-direction: column; gap: 5px; align-items: center;">
            <div style="background: rgba(46,204,113,0.1); display: inline-block; padding: 4px 12px; border-radius: 20px;">
                <small style="color: var(--accent); font-weight: 800; font-size: 0.7rem;">MATRÍCULA: #<?= $u['matricula'] ?></small>
            </div>

            <?php if (!empty($u['vip_expiracao'])): ?>
                <div style="color: var(--gray); font-size: 0.65rem; font-weight: 700; text-transform: uppercase;">
                    Válido até: <span style="color: var(--text);"><?= date('d/m/Y', strtotime($u['vip_expiracao'])) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-saude">
        <div class="dashboard-item">
            <div class="dashboard-label">Peso</div>
            <div class="dashboard-value"><?= $peso ?><small class="dashboard-unit"> kg</small></div>
        </div>
        <div class="dashboard-item">
            <div class="dashboard-label">Idade</div>
            <div class="dashboard-value"><?= $idade ?><small class="dashboard-unit"> anos</small></div>
        </div>
        <div class="dashboard-item">
            <div class="dashboard-label">Água/Dia</div>
            <div class="dashboard-value" style="color: #3498db;"><?= $agua ?><small class="dashboard-unit"> L</small></div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Editar Meus Dados</h3>
        <form action="atualizar_perfil_vip.php" method="POST" id="perfilForm">
            
            <div class="input-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" value="<?= $u['data_nascimento'] ?>">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex:1;">
                    <label>Peso Atual (kg)</label>
                    <input type="number" step="0.1" name="peso_atual" value="<?= $peso ?>" required>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>Altura (cm)</label>
                    <input type="number" name="altura" value="<?= $altura ?>" required>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--border); margin: 20px 0;">
            <h3 class="section-title" style="color: var(--gold);">Objetivo & Meta</h3>

            <div class="input-group">
                <label>Objetivo Principal</label>
                <select name="objetivo" required>
                    <option value="Emagrecimento" <?= $u['objetivo'] == 'Emagrecimento' ? 'selected' : '' ?>>Emagrecimento</option>
                    <option value="Ganho de Massa Muscular" <?= $u['objetivo'] == 'Ganho de Massa Muscular' ? 'selected' : '' ?>>Ganho de Massa</option>
                    <option value="Emagrecimento + Ganho de Massa" <?= $u['objetivo'] == 'Emagrecimento + Ganho de Massa' ? 'selected' : '' ?>>Emagrecimento + Ganho de Massa</option>
                </select>
            </div>

            <div class="input-group">
                <label>Peso Meta (Onde quer chegar?)</label>
                <input type="number" step="0.1" name="peso_meta" value="<?= $meta['peso_meta'] ?? '' ?>" required>
            </div>

            <input type="hidden" name="ritmo_semanal" value="1.0">

            <div style="border-top: 1px solid var(--border); padding-top: 15px; margin-top: 20px;">
                <label style="color: var(--gold)">Alterar Senha (Opcional)</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova senha" style="margin-bottom:10px;">
                <input type="password" id="confirma_senha" placeholder="Confirmar nova senha">
            </div>
            
            <button type="submit" class="btn-save">SALVAR ALTERAÇÕES</button>
        </form>
    </div>

    <nav class="bottom-nav">
        <a href="painel_vip.php" class="nav-item"><span>🏠</span><span>Início</span></a>
        <a href="treino_atual_vip.php?id=<?= $u['id'] ?>" class="nav-item"><span>💪</span><span>Treino</span></a>
        <a href="visual_vip.php" class="nav-item"><span>📊</span><span>Analytics</span></a>
        <a href="prescrever_treinovip.php" class="nav-item"><span>📝</span><span>Montar</span></a>
        <a href="meus_dados_vip.php" class="nav-item active"><span>👤</span><span>Perfil</span></a>
        <a href="logout.php" class="nav-item"><span>🚪</span><span>Sair</span></a>        
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