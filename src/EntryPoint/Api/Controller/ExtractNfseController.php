<?php

declare(strict_types=1);

namespace MsNfseParser\EntryPoint\Api\Controller;

use InvalidArgumentException;
use MsNfseParser\Application\UseCase\ExtractNfseUseCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

final class ExtractNfseController
{
    #[Route('/api/extract', name: 'api_nfse_extract', methods: ['POST'])]
    public function __invoke(Request $request, ExtractNfseUseCase $useCase): JsonResponse
    {
        try {
            $xml = $this->resolveXml($request);
            $output = $useCase->execute($xml);

            return new JsonResponse($output->payload, Response::HTTP_OK);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (RuntimeException $exception) {
            return new JsonResponse([
                'error' => 'Nao foi possivel extrair os dados da NFS-e.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
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
