<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class GeneratedProjects extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Factory 2.0';
    protected static ?string $navigationLabel = 'Projetos';
    protected static ?string $title = 'Projetos Gerados';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.generated-projects';

    public function getProjects(): array
    {
        $items = [];
        $dir = storage_path('app/factory/blueprints');

        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $file) {
                $data = json_decode((string) File::get($file->getPathname()), true);
                if (! is_array($data)) continue;

                $slug = $data['slug'] ?? pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $items[] = [
                    'name' => $data['name'] ?? $slug,
                    'slug' => $slug,
                    'description' => $data['description'] ?? '',
                    'modules' => count($data['modules'] ?? []),
                    'status' => File::isDirectory(storage_path('app/factory/real-builds/' . $slug)) ? 'Build gerado' : 'Blueprint',
                ];
            }
        }

        return collect($items)->sortBy('name')->values()->all();
    }
}
