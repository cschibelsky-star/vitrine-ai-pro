<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HeygenVoiceCatalogTest extends TestCase
{
    public function test_only_selected_male_voices_are_approved(): void
    {
        $catalog = require __DIR__ . '/../../../config/heygen_voice_catalog.php';

        self::assertSame('pt-BR', $catalog['language']);
        self::assertSame('pt', $catalog['tts_language']);
        self::assertSame('orca', $catalog['tts_engine']);
        self::assertSame('adriano', $catalog['default_voice_key']);
        self::assertSame(['adriano', 'yuri'], array_keys($catalog['approved']));
        self::assertSame('c8ac31e97555494fb8502599e6bc5461', $catalog['approved']['adriano']['voice_id']);
        self::assertSame('14be84ff0ad848d79bd867a79a870f4a', $catalog['approved']['yuri']['voice_id']);

        foreach ($catalog['candidates'] as $candidate) {
            self::assertSame('female', $candidate['gender']);
            self::assertSame('pending_user_selection', $candidate['status']);
        }

        self::assertSame('Vitrine IA Pro', $catalog['brand_name_written']);
        self::assertSame('Vitrine IA Pró', $catalog['brand_name_spoken_ptbr']);
        self::assertFalse($catalog['auto_publish']);
        self::assertFalse($catalog['fallback_to_unapproved_voice']);
    }
}
