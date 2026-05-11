<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'VIP') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');

// 👤 1. DADOS DO ALUNO E CÁLCULO BASAL
$stmt = $pdo->prepare("SELECT *, (YEAR(CURDATE()) - YEAR(data_nascimento)) as idade FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();

$peso = $aluno['peso_atual'];
$altura = $aluno['altura'];
$idade = $aluno['idade'];
$nivel_ativ = $aluno['nivel_atividade'] ?? 1.375; 

// Mifflin-St Jeor
$tmb = (10 * $peso) + (6.25 * $altura) - (5 * $idade) + 5;
$manutencao = $tmb * $nivel_ativ;

// METAS DIÁRIAS FIXAS
$meta_kcal_base = 2300; 
$meta_prot_g = 185;
$meta_gord_g = 70; 
$meta_fibra_g = 35; 
$meta_agua_ml = round($peso * 35); 
$meta_carb_g = round(($meta_kcal_base - ($meta_prot_g * 4) - ($meta_gord_g * 9)) / 4);

// 🎯 2. META E PREVISÃO (RITMO REALISTA)
$stmt = $pdo->prepare("SELECT * FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$meta = $stmt->fetch();

$eliminados = 0;
$ritmo_semanal = "0.0";
$previsao_chegada = "Aguardando dados...";

if ($meta && $meta['peso_meta'] > 0) {
    $peso_meta = $meta['peso_meta'];
    $peso_inicial = $meta['peso_inicial'];
    $data_inicio = new DateTime($meta['data_inicio']);
    $hoje_dt = new DateTime();
    
    // Total eliminado desde o começo
    $eliminados = $peso_inicial - $peso;
    $restante = $peso - $peso_meta;
    
    // Dias de jornada (mínimo 1 para evitar divisão por zero)
    $dias_jornada = max(1, $data_inicio->diff($hoje_dt)->days);
    
    // Cálculo do Ritmo: 
    // Se você perdeu peso, calculamos a média semanal real.
    if ($eliminados > 0) {
        $ritmo_diario = $eliminados / $dias_jornada;
        $ritmo_semanal_num = $ritmo_diario * 7;
        $ritmo_semanal = number_format($ritmo_semanal_num, 1);
        
        if ($restante > 0) {
            // Previsão baseada no ritmo real
            $dias_para_meta = ceil($restante / $ritmo_diario);
            
            // Limitador de segurança: se o ritmo for muito baixo, não prever mais de 2 anos
            if ($dias_para_meta > 730) {
                $previsao_chegada = "Longo prazo";
            } else {
                $data_estimada = clone $hoje_dt;
                $data_estimada->modify("+$dias_para_meta days");
                $previsao_chegada = $data_estimada->format('d/m/Y');
            }
        } else {
            $previsao_chegada = "Meta Alcançada! 🎉";
        }
    } else {
        $ritmo_semanal = "0.0";
        $previsao_chegada = "Calibrando ritmo...";
    }
}

// 📅 3. LOGS E FILTROS (DOMINGO COMO 1º DIA)
$filtro = $_GET['filtro'] ?? 'hoje';
if ($filtro == 'semana') { $where = "YEARWEEK(data_registro, 0) = YEARWEEK(CURDATE(), 0)"; $dias_count = 7; }
elseif ($filtro == 'mes') { $where = "MONTH(data_registro) = MONTH(CURDATE()) AND YEAR(data_registro) = YEAR(CURDATE())"; $dias_count = 30; }
else { $where = "DATE(data_registro) = CURDATE()"; $dias_count = 1; }

// Busca Consumo
$stmt_diario = $pdo->prepare("SELECT DATE(data_registro) as data, SUM(kcal) as total_kcal, SUM(proteina) as prot, SUM(carbo) as carb, SUM(gordura) as gord, SUM(fibra) as fibra FROM consumo_diario WHERE usuario_id = ? AND $where GROUP BY DATE(data_registro) ORDER BY data ASC");
$stmt_diario->execute([$user_id]);
$logs_consumo_grafico = $stmt_diario->fetchAll(PDO::FETCH_ASSOC);

// Busca Treino
$stmt_treino_dia = $pdo->prepare("SELECT DATE(data_registro) as data, SUM(kcal_gasta) as total_gasta, SUM(duracao_segundos) as segundos FROM historico_atividades WHERE usuario_id = ? AND $where GROUP BY DATE(data_registro)");
$stmt_treino_dia->execute([$user_id]);
$logs_treino_grafico = $stmt_treino_dia->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

$total_consumo = 0; $total_prot = 0; $total_carb = 0; $total_gord = 0; $total_fibra = 0;
foreach($logs_consumo_grafico as $l) {
    $total_consumo += $l['total_kcal'];
    $total_prot += $l['prot'];
    $total_carb += $l['carb'];
    $total_gord += $l['gord'];
    $total_fibra += ($l['fibra'] ?? 0);
}

$kcal_treino_total = 0; $tempo_treino_total = 0;
foreach($logs_treino_grafico as $t) { 
    $kcal_treino_total += $t['total_gasta']; 
    $tempo_treino_total += round($t['segundos'] / 60);
}

$meta_periodo = ($meta_kcal_base * $dias_count) + $kcal_treino_total;
$saldo_final = $meta_periodo - $total_consumo;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Analytics | GSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #050a0c; --card: rgba(255,255,255,0.05); --border: rgba(255,255,255,0.1); --blue: #3498db; --yellow: #f1c40f; --orange: #e67e22; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; margin: 0; padding: 20px; padding-bottom: 50px; }
        .container { max-width: 1000px; margin: auto; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 20px; backdrop-filter: blur(10px); }
        
        /* Estilo do Botão Retornar */
        .btn-return { display: inline-flex; align-items: center; text-decoration: none; color: white; background: var(--card); border: 1px solid var(--border); padding: 8px 16px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; transition: 0.3s; margin-bottom: 15px; }
        .btn-return:hover { background: var(--primary); color: #000; border-color: var(--primary); }

        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-box { text-align: center; padding: 15px; border-radius: 15px; background: rgba(0,0,0,0.2); border: 1px solid transparent; }
        .stat-val { display: block; font-size: 1.6rem; font-weight: 900; color: var(--primary); }
        .stat-label { font-size: 0.7rem; color: #8899a6; text-transform: uppercase; font-weight: bold; }
        
        .filter-nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-nav a { text-decoration: none; color: #8899a6; padding: 8px 15px; border-radius: 10px; background: var(--card); font-size: 0.8rem; border: 1px solid var(--border); }
        .filter-nav a.active { background: var(--primary); color: #000; font-weight: bold; border-color: var(--primary); }
        
        input[type="text"] { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid var(--border); background: rgba(0,0,0,0.3); color: white; outline: none; box-sizing: border-box; }
        .dia-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
        .dia-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 12px; border-radius: 12px; text-align: center; cursor: pointer; transition: 0.3s; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        
        .progress-container { background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; margin-top: 8px; overflow: hidden; }
        .progress-bar { height: 100%; transition: 0.5s; }
        
        #box-detalhe-treino { display: none; background: linear-gradient(90deg, rgba(46,204,113,0.2), transparent); border-left: 4px solid var(--primary); padding: 15px; border-radius: 0 15px 15px 0; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    
    <a href="painel_vip.php" class="btn-return">
        <span style="margin-right:8px;">←</span> PAINEL VIP
    </a>

    <header style="margin-bottom: 25px;">
        <h1 style="margin:0; font-weight:900;">DASHBOARD <span style="color:var(--primary)">VIP</span></h1>
        <div style="display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap;">
            <div><span class="stat-label">Basal:</span> <b style="color:white"><?= round($tmb) ?> kcal</b></div>
            <div><span class="stat-label">Manutenção:</span> <b style="color:white"><?= round($manutencao) ?> kcal</b></div>
            <div><span class="stat-label">Meta Água:</span> <b style="color:var(--blue)"><?= number_format($meta_agua_ml/1000, 1) ?>L/dia</b></div>
        </div>
    </header>

    <div class="filter-nav">
        <a href="?filtro=hoje" class="<?= $filtro == 'hoje' ? 'active' : '' ?>">HOJE</a>
        <a href="?filtro=semana" class="<?= $filtro == 'semana' ? 'active' : '' ?>">SEMANA</a>
        <a href="?filtro=mes" class="<?= $filtro == 'mes' ? 'active' : '' ?>">MÊS</a>
    </div>

    <div class="card">
        <h3 style="margin-top:0; color:var(--primary); font-size: 1rem;">REGISTRAR ALIMENTO (API)</h3>
        <input type="text" id="foodSearch" placeholder="Digite o nome do alimento..." autocomplete="off">
        <div id="apiResults" style="max-height: 200px; overflow-y: auto;"></div>
    </div>

    <div class="card" style="border: 1px solid var(--primary); background: linear-gradient(180deg, rgba(46,204,113,0.05), transparent);">
        <div class="grid-stats">
            <div class="stat-box"><span class="stat-label">Meta Período</span><span class="stat-val"><?= number_format($meta_periodo, 0, ',', '.') ?></span></div>
            <div class="stat-box"><span class="stat-label">Ingerido Total</span><span class="stat-val" style="color:#fff;"><?= number_format($total_consumo, 0, ',', '.') ?></span></div>
            <div class="stat-box" style="background: rgba(46, 204, 113, 0.1);"><span class="stat-label">Saldo Líquido</span><span class="stat-val"><?= number_format($saldo_final, 0, ',', '.') ?></span></div>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--blue);">
        <h4 style="margin:0 0 15px 0; font-size:0.8rem; color:var(--blue);">PERFORMANCE DE TREINO</h4>
        <div class="grid-stats">
            <div class="stat-box">
                <span class="stat-label">Tempo Ativo</span>
                <span class="stat-val" style="color:white;"><?= $tempo_treino_total ?> <small style="font-size:0.8rem;">min</small></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Kcal Queimadas</span>
                <span class="stat-val" style="color:var(--blue);"><?= number_format($kcal_treino_total, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <h4 style="margin:0 0 15px 0; font-size:0.9rem; color:#8899a6;">DISTRIBUIÇÃO DE MACRONUTRIENTES</h4>
        <div class="grid-stats">
            <div class="stat-box">
                <span class="stat-label" style="color:var(--blue);">Proteínas</span>
                <span style="display:block; font-weight:900;"><?= round($total_prot) ?> / <?= $meta_prot_g * $dias_count ?>g</span>
                <div class="progress-container"><div class="progress-bar" style="width:<?= min(($total_prot/($meta_prot_g * $dias_count))*100, 100) ?>%; background:var(--blue);"></div></div>
            </div>
            <div class="stat-box">
                <span class="stat-label" style="color:var(--orange);">Carboidratos</span>
                <span style="display:block; font-weight:900;"><?= round($total_carb) ?> / <?= $meta_carb_g * $dias_count ?>g</span>
                <div class="progress-container"><div class="progress-bar" style="width:<?= min(($total_carb/($meta_carb_g * $dias_count))*100, 100) ?>%; background:var(--orange);"></div></div>
            </div>
            <div class="stat-box">
                <span class="stat-label" style="color:var(--yellow);">Gorduras</span>
                <span style="display:block; font-weight:900;"><?= round($total_gord) ?> / <?= $meta_gord_g * $dias_count ?>g</span>
                <div class="progress-container"><div class="progress-bar" style="width:<?= min(($total_gord/($meta_gord_g * $dias_count))*100, 100) ?>%; background:var(--yellow);"></div></div>
            </div>
            <div class="stat-box">
                <span class="stat-label" style="color:var(--primary);">Fibras</span>
                <span style="display:block; font-weight:900;"><?= round($total_fibra) ?> / <?= $meta_fibra_g * $dias_count ?>g</span>
                <div class="progress-container"><div class="progress-bar" style="width:<?= min(($total_fibra/($meta_fibra_g * $dias_count))*100, 100) ?>%; background:var(--primary);"></div></div>
            </div>
        </div>
    </div>

    <div class="grid-stats">
        <div class="card">
            <h4 style="margin:0 0 10px 0; font-size: 0.9rem;">ELIMINADOS</h4>
            <span style="font-size:2rem; font-weight:900; color:var(--primary);"><?= number_format($eliminados, 1) ?>kg</span>
            <p style="margin:0; font-size:0.7rem; color:#8899a6;">Desde <?= $data_inicio->format('d/m/Y') ?></p>
        </div>
        <div class="card">
            <h4 style="margin:0 0 10px 0; font-size: 0.9rem;">RITMO E CHEGADA</h4>
            <span style="display:block; font-weight:bold; color:var(--blue);"><?= $ritmo_semanal ?> kg/semana</span>
            <span class="stat-label">Previsão Meta:</span> 
            <b style="color:var(--primary); font-size:1.2rem; display:block; margin-top:5px;"><?= $previsao_chegada ?></b>
        </div>
    </div>

    <?php if($filtro != 'hoje'): ?>
    <div class="card">
        <h4 style="margin:0 0 10px 0;">HISTÓRICO DIÁRIO</h4>
        <div class="dia-grid">
            <?php foreach($logs_consumo_grafico as $log): ?>
            <div class="dia-card" onclick="carregarTudoDoDia('<?= $log['data'] ?>', '<?= date('d/m', strtotime($log['data'])) ?>')">
                <span class="stat-label"><?= date('d/m', strtotime($log['data'])) ?></span>
                <span style="display:block; font-weight:800;"><?= round($log['total_kcal']) ?> <small>kcal</small></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div id="box-detalhe-treino">
        <div style="display:flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin:0; color:var(--primary);">DETALHES DO DIA SELECIONADO</h4>
                <p style="margin:0; font-size:0.8rem;" id="txt-info-treino">Carregando...</p>
            </div>
            <div id="status-meta-dia" style="font-weight:900; font-size:0.8rem;"></div>
        </div>
    </div>

    <div class="card">
        <h4 style="margin:0;">Refeições de <span id="data-titulo" style="color:var(--primary)">Hoje</span></h4>
        <table>
            <thead><tr style="color:#8899a6; font-size:0.7rem;"><th>ALIMENTO</th><th>QTD</th><th>KCAL</th><th>✕</th></tr></thead>
            <tbody id="corpo-tabela"></tbody>
        </table>
    </div>
</div>

<script>
// Scripts mantidos conforme sua versão anterior
let searchTimer;
document.getElementById('foodSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    let q = this.value;
    if(q.length < 3) return;
    searchTimer = setTimeout(() => {
        fetch('buscar_alimento.php?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                let html = '';
                if(data.hints) {
                    data.hints.slice(0, 5).forEach(item => {
                        let f = item.food;
                        html += `<div style="padding:10px; border-bottom:1px solid var(--border); cursor:pointer;" onclick="registrar('${f.label}', ${f.nutrients.ENERC_KCAL}, ${f.nutrients.PROCNT}, ${f.nutrients.CHOCDF}, ${f.nutrients.FAT}, ${f.nutrients.FIBTG || 0})">
                            <b>${f.label}</b><br><small>${Math.round(f.nutrients.ENERC_KCAL)} kcal (100g)</small>
                        </div>`;
                    });
                }
                document.getElementById('apiResults').innerHTML = html;
            });
    }, 500);
});

function registrar(n, k, p, c, g, f) {
    let qtd = prompt(`Gramas de ${n}:`, "100");
    if(qtd) window.location.href = `salvar_consumo.php?nome=${encodeURIComponent(n)}&g=${qtd}&kcal=${k}&prot=${p}&carb=${c}&gord=${g}&fibra=${f}`;
}

function carregarTudoDoDia(dataIso, dataBr) {
    document.getElementById('data-titulo').innerText = dataBr;
    fetch('buscar_refeicoes_dia.php?data=' + dataIso)
        .then(res => res.json())
        .then(refeicoes => {
            let html = '';
            refeicoes.forEach(r => {
                html += `<tr><td>${r.alimento_nome}</td><td>${Math.round(r.quantidade_g)}g</td><td>${Math.round(r.kcal)}</td><td><a href="excluir_alimento.php?id=${r.id}" style="color:red; text-decoration:none;">✕</a></td></tr>`;
            });
            document.getElementById('corpo-tabela').innerHTML = html || '<tr><td colspan="4">Nenhum registro.</td></tr>';
        });

    fetch('buscar_status_dia.php?data=' + dataIso)
        .then(res => res.json())
        .then(res => {
            const box = document.getElementById('box-detalhe-treino');
            box.style.display = 'block';
            if(res.treinou) {
                document.getElementById('txt-info-treino').innerHTML = `<b>${res.tempo_min} min</b> de treino | Gasto de <b>${res.kcal_treino} kcal</b>`;
            } else {
                document.getElementById('txt-info-treino').innerText = "Sem treino registrado.";
            }
            const statusMeta = document.getElementById('status-meta-dia');
            statusMeta.innerHTML = res.saldo_dia >= 0 ? `<span style="color:var(--primary)">META OK</span>` : `<span style="color:red">EXCEDEU</span>`;
        });
}

window.onload = () => carregarTudoDoDia('<?= $hoje ?>', 'Hoje');
</script>
</body>
</html>