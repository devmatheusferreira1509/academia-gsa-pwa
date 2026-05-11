<?php
require 'db.php';

$user_id = ID; // Coloque o ID do usuário VIP que você quer gerar os dados (ex: 2)
$data_inicio = new DateTime('2026-01-01');
$data_fim = new DateTime('2026-03-18');

echo "<h2 style='font-family:sans-serif;'>Simulando ecossistema de dados para ID $user_id...</h2>";

while ($data_inicio <= $data_fim) {
    $data_str = $data_inicio->format('Y-m-d');
    
    // --- 1. SIMULAR ATIVIDADE FÍSICA ---
    $tempo = rand(1800, 3600); // 30-60 min
    $kcal_treino = rand(300, 600); // Treino pesado
    
    $stmt_ativ = $pdo->prepare("INSERT INTO historico_atividades (usuario_id, duracao_segundos, kcal_gasta, data_registro) VALUES (?, ?, ?, ?)");
    $stmt_ativ->execute([$user_id, $tempo, $kcal_treino, $data_str]);

    // --- 2. SIMULAR ALIMENTAÇÃO (Macros Realistas) ---
    $refeicoes = [
        ['nome' => 'Café da Manhã Fit', 'prot' => 30, 'carb' => 40, 'gord' => 10],
        ['nome' => 'Almoço Dash', 'prot' => 60, 'carb' => 80, 'gord' => 25],
        ['nome' => 'Jantar VIP', 'prot' => 50, 'carb' => 60, 'gord' => 20]
    ];

    foreach ($refeicoes as $ref) {
        $p = $ref['prot'] * (rand(90, 110) / 100);
        $c = $ref['carb'] * (rand(90, 110) / 100);
        $g = $ref['gord'] * (rand(90, 110) / 100);
        $total_kcal = ($p * 4) + ($c * 4) + ($g * 9);

        $stmt_cons = $pdo->prepare("INSERT INTO consumo_diario (usuario_id, alimento_nome, quantidade_g, kcal, proteina, carbo, gordura, data_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_cons->execute([$user_id, $ref['nome'], 300, $total_kcal, $p, $c, $g, $data_str]);
    }

    echo "<span style='color: #2ecc71;'>✔</span> Dia $data_str: Treino + 3 Refeições inseridas.<br>";
    $data_inicio->modify('+1 day');
}

echo "<h3>✅ Tudo pronto! Agora o filtro 'ESTE MÊS' terá dados reais para calcular.</h3>";