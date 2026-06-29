<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Repositories\AgendamentoRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AgendamentoService
{
    public function __construct(
        protected AgendamentoRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Agendamento
    {
        return $this->repository->create($data);
    }

    public function update(Agendamento $record, array $data): Agendamento
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Agendamento $record): bool
    {
        return $this->repository->delete($record);
    }
}
