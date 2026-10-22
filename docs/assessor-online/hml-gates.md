# Assessor Online — critérios de homologação (13/10/2026)

## Situação
- Landing comercial: assessor-online.php (branch dedicada)
- Página de demonstração: assessor-online-demo.php (dados inteiramente sintéticos)
- PR draft: #108
- Registro do projeto no Super Centro Operacional confirmado em auditoria posterior; a observação de não registro em 13/10/2026 é histórica.
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


## Fluxo de entrega: Cockpit HML -> homologação -> venda oficial -> página individual

**Decisão de produto (22/10/2026):** a marca oficial é **Assessor Online**, da Vitrine IA Pro. Não renomear para Elo Mandato ou Gabinete Online. Este projeto EVOLUI o que já existe em Git e HML, sem recriar um produto paralelo.

### Gate A — HML primeiro, acesso pelo Cockpit
1. Evoluir a aplicação REAL (cadastros, casos, protocolos, timeline, documentos, permissões e portal público), com dados fictícios controlados, no runtime Laravel HML isolado. A demonstração estática sozinha não conta como conclusão.
2. Reconciliar a base Git de forma não destrutiva. Evitar merge/checkout cego entre PRs #108, #109 e #114; preservar commits e validar testes de regressão e CI.
3. O cartão **Assessor Online** no Cockpit deve apontar para a URL ADMIN HML somente após confirmação de rota, DNS/TLS, health e login autenticado. Sem URL inventada e sem simular SSO.
4. Testar a jornada no próprio Cockpit: login e RBAC, criar contato, registrar atendimento, mudar status, histórico, anexos, prazo, consulta por protocolo e portal público de teste; comprovar isolamento entre dois gabinetes e comportamento mobile.
5. A avaliação de 30 dias somente pode ser habilitada depois dos gates do fluxo de provisionamento do PR #109; sem contas/dados reais antes de homologação.

### Gate B — aceite antes da página comercial oficial
- A homologação deve produzir evidências (URLs válidas, health, testes funcionais, captura real, CI, RBAC, LGPD e rollback) e aceite explícito.
- Não alterar a página comercial pública nem promover rotas para produção enquanto o Gate A estiver pendente.
- A landing oficial em `assessor.vitrineiapro.com.br` é comercial: apresentação do produto, benefícios, planos/avaliação somente quando disponíveis, CTA e acesso à área do cliente; não usar a landing como painel administrativo.

### Gate C — venda com página própria do mandatário/cliente
- Após contratação e provisionamento seguro, cada vereador, deputado ou outro cliente autorizado recebe instância/tenant e **página individual**, administrada pelo respectivo gabinete, separada da landing de vendas.
- A página individual deve ter endereço próprio estável vinculado ao cliente e link disponível no fluxo comercial/área do cliente. Definir e validar o padrão final de URL com DNS/TLS e roteamento antes de publicar (ex.: caminho `/mandato/{slug}` é **apenas exemplo de contrato**, não link ativo).
- O cliente tem autenticação real e controle de equipe/roles; o cidadão acessa somente o portal público autorizado, registra manifestação e acompanha protocolo sem visualizar dados de outros clientes.
- O vínculo da landing para a página individual deve ser resolvido por cadastro/provisionamento validado, sem nome arbitrário do usuário nem destino externo não validado (evitar redirecionamento aberto).
- Exigir isolamento tenant de banco/consultas/storage, política de dados e consentimentos, audit trail, backups, revisão de permissões e aceite do contratante; publicações externas e WhatsApp seguem gates independentes.
- Não disponibilizar link de gabinete inexistente/desabilitado nem insinuar que a instância já está ativa. A vitrine poderá exibir acesso do cliente somente quando a instância existir e estiver saudável.

### Estado conhecido em 22/10/2026
- PR #108 em draft, com estrutura inicial de demandas; melhorias no workflow e testes adicionados na branch dedicada.
- O Cockpit possui cartão Assessor Online na branch `feature/cockpit-assessor-online-hml`, porém `integration=pending`, `admin_url=null`, `health=unknown`.
- PR #109 descreve bloqueios de provisionamento, TLS/rota e adapter por tenant; **não há comprovação de HML funcional integrada**.
- Nenhuma URL de HML, comercial ou página individual é declarada homologada por esta documentação.
