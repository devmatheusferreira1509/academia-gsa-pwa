<?php
// salvar_treino_lote.php
session_start();
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$aluno_id = $data['aluno_id'];
$treinos = $data['dados'];

if (!$aluno_id) exit;

$stmt = $pdo->prepare("DELETE FROM treinos_prescritos WHERE usuario_id = ?");
$stmt->execute([$aluno_id]);

foreach ($treinos as $letraFicha => $exercicios) {
    foreach ($exercicios as $ex) {
        $sql = "INSERT INTO treinos_prescritos (usuario_id, nome_treino, exercicio_nome, imagem_url, series, repeticoes, tempo_descanso) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $aluno_id,
            $letraFicha,
            $ex['nome'],
            $ex['gif'],
            $ex['s'],
            $ex['r'],
            $ex['p']
        ]);
    }
}
echo json_encode(['status' => 'success']);