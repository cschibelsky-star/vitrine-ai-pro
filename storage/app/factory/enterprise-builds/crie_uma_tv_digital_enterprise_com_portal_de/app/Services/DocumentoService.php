<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use App\Repositories\DocumentoRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentoService
{
    public function __construct(
        protected DocumentoRepository $repository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): Documento
    {
        return $this->repository->create($data);
    }

    public function update(Documento $record, array $data): Documento
    {
        return $this->repository->update($record, $data);
    }

    public function delete(Documento $record): bool
    {
        return $this->repository->delete($record);
    }
}
