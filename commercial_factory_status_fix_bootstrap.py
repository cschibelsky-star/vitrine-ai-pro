from pathlib import Path

required = [
    Path("app/Console/Commands/CommercialFactoryStatusCommand.php"),
    Path("app/CommercialFactory/Services/CommercialFactoryStatusService.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

print("Commercial Factory Status Fix 1.0 aplicado.")
print("Rode: composer dump-autoload && php artisan optimize:clear && php artisan commercial:factory-status")
