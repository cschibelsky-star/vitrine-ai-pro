# VITRINE AI PRO ENTERPRISE 6.0 RC3 — CLEAN PROVIDER

## Problema corrigido

O `AdminPanelProvider.php` atual usa:

```php
->discoverResources(...)
->discoverPages(...)
```

Isso faz o Filament carregar automaticamente todas as páginas e recursos antigos, gerados e duplicados.

Por isso o menu ficou bagunçado.

## Solução

Esta RC3 substitui o `AdminPanelProvider` por uma versão Enterprise limpa, registrando manualmente apenas:

### Páginas oficiais

- Dashboard/Cockpit Executivo
- Factory Studio
- Projetos Gerados
- Marketplace
- Portal do Cliente
- IA Center

### Recursos oficiais do Centro Operacional

- Clientes/Empresas
- Produtos
- Planos
- Licenças
- Comercial/Leads
- Contratos/Propostas
- Cobranças
- Assinaturas
- Módulos
- Módulos por Plano
- Módulos por Cliente
- Chamados
- Usuários
- Dados da Empresa

## O que sai do menu

- Páginas duplicadas
- FactoryStudio antigo
- EnterpriseDashboard duplicado
- AiDashboard antigo
- Resources gerados: Cliente, Animal, Financeiro, Vacina, Agendamento, Prontuario, Fornecedor
- Resources antigos de IA que poluíam a navegação principal
- Factory resources internos automáticos

## Aplicar

Extraia na raiz do projeto:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc3_clean_provider_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan route:list | grep -E "admin |factory-studio|generated-projects|marketplace|client-portal|ai-center"
```

Depois acesse:

```txt
/admin
```

## Rollback

O bootstrap cria backup automático em:

```txt
storage/app/factory/backups/enterprise_rc3/
```
