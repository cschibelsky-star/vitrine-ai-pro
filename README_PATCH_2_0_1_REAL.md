# Vitrine AI Pro Master Enterprise 2.0.1 — PATCH REAL Sidebar/Factory

Este patch foi gerado em cima do backup real enviado (`backup vitrine master1.zip`).

## O que corrige
- Remove a duplicidade visual `Factory` / `Factory Studio`.
- Esconde a página duplicada `FactoryStudioEnterprise` do menu.
- Esconde a página duplicada `EnterpriseDashboard` do menu.
- Unifica Factory Dashboard, Studio, Projetos, Marketplace, Blueprints, Capabilities, Execuções e Logs no grupo `Factory 2.0`.
- Move CRUDs gerados indevidamente dentro de Factory para `Módulos Gerados`.
- Mantém as rotas e funcionalidades existentes.

## Como aplicar
1. Suba este ZIP na raiz do Laravel: `/home1/cris1649/vitrine-ai-pro`.
2. Extraia com sobrescrita dos arquivos. O ZIP já contém os caminhos reais `app/...`.
3. Rode no SSH:

```bash
cd /home1/cris1649/vitrine-ai-pro
php artisan optimize:clear
php artisan filament:clear-cached-components || true
php artisan view:clear
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

4. Atualize o navegador com Ctrl+F5.

## Validação esperada
No menu lateral deve aparecer apenas um grupo `Factory 2.0`, contendo Dashboard, Studio, Projetos, Marketplace, Blueprints, Capabilities, Execuções e Logs.
