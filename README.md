# ms-nfse-parser

Projeto base em Symfony para evolucao do parser de NFS-e, organizado por camadas com foco em separacao de responsabilidades.

## Requisitos

- Docker e Docker Compose
- Opcional para execucao local sem Docker:
  - PHP 8.2+
  - Composer 2+

## Subindo o projeto com Docker (recomendado)

1. Construir a imagem

```bash
docker compose build
```

2. Instalar dependencias PHP

```bash
docker compose run --rm api composer install
```

3. Subir a aplicacao

```bash
docker compose up
```

4. Testar o endpoint inicial

```bash
curl "http://localhost:8000/api/hello?name=Symfony"
```

5. Testar o endpoint de extracao de NFS-e

```bash
curl -X POST "http://localhost:8000/api/extract" -F "file=@/caminho/para/nfse.xml"
```

Resposta esperada (resumo):

```json
{
  "message": "Hello, Symfony!",
  "architecture": {
    "domain": "MsNfseParser\\Domain\\Service\\GreetingService"
  }
}
```

## Executando sem Docker

1. Instalar dependencias

```bash
composer install
```

2. Subir servidor local

```bash
php -S 0.0.0.0:8000 -t public
```

3. Testar endpoint

```bash
curl "http://localhost:8000/api/hello?name=Symfony"
```

## Rodando testes

Com Docker:

```bash
docker compose run --rm api composer test
```

Sem Docker:

```bash
composer test
```

## Estrutura principal

- src/Domain: regras de negocio
- src/Application: casos de uso, DTOs e portas
- src/Infrastructure: implementacoes concretas
- src/EntryPoint: controllers HTTP

Mais detalhes em docs/arquitetura.md.

Documentacao da extracao em docs/extracao-nfse-gemini.md.
