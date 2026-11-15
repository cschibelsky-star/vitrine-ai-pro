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
