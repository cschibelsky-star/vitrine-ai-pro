Master Start 1.7.2 — Correção Usuários / Cliente vinculado

Problema:
- A tela /admin/users/{id}/edit gerava erro 500.
- O stack trace apontava erro em Filament Forms Select dentro de UserResource/EditUser.
- Causa provável: select de Cliente / Empresa vinculada recebia label nulo de algum registro de companies.

Correção:
- UserResource agora monta as opções de empresa com fallback seguro.
- Se company.name estiver vazio, tenta nome, company_name, razao_social, fantasy_name, fantasia.
- Se todos estiverem vazios, usa Cliente #ID.
- Isso evita erro de label null no Select do Filament.

Instalação:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
1. Abrir /admin/users.
2. Editar usuário.
3. Criar usuário com Perfil = Cliente.
4. Selecionar Cliente / Empresa vinculada.
5. Salvar.
