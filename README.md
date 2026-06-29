# VITRINE AI PRO ENTERPRISE 6.0 RC1

## Inclui

- Centro Operacional 6.0
- Factory Studio 2.0
- Projetos Gerados
- Marketplace
- Comercial → Factory Pipeline
- Ocultação de módulos gerados no menu principal
- Bootstrap único

## Instalação

Extraia na raiz do projeto e rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc1_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "commercial:factory"
```

## Teste

```bash
php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV" --plan=enterprise --email=cliente@teste.com --domain=cliente.tv.br --dry-run
php artisan commercial:factory-status
```
