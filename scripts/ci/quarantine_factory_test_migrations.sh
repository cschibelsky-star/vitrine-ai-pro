#!/usr/bin/env bash
set -euo pipefail

# Clínica Veterinária foi um projeto de teste da Vitrine AI Pro Factory.
# Estas migrations foram instaladas repetidamente no diretório principal durante
# homologações da Factory e não pertencem ao Core. Em CI elas são retiradas do
# caminho ativo de migrations sem apagar o histórico Git nem dados de produção.

QUARANTINE_DIR="database/legacy/factory-tests/clinicas_veterinarias/migrations"
mkdir -p "$QUARANTINE_DIR"

patterns=(
  "database/migrations/2026_06_28_*_create_clientes_table.php"
  "database/migrations/2026_06_28_*_create_animais_table.php"
  "database/migrations/2026_06_28_*_create_agendamentos_table.php"
  "database/migrations/2026_06_28_*_create_prontuarios_table.php"
  "database/migrations/2026_06_28_*_create_vacinas_table.php"
  "database/migrations/2026_06_28_*_create_financeiro_table.php"
)

moved=0
for pattern in "${patterns[@]}"; do
  for file in $pattern; do
    if [ -f "$file" ]; then
      mv "$file" "$QUARANTINE_DIR/"
      moved=$((moved + 1))
    fi
  done
done

echo "Factory test migrations quarantined: $moved"
