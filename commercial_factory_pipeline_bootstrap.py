from pathlib import Path

required = [
    Path("config/commercial_factory.php"),
    Path("app/CommercialFactory/Services/CommercialProductResolver.php"),
    Path("app/CommercialFactory/Services/CommercialFactoryIntakeService.php"),
    Path("app/CommercialFactory/Services/CommercialFactoryStatusService.php"),
    Path("app/Console/Commands/CommercialFactoryIntakeCommand.php"),
    Path("app/Console/Commands/CommercialFactoryStatusCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

print("Commercial to Factory Pipeline 1.0 aplicado.")
print("Rode: composer dump-autoload && php artisan optimize:clear")
print("Comandos: commercial:factory-intake, commercial:factory-status")
