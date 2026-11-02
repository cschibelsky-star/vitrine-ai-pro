# Norma de engenharia: entrega funcional completa (EFC-001)

Status: proposta para adoção transversal. A aprovação deste documento não substitui proteções de CI, branch protection nem homologação real.

## Princípio
A unidade de trabalho é uma funcionalidade completa, com critérios de aceitação verificáveis, e não um pequeno commit ou um teste verde. É proibido declarar conclusão apenas com lint, build ou testes unitários.

## Fluxo obrigatório
1. Diagnosticar o fluxo ponta a ponta, contratos, dependências, regressões e ambiente.
2. Definir escopo, critérios de aceitação, responsáveis, evidências e plano de reversão antes de alterar.
3. Implementar de forma integrada em branch de trabalho, preservando GitHub como fonte de verdade e sem sobrescrever alterações alheias.
4. Executar testes rápidos e incrementais durante a implementação. CI por commit pode continuar; seu resultado não é um marco de entrega ou motivo para interromper o trabalho.
5. Executar bateria consolidada: testes específicos, integração, regressão, segurança e falhas esperadas, com logs e evidências.
6. Homologar o percurso completo com dados representativos, interfaces e integrações; registrar aprovação expressa da homologação.
7. Só então propor merge e deploy controlados, com autorização operacional aplicável e rollback. Nunca inferir autorização de deploy de um pedido genérico de continuidade.
8. Validar em produção o comportamento aprovado, saúde e ausência de regressão; registrar o estado real (implementado, testado, homologado, publicado, validado).

## Continuidade operacional
Quando o responsável disser EXECUTAR, avançar por todas as etapas seguras e já autorizadas do mesmo escopo, sem solicitar nova aprovação a cada commit. Parar somente em bloqueio real, alteração de escopo, operação sensível não autorizada ou gate obrigatório. Reportar marcos substantivos, não microalterações.

## Critérios de encerramento
- Fluxo ponta a ponta funcional e reproduzível.
- Casos negativos e regressões relevantes testados.
- Evidência da homologação, aprovação e versão identificada.
- Produção verificada, quando a entrega incluir publicação.
- Pendências e riscos residuais documentados; sem marcar como concluído se houver bloqueio.

## Aplicação
Norma proposta para Core, Factory, VIA, TV Sumaré, Assessor Online e demais produtos Vitrine IA Pro. A adoção técnica exige integrar templates de PR, checklists dos agentes e gates de CI de cada repositório; este documento sozinho não altera essas proteções.

## Exemplo: Radar 1.3
A entrega é coleta -> resolução de fonte -> classificação e atualidade -> Editor IA -> revisão humana -> aprovação -> publicação -> auditoria. Quotas editoriais não podem limitar a capacidade técnica de processamento. Não há publicação automática sem aprovação humana.
