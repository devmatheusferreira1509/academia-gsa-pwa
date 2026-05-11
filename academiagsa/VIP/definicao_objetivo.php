<?php
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $obj       = $_GET['objetivo'] ?? '';
    $peso      = floatval($_GET['peso'] ?? 0);
    $altura    = floatval($_GET['altura'] ?? 0);
    $idade     = intval($_GET['idade'] ?? 0);
    $ritmo     = floatval($_GET['ritmo'] ?? 1.0);
    $treinou   = ($_GET['treinou'] ?? 'nao') === 'sim';
    $kcal_exp  = floatval($_GET['kcal_treino'] ?? 0);

    $tmb = ($peso > 0) ? round(66 + (13.7 * $peso) + (5 * $altura) - (6.8 * $idade)) : 0;
    
    $deficit_ritmo = ($ritmo * 7700) / 7;
    
    $cor = "#2ecc71";
    $status_msg = "";
    $meta_final = $tmb;

    if ($obj == "Emagrecimento") {
        $meta_final = $tmb - $deficit_ritmo;
        $cor = "#2ecc71";
        $status_msg = "Seu corpo precisa de um déficit de " . round($deficit_ritmo) . " kcal/dia para perder {$ritmo}kg por semana.";
    } elseif ($obj == "Ganho de Massa Muscular") {
        $meta_final = $tmb + 300;
        $cor = "#3498db";
        $status_msg = "Foco em superávit leve para construir músculos sem ganhar gordura excessiva.";
    } else {
        $meta_final = $tmb - 200;
        $cor = "#f1c40f";
        $status_msg = "Meta de recomposição: leve déficit com foco em densidade muscular.";
    }

    $meta_com_treino = $meta_final + ($treinou ? $kcal_exp : 0);

    echo json_encode([
        "tmb" => $tmb,
        "meta_base" => round($meta_final),
        "meta_hoje" => round($meta_com_treino),
        "cor" => $cor,
        "mensagem" => $status_msg,
        "orientacao" => ($treinou) 
            ? "Como você treinou hoje, sua meta subiu para manter o déficit seguro." 
            : "Hoje é dia de descanso, mantenha-se na meta basal calculada.",
        "alerta" => ($meta_com_treino < 1200) ? "⚠️ Alerta: Calorias muito baixas! Aumente a ingestão." : ""
    ]);
    exit;
}

require 'db.php';
session_start();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configuração VIP Inteligente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #050a0c; --card: rgba(255,255,255,0.05); --border: rgba(255,255,255,0.1); }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; padding: 20px; }
        .container { max-width: 500px; margin: auto; }
        .card { background: var(--card); border: 1px solid var(--border); padding: 20px; border-radius: 20px; margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; color: #8899a6; text-transform: uppercase; margin-bottom: 8px; font-weight: bold; }
        input, select { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid var(--border); background: #000; color: #fff; margin-bottom: 15px; }
        
        .ritmo-selector { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .ritmo-opt { background: var(--card); border: 1px solid var(--border); padding: 10px; border-radius: 10px; text-align: center; cursor: pointer; font-size: 0.8rem; }
        .ritmo-opt.active { border-color: var(--primary); background: rgba(46,204,113,0.1); }
        
        #display-resultado { display: none; border-left: 4px solid var(--primary); padding-left: 15px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body>

<div class="container">
    <h1 style="font-weight:900;">OBJETIVO <span style="color:var(--primary)">VIP</span></h1>

    <form action="salvar_setup_vip.php" method="POST">
        <div class="card">
            <label>Peso Atual (kg)</label>
            <input type="number" step="0.1" name="peso" id="peso" value="<?= $aluno['peso_atual'] ?>" oninput="atualizarCalculos()">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div>
                    <label>Altura (cm)</label>
                    <input type="number" name="altura" id="altura" value="<?= $aluno['altura'] ?>" oninput="atualizarCalculos()">
                </div>
                <div>
                    <label>Idade</label>
                    <input type="number" id="idade" value="30" oninput="atualizarCalculos()">
                </div>
            </div>

            <label>Objetivo Principal</label>
            <select name="objetivo" id="objetivo" onchange="atualizarCalculos()">
                <option value="Emagrecimento">Emagrecimento</option>
                <option value="Ganho de Massa Muscular">Ganho de Massa</option>
                <option value="Definição Muscular">Definição Muscular</option>
            </select>

            <label>Ritmo de Perda/Ganho (kg/semana)</label>
            <input type="hidden" name="ritmo" id="ritmo_val" value="1.0">
            <div class="ritmo-selector">
                <div class="ritmo-opt" onclick="setRitmo(0.5, this)">0.5kg</div>
                <div class="ritmo-opt active" onclick="setRitmo(1.0, this)">1.0kg</div>
                <div class="ritmo-opt" onclick="setRitmo(1.5, this)">1.5kg</div>
            </div>
        </div>

        <div class="card" id="display-resultado">
            <h3 id="res-titulo" style="margin-top:0;">Plano Calculado</h3>
            <p id="res-msg" style="font-size:0.9rem; color:#ccc;"></p>
            
            <div style="background:rgba(255,255,255,0.03); padding:15px; border-radius:12px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                    <span>Basal:</span> <b id="res-tmb">0</b>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:1.2rem;">
                    <span>Meta de Hoje:</span> <b id="res-meta" style="color:var(--primary);">0 kcal</b>
                </div>
                <small id="res-orientacao" style="display:block; margin-top:10px; color:#8899a6;"></small>
            </div>
            <p id="res-alerta" style="color:#e74c3c; font-weight:bold; font-size:0.8rem;"></p>
        </div>

        <button type="submit" style="width:100%; padding:20px; background:var(--primary); border:none; border-radius:15px; font-weight:900; cursor:pointer;">SALVAR E ATIVAR PLANO</button>
    </form>
</div>

<script>
function setRitmo(v, el) {
    document.getElementById('ritmo_val').value = v;
    document.querySelectorAll('.ritmo-opt').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    atualizarCalculos();
}

function atualizarCalculos() {
    const params = new URLSearchParams({
        ajax: 1,
        objetivo: document.getElementById('objetivo').value,
        peso: document.getElementById('peso').value,
        altura: document.getElementById('altura').value,
        idade: document.getElementById('idade').value,
        ritmo: document.getElementById('ritmo_val').value,
        treinou: 'sim',
        kcal_treino: 450
    });

    fetch(`definicao_objetivo.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            const display = document.getElementById('display-resultado');
            display.style.display = 'block';
            display.style.borderLeftColor = data.cor;
            
            document.getElementById('res-tmb').innerText = data.tmb + ' kcal';
            document.getElementById('res-meta').innerText = data.meta_hoje + ' kcal';
            document.getElementById('res-meta').style.color = data.cor;
            document.getElementById('res-msg').innerText = data.mensagem;
            document.getElementById('res-orientacao').innerText = data.orientacao;
            document.getElementById('res-alerta').innerText = data.alerta;
        });
}

window.onload = atualizarCalculos;
</script>

</body>
</html>