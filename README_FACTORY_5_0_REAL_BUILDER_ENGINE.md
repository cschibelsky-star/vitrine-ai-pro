# FACTORY 5.0 — REAL BUILDER ENGINE

## Objetivo

Finalizar a Factory adicionando o motor que transforma blueprints em código Laravel/Filament real.

## Novos comandos

### Gerar código real em modo seguro

```bash
php artisan factory:real-build clinicas_veterinarias
```

### Simular instalação do código gerado

```bash
php artisan factory:real-install clinicas_veterinarias --dry-run
```

### Instalar de verdade após validação

```bash
php artisan factory:real-install clinicas_veterinarias --force
php artisan migrate
php artisan optimize:clear
```

### Fluxo completo desde o pedido

```bash
php artisan factory:finish-project "Quero um sistema para clínicas veterinárias com agenda, clientes, prontuários, vacinas e financeiro"
```

## O que é gerado

```txt
storage/app/factory/real-builds/{blueprint}/app/Models
storage/app/factory/real-builds/{blueprint}/app/Policies
storage/app/factory/real-builds/{blueprint}/app/Filament/Resources
storage/app/factory/real-builds/{blueprint}/database/migrations
storage/app/factory/real-builds/{blueprint}/database/seeders
storage/app/factory/real-builds/{blueprint}/REAL_BUILD_REPORT.json
```

## Instalação do pacote

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_5_real_builder_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "real-build|real-install|finish-project"
```

## Homologação recomendada

```bash
php artisan factory:real-build clinicas_veterinarias
php artisan factory:real-install clinicas_veterinarias --dry-run
```

Se o dry-run passar:

```bash
php artisan factory:real-install clinicas_veterinarias --force
composer dump-autoload
php artisan optimize:clear
php artisan migrate
```

## Importante

Este pacote instala com segurança e cria backups antes de sobrescrever arquivos quando `--force` for usado.
