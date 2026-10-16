# Assessor Online — gate de fidelidade visual (16/10/2026)

## Situação comprovada
- Usuário rejeitou o visual do endereço antigo como diferente do aprovado.
- Branch de HML em auditoria: `design/assessor-panel-audit-20261014` (antes desta documentação, HEAD `eaec450a0d747ee909cd89baf640c8d7b1356f85`).
- `assessor-online.php`: hero em gradiente azul, título textual e preview conceitual de fluxo; **não há logo próprio Assessor Online explícito no markup da página**, que usa o cabeçalho institucional compartilhado.
- `assessor-online-demo.php`: dashboard estático com exemplos fictícios.
- `assets/img/` tem imagens e logo da Vitrine IA Pro; a auditoria não encontrou o logo específico do Assessor Online no inventário desta pasta.
- Arte encontrada no histórico de arquivos: `Imagem do ChatGPT 13 de out. de 2026, 18_22_50.png` (logo Assessor Online), mas **não é comprovação de aprovação do layout integral**.
- Container HML `assessor_web` foi visto healthy em localhost 18782; não equivale a URL externa aprovada.

## Gate obrigatório antes de alterar o layout
1. Recuperar o screenshot ou arquivo aprovado da landing completa e identificar origem e revisão.
2. Registrar arte original e seu hash de forma rastreável, sem substituir o arquivo nem editar o conteúdo aprovado.
3. Comparar o HTML servido com a arte em viewports 1440x900, 390x844 e 320x700; verificar logo, tipografia, hero, espaçamento, ordem das seções e CTA.
4. Abrir alterações pontuais no código em branch dedicada; evitar alterar `index.php` institucional e alterações não consolidadas.
5. Capturar evidências lado a lado, executar testes de responsividade, links, contraste, teclado e smoke no runtime HML.
6. Validar rota HML externa, DNS/TLS e Cockpit; somente depois submeter a aprovação humana. Não fazer merge/cutover de produção automaticamente.

## Critérios que NÃO equivalem a aprovação visual
- CI verde, servidor healthy, URL acessível, imagem publicitária de referência, protótipo semelhante, screenshot do endereço antigo ou presença de cores próximas.

## Escopo preservado
Não modificar dados de cidadãos, trial, autenticação, cobrança, migrations, produção nem workspace institucional com modificações locais. Registros de referência: issue #116, PR #108, PR #109, PR #114.
