<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Financeiro;
use App\Repositories\FinanceiroRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class FinanceiroService
{
    public function __construct(
        protected FinanceiroRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Financeiro
    {
        return $this->repository->create($data);
    }

    public function update(Financeiro $record, array $data): Financeiro
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Financeiro $record): bool
    {
        return $this->repository->delete($record);
    }
}
