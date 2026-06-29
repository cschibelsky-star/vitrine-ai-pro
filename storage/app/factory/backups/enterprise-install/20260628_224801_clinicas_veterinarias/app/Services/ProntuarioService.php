<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prontuario;
use App\Repositories\ProntuarioRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ProntuarioService
{
    public function __construct(
        protected ProntuarioRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Prontuario
    {
        return $this->repository->create($data);
    }

    public function update(Prontuario $record, array $data): Prontuario
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Prontuario $record): bool
    {
        return $this->repository->delete($record);
    }
}
