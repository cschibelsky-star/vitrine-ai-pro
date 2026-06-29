from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

import_line = "use App\\Factory\\Console\\Commands\\FactoryAiBlueprintCommand;\n"

if import_line not in text:
    text = text.replace(
        "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n",
        "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + import_line
    )

if "FactoryAiBlueprintCommand::class" not in text:
    text = text.replace(
        "FactorySyncCommand::class",
        "FactorySyncCommand::class, FactoryAiBlueprintCommand::class"
    )

path.write_text(text)
print("Factory BUILD 005 registrada no FactoryServiceProvider.")
