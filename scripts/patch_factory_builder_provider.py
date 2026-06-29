from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

import_line = "use App\\Factory\\Console\\Commands\\FactoryMakeModuleCommand;\n"
if import_line not in text:
    marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
    text = text.replace(marker, marker + import_line)

if "FactoryMakeModuleCommand::class" not in text:
    text = text.replace(
        "FactorySyncCommand::class",
        "FactorySyncCommand::class, FactoryMakeModuleCommand::class"
    )

path.write_text(text)
print("FactoryMakeModuleCommand registrado no FactoryServiceProvider.")
