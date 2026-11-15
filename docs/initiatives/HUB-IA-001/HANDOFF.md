# HUB-IA-001 — Continuidade ChatGPT/Codex
Leia PROJECT_STATE.md, DECISIONS.md, EVIDENCE.md nesta branch e confronte o estado com GitHub e runtime. Não confunda decisões com implementação.
Missão: Hub independente com Roteia, Invision e Avello2, para produção pessoal/Codex e integração futura separada ao Centro IA Vitrine. A produção operacional do Avello2 é objetivo declarado, não estado comprovado.
Próxima ação única: levantamento técnico de capacidades reais dos três provedores e do Avello2 já existente; verificar a rota completa escrever/testar/implantar/expor/usar antes de abrir novos PRs.
Não modificar árvores sujas; não criar PRs em cascata; não ler segredos; sem merge, deploy ou API paga sem autorização específica. Laboratório API é test_only, não executor operacional.
Gate: PENDENTE até que tarefa real seja executada no cliente-alvo e seu resultado evidenciado. Preservar isolamento pessoal/HML/PROD.
Ao concluir cada ação, atualizar estado, decisões (se houver), evidências, bloqueios e próxima ação concreta.

## Resultados da execução atual
Avello2 já está exposto como conector no ChatGPT e responde a status/modelos. O Centro IA já tem roteamento Roteia, suporte de mídia com endpoints configuráveis e controlador de vídeo. Reutilize esses componentes; não crie novo gateway empresarial em paralelo.
Primeiro caminho de entrega: adaptador PESSOAL independente para cliente Codex que utilize APIs verificadas de gateways, isolando credenciais. Não presumir que o MCP Avello2 fornece automaticamente endpoint de provider para Codex. Primeiro recuperar configuração Codex existente (não acessível neste conector), contrato HTTP real da Invision e Roteia e compatibilidade; somente depois configurar e executar tarefa real autorizada.
Blockers concretos: sem acesso à configuração local ativa Codex nesta sessão; sem comprovação de endpoints autenticados Invision; sem orçamento autorizado para inferência potencialmente cobrada; Avello2 não disponibiliza execução remota de comandos. Não declarar conector entregue por causa de documentos ou catálogos.
Próximo passo técnico: examinar os provedores/configurações locais por canal autorizado do Codex e confirmar um adaptador de modelo; executar teste ponta a ponta apenas após resolver acesso e limite financeiro.

## Entrega de cliente portatil nesta conversa
Foi criado um pacote ZIP local HUB-IA-001-cliente-independente.zip contendo script Python 3.10+ e 5 testes locais aprovados. Ainda nao foi versionado no GitHub nem instalado no computador do usuario. O status tem chave ausente neste runtime efemero.
Para continuar: obter o pacote da conversa, testar no Windows com as credenciais mantidas apenas no ambiente pessoal e consultar modelos. Nao realizar chat pago sem teto de gastos autorizado. Validar separadamente a configuracao ativa Codex e a API Responses/tool calling antes de configurar o seletor; evitar sobrescrever config.toml existente. Nao confundir app Invision ja instalado com nosso script. Avello2 segue disponivel nesta conversa para status/catalogo, nao e um provider Codex automaticamente.

## Continuidade V2 — 2026-11-15
Artefato local V2 foi anexado à conversa: HUB-IA-001-cliente-codex-v2.zip. Não é software instalado na máquina do usuário. O script codex_preview.py prepara apenas argumentos efêmeros para Codex/Invision, sem escrever config.toml e sem transmitir chave. Dez testes locais passam.
Antes de integrar: consultar versão e configuração Codex ativa no computador real, preservando instalações Invision e configurações existentes. Validação Codex Responses/tool calling ainda não realizada. Se calls forem pagas, exige autorização de teto e controles na conta; --allow-paid do cliente HTTP NÃO impõe orçamento automático. Codex não é acionado automaticamente pelo pacote.
O Roteia possui compatibilidade chat documentada, não foi assumida compatibilidade Codex Responses. Avello2 MCP segue separado e preservado.

## Continuidade após primeira resposta real — 2026-11-15
Usuário instalou/extraiu Hub V2 no Windows e executou 10 testes OK. O Codex 0.169.0 já tinha provedor Invision cadastrado e manteve Avello como padrão em config.toml. Uma sessão explicitamente com Invision / Qwen3.8 Max respondeu 'OK' em 22 s; o /status subsequente mostrou 25.7K tokens totais (25.6K entrada, 76 saída). NÃO afirmar que o Hub V2 mediou a chamada ou que o custo foi conhecido.
Gate conectividade Invision/Codex APROVADO somente para texto nesta sessão; fluxo de programação, integração do Hub, Roteia, Avello2 e vídeo PENDENTES.
Próxima etapa: conciliar custo/limite no painel do fornecedor e solicitar teto específico ANTES de novo benchmark pago. Caso autorizado, aplicar CODE01 do protocolo v1.0 em workspace pessoal isolado e modo somente leitura; verificar saída e evidência independentemente. Sem PR, deploy ou alteração global.

## Rodada CODE01 autorizada até R$ 2,00, mas NÃO DISPARADA — 2026-11-15
Condição expressa: teto técnico de gasto comprovado ANTES de envio. Ferramentas atuais não apresentam reserva ou aplicação atômica desse orçamento. CODE01-ROUND.md contém prompt congelado, rubrica e condição de desbloqueio. Próxima tarefa única: verificar e comprovar controle de gastos no gateway para esta conta; sem essa prova não fazer inferência, mesmo com consentimento genérico. Nenhum PR, deploy ou alteração de runtime nesta rodada.

## Entrega local para expansão gradual do seletor Windows — 2026-11-15
- Usuário informou resposta de programação correta com Qwen3.8 Max no aplicativo Codex Windows usando o catálogo mínimo; houve reconexão antes da resposta, portanto estabilidade ainda pendente. Custos/ID upstream desconhecidos.
- Pacote gerado nesta conversa: HUB-IA-001-Codex-Windows-3-modelos.zip. Ele expande apenas o arquivo local hub-ia-001-invision-min.json usando entradas já presentes no invision-models.json, adicionando candidatos qwen/qwen3.8-flash-next e invision/coding ao qwen/qwen3.8-max validado. Modelo padrão não muda.
- O pacote NÃO acessa a rede nem a credencial; NÃO altera config.toml, Avello, original da Invision, nem ambientes VPS. Possui backup de catálogo e desfazer-expansao.bat.
- Teste isolado de expansão, bloqueio de reexecução e rollback passou com fixtures sintéticas. NÃO foi executado no Windows do usuário; nenhum dos dois novos modelos foi validado na API ou no app.
- Próximo gate: usuário aplicar com Codex fechado, confirmar três entradas no seletor, sem inferência paga automática. Só depois validar suporte de cada candidato em sessões isoladas e com orçamento acompanhado.
