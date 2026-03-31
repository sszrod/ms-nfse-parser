<?php

declare(strict_types=1);

namespace MsNfseParser\Infrastructure\Repository;

use DateTimeImmutable;
use MsNfseParser\Application\Port\NfseTemplateRepositoryPort;
use MongoDB\Client;

final class MongoNfseTemplateRepository implements NfseTemplateRepositoryPort
{
    public function __construct(
        private readonly Client $client,
        private readonly string $databaseName,
        private readonly string $collectionName,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByMunicipioCodigo(string $municipioCodigo): array
    {
        $cursor = $this->collection()->find([
            'municipio_codigo' => $municipioCodigo,
            'active' => ['$ne' => false],
        ], [
            'sort' => ['updated_at' => -1],
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $templates = [];

        foreach ($cursor as $document) {
            $template = (array) $document;

            if (isset($template['_id'])) {
                $template['_id'] = (string) $template['_id'];
            }

            $templates[] = $template;
        }

        return $templates;
    }

    /**
     * @param array<string, mixed> $template
     */
    public function save(array $template): void
    {
        $municipioCodigo = (string) ($template['municipio_codigo'] ?? '');
        $fieldXpaths = $template['field_xpaths'] ?? [];

        if ($municipioCodigo === '' || !is_array($fieldXpaths) || $fieldXpaths === []) {
            return;
        }

        $payload = [
            'municipio_codigo' => $municipioCodigo,
            'name' => (string) ($template['name'] ?? 'template-sem-nome'),
            'field_xpaths' => $fieldXpaths,
            'validator_xpaths' => is_array($template['validator_xpaths'] ?? null) ? $template['validator_xpaths'] : [],
            'active' => (bool) ($template['active'] ?? true),
            'origin' => (string) ($template['origin'] ?? 'manual'),
            'updated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ];

        $templateHash = hash('sha256', json_encode([
            'municipio_codigo' => $payload['municipio_codigo'],
            'field_xpaths' => $payload['field_xpaths'],
            'validator_xpaths' => $payload['validator_xpaths'],
        ], JSON_THROW_ON_ERROR));

        $payload['template_hash'] = $templateHash;

        $this->collection()->updateOne(
            [
                'municipio_codigo' => $municipioCodigo,
                'template_hash' => $templateHash,
            ],
            [
                '$set' => $payload,
                '$setOnInsert' => [
                    'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
                ],
            ],
            [
                'upsert' => true,
            ],
        );
    }

    private function collection(): \MongoDB\Collection
    {
        return $this->client->selectCollection($this->databaseName, $this->collectionName);
    }
}
