# Factory BUILD 010 — Autonomous Software Architect

## Objetivo

Evoluir a Factory para pensar como arquiteto de software antes de gerar sistemas.

## Entregas do pacote

1. Domain Knowledge Engine
2. Component Marketplace
3. Architecture Designer

## Novos comandos

```bash
php artisan factory:domain-knowledge "compras públicas"
php artisan factory:components-for-domain "compras públicas"
php artisan factory:architecture-design "Crie um sistema para gestão de eventos municipais integrado ao Guia Digital da Cidade"
```

## O que cada comando faz

### Domain Knowledge Engine

Identifica domínio de negócio e módulos típicos.

### Component Marketplace

Sugere componentes reutilizáveis conforme o domínio:

- Upload de arquivos
- Timeline
- Comentários
- Auditoria
- Workflow
- Dashboard
- API REST
- Exportação PDF/Excel
- Assinatura digital

### Architecture Designer

Gera um documento JSON de arquitetura contendo:

- domínio
- módulos
- componentes
- relacionamentos sugeridos
- dashboards
- APIs
- roadmap técnico

## Instalação

Extraia na raiz do projeto:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_010_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "domain-knowledge|components-for-domain|architecture-design"
```

## Homologação sugerida

```bash
php artisan factory:domain-knowledge "compras públicas"
php artisan factory:components-for-domain "compras públicas"
php artisan factory:architecture-design "Crie um sistema para gestão de eventos municipais integrado ao Guia Digital da Cidade"
```

## Saídas geradas

```txt
storage/app/factory/architecture/*.json
```
