<?php

declare(strict_types=1);

namespace MsNfseParser\EntryPoint\Api\Controller;

use MsNfseParser\Application\UseCase\GetHelloWorldUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HelloWorldController
{
    #[Route('/api/hello', name: 'api_hello_world', methods: ['GET'])]
    public function __invoke(Request $request, GetHelloWorldUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute($request->query->get('name'));

        return new JsonResponse([
            'message' => $output->message,
            'architecture' => [
                'domain' => 'MsNfseParser\\Domain\\Service\\GreetingService',
                'application' => 'MsNfseParser\\Application\\UseCase\\GetHelloWorldUseCase',
                'infrastructure' => 'MsNfseParser\\Infrastructure\\Provider\\StaticGreetingProvider',
                'entrypoint' => 'MsNfseParser\\EntryPoint\\Api\\Controller\\HelloWorldController',
            ],
        ]);
    }
}
