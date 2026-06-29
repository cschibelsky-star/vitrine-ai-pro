from pathlib import Path

required = [
    Path("app/Filament/Pages/EnterpriseDashboard.php"),
    Path("app/Filament/Pages/FactoryStudioEnterprise.php"),
    Path("app/Filament/Pages/GeneratedProjects.php"),
    Path("app/Filament/Pages/MarketplaceEnterprise.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

resources = [
    "ClienteResource.php",
    "AnimalResource.php",
    "AgendamentoResource.php",
    "ProntuarioResource.php",
    "VacinaResource.php",
    "FinanceiroResource.php",
]

base = Path("app/Filament/Resources")

for filename in resources:
    p = base / filename
    if not p.exists():
        continue
    text = p.read_text()
    if "public static function shouldRegisterNavigation()" not in text:
        text = text.replace(
            "public static function getPages(): array",
            "public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }\n\n    public static function getPages(): array"
        )
        p.write_text(text)
        print("Resource ocultado do menu:", filename)

print("Vitrine AI Pro Enterprise UI 1.0 aplicada.")
print("Valide no /admin: Centro Operacional, Factory Studio, Projetos e Marketplace.")
