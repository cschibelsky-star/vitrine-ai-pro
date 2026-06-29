# VITRINE AI PRO ENTERPRISE 6.0 RC2

## Objetivo

RC2 consolida a Enterprise 6.0 e corrige o erro do `commercial:factory-status`.

## Instalação

Extraia na raiz:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc2_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "commercial:factory"
```

## Homologação

```bash
php artisan commercial:factory-status

php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV RC2" --plan=enterprise --email=cliente@teste.com --domain=cliente-rc2.tv.br --dry-run

php artisan commercial:factory-status
```

## Validar no painel

```txt
/admin
Centro Operacional → Dashboard Executivo
Factory Studio → Studio
Projetos → Projetos Gerados
Marketplace → Marketplace
Portal do Cliente → Portal
IA Center → Agentes
```
