<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registro;
use App\Repositories\RegistroRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RegistroService
{
    public function __construct(
        protected RegistroRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Registro
    {
        return $this->repository->create($data);
    }

    public function update(Registro $record, array $data): Registro
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Registro $record): bool
    {
        return $this->repository->delete($record);
    }
}
