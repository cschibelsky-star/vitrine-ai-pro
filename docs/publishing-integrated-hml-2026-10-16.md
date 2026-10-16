# Homologação integrada do publicador — 16/10/2026

Estado: PARCIAL. CI simulada validada; runtime integrado na VPS ainda não homologado.

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
