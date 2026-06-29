<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Agendamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AgendamentoRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Agendamento::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Agendamento::query()->latest()->get();
    }

    public function find(int|string $id): ?Agendamento
    {
        return Agendamento::query()->find($id);
    }

    public function create(array $data): Agendamento
    {
        return Agendamento::query()->create($data);
    }

    public function update(Agendamento $record, array $data): Agendamento
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Agendamento $record): bool
    {
        return (bool) $record->delete();
    }
}
