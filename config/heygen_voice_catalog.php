<?php

declare(strict_types=1);

/**
 * Catálogo editorial de vozes HeyGen para o Marketing IA.
 *
 * Governança: seleção aprovada não autoriza publicação, geração automática,
 * consumo de créditos ou troca de voz na TV Sumaré.
 * Os IDs de voz são referências públicas do catálogo HeyGen, não credenciais.
 */
return [
    'schema_version' => '1.0.0',
    'provider' => 'heygen',
    'language' => 'pt-BR',
    'tts_language' => 'pt',
    'tts_engine' => 'orca',
    'default_voice_key' => 'adriano',
    'approved' => [
        'adriano' => [
            'voice_id' => 'c8ac31e97555494fb8502599e6bc5461',
            'name' => 'Adriano',
            'gender' => 'male',
            'status' => 'approved',
            'use' => 'institutional_commercial',
        ],
        'yuri' => [
            'voice_id' => '14be84ff0ad848d79bd867a79a870f4a',
            'name' => 'Yuri',
            'gender' => 'male',
            'status' => 'approved',
            'use' => 'alternative_narration',
        ],
        'aida' => [
            'voice_id' => '2799eccbd72d48e0af3202a147d51146',
            'name' => 'Aida',
            'gender' => 'female',
            'status' => 'approved',
            'use' => 'female_institutional_narration',
        ],
    ],
    // As vozes abaixo existem no catálogo, mas NÃO estão aprovadas.
    'candidates' => [
        'sofia_brazil' => [
            'voice_id' => '94ec497104a04c87904a8aa138d6e46c',
            'name' => 'Sofia Brazil',
            'gender' => 'female',
            'status' => 'pending_user_selection',
        ],
        'ana_carvalho' => [
            'voice_id' => '0d23c5b2f6004e909802a2e8bfcd52c2',
            'name' => 'Ana Carvalho',
            'gender' => 'female',
            'status' => 'pending_user_selection',
        ],

    ],
    // O nome permanece sem acento nos elementos de marca.
    'brand_name_written' => 'Vitrine IA Pro',
    'brand_name_spoken_ptbr' => 'Vitrine IA Pró',
    'pronunciation_hint' => 'Vitrine iá pró',
    'requires_voice_selection' => true,
    'fallback_to_unapproved_voice' => false,
    'auto_publish' => false,
];
