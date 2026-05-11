<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require 'db.php';
require 'alimentacao_vip.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'VIP') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');

$stmt = $pdo->prepare("SELECT *, (YEAR(CURDATE()) - YEAR(data_nascimento)) as idade FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$aluno = $stmt->fetch();

$stmt_meta = $pdo->prepare("SELECT ritmo_semanal, peso_meta FROM metas_usuario WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt_meta->execute([$user_id]);
$config_vip = $stmt_meta->fetch();

$ritmo = $config_vip['ritmo_semanal'] ?? 1.0; 
$peso_alvo = $config_vip['peso_meta'] ?? 80.0;

$peso   = $aluno['peso_atual'];
$altura = $aluno['altura']; 
$idade  = $aluno['idade'];
$tmb_unitária = round(66 + (13.7 * $peso) + (5 * $altura) - (6.8 * $idade));

$plano = calcularPlanoEmagrecimento($user_id, $pdo);
$meta_kcal_base = $tmb_unitária; 
$meta_prot_g    = $plano['prot_g'];
$meta_carb_g    = $plano['carb_g'];
$meta_gord_g    = $plano['gord_g'];
$meta_fibra_g   = $plano['fibra_g'];
$meta_agua_L    = $plano['agua_L'];

$filtro = $_GET['filtro'] ?? 'hoje';
$dias_count = ($filtro == 'semana') ? 7 : (($filtro == 'mes') ? 30 : 1);

$where = ($filtro == 'semana') ? "YEARWEEK(data_registro, 0) = YEARWEEK(CURDATE(), 0)" : 
         (($filtro == 'mes') ? "MONTH(data_registro) = MONTH(CURDATE()) AND YEAR(data_registro) = YEAR(CURDATE())" : 
         "DATE(data_registro) = CURDATE()");

$stmt_cons = $pdo->prepare("SELECT SUM(kcal) as k, SUM(proteina) as p, SUM(carbo) as c, SUM(gordura) as g, SUM(fibra) as f FROM alimentos WHERE usuario_id = ? AND $where");
$stmt_cons->execute([$user_id]);
$consumo = $stmt_cons->fetch();

$stmt_lista = $pdo->prepare("SELECT id, nome as alimento, porcao_referencia as quantidade, kcal, proteina, carbo, gordura, fibra FROM alimentos WHERE usuario_id = ? AND $where ORDER BY id DESC");
$stmt_lista->execute([$user_id]);
$lista_alimentos = $stmt_lista->fetchAll();

$stmt_tr = $pdo->prepare("SELECT SUM(kcal_gasta) as gasta, SUM(duracao_segundos) as segundos FROM historico_atividades WHERE usuario_id = ? AND $where");
$stmt_tr->execute([$user_id]);
$treino = $stmt_tr->fetch();

$kcal_treino = $treino['gasta'] ?? 0;
$segundos_totais = $treino['segundos'] ?? 0;

$horas = floor($segundos_totais / 3600);
$minutos = floor(($segundos_totais % 3600) / 60);
$tempo_formatado = ($horas > 0) ? "{$horas}h {$minutos}m" : "{$minutos}m";

$gasto_basal_periodo = $tmb_unitária * $dias_count;
$gasto_total_periodo = $gasto_basal_periodo + $kcal_treino;
$ingerido = ($consumo['k'] ?? 0);
$balanco_real = $ingerido - $gasto_total_periodo;

$objetivo_atual = $aluno['objetivo'] ?? '';

$is_recomposicao = (stripos($objetivo_atual, 'Massa') !== false && stripos($objetivo_atual, 'Emagrecimento') !== false);

if ($is_recomposicao) {
    $ajuste_diario = 350; 
    $meta_ingestao_periodo = $gasto_total_periodo - ($ajuste_diario * $dias_count);
    $label_meta = "Meta Recomposição";
    $txt_footer_meta = "Ganho de Massa + Queima";
    $status_energia = ($balanco_real >= 0) ? "SUPERÁVIT" : "DÉFICIT";
    $cor_status = "var(--blue)";
} elseif (stripos($objetivo_atual, 'Massa') !== false) {
    $superavit_dia = 400; 
    $meta_ingestao_periodo = $gasto_total_periodo + ($superavit_dia * $dias_count);
    $label_meta = "Meta Ingestão";
    $txt_footer_meta = "Foco em Ganho Limpo";
    $status_energia = ($balanco_real >= 0) ? "SUPERÁVIT" : "DÉFICIT";
    $cor_status = ($balanco_real < 0) ? "#e74c3c" : "var(--primary)";
} else {
    $ajuste_diario = ($ritmo * 7700) / 7;
    $meta_ingestao_periodo = $gasto_total_periodo - ($ajuste_diario * $dias_count);
    $label_meta = "Limite Ingestão";
    $txt_footer_meta = "Ritmo: {$ritmo}kg/sem";
    $status_energia = ($balanco_real >= 0) ? "SUPERÁVIT" : "DÉFICIT";
    $cor_status = ($balanco_real > 0) ? "#e74c3c" : "var(--primary)";
}

$saldo_final = $meta_ingestao_periodo - $ingerido;

$peso_restante = abs($peso - $peso_alvo);
$semanas_restantes = ($ritmo > 0) ? ($peso_restante / $ritmo) : 0;
$data_prevista = date('d/m/Y', strtotime("+" . round($semanas_restantes * 7) . " days"));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Analytics | <?= htmlspecialchars($aluno['nome']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ecc71; --bg: #050a0c; --card: rgba(255,255,255,0.05); --border: rgba(255,255,255,0.1); --blue: #3498db; --orange: #e67e22; --yellow: #f1c40f; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; padding: 20px; margin:0; }
        .container { max-width: 1000px; margin: auto; padding-bottom: 100px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 20px; backdrop-filter: blur(10px); }
        
        .btn-back { display: inline-flex; align-items: center; justify-content: center; background: var(--card); border: 1px solid var(--border); color: var(--primary); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-size: 0.75rem; font-weight: 900; transition: 0.3s; margin-bottom: 20px; text-transform: uppercase; gap: 8px; }
        .btn-back:hover { background: var(--primary); color: #000; }

        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-box { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; text-align: center; border: 1px solid var(--border); }
        .stat-val { display: block; font-size: 1.8rem; font-weight: 900; color: var(--primary); }
        .stat-label { font-size: 0.7rem; color: #8899a6; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        
        .balanco-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .balanco-item { flex: 1; min-width: 120px; }
        .balanco-divisor { font-size: 1.5rem; color: var(--border); }
        .balanco-resultado { flex: 2; min-width: 250px; text-align: right; padding-left: 20px; border-left: 2px solid var(--border); }
        
        @media (max-width: 600px) {
            .balanco-container { flex-direction: column; text-align: center; }
            .balanco-resultado { border-left: none; border-top: 1px solid var(--border); padding-left: 0; padding-top: 15px; text-align: center; }
            .balanco-divisor { transform: rotate(90deg); margin: -5px 0; }
        }

        .btn-add { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #000; box-shadow: 0 10px 20px rgba(46,204,113,0.3); z-index: 1000; border: none; cursor: pointer; transition: 0.3s; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 2000; padding: 20px; }
        .modal-content { max-width: 500px; margin: 20px auto; background: #111; padding: 25px; border-radius: 25px; border: 1px solid var(--border); }
        #search-input { width: 100%; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 12px; color: white; font-size: 1rem; outline: none; margin-bottom: 20px; }
        #results { max-height: 60vh; overflow-y: auto; scrollbar-width: none; }
        .food-item { padding: 15px; border-bottom: 1px solid var(--border); cursor: pointer; transition: 0.2s; border-radius: 10px; margin-bottom: 5px; }
        
        .filter-nav { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; scrollbar-width: none; }
        .filter-nav a { padding: 10px 20px; background: var(--card); color: #8899a6; text-decoration: none; border-radius: 10px; font-size: 0.8rem; border: 1px solid var(--border); white-space: nowrap; }
        .filter-nav a.active { background: var(--primary); color: #000; font-weight: bold; border-color: var(--primary); }
        
        .progress-bg { background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .progress-fill { height: 100%; transition: 1s ease-in-out; }
        
        .vip-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .vip-table { width: 100%; border-collapse: collapse; min-width: 480px; } 
        .vip-table th { text-align: left; padding: 8px; color: var(--primary); font-size: 0.6rem; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .vip-table td { padding: 10px 8px; border-bottom: 1px solid var(--border); font-size: 0.8rem; }
        
        @media (max-width: 600px) {
            body { padding: 12px; }
            .grid-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-val { font-size: 1.4rem; }
            .stat-box { padding: 10px; }
            header h1 { font-size: 1.6rem !important; }
        }
        .btn-del { color: #e74c3c; text-decoration: none; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="container">
    <a href="painel_vip.php" class="btn-back">
        <span>⬅</span> PAINEL VIP
    </a>

<header style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin:0; font-weight:900; font-size: 1.8rem; letter-spacing: -1px;">CENTRAL <span style="color:var(--primary)">VIP</span></h1>
            <div style="text-align: right;">
                <span class="stat-label" style="font-size:0.5rem;">Previsão</span>
                <div style="color: var(--primary); font-weight: 900; font-size: 0.9rem;"><?= $data_prevista ?></div>
            </div>
        </div>
        <p style="margin:5px 0 0 0; color:#8899a6; font-size: 0.75rem; border-top: 1px solid var(--border); padding-top: 5px;">
            <b><?= strtoupper($aluno['objetivo'] ?? '') ?></b> • <?= $aluno['peso_atual'] ?>kg
        </p>
    </header>

    <nav class="filter-nav">
        <a href="?filtro=hoje" class="<?= $filtro == 'hoje' ? 'active' : '' ?>">HOJE</a>
        <a href="?filtro=semana" class="<?= $filtro == 'semana' ? 'active' : '' ?>">ESTA SEMANA</a>
        <a href="?filtro=mes" class="<?= $filtro == 'mes' ? 'active' : '' ?>">ESTE MÊS</a>
    </nav>

    <div class="card" style="border: 1px solid var(--primary); background: linear-gradient(180deg, rgba(46,204,113,0.08), transparent);">
        <div class="grid-stats">
            <div class="stat-box"><span class="stat-label">Gasto Total</span><span class="stat-val"><?= number_format($gasto_total_periodo, 0, ',', '.') ?></span></div>
            <div class="stat-box"><span class="stat-label">Ingerido</span><span class="stat-val" style="color:white"><?= number_format($consumo['k'] ?? 0, 0, ',', '.') ?></span></div>
            <div class="stat-box" style="background: rgba(46,204,113,0.1);"><span class="stat-label">Saldo P/ Meta</span><span class="stat-val"><?= number_format($saldo_final, 0, ',', '.') ?></span></div>
        </div>
    </div>

    <div class="card">
        <div class="grid-stats">
            <?php 
            $macs = [
                ['L' => 'Proteínas', 'A' => $consumo['p'] ?? 0, 'M' => $meta_prot_g * $dias_count, 'C' => 'var(--blue)'],
                ['L' => 'Carbos', 'A' => $consumo['c'] ?? 0, 'M' => $meta_carb_g * $dias_count, 'C' => 'var(--orange)'],
                ['L' => 'Gorduras', 'A' => $consumo['g'] ?? 0, 'M' => $meta_gord_g * $dias_count, 'C' => 'var(--yellow)'],
                ['L' => 'Fibras', 'A' => $consumo['f'] ?? 0, 'M' => $meta_fibra_g * $dias_count, 'C' => 'var(--primary)']
            ];
            foreach($macs as $m): 
                $p = min(($m['A'] / max(1, $m['M'])) * 100, 100);
            ?>
            <div class="stat-box">
                <span class="stat-label" style="color:<?= $m['C'] ?>"><?= $m['L'] ?></span>
                <div style="font-weight:900; margin:8px 0;"><?= round($m['A']) ?>g <small style="color:#555">/<?= round($m['M']) ?>g</small></div>
                <div class="progress-bg"><div class="progress-fill" style="width:<?= $p ?>%; background:<?= $m['C'] ?>;"></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card" style="margin-top: 10px; background: rgba(0,0,0,0.3); border: 1px dashed var(--border);">
        <div class="balanco-container">
            <div class="balanco-item">
                <span class="stat-label">Taxa Basal</span>
                <div style="font-size: 1.4rem; font-weight: 700; color: #fff;"><?= number_format($gasto_basal_periodo, 0, ',', '.') ?> <small style="font-size: 0.6rem; color:#8899a6;">KCAL</small></div>
            </div>
            <div class="balanco-divisor">+</div>
            <div class="balanco-item">
                <span class="stat-label">Gasto Treino</span>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--orange);"><?= number_format($kcal_treino, 0, ',', '.') ?> <small style="font-size: 0.6rem; color:#8899a6;">KCAL</small></div>
            </div>
            
            <div class="balanco-item" style="text-align: center; border-left: 1px solid var(--border); border-right: 1px solid var(--border); padding: 0 15px;">
                <span class="stat-label" style="color: var(--blue);"><?= $label_meta ?></span>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--blue);">
                    <?= number_format($meta_ingestao_periodo, 0, ',', '.') ?>
                </div>
                <small style="font-size: 0.6rem; color:#8899a6;"><?= $txt_footer_meta ?></small>
            </div>

            <div class="balanco-resultado">
                <span class="stat-label">Status Energético</span>
                <div style="font-size: 1.8rem; font-weight: 900; color: <?= $cor_status ?>;">
                    <?= $status_energia ?> 
                    <span style="font-size: 1.2rem; opacity: 0.8; display: block;">(<?= number_format(abs($balanco_real), 0, ',', '.') ?> kcal)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0; font-weight:900; color:var(--primary); font-size: 0.9rem; text-transform: uppercase;">Log de Alimentação</h3>
        <div style="overflow-x: auto;">
            <table class="vip-table">
                <thead>
                    <tr>
                        <th>Alimento / Qtd</th>
                        <th>Kcal</th>
                        <th>Prot.</th>
                        <th>Carb.</th>
                        <th>Gord.</th>
                        <th>Fib.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($lista_alimentos)): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px; color: #555;">Nenhum registro para este período.</td></tr>
                    <?php else: foreach($lista_alimentos as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:bold; color:#fff;"><?= htmlspecialchars($item['alimento']) ?></div>
                            <div style="font-size:0.7rem; color:var(--primary);"><?= $item['quantidade'] ?>g/ml</div>
                        </td>
                        <td><?= round($item['kcal']) ?></td>
                        <td style="color:var(--blue)"><?= round($item['proteina']) ?>g</td>
                        <td style="color:var(--orange)"><?= round($item['carbo']) ?>g</td>
                        <td style="color:var(--yellow)"><?= round($item['gordura']) ?>g</td>
                        <td style="color:var(--primary)"><?= round($item['fibra']) ?>g</td>
                        <td><a href="excluir_alimento_vip.php?id=<?= $item['id'] ?>" class="btn-del" onclick="return confirm('Excluir registro?')">&times;</a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<button class="btn-add" onclick="toggleModal(true)">+</button>

<div id="foodModal" class="modal">
    <div class="modal-content">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin:0; font-weight:900; color:var(--primary);">BUSCAR ALIMENTO</h2>
            <button onclick="toggleModal(false)" style="background:none; border:none; color:#8899a6; font-size:1.8rem; cursor:pointer;">&times;</button>
        </div>
        <input type="text" id="search-input" placeholder="Ex: Frango grelhado..." onkeyup="searchFood(this.value)">
        <div id="results"></div>
    </div>
</div>

<script>
let debounceTimer;
function toggleModal(show) {
    const modal = document.getElementById('foodModal');
    modal.style.display = show ? 'block' : 'none';
    if(show) document.getElementById('search-input').focus();
}

function searchFood(query) {
    clearTimeout(debounceTimer);
    const resultsDiv = document.getElementById('results');
    
    if (!query || query.length < 3) { 
        resultsDiv.innerHTML = ''; 
        return; 
    }
    
    resultsDiv.innerHTML = '<p style="text-align:center; color:#8899a6;">Buscando...</p>';
    
    debounceTimer = setTimeout(() => {
        fetch(`buscar_alimento.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = ''; 
                if (data && data.hints && data.hints.length > 0) {
                    data.hints.forEach(item => {
                        const food = item.food;
                        const nutrients = food.nutrients || {};
                        
                        const kcal = Math.round(nutrients.ENERC_KCAL || 0);
                        const prot = Math.round(nutrients.PROCNT || 0);
                        const carb = Math.round(nutrients.CHOCDF || 0);
                        const gord = Math.round(nutrients.FAT || 0);
                        const fibr = Math.round(nutrients.FIBTG || 0);

                        const div = document.createElement('div');
                        div.className = 'food-item';
                        
                        div.innerHTML = `
                            <div style="font-weight:bold; color:var(--primary);">${food.label}</div>
                            <div style="font-size:0.8rem; color:#8899a6;">
                                ${kcal} kcal | P: ${prot}g | C: ${carb}g | G: ${gord}g | F: ${fibr}g
                            </div>
                        `;
                        
                        div.onclick = () => {
                            let qtd = prompt(`Quanto de "${food.label}" você consumiu? (em gramas ou ml)`, "100");
                            if (qtd !== null && qtd !== "") {
                                const params = new URLSearchParams({
                                    label: food.label,
                                    kcal: nutrients.ENERC_KCAL || 0,
                                    prot: nutrients.PROCNT || 0,
                                    carb: nutrients.CHOCDF || 0,
                                    gord: nutrients.FAT || 0,
                                    fibr: nutrients.FIBTG || 0,
                                    quantidade: qtd
                                });
                                window.location.href = `adicionar_quantidade_vip.php?${params.toString()}`;
                            }
                        };
                        resultsDiv.appendChild(div);
                    });
                } else {
                    resultsDiv.innerHTML = '<p style="text-align:center; color:#e74c3c;">Nenhum alimento encontrado.</p>';
                }
            })
            .catch(err => {
                resultsDiv.innerHTML = '<p style="text-align:center; color:#e74c3c;">Erro ao conectar com o servidor.</p>';
            });
    }, 600);
}
</script>
</body>
</html>