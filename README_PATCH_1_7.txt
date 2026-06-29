Master Start 1.7 — Área do Cliente / Portal do Cliente

Objetivo:
Criar a primeira versão da área do cliente, separando painel interno da Vitrine AI Pro e visão consultiva do cliente.

Inclui:
- Campos em users: role, company_id, is_active.
- UserResource atualizado para criar usuário administrador ou cliente.
- Usuário cliente vinculado a empresa/instância.
- Rota /cliente protegida por login.
- Controller ClientPortalController.
- Dashboard simples do cliente:
  - dados da empresa/instância
  - licenças
  - módulos contratados
  - contratos
  - cobranças
  - suporte

Regra:
- Usuário interno/admin continua acessando o painel completo.
- Usuário cliente deve ser vinculado a uma empresa e visualizar somente sua própria instância em /cliente.

Instalação:
php artisan optimize:clear
php artisan migrate
php artisan config:cache
php artisan route:cache

Teste:
1. Criar usuário com Perfil = Cliente.
2. Vincular a uma empresa, por exemplo Visite Sumaré / Conheça Sumaré.
3. Acessar /cliente autenticado com esse usuário.
