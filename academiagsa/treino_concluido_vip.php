<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_GET['tempo'])) {
    header("Location: painel_vip.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$tempo_total_segundos = (int)$_GET['tempo'];
$tipo_treino_finalizado = isset($_GET['tipo']) ? $_GET['tipo'] : 'A';

try {
    $stmt = $pdo->prepare("SELECT nome, peso_atual, altura, treino_atual FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) { 
        die("Erro: Usuário não encontrado."); 
    }

    $peso = (float)$aluno['peso_atual'] > 0 ? (float)$aluno['peso_atual'] : 80.0;
    $met_base = 7.0;
    $kcal_por_minuto = $met_base * 0.0175 * $peso;
    $kcal_por_segundo = ($kcal_por_minuto / 60) * 0.70; 
    
    $kcal_estimado = round($kcal_por_segundo * $tempo_total_segundos, 1);
    
    $minutos = floor($tempo_total_segundos / 60);
    $segundos = $tempo_total_segundos % 60;
    $tempo_display = ($minutos > 0) ? $minutos . " min " . $segundos . " seg" : $segundos . " seg";

    $stmt_fichas = $pdo->prepare("SELECT DISTINCT nome_treino FROM treinos_prescritos WHERE usuario_id = ? ORDER BY nome_treino ASC");
    $stmt_fichas->execute([$user_id]);
    $fichas_aluno = $stmt_fichas->fetchAll(PDO::FETCH_COLUMN);

    if (count($fichas_aluno) > 0) {
        $indice_atual = array_search($tipo_treino_finalizado, $fichas_aluno);
        $proximo_treino = ($indice_atual === false || $indice_atual >= count($fichas_aluno) - 1) 
            ? $fichas_aluno[0] 
            : $fichas_aluno[$indice_atual + 1];
    } else {
        $proximo_treino = 'A';
    }

    $pdo->beginTransaction();

    $update = $pdo->prepare("UPDATE usuarios SET total_concluido = total_concluido + 1, treino_atual = ? WHERE id = ?");
    $update->execute([$proximo_treino, $user_id]);

    $hist = $pdo->prepare("INSERT INTO historico_atividades (usuario_id, data_registro, duracao_segundos, kcal_gasta) VALUES (?, NOW(), ?, ?)");
    $hist->execute([$user_id, $tempo_total_segundos, $kcal_estimado]);

    $pdo->commit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    die("Erro ao processar fim do treino: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo do Treino | GSA</title>
    <style>
        :root { --bg: #050a0c; --card: #111b1f; --accent: #2ecc71; --text: #ffffff; --gray: #8899a6; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { background: var(--card); padding: 30px; border-radius: 25px; text-align: center; border: 1px solid rgba(46,204,113,0.2); width: 90%; max-width: 380px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .stats-box { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 20px; margin: 25px 0; border: 1px solid rgba(255,255,255,0.05); }
        .stat-item { margin-bottom: 20px; }
        .stat-label { color: var(--gray); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 5px; }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: var(--accent); }
        .btn { width: 100%; padding: 18px; background: var(--accent); border: none; border-radius: 15px; font-weight: 900; cursor: pointer; color: #000; font-size: 1rem; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container">
        <div style="font-size: 4rem; margin-bottom: 10px;">🔥</div>
        <h1 style="color: var(--accent); margin: 0; font-size: 1.8rem; font-weight: 900;">TREINO PAGO!</h1>
        <p style="color: var(--gray); margin-top: 5px;">Excelente trabalho, <?= explode(' ', $aluno['nome'])[0] ?>!</p>
        
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-label">Tempo Total</div>
                <div class="stat-value"><?= $tempo_display ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Energia Estimada</div>
                <div class="stat-value"><?= number_format($kcal_estimado, 1, ',', '.') ?> <small style="font-size: 0.8rem;">KCAL</small></div>
            </div>
            <div style="font-size: 0.65rem; color: var(--accent); font-weight: bold; margin-top: 10px;">
                PRÓXIMO TREINO DEFINIDO: <?= $proximo_treino ?>
            </div>
        </div>

        <button class="btn" onclick="window.location.href='painel_vip.php'">VOLTAR AO INÍCIO</button>
    </div>
</body>
</html>