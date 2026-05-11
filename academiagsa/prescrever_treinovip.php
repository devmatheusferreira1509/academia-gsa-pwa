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

$grupos_query = $pdo->query("SELECT DISTINCT grupo_muscular FROM biblioteca_exercicios ORDER BY grupo_muscular ASC");
$grupos = $grupos_query->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>GSA | Montar Treino</title>
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
        .btn-cat { background: #000; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
        .lista-selecao-ex { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        .item-ex-selecao { background: #000; border: 1px solid var(--border); border-radius: 15px; padding: 10px; text-align: center; cursor: pointer; transition: 0.3s; }
        .item-ex-selecao img { width: 100%; border-radius: 10px; }
        .item-ex-selecao.ja-adicionado { border: 2px solid var(--accent) !important; background: rgba(46, 204, 113, 0.1); position: relative; }
        .item-ex-selecao.ja-adicionado::after { content: '✓'; position: absolute; top: 5px; right: 5px; background: var(--accent); color: #000; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; line-height: 20px; font-weight: bold; }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        select { background: #000; border: 1px solid var(--border); color: #fff; padding: 14px; border-radius: 10px; width: 100%; }
        .btn-final-save { position: fixed; bottom: 20px; left: 20px; right: 20px; background: var(--purple); color: #fff; border: none; padding: 18px; border-radius: 50px; font-weight: bold; cursor: pointer; display: none; z-index: 1000; }
        #preview_selecionado { width: 100%; display: flex; justify-content: center; margin-bottom: 15px; }
        #preview_selecionado img { max-height: 120px; border-radius: 10px; border: 1px solid var(--border); }
    </style>
</head>
<body>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2 style="margin:0;">Meu Novo Treino</h2>
        <a href="painel_vip.php" style="color:#8899a6; text-decoration:none; font-weight:bold;">✕ Cancelar</a>
    </div>

    <div id="setup_1" class="step-box">
        <h3 style="color: var(--accent)">Quantos treinos por semana?</h3>
        <p style="color: #8899a6; font-size: 0.8rem; margin-bottom: 20px;">Escolha a divisão das suas fichas (A, B, C...)</p>
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
        <button type="button" onclick="validarESalvar()" id="btn_save" class="btn-final-save">CONCLUIR E SALVAR FICHA</button>
    </form>

    <div id="overlay_edit" class="overlay">
        <div class="sheet">
            <h3 id="modal_title" style="margin-bottom:10px;">Configurar Exercício</h3>
            <div id="preview_selecionado" style="display:none;"></div>
            
            <div id="etapa_1_categoria">
                <div class="grid-categorias">
                    <?php foreach($grupos as $g): ?>
                        <button class="btn-cat" onclick="carregarExerciciosGrupo('<?= $g ?>')"><?= $g ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="etapa_2_exercicio" style="display:none;">
                <button type="button" onclick="voltarParaCategorias()" style="background:var(--purple); color:#fff; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold; margin-bottom:15px; width:100%;">← VOLTAR PARA GRUPOS</button>
                <div id="container_escolha_ex" class="lista-selecao-ex"></div>				
            </div>

            <div id="etapa_3_ajustes" style="margin-top:20px;">
                <div id="campos_musculacao" class="grid-inputs">
                    <div><label>Séries</label><select id="ed_s"><option>3</option><option>4</option><option>5</option></select></div>
                    <div><label>Reps</label><select id="ed_r"><option>6-8</option><option>10-12</option><option>15</option><option>Falha</option></select></div>
                </div>

                <div id="campos_cardio" style="display:none; margin-bottom:15px;">
                    <label>Duração (Minutos)</label>
                    <select id="ed_minutos">
                        <option value="10">10 Minutos</option>
                        <option value="15">15 Minutos</option>
                        <option value="20">20 Minutos</option>
                        <option value="30">30 Minutos</option>
                        <option value="45">45 Minutos</option>
                        <option value="60">60 Minutos</option>
                    </select>
                </div>

                <div class="grid-inputs">
                    <div id="div_pausa"><label>Pausa</label><select id="ed_p"><option value="60">60s</option><option value="90">90s</option><option value="30">30s</option><option value="120">120s</option></select></div>
                    <div><label style="color:var(--danger)">Ação</label><button type="button" onclick="removerEx()" style="background:var(--danger); color:#fff; border:none; width:100%; padding:10px; border-radius:10px;">EXCLUIR</button></div>
                </div>
            </div>

            <button type="button" onclick="confirmarFinal()" style="background:var(--accent); width:100%; padding:20px; border-radius:15px; margin-top:20px; font-weight:bold; color: #000;">ADICIONAR AO TREINO</button>
            <button type="button" onclick="fecharModal()" style="width:100%; background:none; color:#8899a6; border:none; margin-top:10px;">Cancelar</button>
        </div>
    </div>

<script>
let treinos = {};
let abaAtual = 'A';
let editandoIdx = -1;
let exSelecionadoTemporario = null;
let grupoAtualSelecionado = "";

function setQtd(n, btn) {
    treinos = {};
    for(let i=0; i<n; i++) treinos[String.fromCharCode(65+i)] = [];
    iniciarVazio();
}

function iniciarVazio() {
    document.getElementById('setup_1').style.display = 'none';
    montarInterface();
}

function montarInterface() {
    const nav = document.getElementById('nav_fichas');
    nav.style.display = 'flex'; nav.innerHTML = '';
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
        let info = ex.tipo === 'cardio' ? `${ex.r} min` : `${ex.s}x${ex.r} - ${ex.p}s`;
        div.innerHTML += `
            <div class="card-ex" onclick="abrirModal(${i})">
                <div class="gif-frame"><img src="gifs/${ex.gif}"></div>
                <div style="padding:15px;">
                    <h3 style="margin:0; color:var(--accent);">${ex.nome}</h3>
                    <p style="color:#8899a6;">${info}</p>
                </div>
            </div>`;
    });
    div.innerHTML += `<div onclick="abrirModal(-1)" style="padding:30px; border:2px dashed var(--border); text-align:center; border-radius:20px; cursor:pointer; color: var(--accent); font-weight: bold;">+ ADICIONAR EXERCÍCIO</div>`;
    document.getElementById('treinos_json').value = JSON.stringify(treinos);
}

function alternarInterfaceCardio(isCardio) {
    document.getElementById('campos_musculacao').style.display = isCardio ? 'none' : 'grid';
    document.getElementById('div_pausa').style.display = isCardio ? 'none' : 'block';
    document.getElementById('campos_cardio').style.display = isCardio ? 'block' : 'none';
}

function abrirModal(i) {
    editandoIdx = i;
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
    const previewDiv = document.getElementById('preview_selecionado');
    
    if(i !== -1) {
        const ex = treinos[abaAtual][i];
        exSelecionadoTemporario = { nome: ex.nome, gif: ex.gif, tipo: ex.tipo };
        alternarInterfaceCardio(ex.tipo === 'cardio');
        
        if(ex.tipo === 'cardio') {
            document.getElementById('ed_minutos').value = ex.r;
        } else {
            document.getElementById('ed_s').value = ex.s;
            document.getElementById('ed_r').value = ex.r;
            document.getElementById('ed_p').value = ex.p;
        }
        document.getElementById('modal_title').innerText = ex.nome;
        previewDiv.innerHTML = `<img src="gifs/${ex.gif}">`;
        previewDiv.style.display = 'flex';
    } else {
        exSelecionadoTemporario = null;
        alternarInterfaceCardio(false);
        document.getElementById('modal_title').innerText = "Adicionar Novo";
        previewDiv.style.display = 'none';
    }
    document.getElementById('overlay_edit').style.display = 'flex';
}

async function carregarExerciciosGrupo(grupo) {
    grupoAtualSelecionado = grupo.toLowerCase();
    const res = await fetch(`busca_exercicios.php?musculos=${grupo}`);
    const data = await res.json();
    const container = document.getElementById('container_escolha_ex');
    container.innerHTML = '';
    const nomesAtuais = treinos[abaAtual].map(e => e.nome);

    data.forEach(ex => {
        const jaAdicionado = nomesAtuais.includes(ex.nome_exercicio);
        container.innerHTML += `<div class="item-ex-selecao ${jaAdicionado ? 'ja-adicionado' : ''}" onclick="selecionarEsteEx('${ex.nome_exercicio}', '${ex.gif_url}', this)"><img src="gifs/${ex.gif_url}"><br><small>${ex.nome_exercicio}</small></div>`;
    });
    document.getElementById('etapa_1_categoria').style.display = 'none';
    document.getElementById('etapa_2_exercicio').style.display = 'grid';
    document.querySelector('.sheet').scrollTop = 0;
}

function selecionarEsteEx(nome, gif, el) {
    const isCardio = (grupoAtualSelecionado === 'cardio' || grupoAtualSelecionado === 'aerobico');
    exSelecionadoTemporario = { nome, gif, tipo: isCardio ? 'cardio' : 'musculacao' };
    alternarInterfaceCardio(isCardio);
    document.getElementById('modal_title').innerText = nome;
    document.getElementById('preview_selecionado').innerHTML = `<img src="gifs/${gif}">`;
    document.getElementById('preview_selecionado').style.display = 'flex';
    voltarParaCategorias();
}

function voltarParaCategorias() {
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
}

function fecharModal() { document.getElementById('overlay_edit').style.display = 'none'; }

function confirmarFinal() {
    if(!exSelecionadoTemporario) { alert("Selecione um exercício!"); return; }
    
    let item;
    if(exSelecionadoTemporario.tipo === 'cardio') {
        item = { nome: exSelecionadoTemporario.nome, gif: exSelecionadoTemporario.gif, tipo: 'cardio', s: '1', r: document.getElementById('ed_minutos').value, p: '0' };
    } else {
        item = { nome: exSelecionadoTemporario.nome, gif: exSelecionadoTemporario.gif, tipo: 'musculacao', s: document.getElementById('ed_s').value, r: document.getElementById('ed_r').value, p: document.getElementById('ed_p').value };
    }

    if(editandoIdx !== -1) treinos[abaAtual][editandoIdx] = item;
    else treinos[abaAtual].push(item);
    fecharModal();
    render();
}

function removerEx() { if(editandoIdx !== -1) { treinos[abaAtual].splice(editandoIdx, 1); fecharModal(); render(); } }

function validarESalvar() {
    for (let letra in treinos) {
        if (treinos[letra].length < 2) {
            alert(`O Treino ${letra} precisa de pelo menos 2 exercícios antes de salvar!`);
            
            abaAtual = letra;
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
                if(t.innerText === `TREINO ${letra}`) t.classList.add('active');
            });
            
            render();
            return;
        }
    }

    document.getElementById('treinos_json').value = JSON.stringify(treinos);
    document.getElementById('form_salvar').submit();
}
</script>
</body>
</html>