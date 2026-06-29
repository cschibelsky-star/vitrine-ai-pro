# Instalação — Factory Core v1.0

Copie os arquivos, registre `App\Factory\Providers\FactoryServiceProvider::class` em `bootstrap/providers.php`, configure discovery do Filament e execute: `composer dump-autoload`, `php artisan optimize:clear`, `php artisan migrate`, `php artisan db:seed --class=FactoryCoreSeeder`, `php artisan factory:health`.
