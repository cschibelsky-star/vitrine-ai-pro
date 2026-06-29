from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryDashboardModuleCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryWidgetsModuleCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryExecutiveDashboardCommand;\n",
]

for imp in imports:
    if imp not in text:
        text = text.replace(
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n",
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + imp
        )

for cmd in [
    "FactoryDashboardModuleCommand::class",
    "FactoryWidgetsModuleCommand::class",
    "FactoryExecutiveDashboardCommand::class",
]:
    if cmd not in text:
        text = text.replace(
            "FactorySyncCommand::class",
            f"FactorySyncCommand::class, {cmd}"
        )

path.write_text(text)
print("Factory BUILD 008 registrada no FactoryServiceProvider.")
