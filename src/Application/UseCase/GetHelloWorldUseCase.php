<?php

declare(strict_types=1);

namespace MsNfseParser\Application\UseCase;

use MsNfseParser\Application\Dto\HelloWorldOutput;
use MsNfseParser\Application\Port\GreetingProviderPort;
use MsNfseParser\Domain\Service\GreetingService;

final class GetHelloWorldUseCase
{
    public function __construct(
        private readonly GreetingProviderPort $greetingProvider,
        private readonly GreetingService $greetingService,
    ) {
    }

    public function execute(?string $name): HelloWorldOutput
    {
        $message = $this->greetingService->buildMessage(
            $this->greetingProvider->baseGreeting(),
            $name,
        );

        return new HelloWorldOutput($message);
    }
}
