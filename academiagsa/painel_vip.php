<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

$dados = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$dados->execute([$user_id]);
$u = $dados->fetch();

$stmt_f = $pdo->prepare("SELECT DISTINCT nome_treino FROM treinos_prescritos WHERE usuario_id = ? ORDER BY nome_treino ASC");
$stmt_f->execute([$user_id]);
$fichas = $stmt_f->fetchAll(PDO::FETCH_COLUMN) ?: ['A', 'B', 'C'];

$stmt_last = $pdo->prepare("SELECT tipo_treino FROM historico_atividades WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
$stmt_last->execute([$user_id]);
$ultimo_concluido = $stmt_last->fetchColumn();

$sugestao = $fichas[0]; 
if ($ultimo_concluido) {
    $pos = array_search($ultimo_concluido, $fichas);
    if ($pos !== false && isset($fichas[$pos + 1])) {
        $sugestao = $fichas[$pos + 1];
    }
}

$hoje = date('Y-m-d');
$inicio_mes = date('Y-m-01');
$fim_mes = date('Y-m-t');
$hoje_obj = new DateTime();
if ($hoje_obj->format('w') == 0) {
    $domingo_semana = $hoje_obj->format('Y-m-d');
} else {
    $domingo_semana = date('Y-m-d', strtotime('last sunday'));
}

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
$ja_treinou_hoje = isset($mapa_treinos[$hoje]);

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

$dias_semana_checks = [];
for ($i = 0; $i < 7; $i++) {
    $data_c = date('Y-m-d', strtotime("+$i days", strtotime($domingo_semana)));
    $nomes_dias = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
    $dias_semana_checks[] = [
        'nome' => $nomes_dias[$i],
        'numero' => date('d', strtotime($data_c)),
        'treinou' => isset($mapa_treinos[$data_c]),
        'hoje' => ($data_c == $hoje)
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GSA | Painel VIP</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --gold: #f1c40f; --text: #ffffff; --gray: #8899a6; --border: #2a3b44; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 15px; padding-bottom: 120px; overflow-x: hidden; }
        
        .progresso-container { background: var(--card); border-radius: 20px; padding: 18px; border: 1px solid var(--border); margin-bottom: 15px; }
        .periodo-selector { display: flex; gap: 5px; background: rgba(0,0,0,0.2); padding: 4px; border-radius: 10px; margin-bottom: 15px; }
        .btn-periodo { flex: 1; border: none; background: transparent; color: var(--gray); padding: 8px; border-radius: 8px; font-size: 0.65rem; font-weight: bold; cursor: pointer; }
        .btn-periodo.active { background: var(--accent); color: #000; }
        .canvas-holder { height: 160px; position: relative; }

        .semana-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 20px; }
        .dia-circulo { width: 100%; aspect-ratio: 1/1; border-radius: 50%; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; background: rgba(255,255,255,0.03); color: var(--gray); position: relative; }
        .dia-circulo.hoje { border-color: var(--accent); color: var(--accent); font-weight: 900; }
        .dia-circulo.check { border-color: var(--accent); background: rgba(46,204,113,0.1); color: #fff; }
        .dia-circulo.check::after { content: '✓'; position: absolute; top: -3px; right: -3px; background: var(--accent); color: #000; width: 14px; height: 14px; border-radius: 50%; font-size: 0.6rem; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        .treino-dia-card { background: linear-gradient(135deg, #1c2d35, #162127); border-radius: 25px; padding: 25px; border: 1px solid #2ecc7133; text-align: center; margin-bottom: 20px; }
        .fichas-container { display: flex; justify-content: center; gap: 15px; margin: 15px 0 25px 0; }
        .ficha-opt { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--gray); cursor: pointer; transition: 0.3s; }
        .ficha-opt.selected { border-color: var(--accent); color: var(--accent); background: rgba(46,204,113,0.1); transform: scale(1.15); box-shadow: 0 0 15px rgba(46,204,113,0.2); }

        .btn-principal { background: var(--accent); color: #000; border: none; width: 100%; padding: 18px; border-radius: 15px; font-weight: 900; font-size: 1.1rem; text-decoration: none; display: block; text-transform: uppercase; cursor: pointer; }
        .btn-outline-visualizar { background: transparent; border: 1px solid var(--accent); color: var(--accent); margin-top: 10px; padding: 15px; border-radius: 15px; font-size: 0.9rem; text-decoration: none; display: block; font-weight: 700; text-transform: uppercase; }

        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #162127; display: flex; justify-content: space-around; padding: 15px 0 30px 0; border-top: 1px solid var(--border); z-index: 1000; }
        .nav-item { text-decoration: none; display: flex; flex-direction: column; align-items: center; color: var(--gray); flex: 1; font-size: 0.65rem; font-weight: 700; gap: 5px; }
        .nav-item.active { color: var(--accent); }
    </style>
</head>
<body>

    <center><h1 style="font-weight: 900; margin: 10px 0 20px 0; font-size: 2.2rem; color: #fff;">G<span style="color:var(--accent);">SA</span></h1></center>

    <div class="progresso-container">
        <div class="periodo-selector">
            <button class="btn-periodo" onclick="mudarFiltro('diario', this)">DIÁRIO</button>
            <button id="btnDefault" class="btn-periodo active" onclick="mudarFiltro('semanal', this)">SEMANAL</button>
            <button class="btn-periodo" onclick="mudarFiltro('mensal', this)">MENSAL</button>
        </div>
        <div class="canvas-holder"><canvas id="graficoAtividade"></canvas></div>
        <div style="display: flex; justify-content: space-around; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 12px;">
            <div style="text-align: center;">
                <div id="txtTempo" style="font-size: 1.2rem; font-weight: 900; color: var(--accent);">0 min</div>
                <div id="txtTempoFull" style="font-size: 0.7rem; color: var(--gray);">0h 00min</div>
            </div>
            <div style="text-align: center;">
                <div id="kcalVal" style="font-size: 1.2rem; font-weight: 900; color: #fff;">0</div>
                <div style="background: var(--gold); color: #000; font-size: 0.55rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">Kcal Gastas</div>
            </div>
        </div>
    </div>

    <div class="semana-grid">
        <?php foreach($dias_semana_checks as $d): ?>
            <div style="text-align: center;">
                <div style="font-size: 0.6rem; color: var(--gray); margin-bottom: 4px; font-weight:900;"><?= $d['nome'] ?></div>
                <div class="dia-circulo <?= $d['treinou'] ? 'check' : '' ?> <?= $d['hoje'] ? 'hoje' : '' ?>"><?= $d['numero'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="treino-dia-card">
        <h2 style="color: #fff; margin: 0; font-weight:900; font-size: 1.4rem;">
            <?= $ja_treinou_hoje ? "HOJE: <span style='color:var(--accent);'>".round($mapa_treinos[$hoje]['tempo']/60)." min</span>" : "QUAL O <span style='color:var(--accent);'>TREINO?</span>" ?>
        </h2>

        <div class="fichas-container">
            <?php foreach($fichas as $f): ?>
                <div class="ficha-opt <?= ($f == $sugestao) ? 'selected' : '' ?>" onclick="selecionarFicha('<?= $f ?>', this)">
                    <?= $f ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form action="executar_treino_vip.php" method="GET">
            <input type="hidden" name="tipo" id="inputFicha" value="<?= $sugestao ?>">
            <button type="submit" class="btn-principal">INICIAR AGORA</button>
        </form>

        <a id="btnVerTreino" href="visualizar_treino_vip.php?tipo=<?= $sugestao ?>" class="btn-outline-visualizar">Visualizar Treino</a>
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

    function selecionarFicha(letra, el) {
        document.querySelectorAll('.ficha-opt').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('inputFicha').value = letra;
        const novaUrl = "visualizar_treino_vip.php?tipo=" + letra;
        document.getElementById('btnVerTreino').href = novaUrl;
        document.getElementById('navVerTreino').href = novaUrl;
    }

    function mudarFiltro(tipo, btn) {
        document.querySelectorAll('.btn-periodo').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        let labels = [], valores = [], calorias = [], totalTempo = 0, totalKcal = 0;
        
        if (tipo === 'diario') {
            let h = dbDiario.find(d => d.full_date === dataHoje) || { minutos: 0, kcal: 0, data: 'Hoje' };
            labels = [h.data]; valores = [h.minutos]; calorias = [h.kcal];
            totalTempo = h.minutos; totalKcal = h.kcal;
        } else if (tipo === 'semanal') {
            let sab = new Date(domSemana); sab.setDate(sab.getDate() + 6);
            let filt = dbDiario.filter(d => d.full_date >= domSemana && d.full_date <= sab.toISOString().split('T')[0]);
            labels = filt.map(d => d.data); valores = filt.map(d => d.minutos); calorias = filt.map(d => d.kcal);
            filt.forEach(d => { totalTempo += d.minutos; totalKcal += d.kcal; });
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
                datasets: [{ 
                    data: valores, 
                    kcal: calorias, 
                    backgroundColor: '#2ecc71', 
                    borderRadius: 5, 
                    barThickness: (tipo === 'diario' || tipo === 'detalhe') ? 30 : 'flex' 
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                // AQUI ESTAVA O QUE FALTAVA: O evento de clique para o detalhamento
                onClick: (evt, elements) => { 
                    if (tipo === 'mensal' && elements.length > 0) {
                        mostrarDetalheSemana(elements[0].index); 
                    } 
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: { 
                        callbacks: { 
                            label: (ctx) => [` Tempo: ${formatarHoras(ctx.raw)}`, ` Calorias: ${Math.round(ctx.dataset.kcal[ctx.dataIndex])} kcal`] 
                        } 
                    } 
                },
                scales: { 
                    y: { display: false, beginAtZero: true }, 
                    x: { grid: { display: false }, ticks: { color: '#8899a6', font: { size: 9, weight: 'bold' } } } 
                }
            }
        });
    }

    function mostrarDetalheSemana(idx) {
        const sem = dbMensal[idx];
        const detalhes = dbDiario.filter(d => {
            const dia = parseInt(d.full_date.split('-')[2]);
            return dia >= sem.inicio && dia <= sem.fim;
        });
        let tT = 0, tK = 0; 
        detalhes.forEach(d => { tT += d.minutos; tK += d.kcal; });
        
        document.getElementById('txtTempo').innerText = tT + ' min';
        document.getElementById('txtTempoFull').innerText = formatarHoras(tT);
        document.getElementById('kcalVal').innerText = Math.round(tK);
        
        renderizarGrafico(detalhes.map(d => d.data), detalhes.map(d => d.minutos), detalhes.map(d => d.kcal), 'detalhe');
    }

    document.addEventListener('DOMContentLoaded', () => {
        mudarFiltro('semanal', document.getElementById('btnDefault'));
    });
</script>
</body>
</html>