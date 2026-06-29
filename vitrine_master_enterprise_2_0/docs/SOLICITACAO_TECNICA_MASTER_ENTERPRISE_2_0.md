# Solicitação Técnica — Vitrine AI Pro Master Enterprise 2.0

## Objetivo
Atualizar o Centro Operacional Master para uma arquitetura visual mais profissional, eliminando duplicidades no menu e separando claramente duas áreas: operação do negócio e Factory.

## Problemas identificados
- Menu lateral inchado.
- Duplicidade entre `Factory`, `Factory Studio`, `Studio`, `Projetos`, `Marketplace` e `Factory Core`.
- Dashboard Executivo ainda simples, com poucos indicadores.
- Páginas de Factory com aparência de CRUD, não de ambiente de criação.
- Visual ainda muito próximo do Filament padrão.

## Nova navegação pretendida

### Visão Geral
- Dashboard Executivo

### Operação
- Produtos
- Clientes
- Licenças
- Comercial
- Financeiro
- Relatórios

### Inteligência Artificial
- Dashboard IA
- Consumo
- Alertas
- Vídeos HeyGen
- Logs

### Factory
- Factory Dashboard
- Studio
- Projetos
- Marketplace
- Blueprints
- Capabilities
- Execuções
- Deploy
- Logs

### Configurações
- Usuários
- Dados da Empresa

## Regra principal
Não deve existir `Factory Studio` em dois lugares diferentes.
O Studio deve ficar dentro de `Factory` como item único.

## Entregas desta build
- Novo Dashboard Executivo.
- Novo Factory Dashboard.
- Novo Marketplace Enterprise.
- Tema CSS base para identidade visual da Vitrine AI Pro.
- Arquivo de configuração de navegação futura.

## Próxima etapa após instalar
- Ajustar `navigationGroup`, `navigationLabel` e `navigationSort` dos Resources já existentes.
- Remover ou ocultar páginas duplicadas antigas.
- Conectar indicadores reais de banco de dados aos cards.
- Registrar o CSS no PanelProvider se ainda não estiver ativo.
