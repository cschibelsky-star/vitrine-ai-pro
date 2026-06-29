# COMMERCIAL TO FACTORY PIPELINE 1.0

## Objetivo

Conectar o pedido comercial à Factory.

Fluxo:

```txt
Pedido / Lead
↓
Produto + Plano
↓
Cliente
↓
Licença prevista
↓
Projeto Factory
↓
Workspace
↓
Homologação
```

## Comandos

```bash
php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV" --plan=enterprise --email=cliente@teste.com --domain=cliente.tv.br --dry-run
php artisan commercial:factory-status
```

## Instalação

Extraia na raiz:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 commercial_factory_pipeline_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "commercial:factory"
```
