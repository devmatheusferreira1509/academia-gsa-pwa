<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT peso_atual, altura, data_nascimento, objetivo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();

$stmt2 = $pdo->prepare("SELECT peso_meta, ritmo_semanal FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt2->execute([$user_id]);
$meta = $stmt2->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Perfil VIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #050a0c; --card: rgba(255,255,255,0.05); --border: rgba(255,255,255,0.1); }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; padding: 20px; }
        .setup-container { max-width: 500px; margin: auto; }
        .group { margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; color: #8899a6; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
        input, select { 
            width: 100%; padding: 15px; border-radius: 12px; border: 1px solid var(--border); 
            background: rgba(0,0,0,0.3); color: white; outline: none; box-sizing: border-box; 
        }
        /* Estilização da "Barrinha" de Ritmo */
        .ritmo-selector { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
        .ritmo-opt { 
            background: var(--card); border: 1px solid var(--border); padding: 10px; 
            border-radius: 10px; text-align: center; cursor: pointer; font-size: 0.8rem;
        }
        .ritmo-opt span { display: block; font-size: 0.6rem; color: #8899a6; }
        .ritmo-opt.active { border-color: var(--primary); background: rgba(46,204,113,0.1); }
        .btn-ready { 
            width: 100%; padding: 18px; border-radius: 15px; border: none; 
            background: var(--primary); color: #000; font-weight: 900; cursor: pointer; margin-top: 30px; 
        }
    </style>
</head>
<body>

<div class="setup-container">
    <h1 style="font-weight:900;">CONFIGURAÇÃO <span style="color:var(--primary)">VIP</span></h1>
    <p style="color:#8899a6; font-size:0.9rem;">Preencha para calcularmos seu plano.</p>

    <form action="salvar_setup_vip.php" method="POST">
        <div class="group">
            <label>Seu Peso Atual (kg)</label>
            <input type="number" step="0.1" name="peso_atual" value="<?= $aluno['peso_atual'] ?>" required>
        </div>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <div class="group">
                <label>Altura (cm)</label>
                <input type="number" name="altura" value="<?= $aluno['altura'] ?>" placeholder="Ex: 175" required>
            </div>
            <div class="group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nasc" value="<?= $aluno['data_nascimento'] ?>" required>
            </div>
        </div>

        <hr style="border:0; border-top:1px solid var(--border); margin: 20px 0;">

        <div class="group">
            <label>Qual seu objetivo principal?</label>
            <select name="objetivo" required>
                <option value="Emagrecimento" <?= $aluno['objetivo'] == 'Emagrecimento' ? 'selected' : '' ?>>Emagrecimento</option>
                <option value="Ganho de Massa Muscular" <?= $aluno['objetivo'] == 'Ganho de Massa Muscular' ? 'selected' : '' ?>>Ganho de Massa</option>
                <option value="Definição Muscular">Definição Muscular</option>
            </select>
        </div>

        <div class="group">
            <label>Peso Meta (Onde quer chegar?)</label>
            <input type="number" step="0.1" name="peso_meta" value="<?= $meta['peso_meta'] ?? '' ?>" required>
        </div>

        <div class="group">
            <label>Ritmo Semanal (Intensidade)</label>
            <input type="hidden" name="ritmo_semanal" id="ritmo_val" value="<?= $meta['ritmo_semanal'] ?? '1.0' ?>">
            <div class="ritmo-selector">
                <div class="ritmo-opt <?= ($meta['ritmo_semanal'] ?? '') == '0.5' ? 'active' : '' ?>" onclick="setRitmo(0.5, this)">
                    0.5kg <span>Tranquilo</span>
                </div>
                <div class="ritmo-opt <?= ($meta['ritmo_semanal'] ?? '1.0') == '1.0' ? 'active' : '' ?>" onclick="setRitmo(1.0, this)">
                    1.0kg <span>Recomendado</span>
                </div>
                <div class="ritmo-opt <?= ($meta['ritmo_semanal'] ?? '') == '1.5' ? 'active' : '' ?>" onclick="setRitmo(1.5, this)">
                    1.5kg <span>Desafiador</span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-ready">ATIVAR MEU PLANO VIP</button>
    </form>
</div>

<script>
function setRitmo(valor, el) {
    document.getElementById('ritmo_val').value = valor;
    document.querySelectorAll('.ritmo-opt').forEach(opt => opt.classList.remove('active'));
    el.classList.add('active');
}
</script>

</body>
</html>