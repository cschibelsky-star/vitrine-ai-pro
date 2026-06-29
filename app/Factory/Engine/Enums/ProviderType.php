<?php

declare(strict_types=1);

namespace App\Factory\Engine\Enums;

enum ProviderType: string
{
    case Internal = 'internal';
    case OpenAI = 'openai';
    case Gemini = 'gemini';
    case Claude = 'claude';
}
