# Academia GSA — Mobile First Fitness Platform

Aplicação web desenvolvida em PHP para gerenciamento de treinos, evolução física e acompanhamento de desempenho em academia, com foco em experiência mobile e comportamento semelhante a aplicativo nativo através de tecnologias PWA (Progressive Web App).

---

# Objetivo do Projeto

O Academia GSA foi desenvolvido para fornecer uma experiência prática, intuitiva e responsiva para usuários que desejam acompanhar sua evolução física, treinos, metas e histórico de desempenho diretamente pelo navegador ou dispositivo móvel.

O projeto foi construído com foco em:

* Mobile First
* Performance
* Organização de dados
* Experiência do usuário
* Persistência de informações
* Compatibilidade mobile
* Estrutura PWA

---

# Principais Funcionalidades

## Dashboard do Usuário

* Indicadores de evolução física
* Controle de metas
* Histórico de progresso
* Informações centralizadas

## Sistema de Treinos

* Organização de exercícios
* Biblioteca de exercícios
* Histórico de execução
* Estrutura dinâmica de treinos

## Controle de Evolução

* Registro de peso corporal
* Histórico de medidas
* Evolução física
* Monitoramento contínuo

## Progressive Web App (PWA)

* Instalação na tela inicial
* Compatível com Android e iOS
* Service Worker
* Cache local
* Experiência semelhante a aplicativo nativo

## Estrutura Backend

* Integração PHP + MySQL
* Persistência de dados
* Estrutura relacional
* Gerenciamento via phpMyAdmin

---

# Tecnologias Utilizadas

## Frontend

* HTML5
* CSS3
* JavaScript

## Backend

* PHP
* MySQL

## Infraestrutura

* Apache
* Linux
* phpMyAdmin

## Tecnologias Web

* PWA
* Service Worker
* Web App Manifest

---

# Estrutura do Projeto

```bash
academia-gsa-pwa/
│
├── academiagsa/
├── exercicios/
├── manifest.json
├── sw.js
├── sistema_treino.sql
└── README.md
```

---

# Banco de Dados

O projeto utiliza MySQL.

Importe o arquivo SQL disponível no projeto:

```text
sistema_treino.sql
```

Após a importação, configure a conexão com o banco de dados no arquivo correspondente da aplicação.

---

# Biblioteca de Exercícios

Os GIFs dos exercícios não acompanham o repositório para evitar arquivos extremamente pesados.

Para adicionar novos exercícios ao sistema:

1. Baixe um GIF do exercício desejado.
2. Coloque o arquivo na pasta:

```text
exercicios/
```

3. No banco de dados, acesse a tabela:

```text
biblioteca_exercicios
```

4. Adicione o nome do exercício e o nome exato do arquivo `.gif`.

Exemplo:

```text
supino_reto.gif
```

O sistema irá buscar automaticamente o arquivo dentro da pasta `exercicios`.

A tabela `biblioteca_exercicios` é responsável pelo gerenciamento dos exercícios exibidos no sistema.

---

# Objetivos Técnicos

Este projeto também foi utilizado como ambiente de aprendizado e experimentação envolvendo:

* Estruturação de aplicações web
* Organização de banco de dados
* Desenvolvimento mobile-first
* Experiência PWA
* APIs e persistência
* Troubleshooting
* Integração frontend/backend

---

# Status do Projeto

Projeto em evolução contínua, recebendo melhorias estruturais, visuais e funcionais.

---

# Autor

Matheus Ferreira
Engenheiro Civil | Estudante de ADS | QA | Docker | Cloud Computing
