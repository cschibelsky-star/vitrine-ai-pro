<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

class TemplateRenderer
{
    public function render(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                $template = str_replace('{{ ' . $key . ' }}', (string) $value, $template);
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }

        return $template;
    }
}
