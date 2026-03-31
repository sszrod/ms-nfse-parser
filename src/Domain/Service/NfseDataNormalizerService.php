<?php

declare(strict_types=1);

namespace MsNfseParser\Domain\Service;

final class NfseDataNormalizerService
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        return [
            'nfse' => [
                'numero' => $this->asString($this->pick($data, ['nfse.numero', 'numero', 'invoice.number'])),
                'serie' => $this->asString($this->pick($data, ['nfse.serie', 'serie', 'invoice.series'])),
                'codigo_verificacao' => $this->asString($this->pick($data, ['nfse.codigo_verificacao', 'codigo_verificacao'])),
                'data_emissao' => $this->asString($this->pick($data, ['nfse.data_emissao', 'data_emissao', 'issue_date'])),
                'municipio' => [
                    'codigo_ibge' => $this->asString($this->pick($data, ['nfse.municipio.codigo_ibge', 'municipio.codigo_ibge'])),
                    'nome' => $this->asString($this->pick($data, ['nfse.municipio.nome', 'municipio.nome', 'cidade'])),
                    'uf' => $this->asString($this->pick($data, ['nfse.municipio.uf', 'municipio.uf', 'estado'])),
                ],
                'prestador' => [
                    'razao_social' => $this->asString($this->pick($data, ['nfse.prestador.razao_social', 'prestador.razao_social'])),
                    'nome_fantasia' => $this->asString($this->pick($data, ['nfse.prestador.nome_fantasia', 'prestador.nome_fantasia'])),
                    'cnpj' => $this->asString($this->pick($data, ['nfse.prestador.cnpj', 'prestador.cnpj'])),
                    'cpf' => $this->asString($this->pick($data, ['nfse.prestador.cpf', 'prestador.cpf'])),
                    'inscricao_municipal' => $this->asString($this->pick($data, ['nfse.prestador.inscricao_municipal', 'prestador.inscricao_municipal'])),
                ],
                'tomador' => [
                    'razao_social' => $this->asString($this->pick($data, ['nfse.tomador.razao_social', 'tomador.razao_social'])),
                    'nome_fantasia' => $this->asString($this->pick($data, ['nfse.tomador.nome_fantasia', 'tomador.nome_fantasia'])),
                    'cnpj' => $this->asString($this->pick($data, ['nfse.tomador.cnpj', 'tomador.cnpj'])),
                    'cpf' => $this->asString($this->pick($data, ['nfse.tomador.cpf', 'tomador.cpf'])),
                    'email' => $this->asString($this->pick($data, ['nfse.tomador.email', 'tomador.email'])),
                ],
                'servico' => [
                    'descricao' => $this->asString($this->pick($data, ['nfse.servico.descricao', 'servico.descricao'])),
                    'codigo_servico' => $this->asString($this->pick($data, ['nfse.servico.codigo_servico', 'servico.codigo_servico'])),
                    'valor_servicos' => $this->asFloat($this->pick($data, ['nfse.servico.valor_servicos', 'servico.valor_servicos'])),
                    'valor_iss' => $this->asFloat($this->pick($data, ['nfse.servico.valor_iss', 'servico.valor_iss'])),
                    'aliquota_iss' => $this->asFloat($this->pick($data, ['nfse.servico.aliquota_iss', 'servico.aliquota_iss'])),
                ],
                'totais' => [
                    'valor_bruto' => $this->asFloat($this->pick($data, ['nfse.totais.valor_bruto', 'totais.valor_bruto'])),
                    'valor_deducoes' => $this->asFloat($this->pick($data, ['nfse.totais.valor_deducoes', 'totais.valor_deducoes'])),
                    'valor_liquido' => $this->asFloat($this->pick($data, ['nfse.totais.valor_liquido', 'totais.valor_liquido'])),
                ],
            ],
            'metadados' => [
                'modelo_nfse' => $this->asString($this->pick($data, ['metadados.modelo_nfse', 'modelo_nfse', 'modelo'])),
                'fonte_extracao' => $this->asString($this->pick($data, ['metadados.fonte_extracao', 'fonte_extracao'])) ?? 'gemini',
                'confianca' => $this->asFloat($this->pick($data, ['metadados.confianca', 'confianca'])),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $paths
     */
    private function pick(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = $this->pathGet($data, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function pathGet(array $data, string $path): mixed
    {
        $cursor = $data;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    private function asString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function asFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d.,-]/', '', $value) ?? '';
        if ($normalized === '') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
