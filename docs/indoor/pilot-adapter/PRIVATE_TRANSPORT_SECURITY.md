# Transporte privado TV Sumaré -> Vitrine Indoor IA (contrato de segurança, sem endpoint)

## Política de autenticação para HML
- Comunicação **backend para backend** exclusivamente em HTTPS com TLS válido; jamais colocar credenciais no player URL/PWA.
- Preferir identidade de serviço com credenciais segregadas por HML e produção; cada credencial vinculada a um único tenant de destino autorizado.
- Solicitações autenticadas com assinatura HMAC-SHA256 sobre método, caminho canônico, hash SHA-256 do corpo, timestamp Unix e nonce; validar assinatura em tempo constante, timestamp com janela curta e **nonce de uso único persistido** para bloquear replay.
- Nunca aceitar `tenant_id` da requisição como autoridade: derivar tenant da identidade do cliente autenticado e verificar escopo de exportação TV Sumaré.
- Token/segredo apenas em secret manager, com rotação e revogação; jamais comitar credenciais.
- Respostas paginadas, apenas campos projetados do catálogo; não expor contatos, comprovantes, pedidos integrais ou URLs internas.
- Registrar auditoria com identificador da requisição, serviço, tenant, contagem e hash do manifesto; não registrar corpo sensível ou segredo.
- Exportação condicionada a `indoor_consent=true`, direitos válidos e aprovação específica. Revalidação antes da programação e em cada manifesto.
- URLs de mídia somente geradas por componente de distribuição com allowlist de origem, resolução DNS/IP pública, redirects restritos e autorização temporária. Caminho local fornecido pelo exportador não é URL de acesso público.
- Implementação inicial limitada a HML. Nenhum endpoint deverá ser habilitado sem teste de replay, expiração, tenant cruzado, revogação e rate limiting.

## Estado de execução em 2026-11-01
Workflows adicionados:
- TVSUMARE_ENTERPRISE: `.github/workflows/indoor-catalog-tests.yml` — execução 41349087276 concluída com sucesso.
- vitrine-ai-pro: `.github/workflows/indoor-adapter-tests.yml` — resultado a confirmar no GitHub.
Este documento é especificação de segurança, **não implementação de autenticação**.
