# Checklist — Enterprise 6.0 RC2

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc2_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan commercial:factory-status
php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV RC2" --plan=enterprise --email=cliente@teste.com --domain=cliente-rc2.tv.br --dry-run
php artisan commercial:factory-status
```

Validar no painel:

- Centro Operacional
- Factory Studio
- Projetos
- Marketplace
- Portal do Cliente
- IA Center
