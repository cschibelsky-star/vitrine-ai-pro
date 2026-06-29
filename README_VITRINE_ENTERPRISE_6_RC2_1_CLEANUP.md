# VITRINE AI PRO ENTERPRISE 6.0 RC2.1 — CLEANUP

## Correção

Esta atualização corrige exatamente o problema encontrado na RC2:

- `/admin` ainda carregava o Dashboard antigo.
- Menus Enterprise e antigos apareciam duplicados.
- Factory Studio antigo coexistia com Factory Studio Enterprise.
- AiDashboard antigo coexistia com IA Center.
- Resources gerados continuavam com risco de aparecer no menu.

## O que este pacote faz

1. Substitui `app/Filament/Pages/Dashboard.php` pelo Cockpit Executivo moderno.
2. Oculta `EnterpriseDashboard.php` do menu para evitar duplicidade.
3. Oculta `FactoryStudio.php` antigo.
4. Oculta `AiDashboard.php` antigo.
5. Oculta Resources gerados: Cliente, Animal, Agendamento, Prontuario, Vacina e Financeiro.
6. Mantém menus Enterprise:
   - Factory Studio
   - Projetos
   - Marketplace
   - Portal do Cliente
   - IA Center

## Instalação

Extraia na raiz do projeto:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc2_1_cleanup_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan route:list | grep -E "admin |enterprise-dashboard|factory-studio|generated-projects|marketplace|client-portal|ai-center"
```

Depois acesse:

```txt
/admin
```

Agora o `/admin` deve abrir diretamente o Cockpit Executivo moderno.
