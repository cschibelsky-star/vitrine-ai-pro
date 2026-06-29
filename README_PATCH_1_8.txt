Master Start 1.8 — Suporte / Chamados

Inclui:
- Tabela support_tickets.
- Model App\Models\SupportTicket.
- Resource Filament: Suporte e Operação > Chamados.
- Login do cliente permanece em /cliente/login.
- Área do cliente ganha:
  - Abrir chamado.
  - Meus chamados.
  - Indicador de chamados.

Após descompactar:
1. Garantir includes no routes/web.php:
if (file_exists(__DIR__.'/client_portal_auth.php')) {
    require __DIR__.'/client_portal_auth.php';
}
if (file_exists(__DIR__.'/client_support_tickets.php')) {
    require __DIR__.'/client_support_tickets.php';
}

2. Rodar:
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
- Entrar em /cliente/login como cliente@conhecasumare.com.br
- Abrir chamado.
- Conferir no admin: Suporte e Operação > Chamados.
