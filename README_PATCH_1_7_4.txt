Master Start 1.7.4 — Login próprio da Área do Cliente

Problema:
- Usuário cliente não consegue entrar pelo /admin/login.
- Motivo provável: Filament bloqueia usuários que não têm acesso ao painel admin.

Correção:
- Cria login próprio em /cliente/login.
- O login usa Auth::attempt diretamente.
- Usuário client/cliente entra e é redirecionado para /cliente.
- Admin entra e pode ser redirecionado para /admin.
- Cria logout próprio /cliente/logout.

Após descompactar o patch, é necessário incluir uma vez no routes/web.php:

if (file_exists(__DIR__.'/client_portal_auth.php')) {
    require __DIR__.'/client_portal_auth.php';
}

Teste:
https://app.vitrineiapro.com.br/cliente/login
E-mail: cliente@conhecasumare.com.br
Senha: Cliente@123
