# Factory BUILD 004.2 — Blueprint Engine 2.0

## Objetivo

Evoluir a Factory de gerador de módulo isolado para gerador de soluções estruturadas.

## Entregas

1. Blueprint Engine 2.0
2. Smart Relationship Engine
3. Dashboard Generator base

## Novos comandos

```bash
php artisan factory:make-blueprint compras
php artisan factory:make-system compras
php artisan factory:qa-module compras
```

## O que a build faz

Ao executar:

```bash
php artisan factory:make-blueprint compras
```

A Factory gera um blueprint JSON em:

```txt
storage/app/factory/blueprints/compras.json
```

Ao executar:

```bash
php artisan factory:make-system compras
```

A Factory lê o blueprint e gera múltiplos módulos relacionados:

- Categorias
- Fornecedores
- Contratos
- Documentos
- Historicos

Cada módulo é gerado em modo seguro:

```txt
storage/app/factory/builds/
```

## Instalação do patch

Extraia este ZIP na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_004_2_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep factory
```

## Homologação

```bash
php artisan factory:detect-compatibility
php artisan factory:make-blueprint compras
cat storage/app/factory/blueprints/compras.json
php artisan factory:make-system compras
find storage/app/factory/builds -maxdepth 3 -type f | grep -E "categorias|fornecedores|contratos|documentos|historicos"
```

## Observação

Esta build ainda gera em modo seguro. A instalação dos módulos gerados continua sendo feita pelo comando:

```bash
php artisan factory:install-module nome-do-modulo
```
