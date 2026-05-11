<?php
session_start();
require 'db.php';

// 🔐 PROTEÇÃO E SEGURANÇA
if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'VIP') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');

// 1. Dados do Aluno (Puxando peso_atual da tabela usuarios)
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();

// 2. Verificar Validade VIP
if (!$aluno || $aluno['vip_ativo'] == 0 || $hoje > $aluno['vip_expiracao']) {
    echo "<body style='background:#050a0c; color:white; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh;'>
            <div style='text-align:center;'>
                <h2 style='color:#e74c3c;'>🚫 Acesso VIP Expirado</h2>
                <p>Renove sua assinatura para acessar o painel.</p>
                <a href='checkout.php' style='color:#2ecc71; text-decoration:none; font-weight:bold; border:1px solid #2ecc71; padding:10px 20px; border-radius:8px;'>Renovar Agora</a>
            </div>
          </body>";
    exit;
}

// 3. Procurar Meta Ativa
$stmt = $pdo->prepare("SELECT * FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$meta = $stmt->fetch();

// 4. Histórico de Progresso para o Gráfico
$stmt = $pdo->prepare("SELECT peso, data_registro FROM progresso_usuario WHERE usuario_id = ? ORDER BY data_registro ASC LIMIT 10");
$stmt->execute([$user_id]);
$historico = $stmt->fetchAll();

$pesosGrafico = array_column($historico, 'peso');
$labelsDatas = [];
foreach ($historico as $h) {
    $labelsDatas[] = date('d/m', strtotime($h['data_registro']));
}

// 5. Cálculos de Performance
// Se não tiver registro no histórico hoje, o norte é o peso_atual da tabela usuários
$peso_agora = $aluno['peso_atual'] ?? 0;
$dias_vip = floor((strtotime($aluno['vip_expiracao']) - strtotime($hoje)) / 86400);

// 6. Lógica de Nutrição e Projeção (Sincronizada com o Analytics)
$tem_meta = ($meta && $meta['peso_meta'] > 0);
$proteina = 0;
$texto_objetivo = "Defina uma meta";

if ($tem_meta) {
    $objetivo = $meta['objetivo']; // 'emagrecer' ou 'massa'
    $peso_meta = $meta['peso_meta'];
    $diferenca = $peso_agora - $peso_meta;

    // Proteína sugerida
    $multiplicador = ($objetivo == 'massa') ? 2.2 : 2.0;
    $proteina = $peso_agora * $multiplicador;

    if ($diferenca > 0) {
        $texto_objetivo = "Faltam " . number_format(abs($diferenca), 1) . "kg para secar";
    } else {
        $texto_objetivo = "Meta atingida! Manutenção ativa.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel VIP | Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #2ecc71; --bg: #050a0c; --card: rgba(255,255,255,0.05); }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: var(--card); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 20px; backdrop-filter: blur(10px); }
        .stat-val { font-size: 1.8em; font-weight: 900; color: var(--primary); display: block; }
        .btn-acao { background: var(--primary); color: #000; text-decoration: none; padding: 12px; border-radius: 8px; display: block; text-align: center; font-weight: bold; margin-top: 15px; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; }
        .badge { background: rgba(46, 204, 113, 0.2); color: var(--primary); padding: 5px 10px; border-radius: 5px; font-size: 0.8em; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <div>
            <h1 style="margin:0;">Olá, <?= explode(' ', $aluno['nome'])[0] ?>! 👋</h1>
            <p style="opacity:0.6; margin:5px 0;">Seu Plano VIP expira em <span class="badge"><?= $dias_vip ?> dias</span></p>
        </div>
        <a href="analytics_vip.php" class="card" style="text-decoration:none; color:white; padding:10px 20px;">📊 Ver Analytics</a>
    </div>

    <div class="grid">
        <div class="card">
            <small style="opacity:0.6;">PESO ATUAL</small>
            <span class="stat-val"><?= number_format($peso_agora, 1) ?> kg</span>
            <p style="font-size:0.9em; margin-top:10px;"><?= $texto_objetivo ?></p>
            <a href="progresso.php" class="btn-acao">Atualizar Peso Hoje</a>
        </div>

        <div class="card">
            <small style="opacity:0.6;">META DE PROTEÍNA DIA</small>
            <span class="stat-val"><?= number_format($proteina, 0) ?>g</span>
            <p style="font-size:0.8em; opacity:0.6;">Calculado com base no seu objetivo de <?= $meta['objetivo'] ?? 'manutenção' ?>.</p>
            <a href="dieta.php" class="btn-acao" style="background:none; border:1px solid var(--primary); color:var(--primary);">Ver Dieta VIP</a>
        </div>

        <div class="card">
            <h3 style="margin-top:0; font-size:0.9em; opacity:0.6;">EVOLUÇÃO RECENTE</h3>
            <canvas id="graficoPeso" height="150"></canvas>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('graficoPeso').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsDatas) ?>,
        datasets: [{
            label: 'Peso (kg)',
            data: <?= json_encode($pesosGrafico) ?>,
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { 
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#fff' } },
            x: { grid: { display: false }, ticks: { color: '#fff' } }
        }
    }
});
</script>

</body>
</html>