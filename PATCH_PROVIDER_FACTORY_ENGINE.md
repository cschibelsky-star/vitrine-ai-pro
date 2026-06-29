# Patch manual do FactoryServiceProvider

Este patch adiciona o command:

```php
use App\Factory\Console\Commands\FactoryEngineTestCommand;
```

E no método `registerCommands()`, dentro de `$this->commands([...])`, adicionar:

```php
FactoryEngineTestCommand::class,
```

O patch ZIP acompanha um script que tenta fazer isso automaticamente no arquivo `app/Factory/Providers/FactoryServiceProvider.php`.

Comando de teste após aplicar:

```bash
php artisan factory:engine-test
```
