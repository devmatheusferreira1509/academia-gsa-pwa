<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$aluno_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$aluno) { die("Aluno não encontrado."); }

$stmt_treino = $pdo->prepare("SELECT * FROM treinos_prescritos WHERE usuario_id = ? ORDER BY nome_treino ASC");
$stmt_treino->execute([$aluno_id]);
$treino_salvo = $stmt_treino->fetchAll(PDO::FETCH_ASSOC);

$grupos_query = $pdo->query("SELECT DISTINCT grupo_muscular FROM biblioteca_exercicios ORDER BY grupo_muscular ASC");
$grupos = $grupos_query->fetchAll(PDO::FETCH_COLUMN);

$fichas_json = [];
foreach ($treino_salvo as $linha) {
    $fichas_json[$linha['nome_treino']][] = [
        'nome' => $linha['exercicio_nome'],
        'gif' => $linha['imagem_url'],
        's' => $linha['series'],
        'r' => $linha['repeticoes'],
        'p' => $linha['tempo_descanso']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>GSA Master | Treino Atual</title>
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --purple: #8e44ad; --text: #ffffff; --border: #2a3b44; --danger: #e74c3c; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 15px; padding-bottom: 100px; }
        .nav-fichas { display: flex; gap: 8px; margin: 20px 0; overflow-x: auto; padding-bottom: 10px; }
        .tab { background: var(--card); padding: 15px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; color: #8899a6; border: 1px solid var(--border); white-space: nowrap; }
        .tab.active { background: var(--accent); color: #000; border-color: var(--accent); }
        .workout-list { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .card-ex { background: var(--card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); }
        .gif-frame { width: 100%; background: #000; display: flex; justify-content: center; }
        .gif-frame img { width: 100%; max-height: 350px; object-fit: contain; }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 5000; align-items: center; justify-content: center; padding: 20px; }
        .sheet { background: var(--card); border-radius: 25px; padding: 25px; border: 1px solid var(--accent); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .grid-categorias { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 15px 0; }
        .btn-cat { background: #000; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
        .lista-selecao-ex { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        .item-ex-selecao { background: #000; border: 1px solid var(--border); border-radius: 15px; padding: 10px; text-align: center; cursor: pointer; }
        .item-ex-selecao img { width: 100%; border-radius: 10px; }
        .item-ex-selecao.selected { border-color: var(--accent); background: rgba(46, 204, 113, 0.15); }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        select { background: #000; border: 1px solid var(--border); color: #fff; padding: 14px; border-radius: 10px; width: 100%; }
        .btn-final-save { position: fixed; bottom: 20px; left: 20px; right: 20px; background: var(--purple); color: #fff; border: none; padding: 18px; border-radius: 50px; font-weight: bold; cursor: pointer; z-index: 1000; }
    </style>
</head>
<body>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Ficha de: <span style="color:var(--accent);"><?= htmlspecialchars($aluno['nome']) ?></span></h2>
        <a href="painel_master.php" style="color:#8899a6; text-decoration:none; font-weight:bold;">✕ Sair</a>
    </div>

    <?php if (empty($fichas_json)): ?>
        <div class="step-box" style="padding:40px; text-align:center; background:var(--card); border-radius:20px; border:1px solid var(--border);">
            <p>Este aluno ainda não possui um treino prescrito.</p>
            <a href="prescrever_treino.php?id=<?= $aluno_id ?>" style="color:var(--accent); font-weight:bold;">Criar Novo Treino Agora</a>
        </div>
    <?php else: ?>
        <div id="nav_fichas" class="nav-fichas">
            </div>
        <div id="render_treino" class="workout-list">
            </div>
        <button id="btn_save" class="btn-final-save" onclick="enviarBanco()">ATUALIZAR TREINO E SALVAR</button>
    <?php endif; ?>

    <div id="overlay_edit" class="overlay">
        <div class="sheet">
            <h3 id="modal_title" style="margin:0 0 15px 0;">Editar Exercício</h3>
            <div id="etapa_1_categoria">
                <label style="color:var(--accent); font-weight:bold;">Trocar Grupo Muscular:</label>
                <div class="grid-categorias">
                    <?php foreach($grupos as $g): ?>
                        <button class="btn-cat" onclick="carregarExerciciosGrupo('<?= $g ?>')"><?= $g ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="etapa_2_exercicio" style="display:none;">
                <button onclick="voltarParaCategorias()" style="background:none; border:none; color:var(--purple); cursor:pointer; font-weight:bold; margin-bottom:15px;">← VOLTAR AOS GRUPOS</button>
                <div id="container_escolha_ex" class="lista-selecao-ex"></div>
            </div>
            <div id="etapa_3_ajustes" style="margin-top:20px; border-top:1px solid var(--border); padding-top:20px;">
                <div class="grid-inputs">
                    <div><label>Séries</label><select id="ed_s"><option>3</option><option>4</option><option>5</option></select></div>
                    <div><label>Reps</label><select id="ed_r"><option>6-8</option><option selected>10-12</option><option>15</option></select></div>
                    <div><label>Pausa</label><select id="ed_p"><option value="60">60s</option><option value="90">90s</option><option value="120">120s</option></select></div>
                    <div><label style="color:var(--danger)">Ação</label><button onclick="removerEx()" style="background:var(--danger); color:#fff; border:none; padding:12px; border-radius:10px; width:100%; cursor:pointer;">EXCLUIR</button></div>
                </div>
            </div>
            <button onclick="confirmarFinal()" style="background:var(--accent); color:#000; width:100%; padding:20px; border-radius:15px; font-weight:900; margin-top:25px; border:none;">SALVAR ALTERAÇÕES</button>
            <button onclick="fecharModal()" style="width:100%; background:none; border:none; color:#8899a6; margin-top:15px;">Fechar</button>
        </div>
    </div>

<script>
let treinos = <?= json_encode($fichas_json) ?>;
let abaAtual = Object.keys(treinos)[0] || 'A';
let editandoIdx = -1;
let exSelecionadoTemporario = null;

window.onload = () => { if(Object.keys(treinos).length > 0) montarInterface(); };

function montarInterface() {
    const nav = document.getElementById('nav_fichas');
    nav.innerHTML = '';
    Object.keys(treinos).forEach(letra => {
        nav.innerHTML += `<div class="tab ${letra==abaAtual?'active':''}" onclick="switchTab('${letra}', this)">TREINO ${letra}</div>`;
    });
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
    if(!div) return;
    div.innerHTML = '';
    treinos[abaAtual].forEach((ex, i) => {
        div.innerHTML += `
        <div class="card-ex" onclick="abrirModal(${i})">
            <div class="gif-frame"><img src="gifs/${ex.gif}"></div>
            <div class="info-ex" style="padding:15px; border-top:1px solid var(--border);">
                <h3 style="margin:0; color:var(--accent);">${ex.nome}</h3>
                <p style="margin:5px 0 0 0; color:#8899a6; font-weight:bold;">${ex.s} Séries | ${ex.r} Reps | ${ex.p}s Descanso</p>
            </div>
        </div>`;
    });
    div.innerHTML += `<div onclick="abrirModal(-1)" style="padding:30px; background:var(--card); border:2px dashed var(--border); color:#8899a6; border-radius:20px; text-align:center; font-weight:bold; cursor:pointer;">+ ADICIONAR NOVO</div>`;
}

function abrirModal(i) {
    editandoIdx = i;
    exSelecionadoTemporario = null;
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
    if(i !== -1) {
        const ex = treinos[abaAtual][i];
        exSelecionadoTemporario = { nome: ex.nome, gif: ex.gif };
        document.getElementById('ed_s').value = ex.s;
        document.getElementById('ed_r').value = ex.r;
        document.getElementById('ed_p').value = ex.p;
    }
    document.getElementById('overlay_edit').style.display = 'flex';
}

async function carregarExerciciosGrupo(grupo) {
    const res = await fetch(`busca_exercicios.php?musculos=${grupo}`);
    const data = await res.json();
    const container = document.getElementById('container_escolha_ex');
    container.innerHTML = '';
    data.forEach(ex => {
        container.innerHTML += `
        <div class="item-ex-selecao ${exSelecionadoTemporario?.nome == ex.nome_exercicio ? 'selected' : ''}" onclick="selecionarEsteEx('${ex.nome_exercicio}', '${ex.gif_url}', this)">
            <img src="gifs/${ex.gif_url}"><br>
            <span style="font-size:0.75rem; color:#fff;">${ex.nome_exercicio}</span>
        </div>`;
    });
    document.getElementById('etapa_1_categoria').style.display = 'none';
    document.getElementById('etapa_2_exercicio').style.display = 'grid';
}

function selecionarEsteEx(nome, gif, el) {
    document.querySelectorAll('.item-ex-selecao').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    exSelecionadoTemporario = { nome, gif };
}

function voltarParaCategorias() {
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
}

function fecharModal() { document.getElementById('overlay_edit').style.display = 'none'; }

function confirmarFinal() {
    if(!exSelecionadoTemporario) return;
    const item = { nome: exSelecionadoTemporario.nome, gif: exSelecionadoTemporario.gif, s: document.getElementById('ed_s').value, r: document.getElementById('ed_r').value, p: document.getElementById('ed_p').value };
    if(editandoIdx !== -1) treinos[abaAtual][editandoIdx] = item;
    else treinos[abaAtual].push(item);
    fecharModal(); render();
}

function removerEx() {
    if(editandoIdx !== -1) { treinos[abaAtual].splice(editandoIdx, 1); fecharModal(); render(); }
}

function enviarBanco() {
    fetch('salvar_treino_lote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ aluno_id: <?= $aluno_id ?>, dados: treinos })
    }).then(() => { alert("Treino atualizado!"); window.location.href = 'painel_master.php'; });
}
</script>
</body>
</html>