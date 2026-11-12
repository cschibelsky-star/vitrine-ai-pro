# Assessor Online — organização e melhoria

Data: 12/11/2026. Gate: pendente de implementação e validação funcional.

## Objetivo e fonte de verdade

Organizar o produto de gestão de gabinetes, independente do AssessorGov IA de licitações, preservando Core e sistemas existentes. Repositório: cschibelsky-star/vitrine-ai-pro. Base desta entrega documental: PR #137, branch feature/assessor-admin-demandas-20261028, commit 4c55e4bde9017d671e1b92ad1d2d7457cdfaef39. PR consultado aberto e draft.

composer.json confirma PHP ^8.3, Laravel ^12.0 e Filament ^3.3. routes/assessor_online.php contém login/logout, recuperação de senha, painel, cidadãos e demandas. Existência dessas rotas não comprova funcionamento, autorização ou persistência.

AGENTS.md, CLAUDE.md e docs/OPERACAO-CHATGPT.md retornaram 404 na branch documental; docs/HANDOFF.md retornou 404 na branch de desenvolvimento consultada. Não inferir ausência em outras branches.

## Preservação obrigatória

project_status da HML assessor-online-pr137-current-hml identificou cinco arquivos modificados:
- app/Console/Commands/ProvisionAssessorHmlAdmin.php
- docker-compose.assessor-laravel-hml.yml
- hml/assessor-nginx.conf
- hml/assessor/demo.html
- routes/assessor_online.php

Os diffs locais não foram lidos nesta etapa. Antes de atualizar runtime: preservar alterações e arquivos relevantes em mecanismo seguro, registrar referência recuperável, revisar diffs sem segredos e comparar com GitHub. Não usar reset/clean/checkout forçado. Não incorporar configurações operacionais automaticamente ao produto.

## Limites modulares propostos

Preservar componentes existentes; inventariar antes de mover arquivos.

| Módulo | Responsabilidade | Critério de aceite |
| --- | --- | --- |
| Identidade | Login, logout, redefinição e convite | Sessão segura; conta de gabinete sem privilégios Core |
| Gabinetes | Tenants, memberships e permissões | Autorização no servidor em todas as operações |
| Atendimento | Cidadãos, demandas e histórico | Isolamento por gabinete; transições válidas |
| Operação | Agenda, tarefas, documentos e indicadores | Inventariar implementação antes de anunciar disponibilidade |
| Comercial | Trial e planos pagos | Regras independentes da operação do gabinete |
| IA | Contrato existente do Centro IA | Credenciais server-side; sem roteador/cobrança paralelos |
| Interface | Views do produto e referência aprovada | Estados úteis e comparação desktop/mobile |

AUDESP/licitações pertencem ao domínio AssessorGov. A descrição do PR #149 menciona base no PR #137: revisar dependência antes de integrar; isso não prova contaminação do runtime.

## Ordem de execução

### P0 — Consolidar sem perder trabalho

1. Preservar e revisar os cinco diffs da HML.
2. Comparar commits, arquivos e dependências dos PRs #123, #124, #137 e #145.
3. Produzir matriz: componente existente, duplicado, divergente ou ausente.
4. Integrar mudanças mínimas em branch isolada, sem cherry-pick cego ou merge automático.

Aceite: baseline identificada, mudanças anteriores recuperáveis e diferenças revisadas. Esta entrega não executa a preservação nem a integração.

### P1 — Primeiro acesso e autenticação

O comando de convite inspecionado exige ambiente homologation, host HML, flag, destinatário autorizado e SMTP configurado. Cria tenant/usuário/membership em transação; gera token e envia e-mail depois. A compensação remove usuário, memberships e token, mas não remove explicitamente tenant recém-criado. Tratar tenant órfão como risco a testar, não incidente comprovado.

Correção proposta: rastrear recursos criados pela tentativa e compensar somente esses recursos quando seguro. Nunca apagar tenant preexistente ou com dados/membros criados concorrentemente. Tornar repetição controlada e auditável. Confrontar a mensagem fixa de 60 minutos com a configuração efetiva do broker. SMTP configurado não comprova entrega.

Aceite automatizado: falha de transporte/token, compensação, repetição, destinatário inválido, ambiente errado, expiração, replay, reenvio/revogação, rate limit e não enumeração. Testar middleware CSRF habilitado, regeneração/invalidação de sessão, membership inativa e múltiplos gabinetes sem seleção implícita.

Aceite HML posterior: entrega real, ativação pelo titular, login/logout/redefinição e acesso ao Core negado. Nenhum token/senha nos relatórios. Provisionamento real não realizado nesta entrega.

### P2 — Jornada e isolamento

Inventariar e testar cidadão → demanda → atribuição/movimentação → histórico → conclusão com evidência. Não inferir resolução de simples encaminhamento. Validar papéis e tenant em cada consulta/mutação, incluindo IDs de outro gabinete e referências a cidadãos externos ao tenant.

Aceite: gabinete A não lê/altera dados de B; pessoa A não acompanha protocolo de B; transições inválidas rejeitadas sem alteração parcial; histórico consistente e auditável.

### P3 — Completar módulos e experiência

Classificar agenda, tarefas, documentos, comunicação, indicadores e planos como ausente, implementado sem teste, testado isoladamente ou homologado. Implementar apenas lacunas comprovadas. Separar assinatura de boletim do atendimento. Usar referência visual aprovada da issue #116; demonstração fictícia não comprova painel real.

Regras comerciais descritas no PR #123: trial 15 dias, pré-pago 30 dias, anual 12 meses. Conferir implementação, limites de vigência e bloqueio no instante de expiração antes de habilitar comercialmente.

Preservar integração do PR #124 com o Centro IA e seu estado desabilitado até validação. Verificar execução, consumo, falhas controladas e ausência de dados pessoais desnecessários; não habilitar inferência ou cobrança nesta entrega.

## Revisão Avello2

Consulta explicitamente autorizada pelo usuário, realizada por avello_ask(provider=claude). Retorno status=ok; modelo informado pelo conector: claude-opus-5. Foram enviados apenas contexto técnico sanitizado, sem credenciais ou dados de cidadãos.

A revisão recomendou preservar HML, corrigir compensação/validade do convite, separar responsabilidades, revisar dependências e testar a jornada. Recomendações foram avaliadas: compensação não pode apagar tenant preexistente; merges sugeridos não estão autorizados por este documento; AUDESP permanece fora do núcleo de gabinetes. O provedor não executou alterações ou testes. Este registro comprova consulta, não validação funcional independente nem cobrança verificada.

## Evidências e limites desta entrega

- Leitura de arquivos GitHub e estado de PR/HML realizada pelo coordenador.
- Consulta Avello2 concluída; recomendações revisadas pelo coordenador.
- Alteração exclusivamente documental em branch separada.
- Nenhum teste funcional executado nesta entrega; não declarar CI ou homologação aprovados.
- Sem alteração HML/produção, provisionamento, migration, merge ou deploy.

Para futuras alterações de código: executar testes PHP/Laravel com banco isolado e registrar comando, commit, resultado e cobertura efetiva. Para HML: validar HTTP autenticado, UI e integrações separadamente. CI verde não comprova entrega de e-mail, isolamento de volumes ou conformidade visual.

## Handoff

Próxima tarefa: preservar/revisar diffs HML e inventariar testes existentes do primeiro acesso antes de corrigir código. Pendências de #140, #141, #142 e dos PRs associados não são encerradas por esta entrega. Cada etapa só avança com evidência funcional; falha retorna à correção.
