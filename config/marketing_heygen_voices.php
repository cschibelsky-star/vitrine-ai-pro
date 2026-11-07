<?php

declare(strict_types=1);

/**
 * Curadoria de vozes HeyGen aprovada para o Marketing IA.
 *
 * Catalogo de configuracao: nao publica, nao gera videos e nao substitui
 * configuracoes de clientes existentes. Integracao de runtime requer HML.
 *
 * No TTS, usar idioma "pt" explicitamente: depender da deteccao automatica
 * produziu uma segunda locucao com sotaque espanhol no piloto.
 * A grafia visual da marca continua sendo "Vitrine IA Pro".
 */
return [
    'provider' => 'heygen',
    'language' => 'pt-BR',
    'tts' => [
        'language' => 'pt',
        'engine' => 'orca',
        'speed' => 1.0,
    ],
    'default_voice' => 'adriano',
    'voices' => [
        'adriano' => [
            'voice_id' => 'c8ac31e97555494fb8502599e6bc5461',
            'name' => 'Adriano',
            'gender' => 'male',
            'language' => 'pt-BR',
            'status' => 'approved',
            'purpose' => 'institutional',
        ],
        'yuri' => [
            'voice_id' => '14be84ff0ad848d79bd867a79a870f4a',
            'name' => 'Yuri',
            'gender' => 'male',
            'language' => 'pt-BR',
            'status' => 'approved',
            'purpose' => 'alternative',
        ],
        'aida' => [
            'voice_id' => '2799eccbd72d48e0af3202a147d51146',
            'name' => 'Aida',
            'gender' => 'female',
            'language' => 'pt-BR',
            'status' => 'approved',
            'purpose' => 'female_narration',
        ],
    ],
    // Previews de Ana Carvalho e Sofia Brazil rejeitadas pelo usuario
    // por soarem em ingles. Nunca seleciona-las automaticamente.
    'rejected_voice_ids' => [
        '0d23c5b2f6004e909802a2e8bfcd52c2',
        '94ec497104a04c87904a8aa138d6e46c',
    ],
    'brand' => [
        'display_name' => 'Vitrine IA Pro',
        'spoken_pronunciation' => 'Vitrine ia pró',
        'brand_glossary_id' => 'fc0368c380ca4ce58cfc3e4ba92c783e',
    ],
    'governance' => [
        'auto_publish' => false,
        'require_hml_before_runtime_activation' => true,
    ],
];
