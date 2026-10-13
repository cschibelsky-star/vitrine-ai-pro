# Assessor Online — critérios de homologação (13/10/2026)

## Situação
- Landing comercial: assessor-online.php (branch dedicada)
- Página de demonstração: assessor-online-demo.php (dados inteiramente sintéticos)
- PR draft: #108
- Projeto assessor-online: não registrado no Super Centro Operacional na auditoria de 13/10/2026.
- Nenhum deploy, DNS, merge, migração, disparo ou assinatura criada nesta etapa.

## Gates obrigatórios antes de HML funcional
1. Registrar projeto no provedor oficial HML usando identidade e repositório canônicos.
2. Validar domínio, proxy, DNS/TLS, health, isolamento e link Cockpit.
3. Testar páginas PHP, CSS e responsividade em ambiente servido; impedir cache com dados de outros clientes.
4. Implementar backend multi-tenant seguro para solicitações, logs, prazos e notificações; evitar usar o demonstrativo estático como aplicativo produtivo.
5. Formulário de vendas: validar CSRF e persistência; garantir que o campo Assessor Online seja aceito pelo endpoint de gravação.
6. Teste SaaS 30 dias: provisionamento isolado, relógio em UTC, cancelamento explícito, sem cobrança automática, expiração e políticas de retenção/exportação.
7. Dados pessoais: minimização, base legal, transparência, separação entre atendimento e marketing, RBAC por gabinete, auditoria, supressão de descadastro.
8. Social: eventos aprovados e sem dados pessoais, integração por APIs com Marketing IA e Vitrine Social Mídia, aprovação humana antes da publicação.
9. Comunicação de campanha: fontes institucionais e finalidade verificada; validação jurídica/LGPD e entregabilidade antes de disparar.
10. CI e rollback: testes de regressão, backup, evidências de homologação e aprovação humana.

## Demonstração sintética
- Personagem: vereador Rafael Monteiro.
- Cidade: Vila Esperança (fictícia).
- 128 protocolos simulados / 93 concluídos / 24 acompanhamento / 11 aguardando.
- Todos os indicadores são ilustrativos; nenhuma mensagem ou integração externa dispara pela página.
