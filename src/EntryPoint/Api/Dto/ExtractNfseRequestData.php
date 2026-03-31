<?php

declare(strict_types=1);

namespace MsNfseParser\EntryPoint\Api\Dto;

final readonly class ExtractNfseRequestData
{
    public function __construct(
        public string $xml,
        public string $municipioCodigo,
    ) {
    }
}
