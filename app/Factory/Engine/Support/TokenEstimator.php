<?php

declare(strict_types=1);

namespace App\Factory\Engine\Support;

class TokenEstimator
{
    public function estimate(string $text): int
    {
        return max(1, (int) ceil(mb_strlen($text) / 4));
    }
}
