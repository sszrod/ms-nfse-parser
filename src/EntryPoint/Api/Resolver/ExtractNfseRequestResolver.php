<?php

declare(strict_types=1);

namespace MsNfseParser\EntryPoint\Api\Resolver;

use InvalidArgumentException;
use MsNfseParser\EntryPoint\Api\Dto\ExtractNfseRequestData;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class ExtractNfseRequestResolver
{
    public function resolve(Request $request): ExtractNfseRequestData
    {
        return new ExtractNfseRequestData(
            xml: $this->resolveXml($request),
            municipioCodigo: $this->resolveMunicipioCodigo($request),
        );
    }

    private function resolveXml(Request $request): string
    {
        $uploadedFile = $request->files->get('file');

        if ($uploadedFile instanceof UploadedFile) {
            if (!$uploadedFile->isValid()) {
                throw new InvalidArgumentException('Arquivo XML invalido no upload.');
            }

            $content = file_get_contents($uploadedFile->getPathname());
            if (!is_string($content) || trim($content) === '') {
                throw new InvalidArgumentException('Nao foi possivel ler o conteudo do arquivo XML.');
            }

            $this->assertValidXml($content);

            return $content;
        }

        $rawXml = trim($request->getContent());
        if ($rawXml === '') {
            throw new InvalidArgumentException('Envie um arquivo XML em file ou o XML no corpo da requisicao.');
        }

        $this->assertValidXml($rawXml);

        return $rawXml;
    }

    private function resolveMunicipioCodigo(Request $request): string
    {
        $municipioCodigo = trim((string) (
            $request->request->get('codigo_municipio')
            ?? $request->query->get('codigo_municipio')
            ?? $request->headers->get('X-Codigo-Municipio')
            ?? ''
        ));

        if ($municipioCodigo === '') {
            throw new InvalidArgumentException('Informe o codigo_municipio na requisicao.');
        }

        return $municipioCodigo;
    }

    private function assertValidXml(string $xml): void
    {
        $previousState = libxml_use_internal_errors(true);

        try {
            $isValid = simplexml_load_string($xml) !== false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        if (!$isValid) {
            throw new InvalidArgumentException('O conteudo enviado nao e um XML valido.');
        }
    }
}
