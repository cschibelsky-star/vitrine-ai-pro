# Rollback — Factory Final Master

```bash
cd /home1/cris1649/vitrine-ai-pro

python3 - <<'PY'
from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()
text = text.replace("use App\\Factory\\Console\\Commands\\FactoryBuildAndInstallCommand;\n", "")
text = text.replace(", FactoryBuildAndInstallCommand::class", "")
text = text.replace("FactoryBuildAndInstallCommand::class, ", "")
text = text.replace("FactoryBuildAndInstallCommand::class", "")
path.write_text(text)
print("Factory Final Master removido do provider.")
PY

composer dump-autoload
php artisan optimize:clear
```
