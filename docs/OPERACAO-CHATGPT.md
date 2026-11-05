# Operação do ChatGPT — Assessor Online

## Objetivo
Completar a estrutura funcional do Assessor Online antes da homologação integrada. Manter identidade premium Vitrine IA Pro e URL planejada `assessor.vitrineiapro.com.br`.

## Escopo funcional
- Gabinetes e membros, com papéis e isolamento por gabinete
- Cidadãos, demandas/protocolos, histórico e anotações
- Agenda, tarefas, indicadores e recomendações locais
- Avaliação de 15 dias e futura assinatura comercial
- Integração futura com Centro IA por adaptador, sem chamadas pagas neste estágio

## Execução de cada ciclo
1. Conferir branch e PR #124 e inspecionar arquivos antes de alterar.
2. Priorizar correções de segurança e consistência: auditoria transacional, autorização de cada ação, validação do cidadão vinculado, tratamento de erros e compatibilidade das views.
3. Implementar módulos em incrementos verificáveis.
4. Executar testes locais quando o ambiente permitir; registrar bloqueios e evidências.
5. Atualizar `docs/HANDOFF.md` com progresso verificável.

## Critérios de pronto
Código revisado; testes de isolamento, papéis, agenda, demandas e trial passando; interface sem regressão; migrations testadas em banco descartável; HML homologada em etapa separada. A existência de commits não satisfaz esses critérios.

## Limites
Sem deploy, merge, migrations em banco ativo, uso de credenciais reais ou custos externos sem autorização específica. Evitar SSH manual se houver conector apropriado.
