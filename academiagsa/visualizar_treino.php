<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['tipo'])) {
    header("Location: painel_aluno.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tipo_treino = $_GET['tipo'];

$stmt = $pdo->prepare("SELECT * FROM treinos_prescritos WHERE usuario_id = ? AND nome_treino = ?");
$stmt->execute([$user_id, $tipo_treino]);
$exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Visualizar Treino <?= htmlspecialchars($tipo_treino) ?></title>
    <style>
        :root { --bg: #0b1315; --card: #162127; --accent: #2ecc71; --text: #ffffff; --red: #ff4757; --yellow: #f1c40f; --gray: #8899a6; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 10px; padding-top: 80px; padding-bottom: 40px; }

        .header-treino { 
            position: fixed; top: 0; left: 0; width: 100%; height: 70px; 
            background: var(--bg); display: flex; flex-direction: column; align-items: center; 
            justify-content: center; z-index: 1000; border-bottom: 1px solid #2a3b44;
        }

        .ex-card { 
            background: var(--card); border-radius: 15px; margin-bottom: 15px; 
            border: 1px solid #2a3b44; position: relative; overflow: hidden;
        }

        .ex-content { display: flex; padding: 15px; gap: 15px; align-items: flex-start; }
        
        /* Cursor de lupa para indicar que amplia */
        .gif-thumb { 
            width: 85px; height: 85px; border-radius: 12px; object-fit: cover; 
            background: #000; flex-shrink: 0; cursor: zoom-in; 
        }

        .ex-details { flex: 1; min-width: 0; }
        .ex-details h4 { margin: 0; font-size: 1.05rem; color: #ced6e0; line-height: 1.2; }
        
        .series-badge { 
            display: inline-block; padding: 4px 10px; border-radius: 6px; 
            border: 1px solid rgba(46, 204, 113, 0.4); color: var(--accent); 
            font-size: 0.75rem; font-weight: bold; margin-top: 8px; 
        }

        .btn-voltar-painel {
            width: 100%; padding: 18px; background: #2a3b44; color: #fff; 
            border: none; border-radius: 12px; font-weight: bold; font-size: 1.1rem;
            margin-top: 20px; margin-bottom: 30px; cursor: pointer; text-decoration: none;
            display: block; text-align: center;
        }

        .modo-visualizacao { color: var(--yellow); font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }

        /* --- ESTILO DO ZOOM (MODAL) --- */
        #zoom_overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9);
            z-index: 9999; align-items: center; justify-content: center; padding: 20px;
            cursor: zoom-out;
        }
        #zoom_overlay img {
            max-width: 100%; max-height: 80vh; border-radius: 20px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

    <div class="header-treino">
        <div class="modo-visualizacao">Modo de Visualização</div>
        <div style="color:var(--accent); font-size: 1.3rem; letter-spacing: 1px; font-weight: bold;">
            TREINO <?= htmlspecialchars($tipo_treino) ?>
        </div>
    </div>

    <div id="lista-exercicios">
        <?php if(count($exercicios) > 0): ?>
            <?php foreach($exercicios as $ex): ?>
                <?php 
                    // Lógica para identificar se é cardio (baseado na flag ou se séries é 1 e reps alto)
                    $is_cardio = ($ex['tempo_descanso'] == 0 && $ex['series'] == 1);
                ?>
                <div class="ex-card">
                    <div class="ex-content">
                        <img src="gifs/<?= $ex['imagem_url'] ?>" 
                             class="gif-thumb" 
                             onclick="ampliarGif(this.src)"
                             alt="Exercício">
                        
                        <div class="ex-details">
                            <h4><?= htmlspecialchars($ex['exercicio_nome']) ?></h4>
                            
                            <div class="series-badge">
                                <?php if($is_cardio): ?>
                                    ⏱️ <?= $ex['repeticoes'] ?> MINUTOS
                                <?php else: ?>
                                    <?= $ex['series'] ?> SÉRIES x <?= $ex['repeticoes'] ?> REPS
                                <?php endif; ?>
                            </div>

                            <?php if(!$is_cardio): ?>
                            <div style="margin-top: 10px; font-size: 0.8rem; color: var(--gray);">
                                ⏱️ Descanso: <?= $ex['tempo_descanso'] ?>s
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--gray);">Nenhum exercício cadastrado.</p>
        <?php endif; ?>
    </div>

    <a href="painel_aluno.php" class="btn-voltar-painel">
        VOLTAR AO PAINEL
    </a>

    <div id="zoom_overlay" onclick="fecharZoom()">
        <img id="img_ampliada" src="" alt="Zoom">
    </div>

    <script>
        function ampliarGif(src) {
            const overlay = document.getElementById('zoom_overlay');
            const img = document.getElementById('img_ampliada');
            img.src = src;
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function fecharZoom() {
            const overlay = document.getElementById('zoom_overlay');
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    </script>

</body>
</html>