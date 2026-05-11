<?php
session_start();
require 'db.php';
require 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'VIP') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');

//  DADOS DO USUÁRIO
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();

if (!$aluno || !$aluno['vip_ativo'] || $hoje > $aluno['vip_expiracao']) {
    die("<h2 style='color:white;text-align:center;font-family:sans-serif;margin-top:50px;'>Acesso VIP expirado.</h2>");
}

//  METAS E PESO
$stmt = $pdo->prepare("SELECT * FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$meta = $stmt->fetch();

$stmt = $pdo->prepare("SELECT peso, data_registro FROM progresso_usuario WHERE usuario_id = ? ORDER BY data_registro ASC");
$stmt->execute([$user_id]);
$historico_peso = $stmt->fetchAll();

$peso_atual = !empty($historico_peso) ? end($historico_peso)['peso'] : ($meta['peso_inicial'] ?? 0);

//  LÓGICA DE FITNESS
$progresso_pct = 0;
$projecao = ['semanas' => 0];

if ($meta && isset($meta['peso_meta'], $meta['peso_inicial'])) {
    $total_jornada = abs($meta['peso_inicial'] - $meta['peso_meta']);
    $batido = abs($meta['peso_inicial'] - $peso_atual);
    $progresso_pct = $total_jornada > 0 ? min(100, ($batido / $total_jornada) * 100) : 0;
    // Assume-se que FitnessHelper existe em functions.php
    $projecao = FitnessHelper::projetarEvolucao($meta['objetivo'], $peso_atual, $meta['peso_meta']);
}

//  BLOQUEIO DE CHECK-IN (DOMINGO)
$ultimo_reg = !empty($historico_peso) ? end($historico_peso)['data_registro'] : null;
$domingo_alvo = date('Y-m-d', strtotime('last sunday'));
$bloquear_por_peso = (!$ultimo_reg || date('Y-m-d', strtotime($ultimo_reg)) < $domingo_alvo);

//  CÁLCULOS CALÓRICOS
$kcal_basal = $aluno['meta_kcal_diaria'] ?? 2000;

$stmt = $pdo->prepare("SELECT SUM(kcal_gasta) FROM historico_atividades WHERE usuario_id=? AND DATE(data_registro)=?");
$stmt->execute([$user_id, $hoje]);
$kcal_gasta_hoje = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT SUM(kcal) FROM consumo_diario WHERE usuario_id=? AND data_registro=?");
$stmt->execute([$user_id, $hoje]);
$kcal_consumida_hoje = $stmt->fetchColumn() ?? 0;

$deficit_meta = 500; 
$kcal_permitido = ($kcal_basal + $kcal_gasta_hoje) - $deficit_meta;
$deficit_real_hoje = ($kcal_basal + $kcal_gasta_hoje) - $kcal_consumida_hoje;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Dashboard | Fitness Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --accent: #f1c40f; --bg: #050a0c; --card: rgba(255,255,255,0.05); }
        body { margin:0; font-family:'Inter',sans-serif; background:radial-gradient(circle at top, #0f1c22, var(--bg)); color:white; min-height:100vh; }
        .container { max-width:900px; margin:auto; padding:20px; }
        .card { background:var(--card); border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:20px; backdrop-filter:blur(10px); margin-bottom:20px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; }
        input { width:100%; padding:12px; margin:8px 0; border-radius:10px; background:#000; border:1px solid #333; color:white; box-sizing:border-box; }
        button { width:100%; background:var(--primary); border:none; padding:13px; border-radius:10px; font-weight:800; cursor:pointer; transition:.3s; }
        .progress-bar { height:10px; background:#111; border-radius:10px; overflow:hidden; margin:15px 0 5px 0; }
        .progress-fill { height:100%; background:linear-gradient(90deg, var(--primary), #27ae60); width:<?= $progresso_pct ?>%; }
        .val-up { color:var(--primary); } .val-down { color:#e74c3c; }
        .nav-link { color: var(--accent); text-decoration: none; font-weight: 600; font-size: 0.9em; }
        .overlay { position:fixed; inset:0; background:rgba(0,0,0,0.95); display:flex; justify-content:center; align-items:center; z-index:999; backdrop-filter:blur(10px); }
    </style>
</head>
<body>

<?php if($bloquear_por_peso): ?>
<div class="overlay">
    <div class="card" style="max-width:350px; text-align:center;">
        <h2>⚖️ Check-in Semanal</h2>
        <p>Atualize seu peso para continuar.</p>
        <input id="pesoSemanal" type="number" step="0.1" placeholder="Peso atual (kg)">
        <button onclick="enviar('salvar_peso.php', 'peso', 'pesoSemanal')">Atualizar Agora</button>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2 style="margin:0;">Olá, <?= htmlspecialchars(explode(' ', $aluno['nome'])[0]) ?> 💎</h2>
            <a href="analytics_vip.php" class="nav-link">Ver Relatórios Detalhados →</a>
        </div>
        <div style="text-align:right">
            <span style="font-size:1.5em; font-weight:800;"><?= $peso_atual ?>kg</span>
        </div>
    </header>

    <div class="grid">
        <div class="card">
            <h3>🎯 Sua Meta</h3>
            <div class="progress-bar"><div class="progress-fill"></div></div>
            <div style="display:flex; justify-content:space-between; font-size:0.85em; margin-bottom:15px;">
                <span>Progresso: <?= round($progresso_pct) ?>%</span>
                <span>Faltam: <?= $projecao['semanas'] ?? '?' ?> sem.</span>
            </div>
            <input id="pesoMeta" type="number" step="0.1" value="<?= $meta['peso_meta'] ?? '' ?>" placeholder="Novo peso alvo">
            <button onclick="enviar('salvar_meta.php', 'peso_meta', 'pesoMeta')" style="background:rgba(255,255,255,0.1); color:white;">Ajustar Alvo</button>
        </div>

        <div class="card">
            <h3>🔥 Calorias de Hoje</h3>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; text-align:center;">
                <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:10px;">
                    <small>Consumo</small><br><strong><?= $kcal_consumida_hoje ?></strong>
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:10px;">
                    <small>Gasto (Extra)</small><br><strong><?= $kcal_gasta_hoje ?></strong>
                </div>
            </div>
            <p style="text-align:center; margin:15px 0 5px 0;">Limite Sugerido: <b style="color:var(--accent)"><?= round($kcal_permitido) ?> kcal</b></p>
            <p style="text-align:center; font-size:0.85em;">Déficit Atual: <b class="<?= $deficit_real_hoje > 0 ? 'val-up' : 'val-down' ?>"><?= round($deficit_real_hoje) ?> kcal</b></p>
        </div>
    </div>

    <div class="card">
        <h3>🍽️ Registrar Refeição</h3>
        <div style="display:flex; gap:10px;">
            <input id="kcalInput" type="number" placeholder="Calorias (ex: 450)">
            <button style="width:140px;" onclick="enviar('salvar_kcal.php', 'kcal', 'kcalInput')">Adicionar</button>
        </div>
    </div>
</div>

<script>
async function enviar(url, param, inputId) {
    const val = document.getElementById(inputId).value;
    if(!val) return alert("Por favor, preencha o valor!");
    const res = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `${param}=${encodeURIComponent(val)}`
    });
    if(res.ok) { location.reload(); } else { alert("Erro ao salvar."); }
}
</script>
</body>
</html>