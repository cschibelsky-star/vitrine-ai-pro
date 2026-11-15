# HUB-IA-001 — Factory/VIA supervision gate (prototype)

**Escopo**: prototipo offline restrito a TOOL-CODE-02. Nao conecta a VIA, Factory, Codex, CI/CD ou ambientes HML/PRD. Nao executa shell, nao implanta e nao cobra API.

Execute `python -m unittest -v test_gate.py` e `python gate.py report.json`. Saida em JSON; codigo 0 so quando os campos fornecidos satisfazem o contrato, 1 quando ha bloqueios.

## Contrato de entrada
```json
{
  "schema_version": "1.0", "mission_id": "TOOL-CODE-02",
  "environment": "isolated", "source": "codex-windows",
  "evidence_verified": false, "supervisor_approved": false,
  "tests": {"total": 5, "passed": 5, "failed": 0},
  "changed_files": ["order_service.py"], "original_data_preserved": true,
  "diff_sha256": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
}
```

## Restricao de seguranca (P0)
O programa **somente avalia declaracoes JSON**. Qualquer agente pode forjar `evidence_verified=true` e `supervisor_approved=true`. Portanto **nunca utilizar can_advance como autorizacao real**. Uma futura integracao precisa verificar origem, logs de execucao, hash de diff real, arquivos alterados, testes e uma decisao autenticada de uma VIA independente, em controle de acesso server-side. Sem isso, gate operacional fica PENDENTE.

## Aceite futuro
1. Adaptor autenticado Codex -> coleta independente de evidencias e logs.
2. Factory executa testes em sandbox por commit SHA e entrega relatorio assinado.
3. VIA valida policy, testes, diff e escopo, retorna decisao autenticada.
4. Enforcement no workflow bloqueia avanço com `pending_external_verification` ou `rejected`, comprovado por teste negativo E2E.

Nenhum deploy, merge ou mudanca em ferramentas reais foi autorizado nesta entrega.
