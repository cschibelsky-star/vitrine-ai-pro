# Cockpit HML M1 — preservação e reconciliação sem perda (2026-11-16)

## Escopo
Incluir Escritório de Agentes IA no Cockpit HML, preservando os trabalhos locais preexistentes. Nenhuma alteração da configuração do ChatGPT ou de produção.

## Verificações em conector operacional
- Projeto: `cockpit-admin-v1-hml`, worktree ativa `feature/cockpit-assessor-online-hml`.
- Arquivos com alterações locais antes da missão: `config/cockpit-applications.php`, `routes/cockpit.php`.
- `project_backup_status` retornou `backup_not_configured`.
- `project_compose_service_status(cockpit_web)` retornou `controlled_service_not_allowed`, bloqueio de política; não foi contornado.
- `project_git_compare_origin` reportou referência divergente em branch diferente da ativa, por isso não usá-lo como autorização de reset.

## Preservação realizada
1. Criada branch `backup/cockpit-hml-pre-escritorio-20261116` a partir de `feature/cockpit-assessor-online-hml`.
2. Os conteúdos EXATOS obtidos via `project_file_read_safe` para os dois arquivos modificados foram gravados nessa branch com commits `39b51ca95bd7181b762627e2926193f02fc7dfd0` e `926c2f97b241958ada50773c1656b7b7cfbd4418`.
3. Worktree no servidor permaneceu sem reset/checkout; backup é versionado no GitHub, não snapshot completo de disco. Preservação limitada aos dois arquivos identificados.

## Integração composta sobre o backup
Branch: `feature/cockpit-escritorio-m1-preserved-hml-20261116`.
- `resources/views/cockpit/escritorio-agentes.blade.php`: tela M1 offline sem telemetria, adicionada.
- `config/cockpit-applications.php`: cartão do Escritório adicionado sem remover linhas anteriores.
- `routes/cockpit.php`: rota HML-only/admin autenticação e `no-store`; conteúdo anterior preservado integralmente.
- Comparação GitHub com backup: 3 commits à frente, 0 atrás; somente estes 3 arquivos alterados, duas adições e um arquivo novo. Checagem estática mostrou todo o texto anterior preservado.

## Gate e limitações
- Preservação em GitHub: **confirmada**.
- Reconciliação documental da nova branch: **confirmada**.
- PHP lint dos novos arquivos da branch, testes Laravel, URL, autorização real, saúde do container: **não verificados**. Lint anterior foi do workspace, não da nova branch.
- Acesso SSH/container genérico não demonstrado como seguro para esta operação. Deploy de Git disponível exige árvore limpa e pode incluir migrations e alterações não autorizadas.
- Nenhum deploy, merge, migrations, reinício, edição do workspace HML nem pagamento de API realizados.
- Não afirmar que o endereço https://hml.vitrineiapro.com.br/cockpit/escritorio-agentes está publicado.

## Próxima ação
Validar PHP lint da branch composta em runner isolado com acesso ao repositório, e obter rota autorizada para atualizar o Cockpit HML sem reset, migration ou alteração de outros projetos. Após implantação explícita, testar acesso admin e bloqueio para usuário não autorizado, e checar cartões anteriores.

## Tentativa controlada de publicação M1 (2026-11-16)
- A rota V5 de patch pontual foi testada somente para o arquivo **novo e ausente** `resources/views/cockpit/escritorio-agentes.blade.php`, utilizando o conteúdo da branch GitHub e `confirm=EXECUTAR`.
- Leitura anterior do caminho retornou `project_file_not_found`; o patch também retornou **`project_file_not_found`**, indicando que a ferramenta só altera arquivos que já existem. A operação não criou nem modificou esse arquivo.
- Por isso o catálogo de aplicações e as rotas HML **não foram modificados**: seria incorreto expor cartão cuja página não existe.
- A branch consolidada continua com os três arquivos prontos; backup existente permanece intacto.
- V5 não oferece ferramenta de criação de arquivo nesse namespace, o serviço `cockpit_web` retornou anteriormente `controlled_service_not_allowed`, e o deploy Git genérico dos conectores alternativos tem efeitos fora deste escopo (Composer/migrations) e depende de árvore Git limpa.
- **Gate:** PENDENTE, nenhuma publicação/URL 200 comprovada. É necessária uma rota autorizada de criação de arquivo isolado e atualização controlada do Cockpit HML, sem migrations, reset ou alteração em produção.
