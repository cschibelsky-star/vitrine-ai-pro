# HUB-IA-001 — Estado do projeto
Atualizado: 2026-11-14
Estado global: PENDENTE (fase documental)
Missão: disponibilizar um hub de IA independente, com uso pessoal em Codex/outros clientes e futura integração ao ecossistema Vitrine IA Pro, sem acoplar os ambientes.

## Resultado concreto exigido
Entrega 1 (uso pessoal): selecionar um gateway/modelo, executar tarefa real no Codex, identificar endpoint/provedor efetivo e registrar evidência sem revelar segredos.
Entrega 2 (ecossistema): integrar ao Centro IA em HML separada, somente depois de avaliação técnica, aceite e autorização própria.
Mídia (imagem, voz, vídeo): capacidades independentes; geração de MP4 não deve ser inferida do catálogo do Codex.

## Situação confirmada
- Repositório cschibelsky-star/vitrine-ai-pro na branch main foi consultado; README.md descreve separação APP/FLOW/AI/OPS e separação de credenciais HML/PROD.
- Repositório cschibelsky-star/roteia-ai-lab possui README.md na branch main.
- Consulta V5 ao inventário identificou workspaces roteia-ai-lab, centro-ia-roteia-pr122-hml, vitrine-api-lab-hml e ai-provider-lab.
- O workspace roteia-ai-lab possui arquivo não rastreado scripts/via-code-02.js segundo a consulta de estado; preservar.
- O workspace centro-ia-roteia-pr122-hml estava um commit à frente da referência remota, segundo a consulta; preservar.
- Nenhum endpoint Invision/Roteia foi testado nesta missão; nenhuma credencial foi inspecionada.

## Estado por componente
- Governança documental: EM IMPLEMENTAÇÃO; verificar arquivos e commit.
- Inventário técnico detalhado de APIs/modelos: PENDENTE.
- Conector independente de uso pessoal: NÃO IMPLEMENTADO NESTA MISSÃO.
- Execução E2E real no Codex: NÃO VERIFICADA.
- Integração Centro IA: NÃO INICIADA NESTA MISSÃO.
- Deploy e produção: NÃO AUTORIZADOS.

## Próxima ação única
Auditar configurações e contratos reais dos conectores existentes (somente leitura), montar matriz capacidades/IDs/custos/endpoint por gateway e definir adaptador mínimo do Codex. Não criar PR ou repositório novo apenas para documentar.

## Bloqueios
Acesso à configuração local ativa do Codex e aos contratos autenticados das contas não foi verificado. Chamadas pagas exigem orçamento explícito. Não afirmar sucesso E2E.

## Critério de pronto
Prova reproduzível de tarefa Codex usando o gateway escolhido e modelo efetivo, com isolamento pessoal/empresarial, custo verificável quando aplicável, modo de reinstalação e evidência sanitizada. Teste verde ou PR não satisfaz isoladamente este critério.

## Inclusão de escopo — Avello2 (2026-11-14)
O Avello2 é componente obrigatório do Hub, além de Roteia e Invision. O objetivo contempla uso operacional no ambiente pessoal e possibilidade de integração ao ecossistema Vitrine, com contextos, credenciais, permissões, limites e auditorias separados.
Inclusão no escopo NÃO equivale a autorização de implantação em produção, habilitação de ferramentas mutáveis ou validação de disponibilidade. Auditar capacidades reais, autenticação, endpoints, compatibilidade com Codex/ChatGPT e contratos do Avello2 antes de usar.
Próxima ação inclui inventário Avello2, com os demais gateways e conectores existentes.
