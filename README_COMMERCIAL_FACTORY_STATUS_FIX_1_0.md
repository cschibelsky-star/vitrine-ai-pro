# COMMERCIAL FACTORY STATUS FIX 1.0

## Correção

Corrige o erro:

```txt
A cell must be a TableCell, a scalar or an object implementing "__toString()", "array" given.
```

## Instalação

Extraia na raiz do projeto:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 commercial_factory_status_fix_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan commercial:factory-status
```
