# Escritório Operacional — direção de interface e contrato funcional

## Referência visual
O vídeo de referência apresenta uma agência como planta isométrica: departamentos com mesas e pessoas, camadas do processo em uma barra inferior, fila de atividade e decisão central de aprovação. O Cockpit M1 atual é um formulário administrativo e não representa essa operação. Este protótipo traduz a referência para componentes HTML/CSS/JS responsivos.

## Estado do protótipo
- A planta, os departamentos e os bonequinhos são elementos HTML/CSS reais.
- Selecionar um funcionário destaca o posto.
- O botão VIA simula a aprovação de handoff para demonstrar a interação.
- A interface exibe um aviso permanente de dados demonstrativos; não há chamadas a agentes, persistência, n8n, publicação, nem status de produção.

## Contrato para operacionalizar cada bonequinho
Cada funcionário visível deve apontar para um registro persistente de agente e cada tarefa deve guardar pelo menos:

- `company_id` / produto e canal, para separar Vitrine Social Mídia de Marketing IA;
- `agent_definition_id` do funcionário responsável;
- `campaign_id`, `task_key`, prioridade e dependências;
- status operacional (`pending`, `ready`, `running`, `awaiting_via_review`, `needs_revision`, `ready_for_handoff`, `completed`, `failed`);
- tentativa, versão do artefato, checksum e timestamps;
- eventos de início, entrega, decisão VIA, revisão do gestor e publicação;
- motivo e ator de cada decisão.

O estado exibido deve vir da API/banco. Nunca derivar "produzindo" só da animação. Se faltarem heartbeat ou evento recente, mostrar `sem telemetria`/`desconhecido`.

## Gates obrigatórios
1. Agente conclui artefato e o envia para revisão VIA.
2. VIA aprova: persiste ator, versão, nota/evidência e horário; só então libera o próximo agente.
3. VIA pede ajustes: a tarefa volta para o mesmo `agent_definition_id`, cria nova versão e conserva as anteriores.
4. Gestor aprova a peça final depois de QA.
5. Social Media agenda/publica somente a versão aprovada pelo gestor; registrar confirmação Meta para declarar `published`.

Nenhum click de protótipo representa autorização real. Handoff, chamada paga ou publicação só podem ocorrer por endpoint autenticado, com política e auditoria server-side.

## Critério de aceite visual-funcional
- Desktop e mobile mantêm a planta compreensível e os postos acessíveis.
- Cada agente mostra nome, departamento, estado persistido, tarefa e heartbeat.
- Clicar num posto abre a tarefa e o histórico reais.
- Uma rejeição VIA é testada e retorna para o mesmo agente.
- O próximo agente não recebe tarefa antes da aprovação VIA.
- Publicação não pode ser chamada sem aprovação humana da versão atual.
- Testes de integração verificam transições, isolamento entre produtos e trilha de auditoria.

## Estado contratual

- state.schema.json define o snapshot que a tela precisa receber do backend.
- state.demo.json é uma fixture de demonstração para o protótipo e não representa runtime HML.
- O backend deve substituir a fixture por dados persistidos de campanhas, tarefas, gates e eventos.

## Rota de leitura preparada

- GET /cockpit/escritorio-agentes/estado exige sessão de administrador e ambiente homologation.
- A rota agrega agentes configurados, tarefas de campanha, gate VIA e eventos de auditoria.
- O HTML usa a fixture em localhost e essa rota quando executado em HML/produção controlada.
