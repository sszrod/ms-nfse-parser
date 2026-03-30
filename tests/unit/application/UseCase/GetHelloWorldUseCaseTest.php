<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Unit\Application\UseCase;

use MsNfseParser\Application\Port\GreetingProviderPort;
use MsNfseParser\Application\UseCase\GetHelloWorldUseCase;
use MsNfseParser\Domain\Service\GreetingService;
use PHPUnit\Framework\TestCase;

final class GetHelloWorldUseCaseTest extends TestCase
{
    public function testExecuteReturnsComposedGreeting(): void
    {
        $provider = new class() implements GreetingProviderPort {
            public function baseGreeting(): string
            {
                return 'Hello';
            }
        };

        $useCase = new GetHelloWorldUseCase($provider, new GreetingService());

        $output = $useCase->execute('Clean Architecture');

        self::assertSame('Hello, Clean Architecture!', $output->message);
    }
}
