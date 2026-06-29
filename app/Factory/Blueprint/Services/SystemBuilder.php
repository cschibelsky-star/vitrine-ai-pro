<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\Services;

use App\Factory\Blueprint\DTO\BlueprintField;
use App\Factory\Blueprint\DTO\SystemBlueprint;
use App\Factory\Blueprint\DTO\SystemModuleBlueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemBuilder
{
    public function build(SystemBlueprint $system): array
    {
        $results = [];

        foreach ($system->modules as $module) {
            $results[] = $this->buildModule($system, $module);
        }

        return $results;
    }

    protected function buildModule(SystemBlueprint $system, SystemModuleBlueprint $module): array
    {
        $model = $this->modelName($module->slug);
        $listPage = $this->listPageName($module->slug);
        $base = storage_path('app/factory/builds/' . $module->slug);

        if (File::isDirectory($base)) {
            File::deleteDirectory($base);
        }

        File::makeDirectory($base, 0775, true);

        $timestamp = date('Y_m_d_His');

        $this->put($base . "/database/migrations/{$timestamp}_create_{$module->slug}_table.php", $this->migration($module));
        $this->put($base . "/app/Models/{$model}.php", $this->model($module, $model));
        $this->put($base . "/app/Policies/{$model}Policy.php", $this->policy($model));
        $this->put($base . "/database/seeders/{$model}Seeder.php", $this->seeder($module, $model));
        $this->put($base . "/app/Filament/Resources/{$model}Resource.php", $this->resource($module, $model, $listPage));
        $this->put($base . "/app/Filament/Resources/{$model}Resource/Pages/{$listPage}.php", $this->page($model, $listPage, 'ListRecords'));
        $this->put($base . "/app/Filament/Resources/{$model}Resource/Pages/Create{$model}.php", $this->page($model, "Create{$model}", 'CreateRecord'));
        $this->put($base . "/app/Filament/Resources/{$model}Resource/Pages/Edit{$model}.php", $this->page($model, "Edit{$model}", 'EditRecord'));
        $this->put($base . "/app/Filament/Resources/{$model}Resource/Pages/View{$model}.php", $this->page($model, "View{$model}", 'ViewRecord'));
        $this->put($base . "/module.json", json_encode([
            'system' => $system->slug,
            'module' => $module->slug,
            'model' => $model,
            'relationships' => $this->relationships($module),
            'dashboard_metrics' => $module->dashboardMetrics,
            'generated_by' => 'Factory BUILD 004.2 Blueprint Engine',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return [
            'module' => $module->slug,
            'model' => $model,
            'path' => $base,
        ];
    }

    protected function put(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    protected function modelName(string $slug): string
    {
        return match ($slug) {
            'fornecedores' => 'Fornecedor',
            'categorias' => 'Categoria',
            'contratos' => 'Contrato',
            'documentos' => 'Documento',
            'historicos' => 'Historico',
            default => Str::studly(Str::singular($slug)),
        };
    }

    protected function listPageName(string $slug): string
    {
        return match ($slug) {
            'fornecedores' => 'ListFornecedores',
            'categorias' => 'ListCategorias',
            'contratos' => 'ListContratos',
            'documentos' => 'ListDocumentos',
            'historicos' => 'ListHistoricos',
            default => 'List' . Str::studly(Str::plural($slug)),
        };
    }

    protected function label(string $field): string
    {
        return Str::of($field)->replace('_id', '')->replace('_', ' ')->headline()->toString();
    }

    protected function migration(SystemModuleBlueprint $module): string
    {
        $columns = collect($module->fields)->map(function (BlueprintField $field): string {
            if ($field->type === 'foreignId') {
                $table = Str::snake(Str::pluralStudly((string) $field->relatedModel));
                return "            \$table->foreignId('{$field->name}')->constrained('{$table}')->cascadeOnDelete();";
            }

            return match ($field->type) {
                'text' => "            \$table->text('{$field->name}')" . ($field->nullable ? '->nullable()' : '') . ";",
                'date' => "            \$table->date('{$field->name}')" . ($field->nullable ? '->nullable()' : '') . ";",
                'decimal' => "            \$table->decimal('{$field->name}', 12, 2)->default(0);",
                default => "            \$table->string('{$field->name}')" . ($field->nullable ? '->nullable()' : '') . ";",
            };
        })->implode("\n");

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('{$module->slug}')) {
            return;
        }

        Schema::create('{$module->slug}', function (Blueprint \$table): void {
            \$table->id();
{$columns}
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$module->slug}');
    }
};

PHP;
    }

    protected function model(SystemModuleBlueprint $module, string $model): string
    {
        $fillable = collect($module->fields)->map(fn (BlueprintField $field) => "        '{$field->name}',")->implode("\n");

        $relations = collect($module->fields)
            ->filter(fn (BlueprintField $field) => $field->type === 'foreignId' && $field->relationship === 'belongsTo')
            ->map(function (BlueprintField $field): string {
                $method = Str::camel(str_replace('_id', '', $field->name));
                return <<<PHP

    public function {$method}()
    {
        return \$this->belongsTo(\\App\\Models\\{$field->relatedModel}::class);
    }
PHP;
            })->implode("\n");

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$model} extends Model
{
    use SoftDeletes;

    protected \$table = '{$module->slug}';

    protected \$fillable = [
{$fillable}
    ];
{$relations}
}

PHP;
    }

    protected function policy(string $model): string
    {
        return <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\\{$model};

class {$model}Policy
{
    public function viewAny(User \$user): bool { return true; }
    public function view(User \$user, {$model} \$record): bool { return true; }
    public function create(User \$user): bool { return true; }
    public function update(User \$user, {$model} \$record): bool { return true; }
    public function delete(User \$user, {$model} \$record): bool { return true; }
}

PHP;
    }

    protected function seeder(SystemModuleBlueprint $module, string $model): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use App\Models\\{$model};
use Illuminate\Database\Seeder;

class {$model}Seeder extends Seeder
{
    public function run(): void
    {
        // Seeder base gerado pela Factory.
    }
}

PHP;
    }

    protected function resource(SystemModuleBlueprint $module, string $model, string $listPage): string
    {
        $pageImports = <<<PHP
use App\Filament\Resources\\{$model}Resource\Pages\Create{$model};
use App\Filament\Resources\\{$model}Resource\Pages\Edit{$model};
use App\Filament\Resources\\{$model}Resource\Pages\\{$listPage};
use App\Filament\Resources\\{$model}Resource\Pages\View{$model};
PHP;

        $formFields = collect($module->fields)->map(function (BlueprintField $field): string {
            if ($field->type === 'foreignId') {
                $relationship = Str::camel(str_replace('_id', '', $field->name));
                return "                    Select::make('{$field->name}')->label('{$this->label($field->name)}')->relationship('{$relationship}', 'nome')->searchable()->preload(),";
            }

            return match ($field->type) {
                'text' => "                    Textarea::make('{$field->name}')->label('{$this->label($field->name)}')->columnSpanFull(),",
                'date' => "                    DatePicker::make('{$field->name}')->label('{$this->label($field->name)}'),",
                'decimal' => "                    TextInput::make('{$field->name}')->label('{$this->label($field->name)}')->numeric(),",
                default => "                    TextInput::make('{$field->name}')->label('{$this->label($field->name)}')->maxLength(255),",
            };
        })->implode("\n");

        $tableColumns = collect($module->fields)->map(function (BlueprintField $field): string {
            if ($field->type === 'foreignId') {
                $relationship = Str::camel(str_replace('_id', '', $field->name));
                return "                TextColumn::make('{$relationship}.nome')->label('{$this->label($field->name)}')->searchable()->sortable(),";
            }

            return "                TextColumn::make('{$field->name}')->label('{$this->label($field->name)}')->searchable()->sortable(),";
        })->implode("\n");

        return <<<PHP
<?php

namespace App\Filament\Resources;

{$pageImports}
use App\Models\\{$model};
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class {$model}Resource extends Resource
{
    protected static ?string \$model = {$model}::class;
    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string \$navigationGroup = 'Blueprint: {$module->label}';
    protected static ?string \$navigationLabel = '{$module->label}';
    protected static ?string \$slug = '{$module->slug}';

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Section::make('Dados principais')->schema([
{$formFields}
            ]),
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
{$tableColumns}
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
            'index' => {$listPage}::route('/'),
            'create' => Create{$model}::route('/create'),
            'view' => View{$model}::route('/{record}'),
            'edit' => Edit{$model}::route('/{record}/edit'),
        ];
    }
}

PHP;
    }

    protected function page(string $model, string $className, string $baseClass): string
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$model}Resource\Pages;

use App\Filament\Resources\\{$model}Resource;
use Filament\Resources\Pages\\{$baseClass};

class {$className} extends {$baseClass}
{
    protected static string \$resource = {$model}Resource::class;
}

PHP;
    }

    protected function relationships(SystemModuleBlueprint $module): array
    {
        return collect($module->fields)
            ->filter(fn (BlueprintField $field) => $field->relationship !== null)
            ->map(fn (BlueprintField $field) => [
                'field' => $field->name,
                'type' => $field->relationship,
                'related_model' => $field->relatedModel,
            ])
            ->values()
            ->all();
    }
}
