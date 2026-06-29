# Factory Engine — Sprint 002 v1.0

Patch incremental para o Factory Core homologado no Vitrine AI Pro Master.

## Objetivo

Adicionar a base do motor de execução de Blueprints sem alterar a estrutura já homologada do Factory Core.

## Conteúdo

- Contracts
- DTOs
- Enums
- Exceptions
- Jobs
- Providers internos preparados
- Services do Engine
- Support: compilador de prompt, contexto e estimador de tokens

## Segurança

Nesta versão, os providers OpenAI, Gemini e Claude ficam preparados, mas desabilitados por padrão. O Engine consegue preparar a execução e finalizar sem fazer chamadas externas.

## Instalação

Extrair o patch na raiz do projeto:

```bash
/home1/cris1649/vitrine-ai-pro
```

Depois executar:

```bash
cd /home1/cris1649/vitrine-ai-pro
composer dump-autoload
php artisan optimize:clear
php artisan factory:health
```

## Homologação inicial

O Factory Core deve continuar healthy.

```bash
php artisan factory:health
```

Resultado esperado:

```txt
status: healthy
```

## Próxima etapa

Sprint 002 Parte 2: command `factory:engine-run` e botão Filament para executar uma FactoryExecution pelo Engine.
