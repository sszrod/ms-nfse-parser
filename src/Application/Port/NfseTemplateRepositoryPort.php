<?php

declare(strict_types=1);

namespace MsNfseParser\Application\Port;

interface NfseTemplateRepositoryPort
{
    /**
     * @return list<array<string, mixed>>
     */
    public function findByMunicipioCodigo(string $municipioCodigo): array;

    /**
     * @param array<string, mixed> $template
     */
    public function save(array $template): void;
}
