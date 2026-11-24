# Instrucoes de desenvolvimento - Vitrine IA Pro

Estas regras valem para sugestoes de codigo no VS Code e para agentes de IA que trabalhem neste repositorio.

## Governanca

- GitHub e a fonte de verdade.
- Trabalhar em branch dedicada. Nao alterar `main` diretamente.
- Nunca executar `git reset --hard`, `git clean`, exclusoes em massa ou sobrescritas sem preservar o estado atual.
- Nunca executar migrations, merge, deploy, cutover ou alteracao de infraestrutura sem autorizacao explicita.
- Antes de sugerir mudancas, localizar implementacoes existentes e reutiliza-las.
- Nao duplicar servicos, providers, rotas ou integrações que ja tenham uma implementacao canonica.

## IA e Roteia

- O ponto de entrada de IA do Core e `App\Services\Ai\AiRoutingService`.
- Preferir o Hub IA/AiRoutingService em vez de integrar provedores diretamente em features novas.
- A Roteia e gateway preferencial quando estiver configurada e habilitada; manter os fallbacks ja definidos no roteador.
- Nunca versionar API keys, tokens ou segredos. Usar variaveis de ambiente/secret store.
- Antes de adicionar novo modelo/provedor, validar capacidade, custo, timeout, tratamento de erro e fallback.
- Para mudancas na Roteia, preservar compatibilidade com provedores alternativos.

## Qualidade

- Projeto principal: PHP 8.3, Laravel 12 e Filament 3.3.
- Codigo PHP deve seguir PSR-12 e passar por Laravel Pint.
- Toda correcao deve incluir ou atualizar teste quando houver comportamento verificavel.
- Antes de concluir uma alteracao, executar os testes relevantes e inspecionar o diff.
- Tratar estados de erro, timeout, resposta invalida, indisponibilidade de provedor e dados ausentes.
- Evitar alteracoes amplas quando uma mudanca localizada resolver o problema.

## Fluxo para o operador

Quando o pedido vier em linguagem natural, primeiro traduza-o para:
1. objetivo;
2. arquivos afetados;
3. riscos;
4. alteracao minima;
5. testes;
6. diff final.

Se uma acao puder apagar dados, publicar em producao, gerar custo externo relevante ou mudar infraestrutura, nao execute automaticamente.
