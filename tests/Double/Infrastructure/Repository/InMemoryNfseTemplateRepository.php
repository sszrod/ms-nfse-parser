<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Double\Infrastructure\Repository;

use MsNfseParser\Application\Port\NfseTemplateRepositoryPort;

final class InMemoryNfseTemplateRepository implements NfseTemplateRepositoryPort
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $templates = [];

    /**
     * @return list<array<string, mixed>>
     */
    public function findByMunicipioCodigo(string $municipioCodigo): array
    {
        return array_values(array_filter(
            $this->templates,
            static fn (array $template): bool => (string) ($template['municipio_codigo'] ?? '') === $municipioCodigo,
        ));
    }

    /**
     * @param array<string, mixed> $template
     */
    public function save(array $template): void
    {
        $this->templates[] = $template;
    }
}
