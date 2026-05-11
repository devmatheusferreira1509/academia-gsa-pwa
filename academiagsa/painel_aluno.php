<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

$dados = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$dados->execute([$user_id]);
$u = $dados->fetch();

$precisa_completar_cadastro = (empty($u['altura']) || empty($u['peso_atual']) || empty($u['data_nascimento']));

if (isset($_POST['completar_cadastro'])) {
    $alt = $_POST['altura'];
    $pes = $_POST['peso_atual'];
    $nasc = $_POST['data_nascimento'];
    $upd = $pdo->prepare("UPDATE usuarios SET altura = ?, peso_atual = ?, data_nascimento = ? WHERE id = ?");
    if($upd->execute([$alt, $pes, $nasc, $user_id])) {
        header("Location: painel_aluno.php");
        exit;
    }
}

$hoje = date('Y-m-d');
$inicio_mes = date('Y-m-01');
$fim_mes = date('Y-m-t');
$domingo_semana = date('Y-m-d', strtotime('last sunday', strtotime('today')));

$sql_atividades = $pdo->prepare("
    SELECT DATE(data_registro) as dia, SUM(duracao_segundos) as tempo, SUM(kcal_gasta) as calorias 
    FROM historico_atividades 
    WHERE usuario_id = ? AND DATE(data_registro) BETWEEN ? AND ?
    GROUP BY DATE(data_registro)
");
$sql_atividades->execute([$user_id, $inicio_mes, $fim_mes]);
$historico_mes = $sql_atividades->fetchAll(PDO::FETCH_ASSOC);

$mapa_treinos = [];
foreach($historico_mes as $h) { $mapa_treinos[$h['dia']] = $h; }

$dias_semana_checks = [];
for ($i = 0; $i < 7; $i++) {
    $data_c = date('Y-m-d', strtotime("+$i days", strtotime($domingo_semana)));
    $nomes_dias = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
    $dias_semana_checks[] = [
        'nome' => $nomes_dias[$i],
        'numero' => date('d', strtotime($data_c)),
        'treinou' => isset($mapa_treinos[$data_c]),
        'hoje' => ($data_c == $hoje),
        'full_date' => $data_c
    ];
}

$dados_full_js = [];
$periodo = new DatePeriod(new DateTime($inicio_mes), new DateInterval('P1D'), (new DateTime($fim_mes))->modify('+1 day'));
foreach ($periodo as $data_p) {
    $d_format = $data_p->format('Y-m-d');
    $dados_full_js[] = [
        'data' => $data_p->format('d/m'),
        'full_date' => $d_format,
        'minutos' => isset($mapa_treinos[$d_format]) ? round($mapa_treinos[$d_format]['tempo'] / 60) : 0,
        'kcal' => isset($mapa_treinos[$d_format]) ? (float)$mapa_treinos[$d_format]['calorias'] : 0
    ];
}

$mensal_agrupado = [
    ['label' => 'Sem 1', 'inicio' => 1, 'fim' => 7, 'minutos' => 0, 'kcal' => 0],
    ['label' => 'Sem 2', 'inicio' => 8, 'fim' => 14, 'minutos' => 0, 'kcal' => 0],
    ['label' => 'Sem 3', 'inicio' => 15, 'fim' => 21, 'minutos' => 0, 'kcal' => 0],
    ['label' => 'Sem 4', 'inicio' => 22, 'fim' => 28, 'minutos' => 0, 'kcal' => 0],
    ['label' => 'Sem 5', 'inicio' => 29, 'fim' => (int)date('t'), 'minutos' => 0, 'kcal' => 0],
];

foreach($dados_full_js as $d) {
    $dia_num = (int)date('d', strtotime($d['full_date']));
    foreach($mensal_agrupado as &$sem) {
        if($dia_num >= $sem['inicio'] && $dia_num <= $sem['fim']) {
            $sem['minutos'] += $d['minutos'];
            $sem['kcal'] += $d['kcal'];
        }
    }
}

$ja_treinou_hoje = isset($mapa_treinos[$hoje]);
$stmt_f = $pdo->prepare("SELECT DISTINCT nome_treino FROM treinos_prescritos WHERE usuario_id = ? ORDER BY nome_treino ASC");
$stmt_f->execute([$user_id]);
$fichas_cadastradas = $stmt_f->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GSA | Painel</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --gold: #f1c40f; --text: #ffffff; --gray: #8899a6; --border: #2a3b44; }
        * { box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 15px; padding-bottom: 100px; overflow-x: hidden; }
        
        .modal-bloqueio { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: var(--card); padding: 30px; border-radius: 25px; border: 1px solid var(--accent); width: 100%; max-width: 400px; text-align: center; }
        .modal-content label { display: block; text-align: left; font-size: 0.7rem; color: var(--accent); font-weight: bold; margin: 10px 0 5px 5px; text-transform: uppercase; }
        
        .modal-input { 
            width: 100%; 
            padding: 15px; 
            margin-bottom: 10px; 
            background: #050a0c; 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            color: white; 
            font-size: 1rem; 
            text-align: center;
            display: block; /* Garante que ocupe linha própria */
            -webkit-appearance: none; /* Remove estilos padrão mobile */
        }

        .progresso-container { background: var(--card); border-radius: 20px; padding: 18px; border: 1px solid var(--border); margin-bottom: 15px; }
        .periodo-selector { display: flex; gap: 5px; background: rgba(0,0,0,0.2); padding: 4px; border-radius: 10px; margin-bottom: 15px; }
        .btn-periodo { flex: 1; border: none; background: transparent; color: var(--gray); padding: 8px; border-radius: 8px; font-size: 0.65rem; font-weight: bold; cursor: pointer; }
        .btn-periodo.active { background: var(--accent); color: #000; }
        .canvas-holder { height: 160px; position: relative; }
        .kcal-badge { background: var(--gold); color: #000; font-size: 0.65rem; font-weight: 900; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-top: 5px; text-transform: uppercase; }
        .txt-destaque { font-size: 1.1rem; font-weight: 900; }

        .semana-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 25px; }
        .dia-item { text-align: center; }
        .dia-circulo { width: 100%; aspect-ratio: 1/1; border-radius: 50%; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; position: relative; background: rgba(255,255,255,0.03); color: var(--gray); }
        .dia-circulo.hoje { border-color: var(--accent); color: var(--accent); font-weight: 900; }
        .dia-circulo.check { border-color: var(--accent); background: rgba(46,204,113,0.1); color: #fff; }
        .dia-circulo.check::after { content: '✓'; position: absolute; top: -4px; right: -4px; background: var(--accent); color: #000; width: 16px; height: 16px; border-radius: 50%; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 2; }

        .treino-dia-card { background: linear-gradient(135deg, #1c2d35, #162127); border-radius: 25px; padding: 25px; border: 1px solid #2ecc7133; text-align: center; margin-bottom: 25px; width: 100%; }
        .btn-principal { background: var(--accent); color: #000; border: none; width: 100%; padding: 18px; border-radius: 15px; font-weight: 900; font-size: 1.1rem; text-decoration: none; display: block; text-transform: uppercase; text-align: center; cursor: pointer; }
        
        .card-ficha { background: var(--card); border-radius: 15px; padding: 15px; border: 1px solid var(--border); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: inherit; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #162127; display: flex; justify-content: space-around; padding: 12px 0 25px 0; border-top: 1px solid var(--border); z-index: 1000; }
        .nav-item { text-decoration: none; display: flex; flex-direction: column; align-items: center; color: var(--gray); flex: 1; }
        .nav-item.active { color: var(--accent); }
    </style>
</head>
<body>

    <?php if ($precisa_completar_cadastro): ?>
    <div class="modal-bloqueio">
        <div class="modal-content">
            <h2 style="color: var(--accent); margin: 0 0 10px 0; font-weight: 900;">QUASE LÁ! 🚀</h2>
            <p style="color: var(--gray); font-size: 0.85rem; margin-bottom: 20px;">Precisamos de alguns dados para calcular seu desempenho:</p>
            
            <form method="POST">
                <label>Sua Altura (cm)</label>
                <input type="number" name="altura" class="modal-input" placeholder="Ex: 175" inputmode="numeric" required>
                
                <label>Peso Atual (kg)</label>
                <input type="number" step="0.1" name="peso_atual" class="modal-input" placeholder="Ex: 82.5" inputmode="decimal" required>
                
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" class="modal-input" required>
                
                <button type="submit" name="completar_cadastro" class="btn-principal" style="margin-top: 15px;">SALVAR E ACESSAR</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <center><h1 style="font-weight: 900; margin: 10px 0 20px 0; font-size: 2.2rem; color: #fff;">G<span style="color:var(--accent);">SA</span></h1></center>

    <div class="progresso-container">
        <div class="periodo-selector">
            <button class="btn-periodo" onclick="mudarFiltro('diario', this)">DIÁRIO</button>
            <button class="btn-periodo active" onclick="mudarFiltro('semanal', this)">SEMANAL</button>
            <button class="btn-periodo" onclick="mudarFiltro('mensal', this)">MENSAL</button>
        </div>
        
        <div class="canvas-holder">
            <canvas id="graficoAtividade"></canvas>
        </div>

        <div style="display: flex; justify-content: space-around; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 12px;">
            <div style="text-align: center;">
                <div id="txtTempo" class="txt-destaque" style="color: var(--accent);">0 min</div>
                <div id="txtTempoFull" style="font-size: 0.75rem; color: var(--gray);">0h 00min</div>
            </div>
            <div style="text-align: center;">
                <div id="txtKcal" class="txt-destaque">
                    <div id="kcalVal" style="color: #fff;">0</div>
                    <div class="kcal-badge">KCAL GASTAS</div>
                </div>
            </div>
        </div>
    </div>

    <div class="semana-grid">
        <?php foreach($dias_semana_checks as $dia): ?>
            <div class="dia-item">
                <div style="font-size: 0.6rem; color: var(--gray); margin-bottom: 5px; font-weight:900;"><?= $dia['nome'] ?></div>
                <div class="dia-circulo <?= $dia['treinou'] ? 'check' : '' ?> <?= $dia['hoje'] ? 'hoje' : '' ?>">
                    <?= $dia['numero'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="treino-dia-card">
        <?php if($ja_treinou_hoje): ?>
            <h2 style="color: var(--accent); margin: 0; font-weight:900;">Treino Concluído! 🏆</h2>
        <?php else: ?>
            <h2 style="margin: 5px 0 15px 0; font-size: 2rem; font-weight:900;">Ficha <?= $u['treino_atual'] ?: 'A' ?></h2>
            <a href="executar_treino.php?tipo=<?= $u['treino_atual'] ?: 'A' ?>" class="btn-principal">TREINAR AGORA</a>
        <?php endif; ?>
    </div>

    <h3 style="color: var(--gray); font-size: 0.7rem; margin: 0 0 10px 5px; font-weight:900;">MINHAS FICHAS</h3>
    <?php foreach(($fichas_cadastradas ?: ['A']) as $f): ?>
        <a href="visualizar_treino.php?tipo=<?= $f ?>" class="card-ficha">
            <h4 style="margin:0; font-weight:700;">Ficha <?= $f ?></h4>
            <span style="color:var(--accent); font-weight:900;">→</span>
        </a>
    <?php endforeach; ?>

    <nav class="bottom-nav">
        <a href="painel_aluno.php" class="nav-item active"><span>🏠</span><span>Início</span></a>
        <a href="meus_dados.php" class="nav-item"><span>👤</span><span>Perfil</span></a>
        <a href="logout.php" class="nav-item"><span>🚪</span><span>Sair</span></a>        
    </nav>

    <script>
    const dbDiario = <?= json_encode($dados_full_js) ?>;
    const dbMensal = <?= json_encode($mensal_agrupado) ?>;
    const dataHoje = "<?= $hoje ?>";
    const domSemana = "<?= $domingo_semana ?>";
    let myChart = null;

    function formatarHoras(minutosTotal) {
        if (minutosTotal < 60) return `${minutosTotal}min`;
        const horas = Math.floor(minutosTotal / 60);
        const mins = minutosTotal % 60;
        return `${horas}h ${mins.toString().padStart(2, '0')}min`;
    }

    function mudarFiltro(tipo, btn) {
        document.querySelectorAll('.btn-periodo').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        let labels = [], valores = [], calorias = [], totalTempo = 0, totalKcal = 0;
        if (tipo === 'diario') {
            let hoje = dbDiario.find(d => d.full_date === dataHoje) || { minutos: 0, kcal: 0, data: 'Hoje' };
            labels = [hoje.data]; valores = [hoje.minutos]; calorias = [hoje.kcal];
            totalTempo = hoje.minutos; totalKcal = hoje.kcal;
        } else if (tipo === 'semanal') {
            let sabSemana = new Date(domSemana);
            sabSemana.setDate(sabSemana.getDate() + 6);
            let fimStr = sabSemana.toISOString().split('T')[0];
            let filtrados = dbDiario.filter(d => d.full_date >= domSemana && d.full_date <= fimStr);
            labels = filtrados.map(d => d.data); valores = filtrados.map(d => d.minutos); calorias = filtrados.map(d => d.kcal);
            filtrados.forEach(d => { totalTempo += d.minutos; totalKcal += d.kcal; });
        } else if (tipo === 'mensal') {
            labels = dbMensal.map(s => s.label); valores = dbMensal.map(s => s.minutos); calorias = dbMensal.map(s => s.kcal);
            dbMensal.forEach(s => { totalTempo += s.minutos; totalKcal += s.kcal; });
        }
        document.getElementById('txtTempo').innerText = totalTempo + ' min';
        document.getElementById('txtTempoFull').innerText = formatarHoras(totalTempo);
        document.getElementById('kcalVal').innerText = Math.round(totalKcal);
        renderizarGrafico(labels, valores, calorias, tipo);
    }

    function renderizarGrafico(labels, valores, calorias, tipo) {
        if (myChart) myChart.destroy();
        const ctx = document.getElementById('graficoAtividade').getContext('2d');
        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ data: valores, kcal: calorias, backgroundColor: '#2ecc71', borderRadius: 5, barThickness: tipo==='diario'?40:'flex' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                onClick: (evt, elements) => { if (tipo === 'mensal' && elements.length > 0) mostrarDetalheSemana(elements[0].index); },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => [` Tempo: ${formatarHoras(ctx.raw)}`, ` Calorias: ${Math.round(ctx.dataset.kcal[ctx.dataIndex])} kcal`] } } },
                scales: { y: { display: false, beginAtZero: true }, x: { grid: { display: false }, ticks: { color: '#8899a6', font: { size: 9, weight: 'bold' } } } }
            }
        });
    }

    function mostrarDetalheSemana(idx) {
        const sem = dbMensal[idx];
        const detalhes = dbDiario.filter(d => {
            const dia = parseInt(d.full_date.split('-')[2]);
            return dia >= sem.inicio && dia <= sem.fim;
        });
        let tT = 0, tK = 0; detalhes.forEach(d => { tT += d.minutos; tK += d.kcal; });
        document.getElementById('txtTempo').innerText = tT + ' min';
        document.getElementById('txtTempoFull').innerText = formatarHoras(tT);
        document.getElementById('kcalVal').innerText = Math.round(tK);
        renderizarGrafico(detalhes.map(d => d.data), detalhes.map(d => d.minutos), detalhes.map(d => d.kcal), 'detalhe');
    }

    document.addEventListener('DOMContentLoaded', () => {
        mudarFiltro('semanal', document.querySelectorAll('.btn-periodo')[1]);
    });
    </script>
</body>
</html>