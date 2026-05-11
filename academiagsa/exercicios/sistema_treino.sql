-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 17-Mar-2026 às 20:08
-- Versão do servidor: 5.7.33-0ubuntu0.16.04.1
-- PHP Version: 7.0.33-0ubuntu0.16.04.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistema_treino`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `biblioteca_exercicios`
--

CREATE TABLE `biblioteca_exercicios` (
  `id` int(11) NOT NULL,
  `nome_exercicio` varchar(100) NOT NULL,
  `grupo_muscular` varchar(50) NOT NULL,
  `equipamento` varchar(100) DEFAULT NULL,
  `gif_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `biblioteca_exercicios`
--

INSERT INTO `biblioteca_exercicios` (`id`, `nome_exercicio`, `grupo_muscular`, `equipamento`, `gif_url`) VALUES
(1, 'Abdominal com Cabo Ajoelhado', 'Abdomen', 'Polia', 'abdominalcomcaboajoelhado.gif'),
(2, 'Abdominal Máquina Sentado', 'Abdomen', 'Máquina', 'abdominalmaquinasentado.gif'),
(3, 'Abdominal Reto', 'Abdomen', 'Solo', 'abdominalreto.gif'),
(4, 'Adução de Quadril no Cabo', 'Perna', 'Polia', 'aducaodoquadrilporcabo.gif'),
(5, 'Afundo', 'Perna', 'Halteres', 'afundo.gif'),
(6, 'Agachamento Peso do Corpo', 'Perna', 'Livre', 'agachamentocomopesodocorpo.gif'),
(7, 'Agachamento Hack com Panturrilha', 'Perna', 'Máquina Hack', 'agachamentohackcomelevacaodepanturrilha.gif'),
(8, 'Agachamento Smith', 'Perna', 'Máquina Smith', 'agachamentosmith.gif'),
(9, 'Agachamento Sumô com Halter', 'Perna', 'Halteres', 'agachamentosumocomhalter.gif'),
(10, 'Alavanca de Chute Traseiro', 'Gluteos', 'Máquina', 'alavancadechutetraseiroempe.gif'),
(11, 'Barra Fixa', 'Costas', 'Barra Fixa', 'barrafixa.gif'),
(13, 'Bicicleta Ergométrica', 'Cardio', 'Bicicleta', 'bicicletaergometrica.gif'),
(14, 'Cadeira Adutora', 'Perna', 'Máquina', 'cadeiraadutora.gif'),
(15, 'Cadeira Extensora', 'Perna', 'Máquina', 'cadeiraextensora.gif'),
(16, 'Cadeira Extensora Unilateral', 'Perna', 'Máquina', 'cadeiraextensoraunilateral.gif'),
(17, 'Cadeira Flexora', 'Perna', 'Máquina', 'cadeiraflexora.gif'),
(18, 'Crossover no Cabo', 'Peito', 'Polia', 'crossovernocabo.gif'),
(19, 'Crossover Polia Alta', 'Peito', 'Polia', 'crossoverpoliaalta.gif'),
(20, 'Crucifixo Cross', 'Peito', 'Polia', 'crucifixocross.gif'),
(21, 'Crucifixo Invertido', 'Ombro', 'Polia/Halter', 'crucifixoinvertido.gif'),
(22, 'Crucifixo na Máquina', 'Peito', 'Pec Deck', 'crucifixonamaquina.gif'),
(23, 'Desenvolvimento Alavanca', 'Ombro', 'Máquina', 'desenvolvimentodeombroscomalavanca.gif'),
(24, 'Desenvolvimento com Halteres', 'Ombro', 'Halteres', 'desenvolvimentodeombroscomhalteres.gif'),
(25, 'Desenvolvimento Halteres em Pé', 'Ombro', 'Halteres', 'desenvolvimentodeombroscomhalteresempe.gif'),
(26, 'Elevação Panturrilha Unilateral', 'Panturrilha', 'Livre', 'elevacaodepanturrilhacomumapernaso.gif'),
(27, 'Elevação Panturrilha Sentado', 'Panturrilha', 'Máquina', 'elevacaodepanturrilhasentadocomalavanca.gif'),
(28, 'Elevação Panturrilha Hack', 'Panturrilha', 'Máquina Hack', 'elevacaodepanturrilhaunilateralnamaquinahack.gif'),
(29, 'Elevação de Quadril Smith', 'Gluteos', 'Máquina Smith', 'elevacaodequadrilnamaquinasmith.gif'),
(30, 'Elevação Frontal Alternada', 'Ombro', 'Halteres', 'elevacaofrontalalternadacomhalteres.gif'),
(31, 'Elevação Frontal Cabo (2 Braços)', 'Ombro', 'Polia', 'elevacaofrontalcomcabodedoisbracos.gif'),
(32, 'Elevação Frontal Halteres', 'Ombro', 'Halteres', 'elevacaofrontalcomhalteres.gif'),
(33, 'Elevação Lateral Halteres', 'Ombro', 'Halteres', 'elevacaolateralcomhalteres.gif'),
(34, 'Elevação Lateral Sentado', 'Ombro', 'Halteres', 'elevacaolateralcomhalteressentado.gif'),
(35, 'Elevação Lateral no Cabo', 'Ombro', 'Polia', 'elevacaolateraldocabo.gif'),
(36, 'Elevação Lateral Posterior', 'Ombro', 'Halteres', 'elevacaolateralposteriorcomhalteressentado.gif'),
(37, 'Elevação Pélvica', 'Gluteos', 'Livre/Barra', 'elevaçãopelvica.gif'),
(38, 'Encolhimento de Ombros', 'Trapezio', 'Halteres/Barra', 'encolhimentodeombros.gif'),
(39, 'Esteira', 'Cardio', 'Esteira', 'esteira.gif'),
(40, 'Extensão de Quadril Alavanca', 'Gluteos', 'Máquina', 'extensaodequadrilempecomalavanca.gif'),
(41, 'Flexão de Joelhos', 'Perna', 'Máquina', 'flexãodejoelhos.gif'),
(42, 'Hack Machine', 'Perna', 'Máquina', 'hackmachine.gif'),
(43, 'Leg Press 45', 'Perna', 'Máquina', 'legpress45.gif'),
(44, 'Levantamento Terra', 'Costas', 'Barra', 'levantamentoterra.gif'),
(45, 'Terra Romeno', 'Perna', 'Barra', 'levantamentoterraromeno.gif'),
(46, 'Terra Romeno Halteres', 'Perna', 'Halteres', 'levantamentoterraromenocomhalteres.gif'),
(47, 'Abdução de Quadril Máquina', 'Perna', 'Máquina', 'maquinadeabducaodoquadril.gif'),
(48, 'Máquina de Adução', 'Perna', 'Máquina', 'maquinadeaducao.gif'),
(49, 'Mergulho em Paralelas', 'Triceps', 'Barras', 'mergulhoemparalelas.gif'),
(50, 'Mesa Flexora', 'Perna', 'Máquina', 'mesaflexora.gif'),
(51, 'Mesa Flexora Unilateral', 'Perna', 'Máquina', 'mesaflexoraunilateral.gif'),
(52, 'Panturrilha com Halter', 'Panturrilha', 'Halteres', 'panturrilhacomhalter.gif'),
(53, 'Panturrilha em Pé', 'Panturrilha', 'Máquina', 'panturrilhaempe.gif'),
(54, 'Panturrilha no Leg Press 90', 'Panturrilha', 'Máquina', 'panturrilhanolegpess90.gif'),
(55, 'Panturrilha Sentada', 'Panturrilha', 'Máquina', 'panturrilhasentada.gif'),
(56, 'Pulldown', 'Costas', 'Polia', 'pulldown.gif'),
(57, 'Pulldown Polia Alta', 'Costas', 'Polia', 'pulldownnapoliaalta.gif'),
(58, 'Puxada Unilateral Cross', 'Costas', 'Polia', 'puxadaunilateralcrossover.gif'),
(59, 'Recuo no Cabo', 'Gluteos', 'Polia', 'recuodocabo.gif'),
(60, 'Recuo Reverso Smith', 'Perna', 'Máquina Smith', 'recuoreversodamaquinasmith.gif'),
(61, 'Remada Alta Alavanca', 'Ombro', 'Máquina', 'remadaaltacomalavanca.gif'),
(62, 'Remada Alta Halter', 'Ombro', 'Halteres', 'remadaaltacomhalter.gif'),
(63, 'Remada Baixa', 'Costas', 'Polia', 'remadabaixa.gif'),
(64, 'Remada Cavalinho', 'Costas', 'Barra/Máquina', 'remadacavalinho.gif'),
(65, 'Remada Curvada Barra', 'Costas', 'Barra', 'remadacurvadacombarra.gif'),
(66, 'Remada Inclinada 45º Halteres', 'Costas', 'Halteres', 'remadainclinadaa45grauscomhalteres.gif'),
(67, 'Remada Inclinada Reversa', 'Costas', 'Barra', 'remadainclinadareversa.gif'),
(68, 'Remada no Cabo', 'Costas', 'Polia', 'remadanocabo.gif'),
(69, 'Remada Sentada', 'Costas', 'Máquina', 'remadasentada.gif'),
(70, 'Remada Sentada Máquina', 'Costas', 'Máquina', 'remadasentanamaquina.gif'),
(71, 'Remada Reversa Alavanca', 'Costas', 'Máquina', 'remadatreversacomalavanca.gif'),
(72, 'Remada Unilateral', 'Costas', 'Halteres', 'remadaunilateral.gif'),
(73, 'Rosca Alternada', 'Biceps', 'Halteres', 'roscaalternadacomhalteres.gif'),
(74, 'Rosca Biceps Inversa Cabo', 'Antebraco', 'Polia', 'roscabicepscombarraezepegadainvertidanocabo.gif'),
(75, 'Rosca Inversa Unilateral Cabo', 'Antebraco', 'Polia', 'roscabicepsunilateralcompegadainvertidanocabo.gif'),
(76, 'Rosca Concentrada', 'Biceps', 'Halteres', 'roscaconcentrada.gif'),
(77, 'Rosca de Punho Halteres', 'Antebraco', 'Halteres', 'roscadepunhocomhalteres.gif'),
(78, 'Rosca de Punho Martelo', 'Antebraco', 'Halteres', 'roscadepunhomartelo.gif'),
(79, 'Rosca Direta Barra', 'Biceps', 'Barra', 'roscadiretacombarra.gif'),
(80, 'Rosca Inclinada Halteres', 'Biceps', 'Halteres', 'roscainclinadacomhalter.gif'),
(81, 'Rosca Inversa', 'Antebraco', 'Barra', 'roscainversa.gif'),
(82, 'Rosca Martelo', 'Biceps', 'Halteres', 'roscamartelo.gif'),
(83, 'Rosca Martelo Corda', 'Biceps', 'Polia', 'roscamartelonacorda.gif'),
(84, 'Rosca Martelo Scott Halteres', 'Biceps', 'Banco Scott', 'roscamarteloscottcomhalteres.gif'),
(85, 'Rosca Martelo Sentado', 'Biceps', 'Halteres', 'roscamartelosentado.gif'),
(86, 'Rosca Banco Scott', 'Biceps', 'Banco Scott', 'roscanobancoscott.gif'),
(87, 'Rosca Simultânea', 'Biceps', 'Halteres', 'roscasimultanea.gif'),
(88, 'Stiff', 'Perna', 'Barra', 'stiff.gif'),
(89, 'Supino Declinado', 'Peito', 'Barra', 'supinodeclinado.gif'),
(90, 'Supino Inclinado', 'Peito', 'Barra', 'supinoinclinado.gif'),
(91, 'Supino Reto', 'Peito', 'Barra', 'supinoreto.gif'),
(92, 'Supino Reto Halter', 'Peito', 'Halteres', 'supinoretocomhalter.gif'),
(93, 'Tríceps Corda', 'Triceps', 'Polia', 'tricepscorda.gif'),
(94, 'Tríceps Barra Reta', 'Triceps', 'Polia', 'tricepspoliacombarrareta.gif'),
(96, 'Remada Alta', 'Trapezio', NULL, 'remadaaltacomhalter.gif'),
(97, 'Rosca Punho', 'Antebraco', NULL, 'roscadepunhocomhalteres.gif'),
(98, 'Rosca Inversa', 'Antebraco', NULL, 'roscainversa.gif');

-- --------------------------------------------------------

--
-- Estrutura da tabela `exercicios`
--

CREATE TABLE `exercicios` (
  `id` int(11) NOT NULL,
  `nome_exercicio` varchar(100) NOT NULL,
  `met_valor` decimal(4,1) NOT NULL,
  `treino_tipo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `exercicios`
--

INSERT INTO `exercicios` (`id`, `nome_exercicio`, `met_valor`, `treino_tipo`) VALUES
(1, 'Musculação (Geral)', '3.5', ''),
(2, 'Corrida Moderada', '8.0', ''),
(3, 'Ciclismo', '6.0', ''),
(4, 'Caminhada', '3.0', ''),
(5, 'HIIT / Funcional', '7.5', '');

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_atividades`
--

CREATE TABLE `historico_atividades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `exercicio_id` int(11) DEFAULT NULL,
  `kcal_gastas` decimal(7,2) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duracao_segundos` int(11) NOT NULL,
  `kcal_gasta` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_peso`
--

CREATE TABLE `historico_peso` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `treinos_prescritos`
--

CREATE TABLE `treinos_prescritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_treino` varchar(50) DEFAULT NULL,
  `grupamento_muscular` varchar(50) DEFAULT NULL,
  `exercicio_nome` varchar(100) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `series` int(11) DEFAULT '3',
  `repeticoes` varchar(20) DEFAULT NULL,
  `tempo_descanso` int(11) DEFAULT '60'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('Master','Aluno','VIP') DEFAULT 'Aluno',
  `altura` int(11) DEFAULT NULL,
  `peso_atual` decimal(5,2) DEFAULT NULL,
  `objetivo` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `treino_atual` varchar(1) DEFAULT 'A',
  `total_concluido` int(11) DEFAULT '0',
  `meta_objetivo` enum('Emagrecer','Manter','Ganhar Massa') DEFAULT 'Manter',
  `meta_kcal_diaria` int(11) DEFAULT '2000',
  `vip_expiracao` date DEFAULT NULL,
  `vip_ativo` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `matricula`, `nome`, `email`, `telefone`, `usuario`, `senha`, `nivel_acesso`, `altura`, `peso_atual`, `objetivo`, `data_nascimento`, `data_cadastro`, `treino_atual`, `total_concluido`, `meta_objetivo`, `meta_kcal_diaria`, `vip_expiracao`, `vip_ativo`) VALUES
(1, '1', 'Administrador', NULL, NULL, 'admin', 'matheus', 'Master', 180, '159.00', NULL, '1995-09-15', '2026-03-16 16:36:07', 'A', 1, 'Manter', 2000, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `biblioteca_exercicios`
--
ALTER TABLE `biblioteca_exercicios`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exercicios`
--
ALTER TABLE `exercicios`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `historico_atividades`
--
ALTER TABLE `historico_atividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `exercicio_id` (`exercicio_id`);

--
-- Indexes for table `historico_peso`
--
ALTER TABLE `historico_peso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indexes for table `treinos_prescritos`
--
ALTER TABLE `treinos_prescritos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `biblioteca_exercicios`
--
ALTER TABLE `biblioteca_exercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;
--
-- AUTO_INCREMENT for table `exercicios`
--
ALTER TABLE `exercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `historico_atividades`
--
ALTER TABLE `historico_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;
--
-- AUTO_INCREMENT for table `historico_peso`
--
ALTER TABLE `historico_peso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `treinos_prescritos`
--
ALTER TABLE `treinos_prescritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;
--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
--
-- Constraints for dumped tables
--

--
-- Limitadores para a tabela `historico_atividades`
--
ALTER TABLE `historico_atividades`
  ADD CONSTRAINT `historico_atividades_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_atividades_ibfk_2` FOREIGN KEY (`exercicio_id`) REFERENCES `exercicios` (`id`);

--
-- Limitadores para a tabela `historico_peso`
--
ALTER TABLE `historico_peso`
  ADD CONSTRAINT `historico_peso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
