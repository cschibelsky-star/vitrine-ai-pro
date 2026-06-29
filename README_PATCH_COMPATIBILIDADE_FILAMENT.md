# Patch Factory Core MASTER 1.0.1 — Compatibilidade Filament

Este patch corrige o erro 500 causado por incompatibilidade de tipagem nos Resources do Filament.

Erro corrigido:

```txt
Type of App\Factory\Filament\Resources\FactoryBlueprintResource::$navigationGroup must be ?string
```

## Aplicação

Extraia este ZIP na raiz do Laravel:

```txt
/home1/cris1649/vitrine-ai-pro
```

Ele sobrescreve apenas arquivos em:

```txt
app/Factory/Filament/
resources/views/factory/filament/pages/
```

## Depois de extrair

Execute:

```bash
cd /home1/cris1649/vitrine-ai-pro
php artisan optimize:clear
composer dump-autoload
php artisan factory:health
```

Depois acesse:

```txt
https://app.vitrineiapro.com.br/admin
```

Resultado esperado:

- Painel sem erro 500.
- Menu Factory Core visível.
- Dashboard, Projetos, Capabilities, Blueprints, Execuções e Logs acessíveis.
