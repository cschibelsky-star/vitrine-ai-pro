Vitrine AI Pro Master Start 1.4 - Módulos, Funcionalidades e Instâncias por Domínio

Status: patch técnico preparado para homologação.

O que este patch adiciona:
1) Cadastro de Módulos.
2) Módulos por Plano.
3) Módulos por Cliente.
4) Campos de instância/domínio no cadastro de Clientes:
   - dominio_landing
   - dominio_demo
   - tipo_instancia
5) Seeder inicial com módulos por vertical:
   - TV Digital Enterprise
   - Portal News AI
   - Visite Cidade / Conheça Sua Cidade
   - Município Digital IA
   - SISMED em status futuro
6) Instâncias oficiais:
   - Conheça Sumaré: conhecasumare.com.br
   - Conheça Sua Cidade: conhecasuacidade.com.br

Aplicação segura no servidor:

1) Enviar este ZIP para:
/home1/cris1649/vitrine-ai-pro

2) Fazer backup antes de aplicar:
cd /home1/cris1649
zip -r backup-master-antes-1-4-modulos.zip vitrine-ai-pro

3) Entrar no projeto e extrair:
cd /home1/cris1649/vitrine-ai-pro
unzip -o master-start-1-4-modulos-funcionalidades-patch.zip

4) Executar migrations e seeder:
php artisan migrate --force
php artisan db:seed --class=ModuleSeeder --force

5) Recriar cache:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

6) Testar no painel:
https://app.vitrineiapro.com.br/admin

Menus esperados:
- Operação > Módulos
- Produtos & Licenças > Módulos por Plano
- Operação > Módulos por Cliente
- Cadastros > Clientes / Empresas com campos de domínio/instância

Critérios de homologação:
- Acessar Módulos sem erro.
- Ver módulos populados por produto.
- Acessar Módulos por Plano sem erro.
- Acessar Módulos por Cliente sem erro.
- Confirmar Clientes: Conheça Sumaré e Conheça Sua Cidade.
- Confirmar domínios:
  conhecasumare.com.br
  conhecasuacidade.com.br
