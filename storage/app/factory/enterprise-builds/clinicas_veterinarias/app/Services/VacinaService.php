<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vacina;
use App\Repositories\VacinaRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class VacinaService
{
    public function __construct(
        protected VacinaRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Vacina
    {
        return $this->repository->create($data);
    }

    public function update(Vacina $record, array $data): Vacina
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Vacina $record): bool
    {
        return $this->repository->delete($record);
    }
}
