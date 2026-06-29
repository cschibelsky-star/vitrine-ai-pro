# Factory Builder — Sprint 003 v1.0

Patch incremental para o Vitrine AI Pro Master.

## Objetivo

Adicionar a primeira capacidade produtiva real da fábrica: gerar um módulo Laravel + Filament automaticamente em modo seguro.

## Comando novo

```bash
php artisan factory:make-module fornecedores
```

## Resultado esperado

O comando gera arquivos em:

```txt
storage/app/factory/builds/fornecedores/
```

Incluindo:

- migration
- model
- Filament Resource base
- pages List/Create/Edit/View
- policy
- seeder
- README do módulo

## Instalação

Extraia este ZIP na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois execute:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_builder_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep make-module
php artisan factory:make-module fornecedores
```

## Observação

Nesta primeira versão, o Builder gera os arquivos dentro de `storage/app/factory/builds` para homologação segura, sem sobrescrever o projeto real.
