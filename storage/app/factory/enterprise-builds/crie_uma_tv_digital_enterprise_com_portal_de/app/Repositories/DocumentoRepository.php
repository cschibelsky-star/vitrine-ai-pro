<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentoRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Documento::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Documento::query()->latest()->get();
    }

    public function find(int|string $id): ?Documento
    {
        return Documento::query()->find($id);
    }

    public function create(array $data): Documento
    {
        return Documento::query()->create($data);
    }

    public function update(Documento $record, array $data): Documento
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Documento $record): bool
    {
        return (bool) $record->delete();
    }
}
