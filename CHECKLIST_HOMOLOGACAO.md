# Checklist de Homologação — Enterprise 6.0 RC1

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc1_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "commercial:factory"
php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV" --plan=enterprise --email=cliente@teste.com --domain=cliente.tv.br --dry-run
php artisan commercial:factory-status
```

Validar no painel:

- Centro Operacional → Dashboard Executivo
- Factory Studio → Studio
- Projetos → Projetos Gerados
- Marketplace → Marketplace
