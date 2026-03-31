<?php

declare(strict_types=1);

namespace MsNfseParser\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class NfseExtractApiTest extends WebTestCase
{
    public function testExtractEndpointReturnsStandardizedPayload(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<CompNfse>
  <Nfse>
    <InfNfse>
      <Numero>1001</Numero>
    </InfNfse>
  </Nfse>
</CompNfse>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'nfse_');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, $xml);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'nota.xml',
            'text/xml',
            null,
            true,
        );

        $client = static::createClient();
        $client->request('POST', '/api/extract', ['codigo_municipio' => '3550308'], ['file' => $uploadedFile]);

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('1001', $payload['nfse']['numero']);
        self::assertSame('Sao Paulo', $payload['nfse']['municipio']['nome']);
        self::assertEquals(1500.0, $payload['nfse']['totais']['valor_bruto']);
        self::assertSame('gemini', $payload['metadados']['fonte_extracao']);
    }

    public function testExtractEndpointReturnsBadRequestForInvalidXml(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/extract',
            server: ['CONTENT_TYPE' => 'application/xml'],
            content: '<invalid>',
        );

        self::assertResponseStatusCodeSame(400);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('O conteudo enviado nao e um XML valido.', $payload['error']);
    }
}
