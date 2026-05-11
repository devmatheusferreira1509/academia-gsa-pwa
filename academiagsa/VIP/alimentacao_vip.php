<?php
// alimentacao_vip.php
require 'db.php';

function calcularPlanoEmagrecimento($user_id, $pdo) {
    $st = $pdo->prepare("SELECT peso_atual, objetivo FROM usuarios WHERE id = ?");
    $st->execute([$user_id]);
    $u = $st->fetch();

    $peso = (float)$u['peso_atual'];
    $objetivo = $u['objetivo'] ?? '';

    $is_recomposicao = (stripos($objetivo, 'Massa') !== false && stripos($objetivo, 'Emagrecimento') !== false);
    $is_ganho_massa  = (stripos($objetivo, 'Massa') !== false && !$is_recomposicao);

    if ($is_recomposicao) {
        $g_prot_kg = ($peso > 100) ? (220 / $peso) : 2.2; 
        $g_carb_kg = ($peso > 100) ? 1.5 : 2.5; 
        $g_gord_kg = 0.7;
    } 
    elseif ($is_ganho_massa) {
        $g_prot_kg = ($peso > 100) ? (200 / $peso) : 2.0;
        $g_carb_kg = 4.0;
        $g_gord_kg = 1.0;
    }
    else {
        if ($peso >= 150) {
            $g_prot_kg = 1.2; $g_carb_kg = 1.2; $g_gord_kg = 0.35; 
        }
        elseif ($peso >= 130) {
            $g_prot_kg = 1.3; $g_carb_kg = 1.4; $g_gord_kg = 0.5;
        } 
        elseif ($peso >= 90) {
            $percentual = ($peso - 90) / (129 - 90);
            $g_prot_kg = 1.6 - ($percentual * 0.3);
            $g_carb_kg = 1.8 - ($percentual * 0.4);
            $g_gord_kg = 1.1 - ($percentual * 0.6);
        }
        else {
            $g_prot_kg = 2.0; $g_carb_kg = 2.0; $g_gord_kg = 1.0;
        }
    }

    $prot_final  = $peso * $g_prot_kg;
    $carb_final  = $peso * $g_carb_kg;
    $gord_final  = $peso * $g_gord_kg;
    
    $kcal_total  = ($prot_final * 4) + ($carb_final * 4) + ($gord_final * 9);

    $fibra_final = ($kcal_total / 1000) * 12;
    if ($is_recomposicao && $fibra_final < 30) $fibra_final = 30;

    return [
        'kcal_meta' => round($kcal_total),
        'prot_g'    => round($prot_final),
        'carb_g'    => round($carb_final),
        'gord_g'    => round($gord_final),
        'fibra_g'   => round($fibra_final),
        'agua_L'    => round(($peso * 35) / 1000, 1)
    ];
}