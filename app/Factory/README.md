# Factory

Camada responsável pelo ciclo único de intake, simulação, build, provisionamento, instalação, health check, homologação, entrega e ativação de licença.

Regras:
- usa `FactoryStage` como vocabulário oficial de status;
- simulação nunca pode ser marcada como pronta para homologação;
- produtos integram por `ProductModule`;
- o pipeline registra saída, código de retorno e estágio real.
