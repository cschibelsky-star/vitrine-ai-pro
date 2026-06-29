Vitrine AI Pro v2.4 — Site institucional integrado ao Master

Integração realizada:
- Formulários do site continuam salvando lead local em data/leads.
- Formulários também enviam automaticamente para o Master:
  https://app.vitrineiapro.com.br/api/leads

Arquivos alterados:
- config.php
- includes/functions.php
- salvar-lead.php
- salvar-solicitacao.php

Formulários integrados:
- contato.php
- includes/footer.php / Consultor Digital
- proposta/index.php
- solicitacao-institucional.php

Observação técnica:
A integração é server-to-server via PHP, portanto evita bloqueio de CORS no navegador.
Se o Master estiver fora do ar, o site continua salvando o lead localmente e redirecionando para obrigado.php.

Teste:
1. Subir o site no domínio vitrineaipro.com.br.
2. Preencher formulário de contato/consultor.
3. Conferir o lead em app.vitrineiapro.com.br/admin > Comercial.
