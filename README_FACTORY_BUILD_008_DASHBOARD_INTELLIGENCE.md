# Factory BUILD 008 — Dashboard Intelligence

## Objetivo

Fazer a Factory gerar inteligência visual e gerencial para os módulos e sistemas gerados.

## Entregas

1. Dashboard Generator 2.0
2. Widget Intelligence
3. Executive Dashboard Engine

## Correção adicional

- Singularizador inteligente em português para evitar nomes como `Licitaco`.

## Novos comandos

```bash
php artisan factory:dashboard-module fornecedores
php artisan factory:widgets-module fornecedores
php artisan factory:executive-dashboard gestao_licitacoes_publicas
```

## Instalação

Extraia este ZIP na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_008_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "dashboard-module|widgets-module|executive-dashboard"
```

## Homologação sugerida

```bash
php artisan factory:dashboard-module fornecedores
php artisan factory:widgets-module fornecedores
php artisan factory:executive-dashboard gestao_licitacoes_publicas
```

## Saídas geradas

```txt
storage/app/factory/dashboards/modules/{slug}.json
storage/app/factory/widgets/modules/{slug}.json
storage/app/factory/dashboards/systems/{slug}.json
```
