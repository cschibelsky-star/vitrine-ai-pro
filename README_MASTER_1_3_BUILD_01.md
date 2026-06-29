# MASTER 1.3 — Centro de IA / ACC — BUILD 01

Pacote incremental para aplicar sobre o Centro Operacional Master existente em Laravel 12 + Filament 3.

## Entregas do pacote

- Migrations do Centro de IA
- Models Eloquent
- Resources Filament
- Dashboard IA
- Widgets de indicadores
- Seeders de provedores e agentes oficiais

## Arquivos novos

- `app/Models/AiAgent.php`
- `app/Models/AiProvider.php`
- `app/Models/AiQueue.php`
- `app/Models/AiExecution.php`
- `app/Models/AiConsumption.php`
- `app/Models/AiAlert.php`
- `app/Models/AiMemory.php`
- `app/Filament/Pages/AiDashboard.php`
- `app/Filament/Resources/Ai*Resource.php`
- `app/Filament/Widgets/Ai*Widget.php`
- `database/migrations/2026_06_21_130001...130007_*`
- `database/seeders/AiProviderSeeder.php`
- `database/seeders/AiAgentSeeder.php`

## Instalação

1. Faça backup do projeto e do banco.
2. Envie o conteúdo deste ZIP para a raiz do projeto Laravel, preservando as pastas.
3. Execute:

```bash
php artisan migrate
php artisan db:seed --class=AiProviderSeeder
php artisan db:seed --class=AiAgentSeeder
php artisan optimize:clear
```

## Homologação mínima

- Menu **Centro de IA** aparece no Filament.
- Dashboard IA abre em `/admin/centro-ia`.
- Agentes IA aparecem cadastrados.
- Provedores aparecem cadastrados.
- CRUDs de Filas, Consumo, Alertas, Memória e Logs abrem sem erro.

## Observação técnica

Este Build 01 ainda não executa chamadas reais para Gemini, OpenAI ou HeyGen. Ele cria a base operacional para administração, monitoramento, consumo, filas, logs e governança inicial.
