# Rollback — Factory Full Release 1.0

Se precisar remover o comando principal, rode:

```bash
cd /home1/cris1649/vitrine-ai-pro

python3 - <<'PY'
from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

text = text.replace("use App\\Factory\\Console\\Commands\\FactoryProduceEnterpriseCommand;\n", "")
text = text.replace(", FactoryProduceEnterpriseCommand::class", "")
text = text.replace("FactoryProduceEnterpriseCommand::class, ", "")
text = text.replace("FactoryProduceEnterpriseCommand::class", "")

path.write_text(text)
print("Rollback aplicado.")
PY

composer dump-autoload
php artisan optimize:clear
```
