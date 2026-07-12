<?php

namespace App\Services;

use App\Models\FlowStorageObject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FlowStorageManagerService
{
    public function put(array $data): FlowStorageObject
    {
        $disk = $data['disk'] ?? 'local';
        $path = ltrim($data['path'], '/');
        $contents = base64_decode($data['content_base64'], true);

        if ($contents === false) {
            throw new RuntimeException('Conteúdo base64 inválido.');
        }

        Storage::disk($disk)->put($path, $contents, [
            'visibility' => $data['visibility'] ?? 'private',
        ]);

        return FlowStorageObject::updateOrCreate(
            ['disk' => $disk, 'path' => $path],
            [
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'company_id' => $data['company_id'] ?? null,
                'workflow_uuid' => $data['workflow_uuid'] ?? null,
                'execution_id' => $data['execution_id'] ?? null,
                'visibility' => $data['visibility'] ?? 'private',
                'mime_type' => $data['mime_type'] ?? null,
                'size_bytes' => strlen($contents),
                'checksum' => hash('sha256', $contents),
                'status' => 'available',
                'metadata' => $data['metadata'] ?? null,
                'deleted_at' => null,
            ],
        );
    }

    public function metadata(string $uuid): FlowStorageObject
    {
        return FlowStorageObject::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function delete(string $uuid): void
    {
        $object = $this->metadata($uuid);
        Storage::disk($object->disk)->delete($object->path);
        $object->update(['status' => 'deleted']);
        $object->delete();
    }
}
