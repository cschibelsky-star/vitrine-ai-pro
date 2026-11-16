# Roadmap e critérios de aceite — Escritório de Agentes e Habilidades IA

## MVP: uma equipe pequena, trabalho real e observabilidade, sem simular autonomia
**Hipótese de valor:** o operador cria uma missão, vê a distribuição de tarefas, identifica agente/skill/modelo, acompanha evidências e custos, aprova ou devolve para correção.

### Entrega M0 — Inventário e contrato (esta branch, projeto-base)
- [x] definir entidades, limites DEV/ECO, departamentos, agentes, skills, UX e critérios;
- [x] registrar vínculos com HUB-IA-001, Factory, VIA, Flow, Centro IA;
- [ ] inventariar agentes/Skills/repositórios/runtime reais e reconciliar divergências;
- **Gate M0:** revisão de responsáveis e confirmação de que não duplica serviços existentes.

### Entrega M1 — Painel de leitura (sem execução de IA)
- Dashboard: missões, tarefas em Kanban, departamentos, agentes, habilidades, eventos, bloqueios, métricas.
- Escritório 2D como visualização **opcional** do mesmo estado real; modo tabela acessível é obrigatório.
- Dados: inicialmente dados reais do sistema fonte autorizada, com rótulos `real`, `mock` ou `indisponível`; nunca inventar presença.
- Segurança: login/perfis e DEV/ECO segregados; nenhuma escrita em HML/produção por padrão.
- **Gate:** navegar missão → tarefa → evidências; nunca apresentar status fictício como real.

### Entrega M2 — Gestor de missões + habilidades
- CRUD de missão e tarefas apenas no namespace isolado; catálogo de skills versionado com testes e responsáveis.
- Atribuição de tarefas e dependências com idempotência; timeout/cancelamento seguros.
- **Gate:** tarefa duplicada não gera execução duplicada; transição inválida rejeitada; DEV e ECO sem vazamento cruzado.

### Entrega M3 — Seleção de modelos pelo HUB
- Modo AUTO via motor de decisão + modo MANUAL fixo.
- Benchmark por habilidade; score só com dados rastreáveis; razão de escolha e fallback explícito.
- Distinguir pré-seleção na tarefa de substituição no meio da sessão (que pode ser incompatível com Codex/Claude Code).
- **Gate:** seleção coincide com provedor/modelo real auditável, 400/401/429/timeout tratados, nenhum fallback silencioso, orçamento server-side verificado antes de habilitar API paga.

### Entrega M4 — Handoffs e supervisão independente
- Adaptadores por consumidor: Factory/Codex/Claude Code DEV; Centro IA ECO.
- VIA verifica dados, logs e testes independentemente; gate autenticado bloqueia avanço e retorna rework em falha.
- **Gate:** teste negativo E2E bloqueia avanço mesmo quando agente declara "concluído"; fluxo HML isolado e rollback demonstrado.

### Entrega M5 — Autonomia progressiva
- Permitir decomposição e colaboração multiagente dentro de orçamento, limite de passos, permissões e observabilidade.
- Reunião de agentes = evento real com pauta, intervenções e decisões atribuídas, não teatro visual.
- **Gate:** ausência de tarefa infinita/retries não limitados, custo reconciliado, veto humano em ações externas, supervisão VIA.

## Recursos visuais mínimos
1. Visão geral: saúde operacional, missões ativas e pendências.
2. Planta 2D: departamentos, avatares vinculados a agentes reais, status baseado em eventos reais.
3. Equipes: agente, função, skills, ferramentas, modelo selecionado e supervisor.
4. Kanban: a fazer / em execução / testes / aguardando VIA / correção / aprovado.
5. Missão: timeline de eventos, artefatos, diff, custo, critérios de aceite e aprovações.
6. Biblioteca: skills, versões, testes, permissões e uso por departamento.
7. Configurações: ambientes, orçamento, allowlists, conectores, auditoria e política de fallback.

## Riscos P0
- UI teatral mostrando reuniões sem eventos reais.
- Mistura de credenciais ou dados entre DEV, ECO e tenants.
- Roteadores paralelos no Centro IA produzindo decisões conflitantes.
- 'Aprovação VIA' forjável por flags JSON.
- API paga sem teto atômico/server-side.
- Modelo visualmente selecionado mas inferência feita em outro.
- Dependência de mídia/protocolo não comprovada, especialmente Claude Code e streaming.

## Estado e autorização
Plano apenas. Nada neste documento autoriza deploy, merge, mudanças em config.toml, migração de banco, publicação ou chamadas pagas.
