<?php

declare(strict_types=1);

namespace MsNfseParser\Infrastructure\Provider;

use MsNfseParser\Application\Port\NfseExtractorPort;
use RuntimeException;

final class GeminiNfseExtractorProvider implements NfseExtractorPort
{
    private const DEFAULT_MODEL = 'gemini-2.0-flash';

    public function __construct(
        private readonly string $geminiApiKey,
        private readonly string $geminiModel = self::DEFAULT_MODEL,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function extractFromXml(string $xml): array
    {
        if (trim($this->geminiApiKey) === '') {
            throw new RuntimeException('GEMINI_API_KEY nao configurada.');
        }

        $model = trim($this->geminiModel) !== '' ? $this->geminiModel : self::DEFAULT_MODEL;
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->buildPrompt($xml),
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
            ],
        ];

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($model),
            rawurlencode($this->geminiApiKey),
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);

        set_error_handler(static function (int $severity, string $message): bool {
            throw new RuntimeException($message);
        });

        try {
            $rawResponse = file_get_contents($endpoint, false, $context);
            $responseHeaders = $http_response_header ?? [];
        } finally {
            restore_error_handler();
        }

        if (!is_string($rawResponse) || $rawResponse === '') {
            throw new RuntimeException('Gemini nao retornou conteudo.');
        }

        $statusCode = $this->extractStatusCode($responseHeaders);
        if ($statusCode >= 400) {
            throw new RuntimeException(sprintf('Erro Gemini HTTP %d: %s', $statusCode, $rawResponse));
        }

        $decodedResponse = json_decode($rawResponse, true);
        if (!is_array($decodedResponse)) {
            throw new RuntimeException('Resposta da API Gemini nao esta em JSON valido.');
        }

        $jsonContent = $this->extractJsonContent($decodedResponse);
        $decodedExtraction = json_decode($jsonContent, true);

        if (!is_array($decodedExtraction)) {
            throw new RuntimeException('Gemini retornou um payload nao estruturado em JSON.');
        }

        return $decodedExtraction;
    }

    private function buildPrompt(string $xml): string
    {
        return <<<PROMPT
Voce recebera um XML de NFS-e brasileiro, com modelos que variam por prefeitura.
Extraia os dados e devolva APENAS JSON valido, sem markdown e sem texto adicional.
Se uma informacao nao existir no XML, retorne null.

Estrutura obrigatoria:
{
  "nfse": {
    "numero": "string|null",
    "serie": "string|null",
    "codigo_verificacao": "string|null",
    "data_emissao": "string|null",
    "municipio": {
      "codigo_ibge": "string|null",
      "nome": "string|null",
      "uf": "string|null"
    },
    "prestador": {
      "razao_social": "string|null",
      "nome_fantasia": "string|null",
      "cnpj": "string|null",
      "cpf": "string|null",
      "inscricao_municipal": "string|null"
    },
    "tomador": {
      "razao_social": "string|null",
      "nome_fantasia": "string|null",
      "cnpj": "string|null",
      "cpf": "string|null",
      "email": "string|null"
    },
    "servico": {
      "descricao": "string|null",
      "codigo_servico": "string|null",
      "valor_servicos": "number|null",
      "valor_iss": "number|null",
      "aliquota_iss": "number|null"
    },
    "totais": {
      "valor_bruto": "number|null",
      "valor_deducoes": "number|null",
      "valor_liquido": "number|null"
    }
  },
  "metadados": {
    "modelo_nfse": "string|null",
    "confianca": "number|null"
  }
}

XML da NFS-e:
{$xml}
PROMPT;
    }

    /**
     * @param list<string> $headers
     */
    private function extractStatusCode(array $headers): int
    {
        if (!isset($headers[0])) {
            return 0;
        }

        if (preg_match('/\s(\d{3})\s/', $headers[0], $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractJsonContent(array $response): string
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini nao retornou texto com a extracao.');
        }

        $trimmed = trim($text);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        }

        return trim($trimmed);
    }
}
