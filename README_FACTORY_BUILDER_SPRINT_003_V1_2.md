# Factory Builder — Sprint 003 v1.2

Patch incremental para o Vitrine AI Pro Master.

## Objetivo

Adicionar o primeiro instalador seguro de módulos gerados pela Factory.

## Novo comando

```bash
php artisan factory:install-module fornecedores --dry-run
php artisan factory:install-module fornecedores
```

## O que faz

O comando lê o módulo gerado em:

```txt
storage/app/factory/builds/fornecedores
```

E copia os arquivos para os diretórios reais do Laravel:

```txt
app/Models
app/Policies
app/Filament/Resources
database/migrations
database/seeders
```

## Segurança

Por padrão, o comando não sobrescreve arquivos existentes.

Use `--force` somente se souber o que está fazendo.

## Comandos de instalação do patch

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_builder_installer_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep install-module
```

## Homologação sugerida

Primeiro simule:

```bash
php artisan factory:install-module fornecedores --dry-run
```

Depois instale:

```bash
php artisan factory:install-module fornecedores
composer dump-autoload
php artisan optimize:clear
php artisan migrate
```

Depois valide:

```bash
php artisan route:list | grep admin
```

E acesse o painel Filament.
