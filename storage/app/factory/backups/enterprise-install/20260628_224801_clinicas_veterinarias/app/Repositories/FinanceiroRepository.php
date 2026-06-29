<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Financeiro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FinanceiroRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Financeiro::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Financeiro::query()->latest()->get();
    }

    public function find(int|string $id): ?Financeiro
    {
        return Financeiro::query()->find($id);
    }

    public function create(array $data): Financeiro
    {
        return Financeiro::query()->create($data);
    }

    public function update(Financeiro $record, array $data): Financeiro
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Financeiro $record): bool
    {
        return (bool) $record->delete();
    }
}
