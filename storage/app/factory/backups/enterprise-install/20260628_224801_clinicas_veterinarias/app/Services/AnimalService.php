<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Animal;
use App\Repositories\AnimalRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AnimalService
{
    public function __construct(
        protected AnimalRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Animal
    {
        return $this->repository->create($data);
    }

    public function update(Animal $record, array $data): Animal
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Animal $record): bool
    {
        return $this->repository->delete($record);
    }
}
