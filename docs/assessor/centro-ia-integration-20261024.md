# Assessor Online — integração com Centro IA
Estado: implementação para HML; não homologada. PR #124 permanece draft. Nenhum merge, migração, deploy ou alteração de secrets autorizada por este documento.

## Contrato reutilizado
POST HTTPS /api/internal/centro-ia/execute, Authorization Bearer com credencial interna server-side e X-Vitrine-Project: assessor-online.
Capability existente assistant_conversation; o broker resolve o agente configurado no Centro IA e delega a AiRoutingService/AiExecutionService. Sem modelos, provedores ou preços no consumidor.
O broker também suporta Ed25519 com identidade cadastrada, timestamp e nonce antirreplay. A identidade Assessor ainda não está cadastrada; esta implementação usa o mecanismo Bearer existente. Não reutilizar chaves de provedores.
Credencial via ASSESSOR_CENTRO_IA_TOKEN por mecanismo seguro; nunca enviar pelo navegador.
ASSESSOR_CENTRO_IA_BROKER_URL deve apontar para o broker HTTPS confiável, com caminho completo. ASSESSOR_CENTRO_IA_ENABLED permanece false por padrão.

## Privacidade e acesso
Membro com papel admin ou assessor E empresa atual do usuário igual à empresa do gabinete. Revalidação antes de toda coleta e chamada, inclusive se IA desabilitada.
Somente cinco contagens agregadas do gabinete autorizado são enviadas. Nome do gabinete, IDs, cidadãos, contatos, assuntos, descrições e notas nunca entram no prompt.
Resposta validada como JSON limitado a cinco recomendações de texto. Saída tratada como sugestão, sem ações automáticas. Renderização escapada/textContent.
POST autenticado, CSRF do grupo web e limite de três solicitações/minuto; GET e painel usam regras locais sem gerar custos. Sem cache compartilhado. Endpoint de inteligência retorna no-store.
Log registra origem, gabinete, ator, execução e motivo controlado de fallback; sem prompts, respostas ou erros brutos.
Falhas, timeout, credencial ausente, bloqueio central e resposta inválida preservam regras locais com ai_connected=false.
Não há retry no consumidor: evita duplicar consumo após timeout.

## Custos e limites conhecidos
Consumo permanece registrado pelo executor central. Esta alteração não acrescenta orçamento local nem comprova bloqueio de orçamento por empresa: o contrato atual não transporta identidade de tenant para contabilização específica. Essa limitação deve ser resolvida no Centro IA antes de habilitação comercial por gabinete.
Não habilitar em produção apenas porque testes com HTTP fake passaram.

## Homologação e G0–G7
Critérios G0–G7 existentes preservados, sem reclassificação nem aceite automático. Testes específicos cobrem rotas HTTP, permissões, isolamento e contrato de transporte com fake; não substituem HML integrada.
Pendências obrigatórias: executar CI; credencial segura e agente central existentes em HML isolada; chamada real rastreada por execution_id e registro central de consumo; confirmar política central de orçamento/atribuição por empresa; validar UI e CSRF; executar restante do checklist funcional e G0–G7 vigente. Merge e produção permanecem bloqueados até homologação.
