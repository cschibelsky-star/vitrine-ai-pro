# Marketing IA — contrato local dos agentes de desenvolvimento (v1)

Escopo: somente os arquivos e contratos do subsistema Marketing IA. Este arquivo complementa as diretrizes da Factory; não reconfigura o Core, a Factory, a VIA ou o Social Hub.

## Autoridade
- GitHub é fonte de verdade. Conferir branch, diff, PRs e estado do ambiente antes de qualquer alteração.
- CONTINUE = leitura e diagnóstico. EXECUTAR = escrita apenas no escopo imediato autorizado, em branch isolada.
- Merge, deploy, alterações em produção, migrations aplicadas, gastos de API, publicação de campanhas, segredos e exclusões exigem autorização específica.
- Não interpretar descrição de conector, CI verde ou arquivo .mcp.json como prova de serviço em funcionamento.
- Nunca registrar chaves, tokens, dados de clientes ou mídia privada em issues, commits, logs ou handoffs.

## Fronteiras do produto
- Marketing IA: briefing, planejamento, produção criativa, QA de marca e arquivos aprovados.
- Social Hub: contas/canais, calendário de clientes, aprovação de distribuição, status e reconciliação.
- Core: empresas, autenticação, planos e permissões canônicas.
- Centro IA: escolha de provedor/modelo, orçamento, custo, consumo e execução de inferência.
- VIA: supervisão de evidências e alertas; versão OBSERVER não autoriza avançar automaticamente.
- O Marketing IA pode produzir e agendar campanhas próprias conforme seus contratos; não ativar publicação sem gate e autorização específica.

## Fluxo obrigatório de engenharia
G0 escopo/autorização -> G1 contratos/arquitetura -> G2 implementação isolada -> G3 testes reais -> G4 revisão independente -> G5 homologação aplicável -> G6 handoff verificável.
Falha, teste ausente, regressão ou evidência não verificável bloqueiam o avanço e devolvem ao agente responsável; limitações de tentativas/custo exigem escalonamento.
Nunca confundir QA editorial da campanha com QA de engenharia do software.

## Entrega mínima por tarefa
Identificador, branch, SHA inicial/final, arquivos alterados, verificações com comandos e evidências, revisor independente, riscos e próximo responsável. Usar o formato de docs/marketing/OPERACAO-CHATGPT.md e a avaliação de App\Marketing\Governance\AutonomousHandoffGate.
Não alterar estruturas de banco, jobs ou configuração de publicação nesta adoção inicial.
