Master Start 1.7.1 — Segurança Admin / Área do Cliente

Objetivo:
Corrigir o risco de bloqueio 403 no /admin depois da criação da Área do Cliente.

Correções:
- User implementa FilamentUser corretamente.
- Admin interno continua acessando /admin.
- Usuário cliente deve acessar /cliente.
- Usuários antigos sem role continuam tratados como admin.
- Migration corrige usuários existentes:
  - role vazio/null => admin
  - is_active => 1
- Admin também pode abrir /cliente para teste usando a primeira empresa cadastrada.

Instalação:
php artisan optimize:clear
php artisan migrate
php artisan config:cache
php artisan route:cache

Teste:
1. Acessar /admin com usuário admin.
2. Acessar /cliente com admin para pré-visualização.
3. Criar usuário cliente vinculado a uma empresa.
4. Logar com usuário cliente e acessar /cliente.
