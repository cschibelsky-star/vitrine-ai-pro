# FACTORY PRODUCTION ENGINE ENTERPRISE 1.0

## Objetivo

Fazer a Factory produzir de ponta a ponta em modo seguro com um único comando:

```bash
php artisan factory:produce gov360
```

## O que produz

- Blueprint do GOV360
- Sistema com módulos em `storage/app/factory/builds`
- Dashboards e widgets
- Dashboard executivo
- Documentação
- QA dos módulos
- Relatório final de produção

## Instalação

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_enterprise_production_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "factory:produce"
```

## Homologação

```bash
php artisan factory:produce gov360
```
