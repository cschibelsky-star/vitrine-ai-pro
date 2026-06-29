# Vitrine AI Pro Master Enterprise 2.0.1 — Patch de Navegação e Sidebar

## Objetivo
Este patch trabalha diretamente sobre o backup enviado e corrige a organização da navegação do painel.

## Correções principais
- Remove a duplicidade visual `Factory / Factory Studio / Factory Core`.
- Mantém apenas uma área principal: `Factory 2.0`.
- Oculta do menu a página duplicada `FactoryStudioEnterprise`, preservando o arquivo e a rota para segurança.
- Reorganiza os grupos principais da sidebar.
- Separa módulos gerados da área principal da Factory.
- Padroniza os grupos: Centro Operacional, Clientes, Produtos e Licenças, Comercial, Financeiro, Inteligência Artificial, Factory 2.0, Módulos Gerados e Configurações.

## Arquivos alterados
Consulte a pasta `files/` deste pacote. Ela contém apenas arquivos modificados.

## Instalação recomendada no HostGator
1. Envie este ZIP para a raiz do projeto Laravel.
2. Extraia em uma pasta temporária.
3. Rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
bash install_patch_2_0_1.sh
php artisan optimize:clear
```

## Validação visual
Após aplicar, acesse `/admin` e confira:
- Não deve aparecer `Factory Studio` como grupo separado.
- Não deve aparecer `Factory Core` como grupo separado.
- A área Factory deve aparecer agrupada como `Factory 2.0`.
- Módulos gerados devem aparecer separados em `Módulos Gerados`.

## Rollback
Antes de sobrescrever, o instalador salva cópias em `storage/app/backups/patch_2_0_1_<data>`.
