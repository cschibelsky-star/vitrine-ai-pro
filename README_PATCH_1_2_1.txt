Vitrine AI Pro Master Start 1.2.1 - Comercial com Produto, Plano e Valor Automático

Este patch altera somente:
app/Filament/Resources/LeadResource.php

Não altera banco de dados.
Não altera migrations.
Não altera models.
Não altera clientes, produtos, planos, licenças ou financeiro.

Aplicação no servidor:

1) Fazer backup:
cd /home1/cris1649
zip -r backup-master-start-1-2-1-antes.zip vitrine-ai-pro app.vitrineiapro.com.br

2) Enviar o ZIP para:
/home1/cris1649/vitrine-ai-pro

3) Extrair:
cd /home1/cris1649/vitrine-ai-pro
unzip -o master-start-1-2-1-comercial-inteligente-patch.zip

4) Limpar cache:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

5) Testar:
- Comercial
- Novo Lead
- Produto de interesse como lista suspensa
- Plano sugerido filtrado pelo produto
- Valor estimado automático e editável
