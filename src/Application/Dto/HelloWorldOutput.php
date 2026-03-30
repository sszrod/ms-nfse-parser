<?php

declare(strict_types=1);

namespace MsNfseParser\Application\Dto;

final class HelloWorldOutput
{
    public function __construct(
        public readonly string $message,
    ) {
    }
}
