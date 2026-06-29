# Factory BUILD 006 — AI Architect Advanced

## Objetivo

Evoluir o AI Architect local da Factory para gerar blueprints mais inteligentes para diferentes tipos de sistemas.

## Entregas

1. AI Architect avançado
2. Library local de domínios
3. Suporte a novos sistemas:
   - Licitações
   - Patrimônio
   - CRM
   - Saúde/SISMED
   - Turismo/Guia Digital
4. Relatório de arquitetura antes da geração
5. Command `factory:ai-plan`

## Novos comandos

```bash
php artisan factory:ai-plan "Crie um sistema para controlar licitações públicas"
php artisan factory:ai-blueprint "Crie um sistema para controlar licitações públicas"
php artisan factory:make-system gestao_licitacoes_publicas
```

## Instalação

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 scripts/patch_factory_build_006_provider.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep ai-plan
```

## Homologação sugerida

```bash
php artisan factory:ai-plan "Crie um sistema para controlar licitações públicas"
php artisan factory:ai-blueprint "Crie um sistema para controlar licitações públicas"
cat storage/app/factory/blueprints/gestao_licitacoes_publicas.json
php artisan factory:make-system gestao_licitacoes_publicas
```

## Observação

Esta versão ainda usa inteligência local baseada em regras. Ela prepara o caminho para integração real com OpenAI/Gemini/Claude.
