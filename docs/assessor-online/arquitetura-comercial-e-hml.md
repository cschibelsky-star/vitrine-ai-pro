# Arquitetura oficial — Assessor Online e ecossistema Vitrine IA Pro

**Escopo:** Assessor Online, sua landing comercial e avaliação experimental de 30 dias.
**Status:** contrato de responsabilidades e critérios de liberação; **não** constitui autorização para deploy, habilitação de trial ou cobrança.

## Responsabilidades que não podem ser confundidas

| Componente | Papel | O que não faz |
| --- | --- | --- |
| **Vitrine IA Pro (portal institucional)** | Apresentar o portfólio e encaminhar cada visitante à página específica do produto. | Não é um funil central de vendas, não inicia trial, não coleta cadastro comercial de todos os produtos. |
| **Página comercial do Assessor Online** | Explicar a proposta do produto, exibir demonstração fictícia, captar interesse e, **após homologação**, iniciar o fluxo de avaliação do próprio Assessor Online. | Não provisiona tenant nem administra licenças; não promete funcionalidade indisponível. |
| **Core / Centro Operacional** | Sistema administrativo central dos produtos comercializados: cliente/empresa, plano, licença, assinatura e situação financeira. | Não substitui a experiência comercial individual nem o runtime funcional do produto. |
| **Cockpit** | Catálogo e validação de ambientes de homologação (HML), com build, rota, health e evidências reais. | Não é painel comercial nem administra clientes vendidos. |
| **Factory** | Fábrica que desenvolve, versiona, testa e entrega builds e correções. | Não emite licença comercial nem publica diretamente em produção sem gates. |
| **Assessor Online (runtime)** | Atende os gabinetes em ambientes segregados, aplicando permissões, prazos, auditoria e isolamento de dados. | Não pressupõe acesso por simples cadastro de lead. |

## Fluxo de referência

1. Vitrine IA Pro → card do Assessor Online → landing **específica** do produto.
2. Landing → apresentação/demonstração ilustrativa, sem coleta de dados de cidadãos.
3. **Somente quando homologado:** botão de avaliação → autenticação, verificação de responsável/e-mail e vínculo à empresa no Core.
4. Core/Assessor registra solicitação e controle de 30 dias sem cobrança automática, sem repetir trial no mesmo gabinete.
5. Factory prepara build; provisionador oficial cria runtime **isolado**; Cockpit registra rota, TLS, health, build e auditoria. Início do prazo ocorre só após evidências positivas.
6. Runtime Assessor Online aplica a vigência e restrições a toda operação; vencimento/cancelamento nega acesso e aciona revogação com confirmação.
7. Contratação posterior é uma decisão comercial explícita e um fluxo próprio da página do produto; Core gerencia a licença paga. Não migrar automaticamente nem destruir dados ao fim do trial.

## Objetos distintos: PR #108, #109 e #110

- **PR #108**: landing e demonstração do Assessor Online. Não confundir demo estática com aplicativo pronto.
- **PR #109**: avaliação **específica** do Assessor Online, somente HML e flag desabilitada por padrão; é o PR responsável pelo fluxo de 30 dias desse produto.
- **PR #110**: serviço **genérico** de trial no Core. Não é a landing nem a solução de provisionamento do Assessor Online; evitar incorporar duas implementações concorrentes sem desenho de integração, idempotência e fonte de verdade.

## Gate de homologação obrigatório

- [ ] CI na revisão exata, testes de expiração, revogação, isolamento e fluxo negativo.
- [ ] Provisionador oficial funcional, idempotente, com credenciais server-side e revogação comprovada.
- [ ] Rota HML real + DNS/TLS válidos + upstream health/read-back.
- [ ] Registro real da HML no Cockpit, não apenas cartão pending.
- [ ] Onboarding e RBAC do gabinete; verificação de titularidade e empresa.
- [ ] Sem dados de cidadãos reais, publicação automática ou marketing sem fundamento jurídico próprio e consentimento adequado.
- [ ] Política de privacidade, exportação/retencão e mecanismos de atendimento a titulares validados.
- [ ] Conversão comercial explícita e contrato/financeiro no Core; nenhum débito implícito.
- [ ] Publicação em produção apenas por gate de deploy separado e autorizado.

## Constatações da auditoria em 14/10/2026

- PR #108 e #109 abertos/draft.
- PR #109 CI `Assessor trial HML contracts`, `PHP Syntax` e `Architecture Inventory`: aprovados no commit `a2b4ad481fcfc876b85dccb9ebc4e9806d372e32`.
- Workspace `assessor-online-trial-hml` disponível e Git limpo no branch do PR #109.
- `hml_route_inspect(assessor-online-trial-hml)` e `hml_route_inspect(assessor-online)`: `route_not_registered`.
- `proxy_route_inspect(assessor.hml.vitrineiapro.com.br)`: nenhuma rota encontrada; a inspeção reportou duas falhas internas, portanto não inferir ausência de toda configuração.
- Não declarar HML externamente acessível. O adaptador oficial de provisionamento continua indisponível no código desta revisão.
- Nenhum merge, migration, deploy ou ativação pública executados durante esta auditoria.

**Regra de preservação:** não transformar a Home da Vitrine IA Pro em página de vendas, não reproduzir CTA de trial em produtos não habilitados, não redirecionar para avaliação 404 e não sobrescrever a landing aprovada.
