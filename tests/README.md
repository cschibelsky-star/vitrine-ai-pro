# Calendário do Conheça Sumaré

Execute com PHP 8.3, sem dependências adicionais:

```sh
php -d date.timezone=UTC tests/calendar.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

O relógio é convertido explicitamente para America/Sao_Paulo. As datas
start_date e end_date são datas civis (Y-m-d); end_date é inclusiva.
Sem end_date, vale o dia de início. Eventos futuros continuam na agenda.
start_time é apenas exibido; não passa a determinar expiração.
Um evento que atravessa a meia-noite precisa ter end_date no dia seguinte.

Os testes simulam 00h, 21h, 23h59m59s e a meia-noite seguinte em Sumaré,
com o servidor em UTC, São Paulo, Tóquio e Los Angeles. Cobrem início hoje,
fim hoje, travessia da meia-noite, eventos encerrados, futuros e não publicados.
O filtro mantém status=published como a aprovação editorial existente:
esta correção não publica nem confirma anúncios, nem altera suas fontes.

Revisão dos demais pontos: o rótulo de data dos cards também usa o fuso local.
JavaScript e service worker não calculam datas da agenda; navegações usam
a rede sem cache. time() no admin é timestamp Unix (IDs/expiração de cookie),
independente do fuso. O timestamp de health.php é diagnóstico e não participa
da validade dos eventos. Não há outro filtro de calendário nesta branch.

O workflow Conheca Sumare Calendar executa sintaxe e regressões em PRs.
Não contém etapa de build ou deploy e não altera produção.
