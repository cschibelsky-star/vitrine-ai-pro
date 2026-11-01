<?php
/**
 * Vitrine Indoor IA: transformação pura do catálogo privado da TV Sumaré.
 * Não faz requisições, lê arquivos, autentica dispositivos ou publica conteúdo.
 * O chamador valida a autorização do tenant e assina URLs de distribuição.
 */
declare(strict_types=1);

final class TvSumareIndoorAdapter
{
    private const TYPES = ['news', 'ad', 'video', 'podcast'];

    /** @param array<int,mixed> $records */
    public static function preview(array $records, string $tenantId): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $tenantId)) {
            throw new InvalidArgumentException('Tenant inválido');
        }
        $result = [];
        $seen = [];
        foreach ($records as $row) {
            if (!is_array($row) || ($row['source'] ?? null) !== 'tvsumare') continue;
            $id = $row['source_id'] ?? null;
            $type = $row['type'] ?? null;
            $title = $row['title'] ?? null;
            $rights = $row['rights'] ?? null;
            if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $id)) continue;
            if (!is_string($type) || !in_array($type, self::TYPES, true)) continue;
            if (!is_string($title) || trim($title) === '') continue;
            if (!in_array($row['destination'] ?? null, ['indoor_only', 'both'], true)) continue;
            if (($row['editorial_status'] ?? null) !== 'approved') continue;
            if (($row['indoor_consent'] ?? null) !== true || !is_array($rights)) continue;
            if (($rights['indoor_allowed'] ?? null) !== true) continue;
            $validUntil = $rights['valid_until'] ?? null;
            if ($validUntil !== null &&
                (!is_string($validUntil) || ($expiry = strtotime($validUntil)) === false || $expiry <= time())) continue;
            $mediaPath = $row['media_path'] ?? null;
            if (!is_string($mediaPath) || !preg_match(
                '~^uploads/(?:anuncios|videos|noticias|indoor)/[A-Za-z0-9_-]+[.](?:jpe?g|png|webp|mp4)$~iD',
                $mediaPath
            )) continue;
            $slot = $row['slot'] ?? ($type === 'ad' ? 'lateral' : 'central');
            if ($slot !== ($type === 'ad' ? 'lateral' : 'central')) continue;
            $key = $type . ':' . $id;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = [
                'source' => 'tvsumare',
                'tenant_id' => $tenantId,
                'source_id' => $id,
                'type' => $type,
                'title' => trim($title),
                'media_path' => $mediaPath,
                'slot' => $slot,
                'destination' => $row['destination'],
                'rights_valid_until' => $validUntil,
                'import_state' => 'pending_indoor_review'
            ];
        }
        return $result;
    }
}
