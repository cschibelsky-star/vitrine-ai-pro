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
- Critérios G0–G6 e política de correção em docs/marketing/OPERACAO-CHATGPT.md.
- Matriz de integração em docs/marketing/CONTRATOS-E-INTEGRACOES.md.
- Classe pura AutonomousHandoffGate e testes de bloqueio/validade.
- CLI de validação offline de JSON sem rede nem execução de serviço externo.

## Estado das evidências
Validação inicial GitHub Actions do PR #162: Marketing Agents Core Tests PASS (26 testes, 81 assertions), PHP Syntax PASS e Architecture Inventory PASS em 2026-11-08. Links: https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42493949232 ; https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42493949383 ; https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42493949363 .
Validação no commit 9629dd84a282f75cbd9715625e1fd22fe2be84a5: Marketing Agents Core Tests PASS (29 testes, 91 assertions), PHP Syntax PASS e Architecture Inventory PASS. Evidências: https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42494908992 ; https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42494908990 ; https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/42494908987 . Novo CI no PR deve validar o handoff-piloto bloqueado em tests/Fixtures/Marketing/handoff_via_observer_blocked.json. A revisão independente ainda não foi obtida (consulta GitHub PR #162 sem reviews); o handoff-piloto permanece explicitamente blocked; não declarar aprovação final. O gate local nunca garante autenticidade de CI nem autorização de avanço na VIA.
Não houve deploy HML, execução de APIs pagas, publicação social, migração ou merge nesta primeira entrega.

## Próximos responsáveis
1. QA: verificar o CI, executar testes unitários negativos e conferir segurança/compatibilidade.
2. Arquiteto: aprovar fronteiras entre Marketing e Social e versões dos contratos.
3. VIA: integrar verificação externa de evidências em HML, permanecendo OBSERVER até autorização específica.
4. DevOps: projetar homologação isolada com backup e rollback antes de qualquer alteração de runtime.
5. Proprietário: autorizar separadamente merge, deploy, publicação e custos quando cabíveis.


## Continuidade autorizada em 08/11/2026
Implementados testes adicionais em AutonomousHandoffReturnSafetyTest.php e contrato de devolução em CONTRATO-DEVOLUCAO-VIA.md. VIA #37 recebe correlação com política de workflows/identidades, adaptador do contrato PHP e plano puro needs_correction. Factory #20 alinha saída JSON/exit 3 para metadados conformes e bloqueio de branches principais. Verificações locais Node: 84 testes passaram; PHP/PHPUnit dependem do novo CI (runtime local sem PHP). Não há devolução persistida, integração a filas, homologação ou autorização de avanço. Preservados os nove agentes, config/marketing_agents.php e Core/Centro IA/Social. CI novo e revisão independente ainda devem ser verificados para os commits resultantes.
