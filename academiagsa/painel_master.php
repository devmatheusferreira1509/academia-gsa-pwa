<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'Master') {
    header("Location: index.php");
    exit;
}

$user_logado_id = $_SESSION['user_id'];

if (isset($_GET['alterar_id']) && isset($_GET['novo_nivel'])) {
    $id_user = $_GET['alterar_id'];
    $nivel = $_GET['novo_nivel'];
    
    $exp = null;
    $ativo = 0;
    if ($nivel === 'VIP') {
        $exp = date('Y-m-d', strtotime('+30 days'));
        $ativo = 1;
    }

    $upd = $pdo->prepare("UPDATE usuarios SET nivel_acesso = ?, vip_expiracao = ?, vip_ativo = ? WHERE id = ?");
    if ($upd->execute([$nivel, $exp, $ativo, $id_user])) {
        header("Location: painel_master.php?sucesso=nivel_alterado");
        exit;
    }
}

if (isset($_GET['renovar_id']) && isset($_GET['dias'])) {
    $id_vip = $_GET['renovar_id'];
    $dias_adicionais = (int)$_GET['dias'];

    $stmt_exp = $pdo->prepare("SELECT vip_expiracao FROM usuarios WHERE id = ?");
    $stmt_exp->execute([$id_vip]);
    $data_atual = $stmt_exp->fetchColumn();

    $data_base = (strtotime($data_atual) > time()) ? $data_atual : date('Y-m-d');
    $nova_expiracao = date('Y-m-d', strtotime("$data_base + $dias_adicionais days"));

    $update_vip = $pdo->prepare("UPDATE usuarios SET vip_expiracao = ?, vip_ativo = 1 WHERE id = ?");
    if ($update_vip->execute([$nova_expiracao, $id_vip])) {
        header("Location: painel_master.php?sucesso=renovado");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
    $id_excluir = $_POST['aluno_id'];
    $senha_master = $_POST['senha_master'];

    $stmt_check = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt_check->execute([$user_logado_id]);
    $admin = $stmt_check->fetch();

    if ($admin && $senha_master === $admin['senha']) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM treinos_prescritos WHERE usuario_id = ?")->execute([$id_excluir]);
            $pdo->prepare("DELETE FROM historico_atividades WHERE usuario_id = ?")->execute([$id_excluir]);
            $pdo->prepare("DELETE FROM consumo_diario WHERE usuario_id = ?")->execute([$id_excluir]);
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id_excluir]);
            $pdo->commit();
            header("Location: painel_master.php?sucesso=excluido");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao excluir: " . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_aluno'])) {
    $nome = $_POST['nome'];
    $senha = $_POST['senha'];
    $nivel = $_POST['nivel_acesso'];
    $email = !empty($_POST['email']) ? $_POST['email'] : "Sem E-mail";
    
    $stmt_mat = $pdo->query("SELECT MAX(CAST(matricula AS UNSIGNED)) as ultima FROM usuarios");
    $row = $stmt_mat->fetch();
    $nova_matricula = ($row['ultima']) ? $row['ultima'] + 1 : 1000;
    
    $expiracao = ($nivel === 'VIP') ? date('Y-m-d', strtotime('+30 days')) : null;
    $ativo = ($nivel === 'VIP') ? 1 : 0;

    $sql = "INSERT INTO usuarios (nome, email, usuario, senha, matricula, nivel_acesso, vip_expiracao, vip_ativo, total_concluido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$nome, $email, $nova_matricula, $senha, $nova_matricula, $nivel, $expiracao, $ativo])) {
        $_SESSION['novo_aluno'] = ['matricula' => $nova_matricula, 'senha' => $senha, 'nome' => $nome];
        header("Location: painel_master.php?sucesso=cadastrado");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, matricula, nome, senha, nivel_acesso, total_concluido, vip_expiracao FROM usuarios WHERE id != ? ORDER BY id DESC");
$stmt->execute([$user_logado_id]);
$alunos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSA Master | Painel Completo</title>
    <style>
        :root { --bg: #050a0c; --card: #111b1f; --accent: #2ecc71; --vip: #f1c40f; --master: #9b59b6; --text: #ffffff; --border: #2a3b44; --danger: #ff4757; --gray: #8899a6; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 15px; }
        
        .header-master { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .btn-novo { background: var(--accent); color: #000; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 900; cursor: pointer; }

        .grid-alunos { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; }
        .card-aluno { background: var(--card); border-radius: 20px; padding: 20px; border: 1px solid var(--border); position: relative; }
        .card-aluno.is-vip { border-left: 4px solid var(--vip); }
        .card-aluno.is-master { border-left: 4px solid var(--master); }

        .badge { font-size: 0.6rem; padding: 3px 8px; border-radius: 5px; font-weight: 900; text-transform: uppercase; margin-left: 8px; }
        .badge-aluno { background: #1a262c; color: #888; }
        .badge-vip { background: var(--vip); color: #000; }
        .badge-master { background: var(--master); color: #fff; }

        .area-controle { background: rgba(255, 255, 255, 0.03); padding: 10px; border-radius: 12px; margin-top: 12px; border: 1px solid var(--border); }
        .btn-mini { padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.65rem; font-weight: bold; color: #fff; display: inline-block; margin: 2px; }
        
        .senha-view { background: #000; padding: 6px 12px; border-radius: 8px; font-family: monospace; font-size: 0.85rem; color: var(--accent); margin-top: 10px; display: block; border: 1px solid #222; text-align: center; }

        .btn-acao { text-decoration: none; padding: 12px; border-radius: 10px; font-weight: bold; font-size: 0.75rem; text-align: center; display: block; }
        .btn-verde { background: var(--accent); color: #000; }
        .btn-outline { border: 1px solid #3498db; color: #3498db; }
        .btn-azul { background: #3498db; color: #fff; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: var(--card); padding: 25px; border-radius: 25px; width: 90%; max-width: 400px; border: 1px solid var(--border); }
        input, select { width: 100%; padding: 14px; margin: 8px 0; background: #000; border: 1px solid var(--border); color: white; border-radius: 12px; box-sizing: border-box; }
        
        .alert-novo { background: var(--accent); color: #000; padding: 20px; border-radius: 20px; margin-bottom: 20px; text-align: center; border: 2px solid #fff; }
    </style>
</head>
<body>

    <div class="header-master">
        <div style="font-weight:900; font-size:1.2rem;">G<span style="color:var(--accent)">SA</span> MASTER</div>
        <button class="btn-novo" onclick="document.getElementById('modalCad').style.display='flex'">+ NOVO ALUNO</button>
    </div>

    <?php if(isset($_SESSION['novo_aluno'])): ?>
        <div class="alert-novo">
            <h3 style="margin:0">✅ ALUNO CRIADO!</h3>
            <p style="margin: 5px 0;">Dados de acesso do <b><?= $_SESSION['novo_aluno']['nome'] ?></b>:</p>
            <div style="font-size: 1.3rem; font-weight: 900; background: rgba(0,0,0,0.1); padding: 15px; border-radius: 12px; border: 1px dashed #000;">
                LOGIN: <?= $_SESSION['novo_aluno']['matricula'] ?> <br>
                SENHA: <?= $_SESSION['novo_aluno']['senha'] ?>
            </div>
        </div>
        <?php unset($_SESSION['novo_aluno']); ?>
    <?php endif; ?>

    <div class="grid-alunos">
        <?php foreach($alunos as $aluno): ?>
            <div class="card-aluno <?= ($aluno['nivel_acesso'] === 'VIP' ? 'is-vip' : ($aluno['nivel_acesso'] === 'Master' ? 'is-master' : '')) ?>">
                <button onclick="abrirExclusao(<?= $aluno['id'] ?>)" style="position: absolute; top: 15px; right: 15px; background: none; border: none; cursor: pointer; font-size:1.2rem;">🗑️</button>
                
                <div style="display:flex; align-items:center;">
                    <span style="color:var(--accent); font-weight:900;">#<?= $aluno['matricula'] ?></span>
                    <span class="badge <?= 'badge-'.strtolower($aluno['nivel_acesso']) ?>"><?= $aluno['nivel_acesso'] ?></span>
                </div>
                
                <h3 style="margin: 10px 0 2px 0;"><?= htmlspecialchars($aluno['nome']) ?></h3>
                <div class="senha-view">Login/Senha: <?= htmlspecialchars($aluno['senha']) ?></div>

                <div class="area-controle">
                    <span style="font-size: 0.6rem; color: var(--gray); display: block; margin-bottom: 5px;">MUDAR NÍVEL:</span>
                    <a href="?alterar_id=<?= $aluno['id'] ?>&novo_nivel=Aluno" class="btn-mini" style="background:#2a3b44">ALUNO</a>
                    <a href="?alterar_id=<?= $aluno['id'] ?>&novo_nivel=VIP" class="btn-mini" style="background:var(--vip); color:#000">VIP</a>
                    <a href="?alterar_id=<?= $aluno['id'] ?>&novo_nivel=Master" class="btn-mini" style="background:var(--master)">MASTER</a>
                </div>

                <?php if($aluno['nivel_acesso'] === 'VIP'): ?>
                    <div class="area-controle" style="border-color: var(--vip);">
                        <small style="color:var(--vip); font-weight: bold; display:block;">💎 Expira: <?= date('d/m/Y', strtotime($aluno['vip_expiracao'])) ?></small>
                        <div style="margin-top:5px;">
                            <a href="?renovar_id=<?= $aluno['id'] ?>&dias=30" class="btn-mini" style="border:1px solid var(--vip); color:var(--vip)">+30 dias</a>
                            <a href="?renovar_id=<?= $aluno['id'] ?>&dias=365" class="btn-mini" style="border:1px solid var(--vip); color:var(--vip)">+1 ano</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 15px;">
                    <a href="prescrever_treino.php?id=<?= $aluno['id'] ?>" class="btn-acao btn-verde">Prescrever</a>
                    <a href="treino_atual.php?id=<?= $aluno['id'] ?>" class="btn-acao btn-outline">Ver Treino</a>
                    <a href="acompanhar_progresso.php?id=<?= $aluno['id'] ?>" class="btn-acao btn-azul" style="grid-column: span 2;">Ver Evolução do Aluno</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="modalCad" class="modal">
        <div class="modal-content">
            <h2 style="color:var(--accent); margin-top:0;">Novo Registro</h2>
            <form method="POST">
                <input type="text" name="nome" placeholder="Nome Completo" required>
                <input type="email" name="email" placeholder="E-mail (Opcional)">
                <input type="text" name="senha" placeholder="Senha de Acesso" required>
                <select name="nivel_acesso">
                    <option value="Aluno">Aluno</option>
                    <option value="VIP">VIP</option>
                    <option value="Master">Master</option>
                </select>
                <button type="submit" name="cadastrar_aluno" class="btn-novo" style="width:100%; padding:15px; margin-top:10px;">CADASTRAR</button>
                <button type="button" onclick="document.getElementById('modalCad').style.display='none'" style="width:100%; background:none; color:var(--gray); border:none; margin-top:10px;">Sair</button>
            </form>
        </div>
    </div>

    <div id="modalExcluir" class="modal">
        <div class="modal-content" style="border-color: var(--danger);">
            <h3 style="color:var(--danger); margin-top:0;">Apagar Usuário?</h3>
            <form method="POST">
                <input type="hidden" name="aluno_id" id="idParaExcluir">
                <input type="password" name="senha_master" placeholder="Sua Senha Master" required>
                <button type="submit" name="confirmar_exclusao" style="background:var(--danger); color:white; width:100%; padding:15px; border:none; border-radius:12px; font-weight:bold;">CONFIRMAR EXCLUSÃO</button>
                <button type="button" onclick="document.getElementById('modalExcluir').style.display='none'" style="width:100%; background:none; color:var(--gray); border:none; margin-top:10px;">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
    function abrirExclusao(id) {
        document.getElementById('idParaExcluir').value = id;
        document.getElementById('modalExcluir').style.display = 'flex';
    }
    </script>
</body>
</html>