#!/usr/bin/env python3
from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

use_line = "use App\\Factory\\Console\\Commands\\FactoryEngineTestCommand;\n"
if use_line not in text:
    marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
    text = text.replace(marker, marker + use_line)

cmd_line = "                FactoryEngineTestCommand::class,\n"
if cmd_line not in text:
    marker = "                FactorySyncCommand::class,\n"
    text = text.replace(marker, marker + cmd_line)

path.write_text(text)
print("FactoryServiceProvider atualizado com FactoryEngineTestCommand.")
