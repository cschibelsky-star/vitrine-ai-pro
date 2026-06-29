from pathlib import Path

required = [
    Path("config/commercial_factory.php"),
    Path("app/Filament/Pages/EnterpriseDashboard.php"),
    Path("app/Filament/Pages/FactoryStudioEnterprise.php"),
    Path("app/Filament/Pages/GeneratedProjects.php"),
    Path("app/Filament/Pages/MarketplaceEnterprise.php"),
    Path("app/CommercialFactory/Services/CommercialProductResolver.php"),
    Path("app/CommercialFactory/Services/CommercialFactoryIntakeService.php"),
    Path("app/CommercialFactory/Services/CommercialFactoryStatusService.php"),
    Path("app/Console/Commands/CommercialFactoryIntakeCommand.php"),
    Path("app/Console/Commands/CommercialFactoryStatusCommand.php"),
]
missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

for filename in ["ClienteResource.php","AnimalResource.php","AgendamentoResource.php","ProntuarioResource.php","VacinaResource.php","FinanceiroResource.php"]:
    p = Path("app/Filament/Resources") / filename
    if not p.exists():
        continue
    text = p.read_text()
    if "public static function shouldRegisterNavigation()" not in text:
        text = text.replace("public static function getPages(): array", "public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }\n\n    public static function getPages(): array")
        p.write_text(text)
        print("Resource ocultado:", filename)

print("Vitrine AI Pro Enterprise 6.0 RC1 aplicada.")
print("Valide /admin: Centro Operacional, Factory Studio, Projetos e Marketplace.")
