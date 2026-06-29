Master Start 1.7.5 — Logout da Área do Cliente

Objetivo:
Adicionar botão Sair na Área do Cliente.

Inclui:
- Rota POST /cliente/logout.
- Nome de rota: client.logout.
- Encerramento da sessão web.
- Invalidação da sessão.
- Regeneração do token CSRF.
- Redirecionamento para /admin/login.
- Botão "Sair" no topo da página /cliente.

Instalação:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
1. Acessar /cliente logado.
2. Clicar em Sair.
3. Deve ir para /admin/login.
4. Ao acessar /cliente novamente, deve pedir login.
