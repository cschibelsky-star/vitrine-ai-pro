# FACTORY PRODUCTION ENGINE v1.0

## Foco

Criar o motor que faz a Factory executar o fluxo de produção usando as peças já construídas.

## Comandos

```bash
php artisan factory:production-status
php artisan factory:produce gov360
```

## Instalação

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_production_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "factory:produce|factory:production-status"
```

## Teste

```bash
php artisan factory:production-status
php artisan factory:produce gov360
```

## Saída

```txt
storage/app/factory/production/gov360/production_report.json
storage/app/factory/production/gov360/steps/
```
