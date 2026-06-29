# Factory BUILD 005 — AI Architect Engine

## Objetivo

Permitir que a Factory receba uma descrição em linguagem natural e gere um blueprint de sistema automaticamente.

## Novo comando

```bash
php artisan factory:ai-blueprint "Crie um sistema para prefeitura controlar fornecedores, contratos e documentos"
```

## Depois do blueprint

```bash
php artisan factory:make-system gestao_fornecedores_contratos_documentos
```

## Instalação

Extraia na raiz do projeto e rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_005_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep ai-blueprint
```

## Homologação

```bash
php artisan factory:ai-blueprint "Crie um sistema para prefeitura controlar fornecedores, contratos e documentos"
cat storage/app/factory/blueprints/gestao_fornecedores_contratos_documentos.json
php artisan factory:make-system gestao_fornecedores_contratos_documentos
```

## Observação

Esta versão usa análise local por regras. A integração real com OpenAI/Gemini/Claude fica para uma próxima build.
