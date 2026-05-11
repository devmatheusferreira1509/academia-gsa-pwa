<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

require 'db.php'; 

$apiKey = "2af8fa00f7msh8ad4a5c3a307832p149ed3jsnc756825a41c2";
$host = "exercisedb.p.rapidapi.com";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://exercisedb.p.rapidapi.com/exercises?limit=500&offset=130",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: $host",
        "x-rapidapi-key: $apiKey",
        "User-Agent: OverloadApp/1.0"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("❌ Erro de conexão (cURL): " . $err);
}

$exercises = json_decode($response, true);

if (!is_array($exercises) || isset($exercises['message'])) {
    $msg = $exercises['message'] ?? 'Erro desconhecido ou formato inválido.';
    die("⚠️ Resposta da API: " . $msg);
}

$count = 0;

echo "<h2>🚀 Iniciando Carga Total para 'api_exercicios'...</h2>";

foreach ($exercises as $ex) {
    if (!isset($ex['name'])) continue;

    $nome = $ex['name'];
    $target = $ex['target'] ?? 'N/A';
    $equip = $ex['equipment'] ?? 'N/A';
    $gif = $ex['gifUrl'] ?? '';
    $bodyPart = $ex['bodyPart'] ?? '';
    
    $instrucoes = "";
    if (isset($ex['instructions']) && is_array($ex['instructions'])) {
        $instrucoes = implode("\n", $ex['instructions']);
    }

    try {
        $sql = "INSERT INTO api_exercicios (nome, corpo_alvo, equipamento, gif_url, instrucoes, body_part) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $target, $equip, $gif, $instrucoes, $bodyPart]);

        $count++;
        if ($count % 100 == 0) {
            echo "✅ $count exercícios processados...<br>";
            flush();
        }
    } catch (PDOException $e) {
        continue;
    }
}

echo "<br>---<br>";
echo "🏁 <b>Importação concluída!</b> Total de <b>$count</b> exercícios no seu banco de dados.";
?>