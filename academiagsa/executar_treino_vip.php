<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['tipo'])) {
    die("Sessão expirada ou treino não selecionado. Por favor, volte ao painel.");
}

$user_id = $_SESSION['user_id'];
$tipo_treino = $_GET['tipo'];

$stmt = $pdo->prepare("SELECT t.*, u.peso_atual FROM treinos_prescritos t JOIN usuarios u ON t.usuario_id = u.id WHERE t.usuario_id = ? AND t.nome_treino = ?");
$stmt->execute([$user_id, $tipo_treino]);
$exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_exercicios = count($exercicios); 
$peso_aluno = (!empty($exercicios)) ? (float)$exercicios[0]['peso_atual'] : 85.0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Executando Treino | GSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #050a0c; --card: #111b1f; --accent: #2ecc71; --gold: #f1c40f; --text: #ffffff; --sub: #8899a6; --danger: #ff4757; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; overflow-x: hidden; }

        /* HEADER */
        .mission-header { position: fixed; top: 0; width: 100%; max-width: 500px; height: 110px; background: linear-gradient(180deg, rgba(17,27,31,1) 0%, rgba(5,10,12,0.95) 100%); backdrop-filter: blur(15px); z-index: 2000; display: flex; flex-direction: column; justify-content: center; border-bottom: 1px solid rgba(46,204,113,0.3); padding: 0 20px; box-sizing: border-box; }
        .header-top { display: flex; justify-content: space-between; align-items: center; }
        .btn-back { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: var(--text); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-size: 0.75rem; font-weight: bold; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .treino-info h2 { margin: 0; font-size: 1.1rem; font-weight: 900; color: #fff; line-height: 1; }
        .treino-info span { background: var(--accent); color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .stat-val { font-size: 1.3rem; font-weight: 900; color: var(--accent); font-variant-numeric: tabular-nums; line-height: 1; }
        .stat-lbl { font-size: 0.6rem; color: var(--sub); text-transform: uppercase; font-weight: 700; margin-top: 2px; }

        #workout-list { width: 100%; max-width: 500px; padding: 130px 20px 140px 20px; box-sizing: border-box; }

        /* CARDS */
        .ex-card { background: var(--card); border-radius: 28px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; position: relative; transition: opacity 0.3s; }
        .ex-main { padding: 20px; display: flex; align-items: center; gap: 15px; position: relative; }
        .ex-img { width: 80px; height: 80px; border-radius: 20px; object-fit: cover; border: 2px solid rgba(255,255,255,0.05); cursor: zoom-in; }
        .ex-info { flex: 1; }
        .ex-info h3 { margin: 0; font-size: 1.05rem; font-weight: 800; line-height: 1.2; }
        .series-pill { display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; border: 1.5px dashed var(--accent); color: var(--accent); }

        /* DESCANSO */
        .btn-call-rest { width: 100%; background: rgba(46,204,113,0.05); border: none; padding: 16px; color: var(--accent); font-weight: 800; cursor: pointer; border-top: 1px solid rgba(255,255,255,0.03); font-size: 0.85rem; }
        .rest-area { display: none; background: #0c1417; padding: 15px 25px; justify-content: space-between; align-items: center; border-top: 1px solid var(--accent); }
        .rest-area.active { display: flex; animation: slideUp 0.3s ease; }
        .rest-time { color: var(--gold); font-size: 2rem; font-weight: 900; font-variant-numeric: tabular-nums; }
        .verified-btn { background: var(--accent); color: #000; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; }

        /* ZOOM GIF */
        #zoom-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 5000; justify-content: center; align-items: center; cursor: zoom-out; }
        #zoom-overlay img { max-width: 90%; max-height: 80%; border-radius: 20px; border: 2px solid var(--accent); }

        /* SWITCH */
        .switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #24343a; border-radius: 34px; transition: 0.4s; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.4s; }
        input:checked + .slider { background: var(--accent); }
        input:checked + .slider:before { transform: translateX(20px); }

        .btn-finish { position: fixed; bottom: 25px; width: calc(100% - 40px); max-width: 460px; background: #162127; color: var(--accent); border: 2px solid var(--accent); padding: 20px; border-radius: 22px; font-weight: 900; z-index: 2000; font-size: 1rem; cursor: pointer; }
        .btn-ready { background: var(--accent) !important; color: #000 !important; }
        
        @keyframes slideUp { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

    <div id="zoom-overlay" onclick="this.style.display='none'">
        <img id="zoom-img" src="">
    </div>

    <div class="mission-header">
        <div class="header-top">
            <div class="treino-title-area">
                <button class="btn-back" onclick="confirmarVoltar()"><span>‹</span> Sair</button>
                <div class="treino-info">
                    <h2>TREINO <?= htmlspecialchars($tipo_treino) ?></h2>
                    <span><?= $total_exercicios ?> Exercícios</span>
                </div>
            </div>
            <div style="display:flex; gap:15px">
                <div style="text-align:right">
                    <span id="t-main" class="stat-val">00:00</span><br><span class="stat-lbl">Tempo</span>
                </div>
                <div style="text-align:right">
                    <span id="k-main" class="stat-val">0.0</span><br><span class="stat-lbl">Kcal</span>
                </div>
            </div>
        </div>
    </div>

    <div id="workout-list">
        <?php foreach($exercicios as $ex): ?>
            <div class="ex-card" id="card-<?= $ex['id'] ?>" data-total="<?= $ex['series'] ?>" data-rest-base="<?= $ex['tempo_descanso'] ?>">
                <div class="ex-main">
                    <img src="gifs/<?= $ex['imagem_url'] ?>" class="ex-img" onclick="openZoom(this.src)">
                    <div class="ex-info">
                        <h3><?= htmlspecialchars($ex['exercicio_nome']) ?></h3>
                        <div class="series-pill" id="pill-<?= $ex['id'] ?>">
                            <span class="val"><?= $ex['series'] ?></span> SÉRIES RESTANTES
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" class="ex-check" id="tog-<?= $ex['id'] ?>" onchange="forceToggle(<?= $ex['id'] ?>)">
                        <span class="slider"></span>
                    </label>
                </div>

                <button class="btn-call-rest" id="btn-rest-trigger-<?= $ex['id'] ?>" onclick="startRest(<?= $ex['id'] ?>)">
                    PRÓXIMA SÉRIE (DESCANSAR <?= $ex['tempo_descanso'] ?>s)
                </button>

                <div class="rest-area" id="ui-rest-<?= $ex['id'] ?>">
                    <div class="rest-time" id="clock-<?= $ex['id'] ?>">00:00</div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div style="color:var(--sub); font-size: 0.8rem; font-weight:bold; cursor:pointer;" onclick="add10(<?= $ex['id'] ?>)">+10s</div>
                        <div class="verified-btn" onclick="stopRest(<?= $ex['id'] ?>, true)">✔</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="btn-finish" id="btn-finish-mission" onclick="finishAll()">Finalizar Treino</button>

    <script>
        setInterval(() => {
            fetch('heartbeat.php').catch(() => console.log("Ping offline"));
        }, 30000);

        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (err) { }
        }
        requestWakeLock();
        document.addEventListener('visibilitychange', () => {
            if (wakeLock !== null && document.visibilityState === 'visible') requestWakeLock();
        });

        function openZoom(src) {
            document.getElementById('zoom-img').src = src;
            document.getElementById('zoom-overlay').style.display = 'flex';
        }

        let activeIntervals = {};
        function startRest(id) {
            const ui = document.getElementById(`ui-rest-${id}`);
            const btn = document.getElementById(`btn-rest-trigger-${id}`);
            const clock = document.getElementById(`clock-${id}`);
            const baseTime = parseInt(document.getElementById(`card-${id}`).dataset.restBase);
            
            ui.classList.add('active');
            btn.style.display = 'none';
            
            const endTime = Date.now() + (baseTime * 1000);
            activeIntervals['end_'+id] = endTime;

            clearInterval(activeIntervals[id]);
            
            const updateUI = () => {
                let agora = Date.now();
                let restante = Math.ceil((activeIntervals['end_'+id] - agora) / 1000);
                
                if(restante <= 0) {
                    clock.innerText = "00:00";
                    stopRest(id, true);
                    return;
                }
                clock.innerText = `${Math.floor(restante/60).toString().padStart(2,'0')}:${(restante%60).toString().padStart(2,'0')}`;
            };

            updateUI();
            activeIntervals[id] = setInterval(updateUI, 1000);
        }

        function add10(id) {
            if(activeIntervals['end_'+id]) {
                activeIntervals['end_'+id] += 10000;
                let agora = Date.now();
                let restante = Math.ceil((activeIntervals['end_'+id] - agora) / 1000);
                document.getElementById(`clock-${id}`).innerText = `${Math.floor(restante/60).toString().padStart(2,'0')}:${(restante%60).toString().padStart(2,'0')}`;
            }
        }

        function stopRest(id, autoSubtract = true) {
            clearInterval(activeIntervals[id]);
            document.getElementById(`ui-rest-${id}`).classList.remove('active');
            if (autoSubtract) subtractSeries(id);
            if(!document.getElementById(`tog-${id}`).checked) {
                document.getElementById(`btn-rest-trigger-${id}`).style.display = 'block';
            }
            checkAllDone();
        }

        // --- BLINDAGEM 3: CRONÔMETRO GERAL (Calculado por Timestamp fixo) ---
        let start = localStorage.getItem('gsa_start_time') || Date.now();
        if(!localStorage.getItem('gsa_start_time')) localStorage.setItem('gsa_start_time', start);
        
        function ticker() {
            let diff = Math.floor((Date.now() - start) / 1000);
            document.getElementById('t-main').innerText = `${Math.floor(diff/60).toString().padStart(2,'0')}:${(diff%60).toString().padStart(2,'0')}`;
            
            let peso = <?= $peso_aluno ?>;
            let kcalPorSegundo = ((7.0 * 0.0175 * peso) / 60) * 0.70;
            document.getElementById('k-main').innerText = (kcalPorSegundo * diff).toFixed(1);
            
            requestAnimationFrame(ticker);
        }
        ticker();

        function subtractSeries(id) {
            let pillVal = document.querySelector(`#pill-${id} .val`);
            if (!pillVal) return;
            let current = parseInt(pillVal.innerText);
            if (current > 1) { 
                current--; 
                pillVal.innerText = current; 
            } else { 
                setFinished(id); 
            }
        }

        function forceToggle(id) {
            if(document.getElementById(`tog-${id}`).checked) setFinished(id);
            else resetCard(id);
            checkAllDone();
        }

        function setFinished(id) {
            document.getElementById(`card-${id}`).style.opacity = '0.4';
            document.getElementById(`pill-${id}`).innerHTML = "FINALIZADO";
            document.getElementById(`tog-${id}`).checked = true;
            document.getElementById(`btn-rest-trigger-${id}`).style.display = 'none';
            stopRest(id, false);
        }

        function resetCard(id) {
            let total = document.getElementById(`card-${id}`).dataset.total;
            document.getElementById(`card-${id}`).style.opacity = '1';
            document.getElementById(`tog-${id}`).checked = false;
            document.getElementById(`btn-rest-trigger-${id}`).style.display = 'block';
            document.getElementById(`pill-${id}`).innerHTML = `<span class="val">${total}</span> SÉRIES RESTANTES`;
        }

        function checkAllDone() {
            const checks = document.querySelectorAll('.ex-check');
            const allChecked = Array.from(checks).every(c => c.checked);
            const btn = document.getElementById('btn-finish-mission');
            if(allChecked) btn.classList.add('btn-ready');
            else btn.classList.remove('btn-ready');
        }

        function finishAll() {
            if(confirm("Encerrar treino e salvar no histórico?")) {
                let durationSeconds = Math.floor((Date.now() - start) / 1000);
                localStorage.removeItem('gsa_start_time');
                window.location.href = `treino_concluido_vip.php?tempo=${durationSeconds}&tipo=<?= $tipo_treino ?>`;
            }
        }

        function confirmarVoltar() {
            if (confirm("Cancelar treino? O progresso não será salvo.")) {
                localStorage.removeItem('gsa_start_time');
                window.location.href = 'painel_vip.php';
            }
        }
    </script>
</body>
</html>