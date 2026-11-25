<?php

declare(strict_types=1);

namespace App\Factory\Domain\Agents;

use InvalidArgumentException;

final class AgentDna
{
    public const PROVIDERS = ['roteia', 'invision', 'openai', 'gemini'];

    /** Normalize a draft only; never grant runtime permissions or approval. */
    public static function draft(array $input): array
    {
        $allowed = ['name', 'domain', 'company_id', 'department', 'objective',
            'instructions', 'provider', 'model', 'skills'];
        if (array_diff(array_keys($input), $allowed) !== []) {
            throw new InvalidArgumentException('Unsupported DNA fields.');
        }
        $dna = [];
        foreach (['name', 'department', 'objective', 'instructions', 'model'] as $field) {
            $value = $input[$field] ?? null;
            if (! is_string($value) || trim($value) === '' || strlen($value) > 20000) {
                throw new InvalidArgumentException('Invalid DNA field: '.$field);
            }
            $dna[$field] = trim($value);
        }
        $domain = $input['domain'] ?? null;
        $company = $input['company_id'] ?? null;
        if (! in_array($domain, ['DEV', 'ECO'], true)
            || ($domain === 'DEV' && $company !== null)
            || ($domain === 'ECO' && (! is_int($company) || $company < 1))) {
            throw new InvalidArgumentException('Invalid domain/company boundary.');
        }
        $provider = $input['provider'] ?? null;
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException('Provider not allowed in Agent Factory.');
        }
        $skills = $input['skills'] ?? [];
        if (! is_array($skills) || ! array_is_list($skills) || count($skills) > 50) {
            throw new InvalidArgumentException('Invalid skill references.');
        }
        foreach ($skills as $skill) {
            if (! is_string($skill) || ! preg_match('/^[a-zA-Z0-9_.-]{1,120}$/D', $skill)) {
                throw new InvalidArgumentException('Invalid skill reference.');
            }
        }
        return $dna + [
            'schema_version' => 1,
            'domain' => $domain,
            'company_id' => $company,
            'provider' => $provider,
            'requested_skills' => array_values(array_unique($skills)),
            'granted_tools' => [],
            'autonomy' => 'disabled',
            'budget_cents' => 0,
            'status' => 'draft',
            'via_approval' => null,
        ];
    }
}
