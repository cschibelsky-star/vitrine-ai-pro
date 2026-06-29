from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

import_line = "use App\\Factory\\Console\\Commands\\FactoryInstallModuleCommand;\n"
if import_line not in text:
    marker = "use App\\Factory\\Console\\Commands\\FactoryMakeModuleCommand;\n"
    if marker in text:
        text = text.replace(marker, marker + import_line)
    else:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        text = text.replace(marker, marker + import_line)

if "FactoryInstallModuleCommand::class" not in text:
    if "FactoryMakeModuleCommand::class" in text:
        text = text.replace(
            "FactoryMakeModuleCommand::class",
            "FactoryMakeModuleCommand::class, FactoryInstallModuleCommand::class"
        )
    else:
        text = text.replace(
            "FactorySyncCommand::class",
            "FactorySyncCommand::class, FactoryInstallModuleCommand::class"
        )

path.write_text(text)
print("FactoryInstallModuleCommand registrado no FactoryServiceProvider.")
