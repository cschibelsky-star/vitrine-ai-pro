# Factory Core 2.0 — Sprint de Consolidação

## Objetivo

Reduzir o tempo de aplicação das próximas builds, consolidando a Factory em uma base mais organizada.

## Entregas

1. Factory Bootstrap
2. Factory Update Command
3. Factory Plugin Registry

## Novos comandos

```bash
php artisan factory:update
php artisan factory:plugins
```

## Instalação

Extraia o ZIP na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "factory:update|factory:plugins"
```

## Homologação

```bash
php artisan factory:plugins
php artisan factory:update
```

## O que muda na prática

Antes:

```txt
python3 scripts/patch_build_001.py
python3 scripts/patch_build_002.py
python3 scripts/patch_build_003.py
...
```

Agora:

```txt
python3 factory_bootstrap.py
php artisan factory:update
```

A partir daqui, as próximas builds poderão usar o Bootstrap como ponto único de registro.
