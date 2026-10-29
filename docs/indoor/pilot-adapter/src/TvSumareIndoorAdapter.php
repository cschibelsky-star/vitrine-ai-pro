<?php
/**
 * Vitrine Indoor IA — adaptador de catálogo TV Sumaré v1 (HML).
 * Transformação pura de registros fornecidos pelo backend. Não lê arquivos,
 * não expõe endpoints e não autoriza a publicação.
 */
declare(strict_types=1);

final class TvSumareIndoorAdapter
{
    private const ALLOWED_TYPES = ['news', 'ad', 'video', 'podcast'];
    private const ALLOWED_SLOTS = ['central', 'lateral'];

    /** @param array<int,array<string,mixed>> $records */
    public static function preview(array $records, string $tenantId): array
    {
        if (trim($tenantId) === '') {
            throw new InvalidArgumentException('Tenant obrigatório');
        }
        $result = [];
        $seen = [];
        foreach ($records as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sourceId = trim((string) ($row['source_id'] ?? ''));
            $type = (string) ($row['type'] ?? '');
            $title = trim((string) ($row['title'] ?? ''));
            $status = (string) ($row['editorial_status'] ?? '');
            $consent = $row['indoor_consent'] ?? false;
            $rights = $row['rights'] ?? [];
            if ($sourceId === '' || $title === '' || !in_array($type, self::ALLOWED_TYPES, true)) {
                continue;
            }
            // Pendente, rejeitado e descartado nunca são importáveis.
            if ($status !== 'approved' || $consent !== true || !is_array($rights)) {
                continue;
            }
            if (($rights['indoor_allowed'] ?? false) !== true) {
                continue;
            }
            $validUntil = $rights['valid_until'] ?? null;
            if ($validUntil !== null) {
                $timestamp = is_string($validUntil) ? strtotime($validUntil) : false;
                if ($timestamp === false || $timestamp <= time()) {
                    continue;
                }
            }
            $mediaUrl = (string) ($row['media_url'] ?? '');
            $urlParts = parse_url($mediaUrl);
            if (!is_array($urlParts) || strtolower((string) ($urlParts['scheme'] ?? '')) !== 'https'
                || empty($urlParts['host'])) {
                continue;
            }
            $slot = (string) ($row['slot'] ?? 'central');
            if (!in_array($slot, self::ALLOWED_SLOTS, true)) {
                continue;
            }
            $key = $type . ':' . $sourceId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = [
                'source' => 'tvsumare',
                'tenant_id' => $tenantId,
                'source_id' => $sourceId,
                'type' => $type,
                'title' => $title,
                'media_url' => $mediaUrl,
                'slot' => $slot,
                'rights_valid_until' => $validUntil,
                'import_state' => 'pending_indoor_review',
            ];
        }
        return $result;
    }
}
