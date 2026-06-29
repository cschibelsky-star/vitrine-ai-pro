from pathlib import Path
from datetime import datetime

required = [
    Path("app/Filament/Pages/Dashboard.php"),
    Path("resources/views/filament/pages/dashboard.blade.php"),
    Path("app/Filament/Pages/EnterpriseDashboard.php"),
    Path("app/Filament/Pages/FactoryStudio.php"),
    Path("app/Filament/Pages/AiDashboard.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

# Garante que os resources gerados pela Factory não apareçam no menu principal.
resources = [
    "ClienteResource.php",
    "AnimalResource.php",
    "AgendamentoResource.php",
    "ProntuarioResource.php",
    "VacinaResource.php",
    "FinanceiroResource.php",
]

for filename in resources:
    p = Path("app/Filament/Resources") / filename

    if not p.exists():
        continue

    text = p.read_text()

    if "public static function shouldRegisterNavigation()" not in text:
        text = text.replace(
            "public static function getPages(): array",
            "public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }\n\n    public static function getPages(): array"
        )
        p.write_text(text)
        print("Resource ocultado:", filename)

print("Enterprise 6.0 RC2.1 Cleanup aplicado.")
print("O /admin agora usa App\\Filament\\Pages\\Dashboard com visual Enterprise.")
print("Menus antigos ocultados: EnterpriseDashboard, FactoryStudio antigo, AiDashboard antigo.")
