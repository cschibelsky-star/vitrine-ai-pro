Master Start 1.7.3 — Área do Cliente / Dados da Instância

Problema:
- /cliente abriu, mas mostrou título genérico "Cliente" e dados de instância vazios.
- Causa: view e controller liam somente company.name, primary_domain e instance_type, enquanto o banco pode usar nomes diferentes.

Correção:
- ClientPortalController agora usa fallback para nome da empresa:
  name, nome, company_name, razao_social, fantasy_name, fantasia, nome_fantasia.
- Fallback para domínio:
  primary_domain, domain, dominio_principal, dominio, url_principal.
- Fallback para tipo de instância:
  instance_type, tipo_instancia, tipo.
- View atualizada para usar companyName, companyDomain e companyInstanceType.
- Cobranças e módulos também ganharam fallback de campos.

Instalação:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
1. Acessar /cliente com usuário cliente vinculado.
2. Confirmar se aparece Visite Sumaré ou Conheça Sumaré no topo.
3. Confirmar dados da instância.
