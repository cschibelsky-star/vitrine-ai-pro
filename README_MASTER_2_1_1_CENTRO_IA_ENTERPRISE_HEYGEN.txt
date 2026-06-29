MASTER 2.1.1 — Centro IA Enterprise / HeyGen

Entrega:
- Envio real de job para HeyGen via POST /v3/videos.
- Atualização de status via GET /v3/videos/{video_id}.
- Callback público /api/heygen/callback.
- Botões Gerar Agora, Atualizar Status e Assistir.
- Registro de credits_used e ledger de créditos quando concluído.

Base oficial:
HeyGen usa X-Api-Key, POST /v3/videos para criação de vídeo e GET /v3/videos/{video_id} para consulta.

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_2.1.1_CENTRO_IA_ENTERPRISE_HEYGEN.zip
php install_master_2_1_1_heygen_enterprise.php
php artisan optimize:clear
php artisan filament:cache-components
php artisan route:clear
php artisan route:list | grep heygen

Teste:
1. /admin/heygen-video-jobs
2. Criar/editar job.
3. Informar Avatar ID válido em HeygenAvatar ou config do provedor HeyGen.
4. Informar roteiro.
5. Clicar Gerar Agora.
6. Atualizar Status até concluir.
