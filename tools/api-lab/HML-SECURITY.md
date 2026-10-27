# API Lab — homologação de segurança, 27/10/2026

Estado: validação local isolada concluída; homologação remota e integração bloqueadas até os gates abaixo. Base revisada: PR #128, commit 02ca291628193171e376a8e46dfdc71f2167b77d. Sem merge, deploy ou chamadas pagas nesta rodada.

## Evidência reproduzível

Python 3.11+, biblioteca padrão: `python -m unittest discover -s tests -v` a partir de tools/api-lab. A suíte original passou com 29 testes. A ampliação reproduziu quatro falhas antes da correção: finalidade arbitrária com assinatura válida, campo adicional ignorado na chamada interna, confirmação de preço textual em vez de booleana e ausência de ocultação do token HTTP em artefatos. A suíte final contém 46 testes distintos, sem herança duplicando casos. Usa transporte simulado, chaves sintéticas, SQLite temporário e HTTP real somente em loopback. Nenhuma evidência corresponde a inferência ou cobrança real de fornecedor.

Correções: validação central dos argumentos também na API interna; finalidade limitada a test/exception; confirmação de teto estritamente booleana e fonte textual não vazia; ocultação do token HTTP e segredo de aprovação em respostas de fornecedor, além do conteúdo dos artefatos; validade máxima de grant reduzida de 15 para 5 minutos para corresponder ao emissor e à documentação; valores monetários acima da capacidade inteira do SQLite rejeitados antes da gravação. O teste antigo de ausência de autorização agora usa purpose=exception; finalidades desconhecidas têm testes de rejeição próprios.

## Matriz de aceitação

| Controle | Evidência local | Limite da conclusão |
|---|---|---|
| Reserva por execução e global | Rejeição antes do transporte, inclusive duas conexões concorrentes | Reserva depende de teto conservador certificado; não impõe teto de cobrança ao fornecedor |
| Persistência de orçamento | Execuções expiradas continuam no total | Banco deve ser durável; substituir/apagar o banco contorna a contabilidade administrativa |
| Idempotência | Mesmo resultado após reinício; conflito rejeitado; timeout não reenvia | Não oferece retomada automática de tentativa em voo; não compartilhar conexão sem serialização |
| Exceção específica | Ação/hash de todos os argumentos, prazo, assinatura e nonce | HMAC exige segredo no servidor verificador; isolamento do processo é requisito de implantação |
| Uso único | Replay negado, inclusive após reinício | Token consumido antes da execução; falha posterior exige nova aprovação externa |
| Sem autoautorização MCP | Inventário fechado, sem approve/grant/shell/deploy/proxy | Não comprova isolamento do host nem aprovação humana real |
| Namespace | Artefatos de uma execução ausentes em outra | Single operator: run_id não é ACL por usuário |
| Ambiente e destinos | Sem parâmetro de destino/ambiente arbitrário; argumento extra rejeitado; hosts fixos; DNS privado rejeitado | Não é classificador semântico de prompts; destinatários e dados reais exigem governança externa |
| HTTP | Bearer, Host/Origin, loopback obrigatório, lock e protocolo | Não comprova proxy TLS, OAuth ou compatibilidade com ChatGPT |

Exceção não amplia orçamento, destino, ferramentas nem duração da execução. A capacidade de assinar não deve ser concedida ao agente. A autorização humana não pode ser inferida de um authorization_ref escrito pelo próprio agente. O piloto atual verifica assinatura, não a existência de consentimento no Cockpit. Não homologar esse fluxo como completo.

## Plano isolado de HML

1. Criar serviço/usuário exclusivo, diretório privado fora do checkout (0700), banco e configuração privados (0600), namespace e porta de HML próprios. Não montar diretórios, Docker socket, credenciais ou bancos de produção. Não apagar/trocar banco para renovar orçamento. Não provisionado nesta rodada.
2. Primeira fase sem credenciais de fornecedores, catálogo/modelos vazios e orçamento administrativo zero. Rodar a suíte e o handshake stdio; validar listener em 127.0.0.1. Preservar evidências sanitizadas e identificação do commit. O transporte de teste é injetado somente pela suíte, sem endpoint público para alterná-lo.
3. Separar emissor externo do processo cliente/agente. Não montar approve.py, segredo de assinatura nem shell do host no ambiente do agente. Na implantação atual o verificador HMAC também possui o segredo: considerar verificação assimétrica ou serviço externo antes da exposição remota. Vincular consentimento autenticado à identidade, operação exata, run_id, destino/modelo/hash, orçamento, ambiente HML, prazo e referência imutável de auditoria. Falha ou indisponibilidade deve negar a exceção.
4. Validar reinício, cópia de segurança/restore do banco sem rollback de reservas/nonces, isolamento de processos e duas tentativas concorrentes no host HML. Executar cada instalação com um único operador; não anunciar multiusuário.
5. Somente após autorização específica de orçamento e tarifas verificadas, preparar eventual sonda paga separada. Nenhum valor de configuração autoriza gasto por si só. Registrar request_id, reserva, custo reportado e débito conciliado separadamente.

## Gates para orquestrador e ChatGPT

- Continuar draft e test_only; sem tráfego de clientes, fallback operacional, produção ou autorização permanente.
- Identidade/OAuth compatível com o cliente, isolamento e permissões por usuário/execução, TLS/proxy, limitação global de requisições e ensaio de autenticação remoto. Não alegar suporte remoto com base no loopback.
- Aprovação humana no Cockpit com trilha verificável, emissão externa específica e uso único; ensaio negativo de autoemissão e replay entre instalações. Definir audiência/instalação e evitar reutilização do mesmo segredo em bancos distintos.
- Aprovação de orçamento fornecida fora do agente e vinculada à execução. Hoje run_create aceita um orçamento declarado pelo chamador até o teto administrativo; isso não prova aprovação financeira humana.
- Ledger durável, conciliação de custo real, política para falhas de cobrança e auditoria de grants/negativas. Nonce consumido no SQLite não substitui trilha de consentimento do Cockpit.
- Registrar no orquestrador a allowlist exata das seis ferramentas; negar operações desconhecidas e qualquer uso como conector operacional genérico. Testar que nenhuma seleção automática concede exceção.
- Revisão independente dos gates antes de habilitar credenciais, endpoint remoto ou integração. Voz/imagem/vídeo continuam não implementados.
