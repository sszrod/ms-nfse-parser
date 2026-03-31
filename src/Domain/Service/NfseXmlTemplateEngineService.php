<?php

declare(strict_types=1);

namespace MsNfseParser\Domain\Service;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class NfseXmlTemplateEngineService
{
    /**
     * @param list<array<string, mixed>> $templates
     *
     * @return array<string, mixed>|null
     */
    public function extractUsingTemplates(string $xml, array $templates): ?array
    {
        $xpath = $this->createXPath($xml);

        foreach ($templates as $template) {
            if (!$this->matchesTemplate($xpath, $template)) {
                continue;
            }

            $fieldXpaths = $template['field_xpaths'] ?? null;
            if (!is_array($fieldXpaths) || $fieldXpaths === []) {
                continue;
            }

            $extracted = $this->extractFieldValues($xpath, $fieldXpaths);
            if ($extracted === []) {
                continue;
            }

            $templateId = $template['_id'] ?? null;
            $templateId = is_scalar($templateId) ? (string) $templateId : null;

            $extracted['metadados'] = [
                'fonte_extracao' => 'template',
                'template_id' => $templateId,
                'confianca' => 1.0,
            ];

            return $extracted;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $normalizedData
     * @param array<string, mixed>|null $suggestedXpaths
     *
     * @return array<string, mixed>|null
     */
    public function buildTemplateDraft(string $municipioCodigo, string $xml, array $normalizedData, ?array $suggestedXpaths = null): ?array
    {
        $xpath = $this->createXPath($xml);
        $flatFields = $this->flattenScalarFields($normalizedData);
        if ($flatFields === []) {
            return null;
        }

        $fieldXpaths = [];

        if (is_array($suggestedXpaths)) {
            $flatSuggestedXpaths = $this->flattenXPathMap($suggestedXpaths);

            foreach ($flatFields as $field => $value) {
                $candidateXpath = $flatSuggestedXpaths[$field] ?? null;

                if (!is_string($candidateXpath) || trim($candidateXpath) === '') {
                    continue;
                }

                if (!$this->isResolvableXPath($xpath, $candidateXpath)) {
                    continue;
                }

                $fieldXpaths[$field] = $candidateXpath;
            }
        }

        $missingFields = array_diff_key($flatFields, $fieldXpaths);
        if ($missingFields !== []) {
            $valueToXpaths = $this->indexTextNodesByValue($xpath);

            foreach ($missingFields as $field => $value) {
                $normalizedValue = $this->normalizeNodeValue((string) $value);

                if ($normalizedValue === '' || !isset($valueToXpaths[$normalizedValue][0])) {
                    continue;
                }

                $fieldXpaths[$field] = $valueToXpaths[$normalizedValue][0];
            }
        }

        if ($fieldXpaths === []) {
            return null;
        }

        $validatorFields = [
            'nfse.numero',
            'nfse.codigo_verificacao',
            'nfse.data_emissao',
            'nfse.prestador.cnpj',
            'nfse.tomador.cnpj',
            'nfse.servico.valor_servicos',
        ];

        $validatorXpaths = [];
        foreach ($validatorFields as $field) {
            if (isset($fieldXpaths[$field])) {
                $validatorXpaths[] = $fieldXpaths[$field];
            }
        }

        if ($validatorXpaths === []) {
            $validatorXpaths = array_slice(array_values($fieldXpaths), 0, 3);
        }

        return [
            'municipio_codigo' => $municipioCodigo,
            'name' => sprintf('auto-%s-%s', $municipioCodigo, date('YmdHis')),
            'field_xpaths' => $fieldXpaths,
            'validator_xpaths' => array_values(array_unique($validatorXpaths)),
            'active' => true,
            'origin' => 'gemini_fallback',
        ];
    }

    /**
     * @param array<string, mixed> $xpaths
     *
     * @return array<string, string>
     */
    private function flattenXPathMap(array $xpaths, string $prefix = ''): array
    {
        $flat = [];

        foreach ($xpaths as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($key === 'metadados') {
                continue;
            }

            $path = $prefix === '' ? $key : sprintf('%s.%s', $prefix, $key);

            if (is_array($value)) {
                $flat += $this->flattenXPathMap($value, $path);
                continue;
            }

            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $flat[$path] = trim($value);
        }

        return $flat;
    }

    /**
     * @param array<string, mixed> $template
     */
    private function matchesTemplate(DOMXPath $xpath, array $template): bool
    {
        $validatorXpaths = $template['validator_xpaths'] ?? [];

        if (!is_array($validatorXpaths) || $validatorXpaths === []) {
            return true;
        }

        foreach ($validatorXpaths as $validatorXpath) {
            if (!is_string($validatorXpath) || trim($validatorXpath) === '') {
                return false;
            }

            if ($this->extractFirstValue($xpath, $validatorXpath) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $fieldXpaths
     *
     * @return array<string, mixed>
     */
    private function extractFieldValues(DOMXPath $xpath, array $fieldXpaths): array
    {
        $payload = [];

        foreach ($fieldXpaths as $field => $fieldXpath) {
            if (!is_string($field) || !is_string($fieldXpath) || trim($fieldXpath) === '') {
                continue;
            }

            $value = $this->extractFirstValue($xpath, $fieldXpath);
            if ($value === null) {
                continue;
            }

            $this->pathSet($payload, $field, $value);
        }

        return $payload;
    }

    private function extractFirstValue(DOMXPath $xpath, string $expression): ?string
    {
        $nodeList = @$xpath->query($expression);
        if ($nodeList === false || $nodeList->length === 0) {
            return null;
        }

        $value = $this->normalizeNodeValue($nodeList->item(0)?->nodeValue ?? '');

        return $value === '' ? null : $value;
    }

    private function isResolvableXPath(DOMXPath $xpath, string $expression): bool
    {
        $nodeList = @$xpath->query($expression);

        return $nodeList !== false && $nodeList->length > 0;
    }

    /**
     * @return array<string, list<string>>
     */
    private function indexTextNodesByValue(DOMXPath $xpath): array
    {
        $index = [];
        $nodes = $xpath->query('//*[normalize-space(text()) != ""]');

        if ($nodes === false) {
            return $index;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $value = $this->normalizeNodeValue($node->textContent);
            if ($value === '') {
                continue;
            }

            $path = $this->buildLocalNameXPath($node);
            $index[$value] ??= [];
            $index[$value][] = $path;
        }

        return $index;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function flattenScalarFields(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($key === 'metadados') {
                continue;
            }

            $path = $prefix === '' ? $key : sprintf('%s.%s', $prefix, $key);

            if (is_array($value)) {
                $flat += $this->flattenScalarFields($value, $path);
                continue;
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                $flat[$path] = (string) $value;
            }
        }

        return $flat;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function pathSet(array &$payload, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $cursor = &$payload;

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                return;
            }

            $isLast = $index === count($segments) - 1;

            if ($isLast) {
                $cursor[$segment] = $value;
                return;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }
    }

    private function normalizeNodeValue(string $value): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($value));

        return $collapsed ?? '';
    }

    private function createXPath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        $previousState = libxml_use_internal_errors(true);

        try {
            $dom->loadXML($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        return new DOMXPath($dom);
    }

    private function buildLocalNameXPath(DOMElement $element): string
    {
        $segments = [];
        $cursor = $element;

        while ($cursor instanceof DOMElement) {
            $segments[] = sprintf("*[local-name()='%s']", $cursor->localName ?: $cursor->tagName);

            $parent = $cursor->parentNode;
            if (!$parent instanceof DOMNode || !$parent instanceof DOMElement) {
                break;
            }

            $cursor = $parent;
        }

        $segments = array_reverse($segments);

        return '/' . implode('/', $segments);
    }
}
