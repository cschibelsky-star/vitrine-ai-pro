# Política de Homologação Obrigatória e Acesso pelo Cockpit
Status: proposta aprovada pelo responsável do projeto em 2026-10-08; implantação técnica pendente.

## Regra mandatória para todos os produtos e projetos
Nenhum deploy, publicação, alteração de conteúdo técnico ou cutover em produção deve ser executado sem:
1. Ambiente HML ativo, DNS resolvendo, TLS válido, endpoint HTTP saudável;
2. URLs HML e PROD distintas, com infraestrutura/segredos/dados segregados;
3. Mesma versão imutável (SHA/build) testada em HML e candidata à produção;
4. CI, smoke, testes de navegação, mídia, autenticação, PWA e formulários aplicáveis aprovados;
5. Aprovação humana explícita, com usuário, instante, versão, evidências e escopo registrados no Cockpit;
6. Backup verificável e plano de rollback antes de qualquer mutação em PROD.

**Fail closed:** informação desconhecida, HML sem DNS, CI vermelho, ambiente compartilhado, falta de evidências ou aprovação vencida impedem o botão Publicar em Produção. Nenhuma exceção implícita por CLI ou API.

## Cockpit: contrato mínimo
Cada projeto cadastrado deverá exibir:
- Identificador, nome, repositório, branch/tag/SHA, responsável;
- URL HML, URL PROD, data da última verificação e health/DNS/TLS (separadamente);
- Estado da homologação: NAO_CONFIGURADA, BLOQUEADA, EM_TESTES, APROVADA, REPROVADA, DESATUALIZADA;
- Status CI, testes, evidências de QA, pendências e data;
- Ações: Abrir HML, Abrir Produção, Ver testes, Solicitar aprovação, Publicar (somente com gate habilitado), Reverter;
- Registro de auditoria imutável para aprovar/reprovar/publicar/reverter, por usuário e versão.

## Gate de publicação (servidor e interface)
A autorização no backend é obrigatória, independentemente de UI:
`can_publish = dns_hml_ok && tls_hml_ok && health_hml_ok && isolated && tests_passed && ci_green && approved_sha === target_sha && backup_ready && rollback_ready && approver_authorized`
O gate deve rejeitar reuso da aprovação após novos commits, falha ou expiração dos testes.
Nunca armazenar credenciais ou dados reais de clientes em HML; HML não deve disparar publicações reais.

## Primeiro piloto — Conheça Sumaré
- Produção: https://www.conhecasumare.com.br
- HML planejada no compose: https://conheca-sumare-hml.vitrineaipro.com.br
- Em 2026-10-08 HML não resolvia no DNS (NXDOMAIN); domínio e produção apontam para o mesmo serviço Traefik no compose de HML, logo o isolamento **não foi demonstrado**.
- Correção necessária: criar DNS no provedor competente, configurar runtime HML segregado, validar certificado e health, testar links, eventos, imagens, cadastro comercial e PWA.
- O novo layout premium aprovado deve ser comparado visualmente com o mockup e aprovado antes de qualquer publicação.
- Não executar deploy nem migração de dados como parte deste documento.

## Critérios de aceite
1. HML responde de fora da VPS, por HTTPS, sem acessar dados PROD;
2. Cockpit lista o projeto, mostra ambos os endereços e o estado verificável;
3. Em HML com NXDOMAIN, botão de publicação fica bloqueado e exibe causa;
4. Após aprovação, alterações no SHA invalidam a permissão;
5. Aprovação, deploy e rollback deixam trilha auditável;
6. Validação end-to-end em pelo menos um projeto piloto antes da extensão a todos.

## Implantação gradual
Fase 1: inventariar projetos e ambientes sem mutação.
Fase 2: separar e habilitar HML do Conheça Sumaré.
Fase 3: implementar cards/estado e gate no Cockpit.
Fase 4: executar testes negativos e positivos.
Fase 5: estender aos demais produtos, preservando os serviços existentes.
