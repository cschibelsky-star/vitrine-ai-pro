# Factory Builder — Sprint 003 v1.1

Patch incremental para o Vitrine AI Pro Master.

## Objetivo

Evoluir o Builder para gerar um módulo Laravel + Filament completo em modo seguro.

## Correções e melhorias

- Corrige singular em português: `fornecedores` passa a gerar `Fornecedor`.
- Gera:
  - Migration
  - Model
  - Policy
  - Seeder
  - Filament Resource
  - Pages: List, Create, Edit, View
  - README do módulo
  - module.json

## Comando

```bash
php artisan factory:make-module fornecedores -v
```

## Resultado esperado

Arquivos gerados em:

```txt
storage/app/factory/builds/fornecedores/
```

## Instalação

Extraia este ZIP na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois execute:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_builder_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan factory:make-module fornecedores -v
find storage/app/factory/builds/fornecedores -type f
```

## Observação

Ainda é modo seguro: não instala automaticamente no painel real. A instalação automática será a próxima etapa.
