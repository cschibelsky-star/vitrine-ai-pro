# Vitrine API Lab — piloto 0.1

Servidor MCP stdio/HTTP loopback isolado, sem dependências de terceiros. Não é um endpoint remoto conectado ao ChatGPT. Este piloto implementa catálogo, chat curto, evidências e escrita de artefatos em SQLite. Voz, imagem, vídeo, implantação remota com TLS/OAuth, aprovação pelo Cockpit e integração do orquestrador estão pendentes.

## Executar em homologação

Python 3.11+. Criar diretório privado de dados fora do repositório. Definir LAB_CONFIG (configuração JSON), LAB_DATABASE (arquivo SQLite privado), e credenciais dos provedores no gerenciador de segredos do host. Executar `python3 lab.py` por um cliente MCP stdio confiável, um processo por usuário/instalação. O processo atende JSON-RPC por linha; stdout fica reservado ao protocolo. Não expor stdin/stdout na rede.

Copiar config.example.json para configuração privada. Os catálogos podem ser consultados, mas geração fica bloqueada por padrão: nenhum modelo está liberado. Para habilitar um modelo, o operador deve confirmar na conta e documentação o limite conservador de cobrança para o payload fixo (até 4000 bytes de prompt, saída até 128 tokens, sem streaming) e registrar:

```json
{"models":{"ID_EXATO":{"max_charge_brl":"VALOR_VERIFICADO","ceiling_verified":true,"price_source":"fonte, data e plano efetivo"}}}
```

Não copiar uma estimativa publicitária para esse campo. O teto depende do contrato, câmbio e conta; não é limite imposto ao fornecedor. Não ativar modelo sem limite superior verificável. Cada chamada reserva o valor inteiro e nunca libera automaticamente, inclusive em timeout/falha; reserva não é débito confirmado. Ainda não há conciliação automatizada nem estimativa por token. O limite de cada execução deve vir da autorização do usuário; max_run_brl é apenas teto administrativo. max_total_brl limita a soma de reservas de todas as execuções no mesmo banco, inclusive expiradas; abrir outra execução não renova esse saldo. Não apagar nem trocar o banco para renovar o teto.

## Ferramentas

- lab_status: capacidades e presença de credenciais, sem valores.
- run_create: finalidade test, orçamento autorizado e prazo até uma hora; approval vazio somente com orçamento zero; orçamento positivo exige grant externo específico de run_create.
- catalog_read: IDs do catálogo do provedor, vinculado a execução ativa.
- chat_test: modelo autorizado, prompt curto, idempotência persistente e reserva atômica.
- artifact_write: artefatos até 100KB, máximo 20 por execução, armazenados no namespace SQLite.
- run_evidence: tentativas, resultados e reservas separadas de custo real e qualidade.

Testes não devem atingir produção nem tráfego de clientes. Não há proxy arbitrário, shell, URL fornecida pelo chamador, deploy ou filesystem genérico. Os únicos destinos são hosts HTTPS fixos, DNS público validado e IP fixado por conexão; não seguir redirects. Novos provedores exigem revisão do adaptador.

## Exceção autorizada

Cada ferramenta com run_id aceita purpose=exception e approval opcional. O token deve ser emitido por operador externo após autorização específica do usuário, por approve.py; nunca oferecer essa rotina como ferramenta ao agente. O token vincula a instalação HML (instance_id), ação e todos os argumentos, incluindo destino/modelo/payload/run_id, expira em cinco minutos e só pode ser usado uma vez. O segredo de assinatura fica no host/serviço de aprovação, inacessível ao agente; o servidor verifica-o. Nenhum argumento da chamada concede autorização por si só.

A exceção não desbloqueia métodos inexistentes ou destinos arbitrários: neste piloto só pode autorizar uso excepcional das capacidades já implementadas. Operações futuras exigem adaptador, escopo e testes próprios. Não existe autorização permanente ou execução operacional automática. O pipeline de captura da autorização humana e emissão pelo Cockpit ainda precisa ser implementado; não confundir teste sintético do token com aprovação humana real.

## Validação

`python3 -m unittest discover -s tests -v`

Os testes são locais com transporte simulado; não gastam saldo e não comprovam entrega dos provedores. Antes de publicar um endpoint remoto, adicionar identidade por usuário/OAuth conforme cliente, proxy TLS, aprovação externa integrada, rate limits globais e validação em homologação/Cockpit. Aplicar a skill vitrine-api-benchmark para comparações; este servidor não decide ranking nem roteamento do Centro IA.


## Transporte HTTP local implementado

Executar `python3 http_server.py` com LAB_ACCESS_TOKEN forte (mínimo 32 caracteres), LAB_ALLOWED_HOSTS (valores exatos de Host separados por vírgula), LAB_ALLOWED_ORIGINS (origens exatas autorizadas), LAB_PORT e as configurações de dados/credenciais acima. O listener fica obrigatoriamente em 127.0.0.1, nunca público. O processo é de operador único; quem possui o token possui acesso a todas as execuções deste banco. Não compartilhar banco/token entre clientes.

POST /mcp exige Bearer, Content-Type application/json e Accept application/json, text/event-stream. Exigir MCP-Protocol-Version nas chamadas após initialize. Responder notificações com 202 sem corpo e GET com 405 (sem SSE separado). Bloquear Host e Origin não permitidos; não confiar em X-Forwarded-Host. Configurar o proxy para preservar um Host explicitamente autorizado. Limitar conexões no proxy; o processo aceita no máximo oito workers e só uma operação simultânea.

Este transporte foi testado por HTTP em loopback com credenciais sintéticas. Não há domínio, certificado, proxy, credenciais reais ou conexão ChatGPT provisionados. O suporte a Bearer por cliente deve ser verificado antes de tentar conexão remota. Não declarar compatibilidade com autenticação do ChatGPT sem essa validação.

## Homologação de segurança — 27/10/2026

A suíte ampliada possui 58 testes locais distintos, com transporte simulado e HTTP loopback. Consulte [HML-SECURITY.md](HML-SECURITY.md) para falhas reproduzidas/corrigidas, matriz de evidências, plano isolado de HML e gates de integração. Validar tipos e finalidade no servidor é obrigatório; teto de custo exige ceiling_verified booleano true e fonte não vazia. Grants expiram em no máximo cinco minutos e não ampliam o orçamento.

A ausência de ferramenta MCP de emissão não comprova segregação do host: o verificador HMAC possui o segredo e exige isolamento do agente. run_create com orçamento positivo agora exige grant externo assinado, vinculado a valor/prazo/instalação e de uso único. A origem humana do consentimento ainda precisa de integração autenticada no Cockpit. Não habilitar integração remota, credenciais ou uso operacional com base apenas nos testes locais.

## Autorização financeira externa e HML offline

Executar `python3 offline_hml.py` para a validação local isolada. O executor remove variáveis de credenciais conhecidas no processo e bloqueia sockets Python fora do loopback; não substitui firewall/container e não é implantação remota. A CI usa esse executor.

Definir instance_id único por instalação na configuração privada e LAB_INSTANCE_ID idêntico somente no emissor externo. Não compartilhar segredo/identidade entre instalações e não conceder acesso ao emissor ou ao segredo para o agente. Tokens legados sem instance_id deixam de ser aceitos. Configuração sem instance_id nega grants e orçamento positivo. Execução com orçamento zero continua possível, mas não autoriza chat com reserva positiva.

Após consentimento específico, o operador externo pode usar approve.py com --action run_create, --args-file e --authorization-ref. O arquivo contém exatamente purpose=test, budget_brl e ttl_seconds, sem approval. Para as demais ações, continuam necessários os argumentos completos com purpose=exception. O cliente apenas apresenta o token já emitido; não existe ferramenta MCP de emissão. A aprovação de orçamento não concede finalidade excepcional, nem amplia os tetos administrativos ou a allowlist de modelos/provedores.

O SQLite registra consumo, ação, hash dos argumentos, referência externa, instalação, execução e expiração na tabela authorization_audit, sem armazenar o token. O consumo do nonce e a gravação dessa auditoria são atômicos. A referência ainda não é verificada no Cockpit; assinatura válida comprova posse da chave pelo emissor, não consentimento humano por si só. Uma falha após consumir o grant exige nova aprovação. Preserve o banco; restauração de snapshot antigo pode reabrir nonces já usados e precisa de procedimento próprio antes da integração remota.

## Contêiner descartável de homologação

O job container-hml constrói Dockerfile.hml-test e executa a suíte sob isolamento de SO, sem rede externa, usuário root, escrita na raiz, capabilities ou credenciais. container_hml.py deve aprovar todas as proteções antes dos testes. O protocolo inclui 65 testes após adicionar as verificações de admissão. Consulte HML-SECURITY.md para limites e distinção entre teste em contêiner, HML remota e integração.

A imagem serve exclusivamente para testes sintéticos descartáveis. Não é o serviço MCP de produção nem o emissor externo; contém o emissor de fixtures para os testes e não deve receber credenciais reais. Não disponibiliza porta pública ou endpoint remoto.
