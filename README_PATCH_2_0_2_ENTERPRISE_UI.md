# Vitrine AI Pro Master Enterprise — Patch 2.0.2 UI Real

Este patch altera a página real do Dashboard Executivo para um layout Enterprise escuro, com cards, painéis, atividades, acesso rápido e identidade visual integrada.

## Como aplicar

1. Envie e extraia o ZIP na raiz do projeto Laravel:
   `/home1/cris1649/vitrine-ai-pro`

2. Rode no SSH:

```bash
cd /home1/cris1649/vitrine-ai-pro
bash install_patch_2_0_2_enterprise_ui.sh
```

3. Abra `/admin` e force atualização com Ctrl+F5.

## Arquivos alterados

- `app/Filament/Pages/Dashboard.php`
- `resources/views/filament/pages/dashboard-enterprise-ui.blade.php`
- `public/css/vitrine-enterprise-ui.css`

## Observação

Este patch muda o sistema real, não é imagem/mockup. O menu já foi corrigido no Patch 2.0.1; este patch começa a troca visual real das páginas.
