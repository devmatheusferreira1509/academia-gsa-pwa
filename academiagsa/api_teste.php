<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$aluno_id = $_SESSION['user_id']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_salvar_final'])) {
    $dados_treino = json_decode($_POST['treinos_json'], true);

    if ($aluno_id > 0 && !empty($dados_treino)) {
        try {
            $pdo->beginTransaction();
            $stmtDel = $pdo->prepare("DELETE FROM treinos_prescritos WHERE usuario_id = ?");
            $stmtDel->execute([$aluno_id]);

            $sql = "INSERT INTO treinos_prescritos (usuario_id, nome_treino, exercicio_nome, imagem_url, series, repeticoes, tempo_descanso) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtIns = $pdo->prepare($sql);

            foreach ($dados_treino as $aba => $exercicios) {
                foreach ($exercicios as $ex) {
                    $stmtIns->execute([
                        $aluno_id, 
                        $aba, 
                        $ex['nome'], 
                        $ex['gif'],
                        $ex['s'], 
                        $ex['r'], 
                        $ex['p']
                    ]);
                }
            }
            $pdo->commit();
            header("Location: painel_vip.php?msg=treino_salvo");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Erro ao salvar: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

$grupos_query = $pdo->query("SELECT DISTINCT corpo_alvo FROM api_exercicios ORDER BY corpo_alvo ASC");
$grupos = $grupos_query->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --purple: #8e44ad; --text: #ffffff; --border: #2a3b44; --danger: #e74c3c; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 15px; padding-bottom: 100px; }
        .step-box { background: var(--card); border: 1px solid var(--purple); padding: 25px; border-radius: 20px; text-align: center; margin-bottom: 20px; }
        .nav-fichas { display: none; gap: 8px; margin: 20px 0; overflow-x: auto; padding-bottom: 10px; }
        .tab { background: var(--card); padding: 15px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; color: #8899a6; border: 1px solid var(--border); white-space: nowrap; }
        .tab.active { background: var(--accent); color: #000; border-color: var(--accent); }
        .workout-list { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .card-ex { background: var(--card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); margin-bottom: 10px; }
        .gif-frame { width: 100%; background: #000; display: flex; justify-content: center; min-height: 200px; }
        .gif-frame img { width: 100%; max-height: 350px; object-fit: contain; }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 5000; align-items: center; justify-content: center; padding: 20px; }
        .sheet { background: var(--card); border-radius: 25px; padding: 25px; border: 1px solid var(--accent); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .grid-categorias { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 15px 0; }
        .btn-cat { background: #000; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .lista-selecao-ex { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        .item-ex-selecao { background: #000; border: 1px solid var(--border); border-radius: 15px; padding: 10px; text-align: center; cursor: pointer; }
        .item-ex-selecao img { width: 100%; border-radius: 10px; }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        select { background: #000; border: 1px solid var(--border); color: #fff; padding: 14px; border-radius: 10px; width: 100%; }
        .btn-final-save { position: fixed; bottom: 20px; left: 20px; right: 20px; background: var(--purple); color: #fff; border: none; padding: 18px; border-radius: 50px; font-weight: bold; z-index: 1000; }
    </style>
</head>
<body>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2 style="margin:0;">Configurar Treino API</h2>
        <a href="painel_vip.php" style="color:#8899a6; text-decoration:none; font-weight:bold;">✕ Sair</a>
    </div>

    <div id="setup_1" class="step-box">
        <h3 style="color: var(--accent)">Divisão Semanal</h3>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap: wrap;">
            <?php for($i=2; $i<=6; $i++): ?>
                <button class="btn-cat" style="padding:15px 30px; font-size: 1.2rem;" onclick="setQtd(<?= $i ?>, this)"><?= $i ?></button>
            <?php endfor; ?>
        </div>
    </div>

    <div id="nav_fichas" class="nav-fichas"></div>
    <div id="render_treino" class="workout-list"></div>

    <form id="form_salvar" method="POST">
        <input type="hidden" name="treinos_json" id="treinos_json">
        <button type="submit" name="btn_salvar_final" id="btn_save" class="btn-final-save" style="display:none;">SALVAR TODOS OS TREINOS</button>
    </form>

    <div id="overlay_edit" class="overlay">
        <div class="sheet">
            <h3 id="modal_title">Selecionar Exercício</h3>
            
            <div id="etapa_1_categoria">
                <div class="grid-categorias">
                    <?php foreach($grupos as $g): ?>
                        <button class="btn-cat" onclick="carregarExerciciosGrupo('<?= $g ?>')"><?= $g ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="etapa_2_exercicio" style="display:none;">
                <button type="button" onclick="voltarParaCategorias()" style="background:var(--purple); color:#fff; border:none; padding:10px; border-radius:10px; width:100%; margin-bottom:10px;">← VOLTAR</button>
                <div id="container_escolha_ex" class="lista-selecao-ex"></div>                
            </div>

            <div id="etapa_3_ajustes" style="margin-top:20px; display:none;">
                <div class="grid-inputs">
                    <div><label>Séries</label><select id="ed_s"><option>3</option><option>4</option><option>5</option></select></div>
                    <div><label>Reps</label><select id="ed_r"><option>8-10</option><option>12</option><option>15</option><option>Falha</option></select></div>
                    <div id="div_pausa"><label>Pausa</label><select id="ed_p"><option value="60">60s</option><option value="90">90s</option><option value="30">30s</option></select></div>
                </div>
                <button type="button" onclick="confirmarFinal()" style="background:var(--accent); width:100%; padding:20px; border-radius:15px; margin-top:20px; font-weight:bold;">ADICIONAR</button>
            </div>
            
            <button type="button" onclick="fecharModal()" style="width:100%; background:none; color:#8899a6; border:none; margin-top:15px;">Cancelar</button>
        </div>
    </div>

<script>
let treinos = {};
let abaAtual = 'A';
let editandoIdx = -1;
let exSelecionadoTemporario = null;

function setQtd(n, btn) {
    treinos = {};
    for(let i=0; i<n; i++) treinos[String.fromCharCode(65+i)] = [];
    document.getElementById('setup_1').style.display = 'none';
    montarInterface();
}

function montarInterface() {
    const nav = document.getElementById('nav_fichas');
    nav.style.display = 'flex'; 
    nav.innerHTML = '';
    for(let letra in treinos) {
        nav.innerHTML += `<div class="tab ${letra=='A'?'active':''}" onclick="switchTab('${letra}', this)">TREINO ${letra}</div>`;
    }
    document.getElementById('btn_save').style.display = 'block';
    render();
}

function switchTab(L, el) {
    abaAtual = L;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    render();
}

function render() {
    const div = document.getElementById('render_treino');
    div.innerHTML = '';
    treinos[abaAtual].forEach((ex, i) => {
        div.innerHTML += `
            <div class="card-ex">
                <div class="gif-frame"><img src="${ex.gif}"></div>
                <div style="padding:15px;">
                    <h3 style="margin:0; color:var(--accent); text-transform: uppercase;">${ex.nome}</h3>
                    <p style="color:#8899a6;">${ex.s}x${ex.r} - Descanso: ${ex.p}s</p>
                    <button onclick="removerEx(${i})" style="background:var(--danger); border:none; color:white; padding:5px 10px; border-radius:5px;">Remover</button>
                </div>
            </div>`;
    });
    div.innerHTML += `<div onclick="abrirModal(-1)" style="padding:30px; border:2px dashed var(--border); text-align:center; border-radius:20px; cursor:pointer; color: var(--accent);">+ ADICIONAR EXERCÍCIO</div>`;
    document.getElementById('treinos_json').value = JSON.stringify(treinos);
}

function abrirModal(i) {
    editandoIdx = i;
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
    document.getElementById('etapa_3_ajustes').style.display = 'none';
    document.getElementById('overlay_edit').style.display = 'flex';
}

async function carregarExerciciosGrupo(grupo) {
    const res = await fetch(`busca_exercicios_api.php?musculo=${grupo}`);
    const data = await res.json();
    const container = document.getElementById('container_escolha_ex');
    container.innerHTML = '';
    data.forEach(ex => {
        container.innerHTML += `
            <div class="item-ex-selecao" onclick="selecionarEsteEx('${ex.nome}', '${ex.gif_url}')">
                <img src="${ex.gif_url}">
                <br><small>${ex.nome}</small>
            </div>`;
    });
    document.getElementById('etapa_1_categoria').style.display = 'none';
    document.getElementById('etapa_2_exercicio').style.display = 'grid';
}

function selecionarEsteEx(nome, gif) {
    exSelecionadoTemporario = { nome, gif };
    document.getElementById('modal_title').innerText = nome;
    document.getElementById('etapa_2_exercicio').style.display = 'none';
    document.getElementById('etapa_3_ajustes').style.display = 'block';
}

function confirmarFinal() {
    let item = {
        nome: exSelecionadoTemporario.nome,
        gif: exSelecionadoTemporario.gif,
        s: document.getElementById('ed_s').value,
        r: document.getElementById('ed_r').value,
        p: document.getElementById('ed_p').value
    };
    treinos[abaAtual].push(item);
    fecharModal();
    render();
}

function removerEx(i) { treinos[abaAtual].splice(i, 1); render(); }
function voltarParaCategorias() { document.getElementById('etapa_1_categoria').style.display = 'block'; document.getElementById('etapa_2_exercicio').style.display = 'none'; }
function fecharModal() { document.getElementById('overlay_edit').style.display = 'none'; }
</script>
</body>
</html>