# Estacao de Desenvolvimento Vitrine IA Pro - VS Code + GitHub + Hub IA/Roteia

## Objetivo

Este perfil foi preparado para permitir que uma pessoa sem experiencia em programacao trabalhe com o projeto pelo VS Code usando instrucoes em linguagem natural, mantendo GitHub como fonte de verdade e o Hub IA/Roteia como camada canonica de inteligencia artificial.

## O que ja fica preparado no repositorio

- extensoes recomendadas do VS Code;
- tarefas prontas para instalar dependencias, preparar ambiente, rodar testes e iniciar servidor local;
- instrucoes de projeto para Copilot/assistentes de codigo;
- regras de seguranca para evitar reset, clean, migrations, deploy ou merge acidental;
- configuracao documentada da Roteia sem qualquer segredo versionado.

## Instalacao no Windows

Instale apenas estes componentes no computador:

1. Visual Studio Code;
2. Git;
3. PHP 8.3;
4. Composer.

Depois:

1. clone este repositorio pelo VS Code;
2. abra a pasta clonada;
3. aceite a instalacao das extensoes recomendadas;
4. abra `Terminal > Run Task`;
5. execute `Vitrine: instalar dependencias PHP`;
6. execute `Vitrine: preparar .env local`;
7. configure apenas os segredos locais necessarios no arquivo `.env`;
8. execute `Vitrine: testes`;
9. execute `Vitrine: servidor local`.

A aplicacao local ficara, por padrao, em `http://127.0.0.1:8000`.

## Como trabalhar sem saber programar

No chat do VS Code, descreva o objetivo em portugues, por exemplo:

- "Analise por que o login esta falhando. Nao altere nada ainda."
- "Corrija este erro em uma branch e rode os testes."
- "Mostre o diff antes de qualquer merge."
- "Use o Hub IA existente; nao crie integracao direta com outro provedor."
- "Nao execute migration nem deploy."

O assistente deve seguir `.github/copilot-instructions.md`.

## Roteia

O Core ja possui `App\Services\Ai\AiRoutingService`. Portanto, novas funcionalidades devem chamar essa camada em vez de acessar modelos de IA diretamente.

Variaveis locais esperadas:

```env
ROTEIA_BASE_URL=
ROTEIA_API_KEY=
```

Nunca coloque valores reais em commits, mensagens, prints ou documentacao.

## Fluxo recomendado

```text
Pedido em portugues
      |
      v
VS Code + assistente de codigo
      |
      v
branch dedicada
      |
      v
alteracao minima
      |
      v
Pint + testes
      |
      v
diff revisado
      |
      v
commit / GitHub
      |
      v
CI
      |
      v
HML
      |
      v
producao somente com autorizacao
```

## Principio de arquitetura

VS Code e o ambiente de desenvolvimento. Roteia nao deve receber permissao irrestrita para editar o repositorio. A IA sugere/produz mudancas no workspace; Git, testes, CI e homologacao funcionam como controles de seguranca.
