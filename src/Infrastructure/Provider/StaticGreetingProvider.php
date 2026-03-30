<?php

declare(strict_types=1);

namespace MsNfseParser\Infrastructure\Provider;

use MsNfseParser\Application\Port\GreetingProviderPort;

final class StaticGreetingProvider implements GreetingProviderPort
{
    public function baseGreeting(): string
    {
        return 'Hello';
    }
}
