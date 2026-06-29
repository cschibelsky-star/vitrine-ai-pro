# Factory Engine — Sprint 002 v1.1

Patch incremental para o projeto Vitrine AI Pro Master.

## Objetivo

Adicionar a base operacional do Factory Engine sem alterar o Factory Core homologado.

## O que este patch adiciona

- Contracts do Engine
- DTOs
- Enums
- Exceptions
- Services base
- Providers internos simulados
- Jobs
- Suportes de contexto, prompt e estimativa
- Command `factory:engine-test`
- Registro automático do command no FactoryServiceProvider

## Instalação

Extraia este ZIP na raiz do projeto Laravel:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois execute:

```bash
cd /home1/cris1649/vitrine-ai-pro
composer dump-autoload
php artisan optimize:clear
php artisan factory:health
php artisan factory:engine-test
```

## Resultado esperado

O comando `factory:engine-test` deve criar uma execução de teste e finalizar com status `finished`.

## Observação

Este patch não chama APIs externas. OpenAI, Gemini e Claude ficam preparados como provedores estruturais, mas ainda simulados. As integrações reais entram em sprint posterior.
