<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

use App\Factory\Builder\DTO\ModuleBlueprint;

class ModuleBuilder
{
    public function build(ModuleBlueprint $blueprint): string
    {
        $basePath = storage_path('app/factory/builds/' . $blueprint->slug);

        if (is_dir($basePath)) {
            $this->deleteDirectory($basePath);
        }

        mkdir($basePath, 0775, true);

        $timestamp = date('Y_m_d_His');

        $this->write($basePath . "/database/migrations/{$timestamp}_create_{$blueprint->tableName}_table.php", $this->migration($blueprint));
        $this->write($basePath . "/app/Models/{$blueprint->modelName}.php", $this->model($blueprint));
        $this->write($basePath . "/app/Policies/{$blueprint->modelName}Policy.php", $this->policy($blueprint));
        $this->write($basePath . "/database/seeders/{$blueprint->modelName}Seeder.php", $this->seeder($blueprint));
        $this->write($basePath . "/app/Filament/Resources/{$blueprint->modelName}Resource.php", $this->resource($blueprint));

        foreach ([
            $blueprint->listPageName => 'ListRecords',
            "Create{$blueprint->modelName}" => 'CreateRecord',
            "Edit{$blueprint->modelName}" => 'EditRecord',
            "View{$blueprint->modelName}" => 'ViewRecord',
        ] as $class => $base) {
            $this->write($basePath . "/app/Filament/Resources/{$blueprint->modelName}Resource/Pages/{$class}.php", $this->page($blueprint, $class, $base));
        }

        $this->write($basePath . '/module.json', json_encode([
            'name' => $blueprint->name,
            'slug' => $blueprint->slug,
            'model' => $blueprint->modelName,
            'table' => $blueprint->tableName,
            'list_page' => $blueprint->listPageName,
            'generated_by' => 'Factory BUILD 004.1',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

        return $basePath;
    }

    protected function write(string $path, string $content): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, $content);
    }

    protected function deleteDirectory(string $dir): void
    {
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    protected function migration(ModuleBlueprint $blueprint): string
    {
        $columns = collect($blueprint->fields)->map(fn ($field) => '            ' . $field->migrationColumn())->implode("\n");

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$blueprint->tableName}', function (Blueprint \$table): void {
            \$table->id();
{$columns}
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$blueprint->tableName}');
    }
};

PHP;
    }

    protected function model(ModuleBlueprint $blueprint): string
    {
        $fillable = collect($blueprint->fields)->map(fn ($field) => '        ' . $field->fillableLine())->implode("\n");

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$blueprint->modelName} extends Model
{
    use SoftDeletes;

    protected \$table = '{$blueprint->tableName}';

    protected \$fillable = [
{$fillable}
    ];
}

PHP;
    }

    protected function policy(ModuleBlueprint $blueprint): string
    {
        return <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\\{$blueprint->modelName};

class {$blueprint->modelName}Policy
{
    public function viewAny(User \$user): bool { return true; }
    public function view(User \$user, {$blueprint->modelName} \$record): bool { return true; }
    public function create(User \$user): bool { return true; }
    public function update(User \$user, {$blueprint->modelName} \$record): bool { return true; }
    public function delete(User \$user, {$blueprint->modelName} \$record): bool { return true; }
}

PHP;
    }

    protected function seeder(ModuleBlueprint $blueprint): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use App\Models\\{$blueprint->modelName};
use Illuminate\Database\Seeder;

class {$blueprint->modelName}Seeder extends Seeder
{
    public function run(): void
    {
        {$blueprint->modelName}::query()->firstOrCreate(['nome' => '{$blueprint->name} Demonstração'], [
            'documento' => '00000000000',
            'email' => 'demo@example.com',
            'telefone' => '',
            'cidade' => 'Sumaré',
            'status' => 'active',
        ]);
    }
}

PHP;
    }

    protected function resource(ModuleBlueprint $blueprint): string
    {
        $forms = collect($blueprint->fields)->map(fn ($field) => '                    ' . $field->formComponent())->implode("\n");
        $columns = collect($blueprint->fields)->map(fn ($field) => '                ' . $field->tableColumn())->implode("\n");

        return <<<PHP
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\\{$blueprint->modelName}Resource\Pages\Create{$blueprint->modelName};
use App\Filament\Resources\\{$blueprint->modelName}Resource\Pages\Edit{$blueprint->modelName};
use App\Filament\Resources\\{$blueprint->modelName}Resource\Pages\\{$blueprint->listPageName};
use App\Filament\Resources\\{$blueprint->modelName}Resource\Pages\View{$blueprint->modelName};
use App\Models\\{$blueprint->modelName};
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class {$blueprint->modelName}Resource extends Resource
{
    protected static ?string \$model = {$blueprint->modelName}::class;
    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string \$navigationGroup = 'Módulos Gerados';
    protected static ?string \$navigationLabel = '{$blueprint->name}';
    protected static ?string \$slug = '{$blueprint->slug}';

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Section::make('Dados principais')->schema([
{$forms}
            ]),
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
{$columns}
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => {$blueprint->listPageName}::route('/'),
            'create' => Create{$blueprint->modelName}::route('/create'),
            'view' => View{$blueprint->modelName}::route('/{record}'),
            'edit' => Edit{$blueprint->modelName}::route('/{record}/edit'),
        ];
    }
}

PHP;
    }

    protected function page(ModuleBlueprint $blueprint, string $className, string $baseClass): string
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$blueprint->modelName}Resource\Pages;

use App\Filament\Resources\\{$blueprint->modelName}Resource;
use Filament\Resources\Pages\\{$baseClass};

class {$className} extends {$baseClass}
{
    protected static string \$resource = {$blueprint->modelName}Resource::class;
}

PHP;
    }
}
