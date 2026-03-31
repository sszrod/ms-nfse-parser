<?php

declare(strict_types=1);

namespace MsNfseParser\EntryPoint\Api\Controller;

use InvalidArgumentException;
use MsNfseParser\Application\UseCase\ExtractNfseUseCase;
use MsNfseParser\EntryPoint\Api\Resolver\ExtractNfseRequestResolver;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ExtractNfseController
{
    public function __construct(
        private readonly ExtractNfseUseCase $useCase,
        private readonly ExtractNfseRequestResolver $requestResolver,
    ) {
    }

    #[Route('/api/extract', name: 'api_nfse_extract', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = $this->requestResolver->resolve($request);
            $output = $this->useCase->execute($input->xml, $input->municipioCodigo);

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
}
