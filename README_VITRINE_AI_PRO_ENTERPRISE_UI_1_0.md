# VITRINE AI PRO ENTERPRISE UI 1.0

## Objetivo

Separar definitivamente:

- Centro Operacional
- Factory Studio
- Projetos
- Marketplace

## Instalação

Extraia na raiz:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_ui_bootstrap.py
composer dump-autoload
php artisan optimize:clear
```

## Validação

Acesse `/admin`.

Os módulos gerados pela Factory, como Cliente, Animal, Agendamento, Prontuario, Vacina e Financeiro, serão ocultados do menu principal.
