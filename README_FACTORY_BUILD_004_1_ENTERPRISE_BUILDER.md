# Factory BUILD 004.1 — Enterprise Builder

## Entregas

1. Compatibility Engine
2. Template Engine base
3. QA Engine

## Instalação

Extraia na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_004_1_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan factory:detect-compatibility
php artisan factory:make-module fornecedores -v
php artisan factory:qa-module fornecedores
```

## Objetivo

A partir desta build, o Builder passa a gerar Resource compatível com o padrão real do Filament instalado no projeto, evitando os erros já encontrados:

- `Filament\Schemas\Schema`
- `recordActions`
- `toolbarActions`
- imports errados de `Filament\Actions`
- plural `ListFornecedors`
