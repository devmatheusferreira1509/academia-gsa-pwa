<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

function traduzir($texto, $sl = 'pt', $tl = 'en') {
    if (empty($texto)) return $texto;
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$sl&tl=$tl&dt=t&q=" . urlencode($texto);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($res, true);
    return $res[0][0][0] ?? $texto;
}

$app_id = "07dcb02d"; 
$app_key = "45e887fb274384ee305e335cefecb679"; 
$q = $_GET['q'] ?? '';

if (strlen($q) < 3) {
    echo json_encode(["hints" => []]);
    exit;
}

$q_en = traduzir($q, 'pt', 'en');

$url_edamam = "https://api.edamam.com/api/food-database/v2/parser?app_id=$app_id&app_key=$app_key&ingr=" . urlencode($q_en) . "&nutrition-type=logging";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_edamam);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['hints']) && !empty($data['hints'])) {
    $data['hints'] = array_slice($data['hints'], 0, 6);
    
    foreach ($data['hints'] as &$item) {
        $label_en = $item['food']['label'];
        
        $label_pt = traduzir($label_en, 'en', 'pt');
        
        $item['food']['label'] = mb_convert_case($label_pt, MB_CASE_TITLE, "UTF-8");
    }
}

echo json_encode($data);