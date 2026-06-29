# FACTORY ENTERPRISE MATURITY PACK 5.1

## Objetivo

Complementar o Real Builder 5.0, adicionando camadas de arquitetura comercial.

## Novos comandos

### Gerar camadas enterprise

```bash
php artisan factory:enterprise-build clinicas_veterinarias
```

### Simular instalação das camadas enterprise

```bash
php artisan factory:enterprise-install clinicas_veterinarias --dry-run
```

### Instalar após validação

```bash
php artisan factory:enterprise-install clinicas_veterinarias --force
composer dump-autoload
php artisan optimize:clear
```

## O que será gerado

```txt
storage/app/factory/enterprise-builds/{blueprint}/app/Services
storage/app/factory/enterprise-builds/{blueprint}/app/Repositories
storage/app/factory/enterprise-builds/{blueprint}/app/Http/Requests
storage/app/factory/enterprise-builds/{blueprint}/app/Http/Controllers/Api
storage/app/factory/enterprise-builds/{blueprint}/routes/api_factory_{blueprint}.php
storage/app/factory/enterprise-builds/{blueprint}/tests/Feature
storage/app/factory/enterprise-builds/{blueprint}/ENTERPRISE_BUILD_REPORT.json
```

## Instalação do pacote

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_enterprise_maturity_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "enterprise-build|enterprise-install"
```

## Homologação

```bash
php artisan factory:enterprise-build clinicas_veterinarias
php artisan factory:enterprise-install clinicas_veterinarias --dry-run
```

## Observação

Este pacote não substitui o Real Builder. Ele adiciona camadas enterprise ao sistema já produzido.
