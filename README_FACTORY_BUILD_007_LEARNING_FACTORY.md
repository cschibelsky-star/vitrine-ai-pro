# Factory BUILD 007 — Learning Factory

## Objetivo

Adicionar a primeira camada de aprendizado da Factory.

## Entregas

1. Learning Engine
2. Pattern Library
3. Smart QA

## Novos comandos

```bash
php artisan factory:learn-module fornecedores
php artisan factory:patterns fornecedores
php artisan factory:smart-qa fornecedores
```

## O que esta build faz

### Learning Engine

Lê o `module.json` do módulo gerado e grava conhecimento técnico em:

```txt
storage/app/factory/learning/modules/{slug}.json
```

### Pattern Library

Identifica padrões do módulo e sugere componentes futuros.

Exemplo para fornecedores:

- documentos
- contratos
- histórico
- dashboard
- auditoria
- API

### Smart QA

Executa QA avançado sobre o módulo gerado:

- existência do módulo
- module.json
- migrations
- models
- policies
- Filament Resource
- pages
- relationships
- campos foreignId
- compatibilidade básica

## Instalação

Extraia na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_007_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "learn-module|patterns|smart-qa"
```

## Homologação sugerida

```bash
php artisan factory:learn-module fornecedores
php artisan factory:patterns fornecedores
php artisan factory:smart-qa fornecedores
```
