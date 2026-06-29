# VITRINE AI PRO ENTERPRISE 6.0 RC2 — UI HOTFIX

## Objetivo

Corrigir a percepção visual da RC2:

- remover duplicidade de menus;
- ocultar Resources gerados pela Factory;
- substituir visual do Dashboard Executivo;
- reforçar visual moderno das páginas Enterprise;
- reduzir aparência padrão do Filament.

## Instalação

Extraia na raiz do projeto e rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc2_ui_hotfix_bootstrap.py
composer dump-autoload
php artisan optimize:clear
```

Depois acesse:

```txt
/admin
Centro Operacional > Dashboard Executivo
```

## Observação

Este hotfix não altera banco de dados.
