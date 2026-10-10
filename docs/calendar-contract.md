# Agenda canônica em homologação

## Fonte de verdade e limites

A homepage atualmente aprovada é PHP em vitrine-ai-pro (PR #94). Sua agenda curada é a lista events.json em EVENTS_DATA_DIR, com volume persistente no ambiente isolado. Homepage, /eventos, /?source=pwa e /calendar-feed.php consultam essa mesma lista e a mesma política. O React de visite-sumare (PR #1) é outro cliente e uma origem de candidatos; não representa uma segunda agenda pública no HML reconciliado.

A inspeção do compose da VPS em 09/10/2026 encontrou os domínios público e HML no mesmo serviço. Por isso esta alteração usa docker-compose.calendar-hml.yml, novo container, novo volume e rota própria. Não atualizar ou recriar o container antigo. Não houve merge nem cutover de produção. As árvores PHP e React antigas permanecem preservadas.

## Contrato v1

| Canônico PHP/feed | React compatível |
| --- | --- |
| title | name |
| place | location |
| summary | description |
| start_date / end_date | date_start / date_end |
| start_time | time_start |
| image | image_url |
| published | confirmed com approved_at + approved_by |
| candidate / rejected / cancelled | pending/draft / rejected / cancelled |
| source_url / source_name / verified_at | mesmos campos |

Datas são AAAA-MM-DD em America/Sao_Paulo. O último dia é inclusivo; sem fim, o evento dura até o início. Datas impossíveis/invertidas, fonte ausente ou não HTTP(S), conferência futura, cancelamento e falta de título/local impedem exibição. Não se inventa validade ou conferência. published preserva o gate administrativo legado, mas precisa cumprir os demais critérios. confirmed sozinho não prova aprovação; importações entram como candidatos e recebem aprovação explícita no admin canônico.

## Integração atual

1. /admin/events.php recebe uma lista JSON do React/radar com IDs estáveis, origem explícita, login e CSRF. Reimportar a mesma origem + ID é no-op e não sobrescreve curadoria existente.
2. O administrador confere os campos e aprova. A gravação tem lock, backup obrigatório e substituição atômica; IDs, campos extras e registros antigos permanecem armazenados.
3. Home, agenda e PWA passam a exibir o mesmo evento. Cancelar preserva o registro e remove sua exposição.
4. React lê o feed canônico quando VITE_CALENDAR_FEED_URL estiver definido no build. Escritas administrativas do React continuam na API de origem; para publicar no HML canônico, importar e revisar no admin PHP. Não há sincronização automática de duas bases.

O seed do HML preserva os três registros legados e o candidato SESI Diverte encontrado na VPS, sem aprovação. Nenhum fixture de teste é publicado. O feed público tem whitelist de campos e no-store. Service worker não armazena feed/admin nem documentos da agenda; sem rede, não apresenta programação possivelmente cancelada como atual.

## Próxima integração: coleta e notificações

Coletor futuro: Cultura/Turismo/organizadores -> candidatos com origin + external_id -> conheca_import_candidates -> conferência administrativa -> conheca_review_event. O coletor deverá ter autenticação própria, deduplicação de alterações e histórico de execução; não deve usar published nem fabricar verified_at. O import atual é manual e autenticado por sessão; não é um endpoint de coleta automática.

Notificações futuras: consumir mudanças confirmadas depois do commit de conheca_mutate_events, usando id + revision como chave idempotente, com outbox transacional, retries e consentimento dos assinantes. Incluir aprovação, atualização e cancelamento; conferir a política novamente antes do envio. O feed de eventos futuros não é um log de mudanças e não deve ser usado sozinho para notificar cancelamentos. Não há push/coleta automática habilitados nesta entrega.

## Validação

php tests/calendar.php
php tests/calendar-integration.php

O teste integrado cria dados temporários, importa o contrato React, aprova, renderiza homepage/agenda/PWA/feed, cancela e confirma remoção, backups e preservação. A imagem HML executa esses testes antes de iniciar. Produção só pode receber a mudança depois de revisão dos PRs, conferência administrativa dos candidatos e decisão explícita de promoção.

HML PHP: p000095.hml.vitrineiapro.com.br. A curadoria reutiliza ADMIN_TOKEN do HML React por cópia server-side, sem rotação ou divulgação; usuário curadoria. A produção não recebe essa configuração.
