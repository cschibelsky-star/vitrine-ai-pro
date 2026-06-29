# FACTORY FINAL PRODUCER 1.0

## Objetivo

Fechar a esteira pelo SSH:

```txt
pedido livre -> resolver produto -> produzir -> validar -> pronto para instalar
```

## Comandos

```bash
php artisan factory:produce-request "Quero um sistema para pequenas empresas venderem para o governo"
php artisan factory:install-system gov360 --dry-run
```

## Instalação

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_final_producer_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep -E "produce-request|install-system"
```
