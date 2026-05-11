<?php
class FitnessHelper {

    public static function calcularProteina($objetivo, $peso) {
        $multiplicadores = [
            'massa'     => 2.2,
            'emagrecer' => 2.0,
            'definicao' => 1.8 
        ];

        $fator = $multiplicadores[$objetivo] ?? 1.8;
        return $peso * $fator;
    }

    public static function projetarEvolucao($objetivo, $pesoAtual, $pesoMeta) {
        $taxaSemanal = ($objetivo === 'emagrecer') ? 0.7 : 0.4;
        
        $diferenca = abs($pesoAtual - $pesoMeta);
        $semanas = ($diferenca > 0) ? ceil($diferenca / $taxaSemanal) : 0;

        $labels = [];
        $dados = [];

        $limite = min($semanas, 12); 

        for ($i = 0; $i <= $limite; $i++) {
            $labels[] = "Sem $i";
            
            if ($objetivo === 'emagrecer') {
                $valor = $pesoAtual - ($i * 0.7);
                $dados[] = max($valor, $pesoMeta);
            } else {
                $valor = $pesoAtual + ($i * 0.4);
                $dados[] = min($valor, $pesoMeta);
            }
        }

        return [
            'semanas' => (int)$semanas,
            'labels'  => $labels,
            'dados'   => $dados
        ];
    }
}