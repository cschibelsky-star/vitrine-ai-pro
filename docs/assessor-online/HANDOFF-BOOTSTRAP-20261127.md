# Handoff — bootstrap de autenticação

Referência: 27/11/2026, PR #185. Base: feature/assessor-admin-demandas-20261028, commit 4c55e4bde9017d671e1b92ad1d2d7457cdfaef39.

## Correção

Dockerfile HML omitia a migration existente 2026_11_01_000003_add_assessor_auth_columns_to_users.php. A sequência passa a executá-la após memberships e antes de iniciar o servidor. Não foram alteradas as definições de tabelas nem aplicada migration na VPS.

Três testes usam a sequência extraída do CMD real, confrontada com allowlist de quatro migrations, contra SQLite em memória: provisionamento dos campos reais, repetição sem perda de conta/membership e reexecução preservando papel explícito/conta desativada. Não criam usuários reais nem fazem envio externo.

## Validação confirmada

Commit código/testes: a9ea7b1913578f02cb92c4657f2b5130463c46bc.
PHP 8.3.37, PHPUnit 11.5.50.
Comando: php vendor/bin/phpunit --testsuite "Assessor Online Core" --display-warnings.
Resultado: OK (32 tests, 138 assertions).
https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/45977872617
Marketing Agents Core Tests aprovado no mesmo commit:
https://github.com/cschibelsky-star/vitrine-ai-pro/actions/runs/45977872609
Local: git diff --check aprovado. PHP/Composer local continuam indisponíveis; CI forneceu execução PHP real.

A contagem é 29 testes da base + 3 novos. Os 11 testes de convite do PR #168 pertencem a outra branch e não foram removidos nem executados juntos neste PR. Integração futura deve preservar ambos os arquivos de teste e unir entradas de phpunit.xml/workflow. Não afirmar validação combinada.

Não houve build/run Docker nesta entrega: os testes executam a sequência de migrations do Dockerfile em Laravel/SQLite, não validam imagem, rede, volumes ou SSO reais.

## Estado operacional verificado

PR #168 continua aberto/draft, head 285ebca2b25c4fdbbfe68bb2a97453afdd9d08ef. project_git_status da HML mantém cinco arquivos modificados: comando de convite, compose, nginx, demo e rotas. project_backup_status retornou backup_not_configured. Não foi usado reconcile, reset ou checkout remoto.

As capacidades descobertas não forneceram operação de snapshot exclusiva dos cinco diffs. Não usar reconciliação de árvore como substituto de backup, pois também altera o estado. Preservação completa continua não comprovada. A integração SSO local não foi substituída.

## Gate e próxima etapa

Código isolado: testes aprovados. HML: pendente de preservação completa e revisão dos diffs/SSO. Produção: não alterada. Sem merge, deploy, migração aplicada na VPS ou provisionamento real.

Antes de atualizar HML: obter snapshot versionado e recuperável dos cinco arquivos por mecanismo operacional autorizado, revisar SSO separado e confirmar backup/estado dos dados; depois validar integração das correções #168 e #185 em uma branch de homologação e executar testes combinados. Homologar login e recuperação HTTP apenas depois desses gates.
