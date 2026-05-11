<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$aluno_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
                    $stmtIns->execute([$aluno_id, $aba, $ex['nome'], $ex['gif'], $ex['s'], $ex['r'], $ex['p']]);
                }
            }
            $pdo->commit();
            header("Location: painel_master.php?msg=sucesso");
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
    <title>GSA Master | Prescritor</title>
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --purple: #8e44ad; --text: #ffffff; --border: #2a3b44; --danger: #e74c3c; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 15px; padding-bottom: 100px; }
        .step-box { background: var(--card); border: 1px solid var(--purple); padding: 25px; border-radius: 20px; text-align: center; margin-bottom: 20px; }
        .nav-fichas { display: none; gap: 8px; margin: 20px 0; overflow-x: auto; padding-bottom: 10px; }
        .tab { background: var(--card); padding: 15px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; color: #8899a6; border: 1px solid var(--border); white-space: nowrap; }
        .tab.active { background: var(--accent); color: #000; border-color: var(--accent); }
        .card-ex { background: var(--card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); margin-bottom: 10px; }
        .gif-frame { width: 100%; background: #000; display: flex; justify-content: center; min-height: 200px; }
        .gif-frame img { width: 100%; max-height: 350px; object-fit: contain; }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 5000; align-items: center; justify-content: center; padding: 20px; }
        .sheet { background: var(--card); border-radius: 25px; padding: 25px; border: 1px solid var(--accent); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .grid-categorias { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 15px 0; }
        .btn-cat { background: #000; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
        .lista-selecao-ex { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        .item-ex-selecao { background: #000; border: 1px solid var(--border); border-radius: 15px; padding: 10px; text-align: center; cursor: pointer; }
        .item-ex-selecao img { width: 100%; border-radius: 10px; }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        select { background: #000; border: 1px solid var(--border); color: #fff; padding: 14px; border-radius: 10px; width: 100%; }
        .btn-final-save { position: fixed; bottom: 20px; left: 20px; right: 20px; background: var(--purple); color: #fff; border: none; padding: 18px; border-radius: 50px; font-weight: bold; cursor: pointer; display: none; z-index: 1000; }
    </style>
</head>
<body>

    <h2>Prescrever: <span style="color:var(--accent);"><?= htmlspecialchars($aluno['nome']) ?></span></h2>

    <div id="setup_1" class="step-box">
        <h3>Quantos treinos por semana?</h3>
        <div style="display:flex; gap:10px; justify-content:center;">
            <?php for($i=3; $i<=6; $i++): ?>
                <button class="btn-cat" style="padding:15px 30px;" onclick="setQtd(<?= $i ?>, this)"><?= $i ?></button>
            <?php endfor; ?>
        </div>
    </div>

    <div id="setup_2" class="step-box" style="display:none;">
        <button class="btn-cat" style="background:var(--purple); width:100%; padding:20px; font-size: 1rem;" onclick="perguntarQtdSugerida()">⚡ GERAR SUGESTÃO AUTOMÁTICA</button>
        <p style="margin: 15px 0; color:#8899a6;">OU</p>
        <button onclick="iniciarVazio()" style="background:none; border:2px solid var(--accent); color:var(--accent); padding: 15px; border-radius: 10px; width: 100%; font-weight:bold;">MONTAR MANUALMENTE</button>
    </div>

    <div id="nav_fichas" class="nav-fichas"></div>
    <div id="render_treino" class="workout-list"></div>

    <form id="form_salvar" method="POST">
        <input type="hidden" name="treinos_json" id="treinos_json">
        <button type="submit" name="btn_salvar_final" id="btn_save" class="btn-final-save">CONCLUIR E SALVAR FICHA</button>
    </form>

    <div id="overlay_edit" class="overlay">
        <div class="sheet">
            <h3 id="modal_title">Configurar Exercício</h3>
            <div id="preview_selecionado" style="display:none; justify-content: center; margin-bottom: 15px;"></div>
            
            <div id="etapa_1_categoria">
                <div class="grid-categorias">
                    <?php foreach($grupos as $g): ?>
                        <button class="btn-cat" onclick="carregarExerciciosGrupo('<?= $g ?>')"><?= $g ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="etapa_2_exercicio" style="display:none;">
                <button type="button" onclick="voltarParaCategorias()" style="background:var(--purple); color:#fff; border:none; padding:12px; border-radius:10px; margin-bottom:15px; width:100%;">← VOLTAR</button>
                <div id="container_escolha_ex" class="lista-selecao-ex"></div>             
            </div>

            <div id="etapa_3_ajustes" style="margin-top:20px;">
                <div id="campos_musculacao" class="grid-inputs">
                    <div><label>Séries</label><select id="ed_s"><option>3</option><option>4</option><option>5</option></select></div>
                    <div><label>Reps</label><select id="ed_r"><option>10-12</option><option>8-10</option><option>15</option><option>Falha</option></select></div>
                </div>

                <div id="campos_cardio" style="display:none; margin-top:20px;">
                    <label>Tempo (Minutos)</label>
                    <select id="ed_tempo"><option>10 min</option><option>15 min</option><option>20 min</option><option>30 min</option><option>40 min</option></select>
                </div>

                <div class="grid-inputs">
                    <div id="div_pausa"><label>Pausa</label><select id="ed_p"><option value="60">60s</option><option value="90">90s</option><option value="30">30s</option></select></div>
                    <div><label style="color:var(--danger)">Ação</label><button type="button" onclick="removerEx()" style="background:var(--danger); color:#fff; border:none; width:100%; padding:12px; border-radius:10px;">EXCLUIR</button></div>
                </div>
            </div>

            <button type="button" onclick="confirmarFinal()" style="background:var(--accent); width:100%; padding:20px; border-radius:15px; margin-top:20px; font-weight:bold; border:none;">CONFIRMAR</button>
            <button type="button" onclick="fecharModal()" style="width:100%; background:none; color:#8899a6; border:none; margin-top:10px;">Sair</button>
        </div>
    </div>

<script>
let treinos = {};
let abaAtual = 'A';
let editandoIdx = -1;
let exSelecionadoTemporario = null;
let grupoAtualSelecionado = "";

function setQtd(n, btn) {
    document.querySelectorAll('#setup_1 .btn-cat').forEach(b => b.style.borderColor = 'var(--border)');
    btn.style.borderColor = 'var(--accent)';
    document.getElementById('setup_2').style.display = 'block';
    treinos = {};
    for(let i=0; i<n; i++) treinos[String.fromCharCode(65+i)] = [];
}

function iniciarVazio() {
    document.getElementById('setup_1').style.display = 'none';
    document.getElementById('setup_2').style.display = 'none';
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
        // Lógica de exibição inteligente
        let info = (ex.tipo === 'cardio') ? `${ex.r}` : `${ex.s}x${ex.r} - ${ex.p}s`;
        
        div.innerHTML += `
        <div class="card-ex" onclick="abrirModal(${i})">
            <div class="gif-frame"><img src="gifs/${ex.gif}"></div>
            <div style="padding:15px;">
                <h3 style="margin:0; color:var(--accent);">${ex.nome}</h3>
                <p style="color:#8899a6;">${info}</p>
            </div>
        </div>`;
    });
    div.innerHTML += `<div onclick="abrirModal(-1)" style="padding:30px; border:2px dashed var(--border); text-align:center; border-radius:20px; cursor:pointer;">+ ADICIONAR EXERCÍCIO</div>`;
    document.getElementById('treinos_json').value = JSON.stringify(treinos);
}

function perguntarQtdSugerida() {
    let q = prompt("Quantos exercícios por ficha?", "6");
    if(q) gerarSugestao(parseInt(q));
}

async function gerarSugestao(qtd) {
    const mapeamento = {
        'A': 'Peito,Triceps,Ombro',
        'B': 'Costas,Biceps,Trapezio',
        'C': 'Perna,Abdomen',
        'D': 'Cardio,Ombro',
        'E': 'Peito,Costas',
        'F': 'Perna'
    };

    for (let letra in treinos) {
        let gruposStr = mapeamento[letra] || 'Peito,Perna';
        try {
            const res = await fetch(`busca_exercicios.php?musculos=${gruposStr}`);
            const data = await res.json();
            let sorteados = data.sort(() => 0.5 - Math.random()).slice(0, qtd);
            
            treinos[letra] = sorteados.map(ex => {
                // Se o nome do grupo contiver Cardio, ajusta os dados
                let isCardio = gruposStr.toLowerCase().includes('cardio');
                return {
                    nome: ex.nome_exercicio,
                    gif: ex.gif_url,
                    tipo: isCardio ? 'cardio' : 'musculacao',
                    s: isCardio ? '1' : 3, 
                    r: isCardio ? '20 min' : '10-12', 
                    p: isCardio ? '0' : 60
                };
            });
        } catch (e) { console.error(e); }
    }
    iniciarVazio();
}

function abrirModal(i) {
    editandoIdx = i;
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
    
    if(i !== -1) {
        const ex = treinos[abaAtual][i];
        alternarInterface(ex.tipo === 'cardio');
    }
    document.getElementById('overlay_edit').style.display = 'flex';
}

function alternarInterface(isCardio) {
    document.getElementById('campos_musculacao').style.display = isCardio ? 'none' : 'grid';
    document.getElementById('div_pausa').style.display = isCardio ? 'none' : 'block';
    document.getElementById('campos_cardio').style.display = isCardio ? 'block' : 'none';
}

async function carregarExerciciosGrupo(grupo) {
    grupoAtualSelecionado = grupo.toLowerCase();
    const res = await fetch(`busca_exercicios.php?musculos=${grupo}`);
    const data = await res.json();
    const container = document.getElementById('container_escolha_ex');
    container.innerHTML = '';
    data.forEach(ex => {
        container.innerHTML += `<div class="item-ex-selecao" onclick="selecionarEsteEx('${ex.nome_exercicio}', '${ex.gif_url}')"><img src="gifs/${ex.gif_url}"><br>${ex.nome_exercicio}</div>`;
    });
    document.getElementById('etapa_1_categoria').style.display = 'none';
    document.getElementById('etapa_2_exercicio').style.display = 'grid';
}

function selecionarEsteEx(nome, gif) {
    let isCardio = (grupoAtualSelecionado.includes('cardio') || grupoAtualSelecionado.includes('aerobico'));
    exSelecionadoTemporario = { nome, gif, tipo: isCardio ? 'cardio' : 'musculacao' };
    
    document.getElementById('modal_title').innerText = nome;
    document.getElementById('preview_selecionado').innerHTML = `<img src="gifs/${gif}" style="max-height:150px;">`;
    document.getElementById('preview_selecionado').style.display = 'flex';
    
    alternarInterface(isCardio);
    voltarParaCategorias();
}

function confirmarFinal() {
    if(!exSelecionadoTemporario) return;
    
    let isCardio = (exSelecionadoTemporario.tipo === 'cardio');
    
    let item = {
        nome: exSelecionadoTemporario.nome,
        gif: exSelecionadoTemporario.gif,
        tipo: exSelecionadoTemporario.tipo,
        s: isCardio ? '1' : document.getElementById('ed_s').value,
        r: isCardio ? document.getElementById('ed_tempo').value : document.getElementById('ed_r').value,
        p: isCardio ? '0' : document.getElementById('ed_p').value
    };

    if(editandoIdx !== -1) treinos[abaAtual][editandoIdx] = item;
    else treinos[abaAtual].push(item);
    
    fecharModal(); 
    render();
}

function fecharModal() { 
    document.getElementById('overlay_edit').style.display = 'none'; 
    document.getElementById('preview_selecionado').style.display = 'none';
}
function removerEx() { if(editandoIdx !== -1) { treinos[abaAtual].splice(editandoIdx, 1); fecharModal(); render(); } }
function voltarParaCategorias() {
    document.getElementById('etapa_1_categoria').style.display = 'block';
    document.getElementById('etapa_2_exercicio').style.display = 'none';
}
</script>
</body>
</html>