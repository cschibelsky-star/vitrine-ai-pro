from pathlib import Path

required = [
    Path("app/Filament/Pages/EnterpriseDashboard.php"),
    Path("resources/views/filament/pages/enterprise-dashboard.blade.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

print("Centro Operacional UI V4 aplicado.")
print("Valide em /admin -> Centro Operacional -> Dashboard Executivo.")
