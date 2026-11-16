# Cockpit HML — Escritório de Agentes IA M1: integração preparada, implantação pendente

Data de registro: 2026-11-16.
Projeto alvo: cockpit-admin-v1-hml (manifest V5).
URL planejada (NÃO PUBLICADA): https://hml.vitrineiapro.com.br/cockpit/escritorio-agentes

## Escopo autorizado
O usuário autorizou integrar o Escritório M1 exclusivamente ao Cockpit HML, preservando cartões e funcionalidades existentes. Produção, ChatGPT, bancos e missões reais fora de escopo.

## Evidências consultadas (V5)
- Projeto canônico cockpit-admin-v1-hml, workspace /srv/projects/cockpit-admin-v1-hml, repositório cschibelsky-star/vitrine-ai-pro.
- Manifest aponta branch feature/cockpit-admin-v1-hml, mas worktree efetiva está em feature/cockpit-assessor-online-hml; divergência detectada.
- Worktree HML com duas modificações não commitadas já existentes ANTES desta tarefa: config/cockpit-applications.php e routes/cockpit.php. Não foram sobrescritas, limpas ou movidas.
- O arquivo da configuração atual e a rota foram lidos; lista atual inclui 12 aplicações e alterações locais de outros projetos.
- View do Cockpit renderiza o catálogo a partir de config('cockpit-applications') e usa admin_url para cartões.
- Cockpit é servido por /cockpit no domínio HML, com autenticação e gate isAdmin().

## Alterações versionadas apenas nesta branch, isoladas da worktree HML
1. resources/views/cockpit/escritorio-agentes.blade.php — painel M1 DEV/ECO, mapa 2D, 9 definições ECO do seeder, catálogo de habilidades, Kanban vazio, sem chamadas externas nem status fictícios.
2. config/cockpit-applications.php — cartão do Escritório com status development, health not_verified e URL da rota HML.
3. routes/cockpit.php — GET /cockpit/escritorio-agentes acessível apenas quando APP_ENV=homologation, Auth::check(), admin e usuário ativo; Cache-Control no-store, X-Frame-Options DENY; retorno ao Cockpit.
Os arquivos foram relidos no GitHub após gravação; alterações confirmadas. Nenhum arquivo da worktree foi alterado.

## Gate / Status
- Código da integração: PREPARADO na branch `feature/cockpit-escritorio-agentes-m1-hml-20261116`.
- Fonte de dados: inventário de seeder, não banco ou telemetria real.
- Lint PHP e teste Laravel da branch: PENDENTES (sem ambiente local isolado equivalente nesta execução).
- CI do GitHub: NÃO EXECUTADO.
- Deploy HML, rota pública e health HTTP: NÃO EXECUTADOS; não alegar acesso real.
- Motivo do bloqueio: worktree HML suja com alterações importantes existentes; deploy disponível exige worktree limpa e pode incorporar migrations/efeitos fora do escopo.
- Não foi realizado reset, force checkout, merge, compose up, migration nem alteração em produção.

## Rota segura de retomada
1. Preservar e reconciliar explicitamente as duas alterações não commitadas existentes na worktree com GitHub em branch/commit separado, sem sobrescrever o trabalho atual.
2. Em checkout limpo e isolado da branch desta entrega, executar PHP lint, testes Laravel e comparar diff com base original.
3. Habilitar aplicação HML somente após autorização operacional compatível e confirmação de que a rota de deploy não aciona migration inesperada.
4. Confirmar 200 autenticado para admin, 403 para usuário sem permissão e 404 fora de homologação, cartão existente preservado e DEV/ECO renderizando; registrar evidências.
5. Se falhar, rollback específico do novo cartão, rota e view, sem regressão dos itens anteriores.

**Não abrir PR/merge/deploy automático até resolver esses gates.**

## Revalidação na retomada — 2026-11-16
- `project_git_status` do `cockpit-admin-v1-hml` confirma branch ativa `feature/cockpit-assessor-online-hml` e alterações locais em `config/cockpit-applications.php` e `routes/cockpit.php`. **Nada foi limpo ou modificado.**
- `project_git_compare_origin` retornou dirty=true, relation=diverged, ahead=8, behind=3 para branch indicada pelo manifesto `feature/cockpit-admin-v1-hml`. As fontes relatam refs diferentes; reconciliar antes de publicar, sem inferir que a comparação corresponde ao checkout ativo.
- `project_compose_service_status` para `cockpit_web` recusou `controlled_service_not_allowed`. **Bloqueio de política do conector**, não prova de serviço fora do ar; não tentar escalar permissões.
- `mcp__GitHub__compare_commits` na branch de integração contra `feature/cockpit-assessor-online-hml`: 4 commits à frente, 0 atrás, 4 arquivos alterados (config +1 linha; routes +11 linhas; view +50 linhas; este handoff). Nenhum arquivo anterior removido.
- `project_php_lint` retornou exit 0 para os DOIS arquivos PHP **atualmente presentes no workspace HML**, em contêiner efêmero. **Isto NÃO valida a sintaxe dos dois arquivos na branch de integração**, já que ela ainda não foi instalada no workspace.
- Sem nova escrita no runtime; HML segue NÃO PUBLICADO, gate pendente; sem deploy/merge/migration ou modificação do ChatGPT.
