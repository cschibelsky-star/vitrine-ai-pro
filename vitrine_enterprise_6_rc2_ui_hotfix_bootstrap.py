from pathlib import Path

required = [
    Path("app/Filament/Pages/EnterpriseDashboard.php"),
    Path("resources/views/filament/pages/enterprise-dashboard.blade.php"),
    Path("app/Filament/Pages/FactoryStudioEnterprise.php"),
    Path("app/Filament/Pages/GeneratedProjects.php"),
    Path("app/Filament/Pages/MarketplaceEnterprise.php"),
    Path("app/Filament/Pages/ClientPortalEnterprise.php"),
    Path("app/Filament/Pages/AiCenterEnterprise.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

# Oculta resources gerados pela Factory.
for filename in [
    "ClienteResource.php",
    "AnimalResource.php",
    "AgendamentoResource.php",
    "ProntuarioResource.php",
    "VacinaResource.php",
    "FinanceiroResource.php",
]:
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

print("Vitrine Enterprise 6.0 RC2 UI Hotfix aplicado.")
print("Valide: /admin > 01 · Centro Operacional > Cockpit Executivo")
