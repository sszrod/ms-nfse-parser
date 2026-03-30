<?php

declare(strict_types=1);

namespace MsNfseParser\Domain\Service;

final class GreetingService
{
    public function buildMessage(string $baseGreeting, ?string $name): string
    {
        $sanitizedName = trim((string) $name);

        if ($sanitizedName === '') {
            return sprintf('%s, world!', $baseGreeting);
        }

        return sprintf('%s, %s!', $baseGreeting, $sanitizedName);
    }
}
