<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Unit\Domain\Service;

use MsNfseParser\Domain\Service\GreetingService;
use PHPUnit\Framework\TestCase;

final class GreetingServiceTest extends TestCase
{
    public function testBuildMessageReturnsWorldWhenNameIsEmpty(): void
    {
        $service = new GreetingService();

        $result = $service->buildMessage('Hello', '');

        self::assertSame('Hello, world!', $result);
    }

    public function testBuildMessageUsesProvidedName(): void
    {
        $service = new GreetingService();

        $result = $service->buildMessage('Hello', 'Symfony');

        self::assertSame('Hello, Symfony!', $result);
    }
}
