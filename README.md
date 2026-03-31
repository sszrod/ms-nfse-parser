# ms-nfse-parser

POC em Symfony para validar a extração e normalização de NFS-e com apoio de IA em um cenário brasileiro ainda fragmentado por layouts municipais e provedores distintos.

O objetivo central é reduzir a dependência de parsers rígidos e de manutenção manual sempre que um novo layout de XML surge ou quando um emissor altera sua estrutura.

Arquiteturalmente, o projeto segue uma organização inspirada em DDD + Arquitetura Hexagonal, mantendo o fluxo de negócio separado dos adaptadores HTTP, do provider de IA e de detalhes do framework.

## Requisitos

- Docker e Docker Compose
- Opcional para execução local sem Docker:
  - PHP 8.2+
  - Composer 2+

## Subindo o projeto com Docker

1. Construir a imagem

```bash
docker compose build
```

2. Instalar dependências PHP

```bash
docker compose run --rm api composer install
```

3. Subir a aplicação

```bash
docker compose up
```

4. Testar o endpoint de extração de NFS-e

```bash
curl -X POST "http://localhost:8000/api/extract" -F "file=@/caminho/para/nfse.xml"
```

Resposta esperada (resumo):

```json
{
  "nfse": {
    "numero": "1001",
    "municipio": {
      "nome": "São Paulo"
    }
  },
  "metadados": {
    "fonte_extracao": "gemini"
  }
}
```

5. Opcionalmente, validar o endpoint base de scaffold

```bash
curl "http://localhost:8000/api/hello?name=Symfony"
```

## Executando sem Docker

1. Instalar dependências

```bash
composer install
```

2. Subir servidor local

```bash
php -S 0.0.0.0:8000 -t public
```

3. Testar endpoint de extração

```bash
curl -X POST "http://localhost:8000/api/extract" -F "file=@/caminho/para/nfse.xml"
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

- `src/Domain`: serviços de domínio e regras de normalização do núcleo da POC
- `src/Application`: casos de uso, DTOs e portas de entrada para dependências externas
- `src/Infrastructure`: adaptadores concretos, como o provider integrado ao Gemini
- `src/EntryPoint`: adaptadores de entrada HTTP expostos pela API

## Endpoints atuais

- `POST /api/extract`: recebe XML de NFS-e e retorna payload padronizado
- `GET /api/hello`: endpoint de scaffold mantido como base simples de exemplo e validação inicial da estrutura

## Documentação

- Arquitetura e decisões de desenho em `docs/arquitetura.md`
- Fluxo de extração com Gemini em `docs/extracao-nfse-gemini.md`
- Descritivo da POC e contexto de negócio em `docs/apresentacao-poc.md`
