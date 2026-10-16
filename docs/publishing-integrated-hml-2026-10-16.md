# Homologação integrada do publicador — 16/10/2026

Estado: PARCIAL. Social validado em container privado na VPS; integração entre aplicativos ainda não homologada.

## Atualização operacional — 16/10/2026, 09h16 BRT

Estado PARCIAL: bloqueio inicial de build/inicialização superado para o projeto canônico
vitrine-social-publishing-v2-validation. Esta atualização substitui o estado anterior de VPS
“preparado, sem ativação”; as seções históricas abaixo registram a primeira etapa.

Foi encontrado um projeto de validação já cadastrado nos dois conectores, com Compose autorizado.
Sua árvore limpa estava em 8a3e447. Atualizações feitas por fast-forward até
7bdb7c0a22b95e6380fd1c1a6950e9fe36c57ccb, referência atual do PR Social #9.
As cópias social-publisher-v2-hml e marketing-publisher-v2-hml criadas anteriormente não receberam
serviços; não são ambientes adicionais homologados e não devem ser usadas como nova referência.

Operações confirmadas via Super Centro Operacional:
- Build da imagem; a primeira chamada expirou, mas inspeção posterior confirmou a imagem criada.
- Inicialização de validation_secret_init, validation_db, validation_redis e validation_app.
- Credenciais locais dedicadas criadas no volume de validação sem revelar valores.
- Estágio Docker validation opt-in com dependências de testes e SQLite; estágio final padrão production
  deriva do runtime original e continua sem dependências de desenvolvimento.
- Startup exclusivo do Compose de validação executa os testes antes do PHP-FPM.
- Rede interna, sem portas publicadas; storage, banco e secrets em volumes próprios.
- MariaDB healthy; Redis e PHP-FPM ativos. Duas rotas de mídia listadas via Laravel.

Evidência no container validation_app (PHP 8.3.33, PHPUnit 12.5.30):
OK (25 tests, 172 assertions), em 1,762s.

Escopo efetivamente validado:
- Suíte Social PublishingSafetyTest: 23 testes/158 assertions, com SQLite em memória,
  Storage fake e HTTP simulado, executada no container da VPS.
- Worker real por subprocesso queue:work redis em fila sintética única no Redis validation_redis:
  falha controlada na tentativa 1, conclusão na tentativa 2, fila vazia e nenhuma terceira execução.
- Executor real schedule:run com agenda sintética isolada: evento devido executado e evento vetado ignorado.
- Filas e marcadores sintéticos removidos no finally. Nenhum envio a redes sociais.

A suíte não prova consumo de ContentProject por workers persistentes, transações no MariaDB,
concorrência de dois publicadores, reconciliação entre hosts, scheduler em daemon nem
tráfego Social -> receptor Marketing pela rede. Os testes de contratos continuam usando HTTP fake.
A prova do scheduler substitui sua agenda no container de teste, não executa a agenda de clientes.
QUEUE_CONNECTION do aplicativo de validação permanece sync; o teste seleciona Redis explicitamente.

CI do commit 7bdb7c0:
- Publishing safety SUCCESS: https://github.com/cschibelsky-star/vitrine-ai-social-enterprise/actions/runs/38899234210
- Product Contract SUCCESS: https://github.com/cschibelsky-star/vitrine-ai-social-enterprise/actions/runs/38899234514

Pendentes: receptor Marketing isolado em rede; fila persistente de publicações reais simuladas;
calendários e permissões ponta a ponta; mídia HTTPS/Range; Cockpit; consentimentos/gates Meta.
Sem merge, migração de banco persistente, publicação real, alteração de produção ou uso do PR #10.
O banco MariaDB de validação foi inicializado pelo container, sem executar migrations de aplicação.



## Referências
- Social PR #9, head auditado eea7f62cdce98de2eea57f8a70f3e790b1d7ccce.
- Marketing PR #102, código testado 356c302f3f2196709401b65b025823523e09344a.
- PR experimental Social #10 não integrado.

## Intervenção confirmada
A revogação e exclusão de dados Meta percorriam apenas arquivos diretamente em marketing/publisher/meta.
As conexões do Social ficam em bridge/connections. O helper compartilhado agora percorre allFiles,
removendo conexões aninhadas somente quando meta_user_id corresponde à solicitação assinada.
Teste preserva uma conexão pertencente a outro usuário.

Adicionados testes HTTP do receptor v2 com contas fictícias, Storage fake e Http::preventStrayRequests:
Instagram Feed JPEG; Stories JPEG e MP4; Reels MP4; Facebook Feed PNG; repetição sem reenvio;
conexão e consulta de ledger separadas por cliente; rejeição de contrato legado;
Facebook Stories/Reels bloqueados; retomada pelo comando usando o container existente;
consulta de status sem efeitos externos; revogação assinada incluindo conexões aninhadas.
Esses testes não executam os dois aplicativos pela rede e não comprovam autorização ponta a ponta da UI.

## Evidências
- Native publishing: SUCCESS, 19 testes e 74 assertions, lint e Blade aprovados.
  https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/38897918001
- Marketing Agents: SUCCESS, 95 testes, 440 assertions, 2 skipped.
  https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/38897918049
- Primeira execução identificou ausência de Mockery no teste de comando; teste ajustado para Artisan::call.
  Nenhuma dependência foi acrescentada.

## VPS: preparado, sem ativação
Criadas cópias novas a partir do GitHub:
- social-publisher-v2-hml: /srv/projects/social-publisher-v2-hml/repository
- marketing-publisher-v2-hml: /srv/projects/marketing-publisher-v2-hml/repository

Marketing atualizado por fast-forward para o código testado. Árvores isoladas inicialmente limpas.
Manifesto V5 do Social aponta para docker-compose.publisher-v2-validation.yml.
O registry do Super é separado e ainda registra compose vazio para esse projeto.
Nenhum container desses candidatos foi iniciado por esta intervenção.

Build via V5: controlled_service_not_allowed para validation_app.
Fallback Super: compose_file_not_configured. VPS Operations e V4 expõem deploy/testes do
aplicativo principal, sem executor equivalente para esse novo Compose; não utilizados para evitar
atingir runtime existente. Ambiente local sem PHP, Docker ou configuração SSH disponível.

O Compose candidato possui rede interna, volumes próprios e nenhuma porta publicada, mas
QUEUE_CONNECTION=sync, CACHE_STORE=array, sem worker/scheduler/receptor Marketing.
Portanto esse Compose sozinho não permite validar fila real, locks compartilhados, scheduler,
bridge em rede, calendário integrado ou mídia pública HTTPS/Range.

Status somente leitura do Social existente mostrou app, web, worker e scheduler ativos,
MariaDB e Redis healthy. Isso não comprova execução do PR #9.
Consulta Compose do Core via V5 falhou por CORE_DB_PASSWORD ausente no contexto de interpolação
da ferramenta; isso não comprova indisponibilidade do container nem autoriza ler secrets.

## Gates restantes
1. Configurar e autorizar executor para o novo ambiente isolado, sem renomear serviços para contornar política.
2. Preparar ambos aplicativos com DB, fila, cache/locks e storage exclusivos; sem credenciais reais Meta.
3. Executar migrações necessárias apenas no banco de testes após gate específico.
4. Introduzir receptor/provedor simulado em rede interna e workers/schedulers reais; testar reinícios,
   timeout, retomada e concorrência. Não usar endpoints Meta reais.
5. Testar cancelamento e reagendamento concorrentes, geração antiga, aprovação expirada, cliente sem
   permissão e acesso indevido a mídia. CI Social anterior contém casos; não foram reexecutados na VPS.
6. Conferir calendário Marketing e Social, horários America/Sao_Paulo/UTC e contrato de espelhamento.
   A presença de agendadores separados não comprova sincronização dos calendários.
7. Validar originais imutáveis e respectivas cópias de publicação por hash. Social prepara JPEG de PNG
   antes de nova aprovação; os arquivos criativos originais não foram acessados nem modificados aqui.
8. Provisionar HTTPS/Range de mídia e acesso HML com cartão Cockpit somente após isolamento comprovado.
9. Validar consentimento/conta/permissões/elegibilidade Meta em gate separado antes de qualquer publicação real.

Sem merge, publicação real, geração de mídia, migration, troca de produção ou integração do PR #10.
Publicadores permanecem desativados por padrão. CI verde não equivale à homologação operacional.
