# Escritório de Agentes e Habilidades IA — Projeto-base v0.1

**Natureza:** iniciativa proposta para implementação supervisionada, registrada em branch isolada. **Não** é aplicação implantada nem nome comercial definitivo.

## Objetivo
Um escritório virtual com departamentos, agentes especializados, catálogo de habilidades reutilizáveis, distribuição inteligente de missões, observabilidade, controle de custos e supervisão independente. Aproveitar o ecossistema Vitrine IA Pro sem duplicar a Factory, o Centro IA, a VIA ou o HUB-IA-001.

## Produto proposto
- Interface visual híbrida: mapa 2D de departamentos (inspirado nas referências fornecidas), dashboard operacional e lista acessível de agentes e missões.
- Equipes autônomas supervisionadas: o gestor recebe um objetivo, decompõe em tarefas, seleciona habilidades/agentes, usa o HUB para escolher modelos, encaminha a execução e registra evidências.
- Governança: uma missão nunca se declara aprovada por texto do agente; exige verificação objetiva e supervisor autorizado.
- Áreas DEV e ECO totalmente segregadas; conhecimentos técnicos gerais de habilidades/modelos podem ser compartilhados por catálogo versionado, **nunca** chaves, identidades, custos de conta, dados de cliente ou permissões.

## Relações de responsabilidade
| Camada | Responsabilidade | Estado verificado |
|---|---|---|
| Escritório IA | Gestão de missões, departamentos, papéis, habilidades, UI e handoff | Projetado neste documento; não implementado |
| HUB-IA-001 | Descoberta/benchmark/seleção de modelo e provedor por tarefa, políticas e adaptadores | Protótipo isolado; integração de produção pendente |
| Factory / Codex / Claude Code | Executores de desenvolvimento | Ferramentas Codex + Invision relatadas em testes isolados; integração ao escritório pendente |
| VIA | Supervisão independente, aprovação/reprovação | Protótipo de gate documental; integração autenticada pendente |
| Centro IA Laravel | Execução de agentes/provedores do ecossistema | AiRoutingService e AiExecutionService existem no repositório; cobertura de habilidades/custo efetivo a auditar |
| Flow | Eventos, filas, integrações/retries idempotentes | Fronteira de responsabilidade documentada no README raiz |

## Diretrizes aprovadas preservadas
- ChatGPT permanece intocado: sem mudança de configuração, memória, Skills, modelo ou conectores.
- GitHub é fonte de verdade do código; runtime, cobrança e deploy exigem evidências separadas.
- Somente leitura quando a instrução for "Continue"; "EXECUTAR" autoriza apenas o escopo imediato.
- Sem PR em cascata, merge, HML, produção, chamadas pagas, migration aplicada ou acesso a segredos sem autorização própria.
- "Reunião de agentes" deve corresponder a mensagens/eventos reais, não a animação que simula trabalho.
- Não assumir que qualquer modelo possui habilidade, ferramenta, streaming ou protocolo antes de benchmark específico.

## Referências visuais recebidas em conversa
1. WorkHub virtual escuro: mapa da oficina, agentes em tempo real, presença, kanban e atividade.
2. Escritório ilustrado com pesquisa/programação/design/marketing/revisão e orquestrador.
3. Escritório isométrico com agentes e reuniões.
Estas são referências de UX e narrativa visual, **não** comprovação de funcionalidades ou autorização para reproduzir marca/interface de terceiros.

## Artefatos desta proposta
- [ARCHITECTURE.md](./ARCHITECTURE.md): camadas, DEV/ECO, fluxo de execução e contratos mínimos.
- [DEPARTMENTS-AND-SKILLS.md](./DEPARTMENTS-AND-SKILLS.md): estrutura de equipes, papéis e habilidades.
- [MVP-ROADMAP.md](./MVP-ROADMAP.md): etapas pequenas, critérios de aceite, evidências e riscos.
- [HANDOFF.md](./HANDOFF.md): constatações verificadas, bloqueios e próxima ação.

## Definição de pronto do projeto-base
Documentos versionados e alinhados às fronteiras existentes. A definição de pronto do **produto** exigirá implementação funcional, acesso autenticado, execução E2E em HML isolada, dados reais e supervisão comprovada.
