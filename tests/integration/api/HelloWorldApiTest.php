<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HelloWorldApiTest extends WebTestCase
{
    public function testHelloEndpointReturnsExpectedPayload(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello?name=Symfony');

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Hello, Symfony!', $payload['message']);
        self::assertSame(
            'MsNfseParser\\Domain\\Service\\GreetingService',
            $payload['architecture']['domain'],
        );
    }
}
