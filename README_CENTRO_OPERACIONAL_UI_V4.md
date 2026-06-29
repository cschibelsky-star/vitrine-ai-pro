# CENTRO OPERACIONAL UI V4 — TECH DASHBOARD

## Objetivo

Atualizar visualmente o Centro Operacional da Vitrine AI Pro com uma dashboard mais moderna, tecnológica e executiva.

## O que este pacote faz

- Substitui a página `EnterpriseDashboard`.
- Cria uma dashboard visual com:
  - Hero tecnológico.
  - Cards executivos.
  - Blocos de operação.
  - Blocos de Factory.
  - Status dos produtos.
  - Atalhos estratégicos.
- Mantém a Factory e Aplicações Geradas separadas do menu principal.

## Instalação

Extraia na raiz:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 centro_operacional_ui_v4_bootstrap.py
composer dump-autoload
php artisan optimize:clear
```

Depois acesse:

```txt
/admin
Centro Operacional → Dashboard Executivo
```
