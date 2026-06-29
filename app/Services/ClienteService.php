<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ClienteService
{
    public function __construct(
        protected ClienteRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Cliente
    {
        return $this->repository->create($data);
    }

    public function update(Cliente $record, array $data): Cliente
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Cliente $record): bool
    {
        return $this->repository->delete($record);
    }
}
