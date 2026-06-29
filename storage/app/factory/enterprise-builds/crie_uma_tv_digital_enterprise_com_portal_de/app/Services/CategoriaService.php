<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Repositories\CategoriaRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoriaService
{
    public function __construct(
        protected CategoriaRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Categoria
    {
        return $this->repository->create($data);
    }

    public function update(Categoria $record, array $data): Categoria
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Categoria $record): bool
    {
        return $this->repository->delete($record);
    }
}
