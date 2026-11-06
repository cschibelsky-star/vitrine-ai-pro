# Vitrine IA Pro | Nova Home Institucional (conceito implementável)

**Projeto novo e independente. Não é a Home do PR #115.**

## Testar

- Abre diretamente `index.html`, com CSS/JS/assets locais.
- Alternativa: `python3 -m http.server 8000` e acessar `http://localhost:8000`.
- Não depende de bibliotecas externas, banco, API, compilação, PHP ou conexão para renderizar.

## Princípios

1. Aprovar apenas um site funcional em navegador, nunca uma imagem que simule site.
2. UI, rede, cartões, efeitos, botões, responsividade e textos são componentes reais HTML/CSS/SVG/JS.
3. Estilo intencionalmente diferente da imagem antiga: grafite, azul-petróleo, verde-água e visual sistêmico.
4. Navegação, filtros e links são testáveis em desktop e mobile; respeita preferência por movimento reduzido.
5. Não inventar métricas, dados reais de clientes ou capacidades ainda não entregues.
6. SISMED claramente marcado como 'Em desenvolvimento'.
7. Não publicar no domínio oficial nem substituir a Home atual antes de aprovação do usuário e da HML.

## Marca

`assets/marca-referencia.webp` é um **recorte do logotipo já aprovado no cabeçalho** de uma imagem original da Vitrine IA Pro. Serve exclusivamente para preservar a identidade durante o teste e **deverá ser substituído pelo arquivo original oficial da marca** antes da publicação. Não é uma captura de tela usada como website.

## Links

Os links externos de solução e cases usam páginas públicas existentes como destinos. Links internos usam âncoras funcionais. O contato utiliza o e-mail provisório institucional. Nenhum formulário coleta dados nesta prévia.

## Organização

- `index.html`: estrutura semântica e SVG editável da arquitetura do ecossistema.
- `styles.css`: identidade visual, componentes, animações e responsividade.
- `app.js`: menu acessível, filtros de categoria e ano.
- `assets/`: marca temporária e favicon.
- `tests/`: testes automatizados de navegador para desktop/mobile, menu, filtros, links e overflow.

## Gates

A aprovação depende de teste real 1440, 1024, 390 e 360 px, clique nos filtros/menu e revisão visual. Publicação em HML e produção são operações separadas, com autorização explícita.