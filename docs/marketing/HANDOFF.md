# Marketing IA — checkpoint de governança autônoma (2026-11-08)

## Escopo desta entrega
Adoção inicial da metodologia Factory/VIA exclusivamente no desenvolvimento do Marketing IA. Sem alteração do runtime, integração ou conteúdos de clientes.

## Origem e preservação
- Repositório: cschibelsky-star/vitrine-ai-pro
- Base escolhida: main em 5632c6188cfe284566d91c87f1fc6aef83d8655e
- Branch candidata: feature/marketing-autonomous-governance-v1-20261108
- Branch operacional Marketing HML existente: feature/marketing-agents-core-v1, não modificada.
- Referências: Factory PR #20, VIA PR #37, Marketing PR #65 (mesclado), Marketing #102 e #150 (draft), Social Hub #9/#12/#16/#17 (candidatos).

## Entregas propostas na branch
- Regras escopadas em app/Marketing/AGENTS.md.
- Critérios G0–G6 e política de correção em docs/marketing/OPERACO-CHATGPT.md.
- Matriz de integração em docs/marketing/CONTRATOS-E-INTEGRACOES.md.
- Classe pura AutonomousHandoffGate e testes de bloqueio/validade.
- CLI de validação offline de JSON sem rede nem execução de serviço externo.

## Estado das evidências
Os testes de CI e a revisão independente devem ser consultados no PR; não declarar aprovação até existirem resultados verificáveis. O gate local nunca garante autenticidade de CI nem autorização de avanço na VIA.
Não houve deploy HML, execução de APIs pagas, publicação social, migração ou merge nesta primeira entrega.

## Próximos responsáveis
1. QA: verificar o CI, executar testes unitários negativos e conferir segurança/compatibilidade.
2. Arquiteto: aprovar fronteiras entre Marketing e Social e versões dos contratos.
3. VIA: integrar verificação externa de evidências em HML, permanecendo OBSERVER até autorização específica.
4. DevOps: projetar homologação isolada com backup e rollback antes de qualquer alteração de runtime.
5. Proprietário: autorizar separadamente merge, deploy, publicação e custos quando cabíveis.
