<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

class ViaStructuredMissionParser
{
    public const VERSION = 'structured_mission_parser_v1';

    private const ROLES = [
        'orchestrator',
        'project_manager',
        'architect',
        'qa',
        'auditor',
        'report',
    ];

    public function parse(string $content): array
    {
        $raw = trim($content);
        $json = $this->extractJson($raw);

        if ($json === null) {
            return $this->failed('json_not_found', $raw);
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->failed('json_decode_error:' . $e->getMessage(), $raw);
        }

        if (! is_array($decoded)) {
            return $this->failed('json_root_not_object', $raw);
        }

        $decisions = (array) ($decoded['decisions'] ?? []);
        $errors = [];

        foreach (self::ROLES as $role) {
            if (! isset($decisions[$role]) || ! is_array($decisions[$role])) {
                $errors[] = "missing_role:{$role}";
            }
        }

        if ($errors !== []) {
            return [
                'version' => self::VERSION,
                'valid' => false,
                'errors' => $errors,
                'decisions' => $decisions,
                'report' => (string) ($decoded['report'] ?? ''),
                'raw_content' => $raw,
            ];
        }

        return [
            'version' => self::VERSION,
            'valid' => true,
            'errors' => [],
            'decisions' => $decisions,
            'report' => (string) ($decoded['report'] ?? ''),
            'raw_content' => $raw,
        ];
    }

    private function extractJson(string $content): ?string
    {
        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, '{') && str_ends_with($content, '}')) {
            return $content;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/si', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($content, $start, $end - $start + 1);
    }

    private function failed(string $error, string $raw): array
    {
        return [
            'version' => self::VERSION,
            'valid' => false,
            'errors' => [$error],
            'decisions' => [],
            'report' => '',
            'raw_content' => $raw,
        ];
    }
}
