# Vitrine API Lab — piloto 0.1

Servidor MCP stdio isolado, sem dependências de terceiros. Não é um endpoint remoto conectado ao ChatGPT. Este piloto implementa catálogo, chat curto, evidências e escrita de artefatos em SQLite. Voz, imagem, vídeo, HTTP remoto, aprovação pelo Cockpit e integração do orquestrador estão pendentes.

## Executar em homologação

Python 3.11+. Criar diretório privado de dados fora do repositório. Definir LAB_CONFIG (configuração JSON), LAB_DATABASE (arquivo SQLite privado), e credenciais dos provedores no gerenciador de segredos do host. Executar `python3 lab.py` por um cliente MCP stdio confiável, um processo por usuário/instalação. O processo atende JSON-RPC por linha; stdout fica reservado ao protocolo. Não expor stdin/stdout na rede.

Copiar config.example.json para configuração privada. Os catálogos podem ser consultados, mas geração fica bloqueada por padrão: nenhum modelo está liberado. Para habilitar um modelo, o operador deve confirmar na conta e documentação o limite conservador de cobrança para o payload fixo (até 4000 bytes de prompt, saída até 128 tokens, sem streaming) e registrar:

```json
{"models":{"ID_EXATO":{"max_charge_brl":"VALOR_VERIFICADO","ceiling_verified":true,"price_source":"fonte, data e plano efetivo"}}}
```

Não copiar uma estimativa publicitária para esse campo. O teto depende do contrato, câmbio e conta; não é limite imposto ao fornecedor. Não ativar modelo sem limite superior verificável. Cada chamada reserva o valor inteiro e nunca libera automaticamente, inclusive em timeout/falha; reserva não é débito confirmado. Ainda não há conciliação automatizada nem estimativa por token. O limite de cada execução deve vir da autorização do usuário; max_run_brl é apenas teto administrativo. max_total_brl limita a soma de reservas de todas as execuções no mesmo banco, inclusive expiradas; abrir outra execução não renova esse saldo. Não apagar nem trocar o banco para renovar o teto.

## Ferramentas

- lab_status: capacidades e presença de credenciais, sem valores.
- run_create: finalidade test, orçamento autorizado e prazo até uma hora; approval vazio em teste normal.
- catalog_read: IDs do catálogo do provedor, vinculado a execução ativa.
- chat_test: modelo autorizado, prompt curto, idempotência persistente e reserva atômica.
- artifact_write: artefatos até 100KB, máximo 20 por execução, armazenados no namespace SQLite.
- run_evidence: tentativas, resultados e reservas separadas de custo real e qualidade.

Testes não devem atingir produção nem tráfego de clientes. Não há proxy arbitrário, shell, URL fornecida pelo chamador, deploy ou filesystem genérico. Os únicos destinos são hosts HTTPS fixos, DNS público validado e IP fixado por conexão; não seguir redirects. Novos provedores exigem revisão do adaptador.

## Exceção autorizada

Cada ferramenta com run_id aceita purpose=exception e approval opcional. O token deve ser emitido por operador externo após autorização específica do usuário, por approve.py; nunca oferecer essa rotina como ferramenta ao agente. O token vincula ação e todos os argumentos, incluindo destino/modelo/payload/run_id, expira em cinco minutos e só pode ser usado uma vez. O segredo de assinatura fica no host/serviço de aprovação, inacessível ao agente; o servidor verifica-o. Nenhum argumento da chamada concede autorização por si só.

A exceção não desbloqueia métodos inexistentes ou destinos arbitrários: neste piloto só pode autorizar uso excepcional das capacidades já implementadas. Operações futuras exigem adaptador, escopo e testes próprios. Não existe autorização permanente ou execução operacional automática. O pipeline de captura da autorização humana e emissão pelo Cockpit ainda precisa ser implementado; não confundir teste sintético do token com aprovação humana real.

## Validação

`python3 -m unittest discover -s tests -v`

Os testes são locais com transporte simulado; não gastam saldo e não comprovam entrega dos provedores. Antes de publicar um endpoint remoto, adicionar autenticação/identidade por usuário, transporte HTTP MCP, aprovação externa integrada, rate limits globais e validação em homologação/Cockpit. Aplicar a skill vitrine-api-benchmark para comparações; este servidor não decide ranking nem roteamento do Centro IA.
