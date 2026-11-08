# Operação autônoma supervisionada — Marketing IA (v1)

Status: proposta implementada em branch isolada; não implantada em runtime e não homologada na VPS.
Repositório canônico: cschibelsky-star/vitrine-ai-pro. Subsistema: app/Marketing; contratos: resources/schemas/marketing.
Objetivo: desenvolver e melhorar o Marketing IA com a Factory e revisão VIA sem duplicar o motor de distribuição do Social Hub.

## Separação de papéis
**Engenharia (Factory):** arquiteto -> desenvolvedor -> QA independente -> segurança -> supervisão VIA -> DevOps de HML.
**Operação criativa (Marketing IA):** diretor -> estratégia -> planejamento -> copy -> direção de arte/vídeo -> distribuição proposta -> QA de marca -> aprovação humana.
Os nove agentes de Marketing existentes em config/marketing_agents.php são de produção de campanhas, NÃO substituem os agentes de engenharia.

## Gates de desenvolvimento
| Gate | Critério |
| --- | --- |
| G0 | Escopo, autorização, branch isolada, orçamento e operações proibidas explicitados |
| G1 | Contratos, ownership, impactos e compatibilidade com Core, Centro IA e Social Hub revisados |
| G2 | Diff mínimo, arquitetura preservada, ausência de credenciais e efeitos externos |
| G3 | Testes positivos, negativos, multi-tenant e regressão executados; registrar comando, ambiente, resultado e evidência |
| G4 | Revisão independente documentada e ações críticas de segurança aprovadas |
| G5 | Homologação isolada e verificável se houver alteração de fluxo/runtime; sem confundir CI com deploy |
| G6 | Handoff com SHA, artefatos, verificações, riscos e próximo responsável; verificação da VIA com evidência autenticada |

Se um gate aplicável falhar, retornar ao executor e corrigir somente o item; não avançar com testes ausentes.
A VIA em modo OBSERVER responde pending_external_verification, nunca autorização automática.
Nenhum agente pode declarar aprovação humana substituída por revisão de IA.

## Regras de execução
1. Consultar o estado do Git, mudanças anteriores e branches/PRs correlatos; preservar qualquer árvore suja.
2. Contrato primeiro; consumidor depois. Mudanças na ponte Social Hub exigem teste conjunto e HML isolada.
3. Priorizar Centro IA para roteamento de modelos; nenhuma credencial Roteia embutida no Marketing.
4. Criativos: um arquivo final por mídia, logo oficial sem redesenho, sem repetição de peças, branding por tenant e aprovação da versão exata antes do envio.
5. Publicação: jamais gerar mídia nova durante publish; usar hash/identidade de peça aprovada, idempotência e reconciliação antes de retry.
6. Contratos de publicação dos PRs Marketing #102/#150 e Social #9/#12/#16/#17 são candidatos, não runtime comprovado.
7. Checagens de handoff propostas neste PR verificam FORMATO e consistência local; evidência de CI/PR só é válida após verificação externa.
8. Somente branch/docs/testes nesta entrega. Sem merge, deploy, migrations em banco, consumo de IA ou publicação externa.

## Aceite por etapa
- Governança v1: AGENTS escopado, contrato, teste de sucesso e testes negativos, documentação e PR draft com CI.
- Publicador: uma operação por versão/canal/cliente, sem duplicatas após timeout e IDs externos rastreáveis.
- Criação: preview, arte final, versão, cliente e aprovação humana consistentes.
- Integração: isolamento de tenants, autenticação e limites de rede verificados.
- Comercial: não liberar antes da homologação HML e autorização para produção.

## Ferramentas
- Testes unitários: vendor/bin/phpunit tests/Unit/Marketing
- Lint: php -l app/Marketing/Governance/AutonomousHandoffGate.php
- Exemplo de verificador de handoff: php tools/marketing/verify-autonomous-handoff.php /caminho/handoff.json
O último script somente valida metadados; não autentica provas nem autoriza publicação.

### Códigos de saída do verificador
- `1`: bloqueado por metadados/revisão/checks inválidos.
- `2`: arquivo ou JSON inválido.
- `3`: metadados estruturalmente conformes, **pendentes** de verificação externa. É deliberadamente diferente de zero; nenhum pipeline pode interpretar como aprovação da VIA.
- `0`: reservado para uma autorização final futura, não emitida neste piloto.

CI deve testar as três situações, sempre sem chamadas ao provedor, sem postagem e sem tráfego para o runtime da VIA.
