<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Prontuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProntuarioRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Prontuario::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Prontuario::query()->latest()->get();
    }

    public function find(int|string $id): ?Prontuario
    {
        return Prontuario::query()->find($id);
    }

    public function create(array $data): Prontuario
    {
        return Prontuario::query()->create($data);
    }

    public function update(Prontuario $record, array $data): Prontuario
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Prontuario $record): bool
    {
        return (bool) $record->delete();
    }
}
