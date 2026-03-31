# ms-nfse-parser

POC em Symfony para validar a extração e normalização de NFS-e com apoio de IA em um cenário brasileiro ainda fragmentado por layouts municipais e provedores distintos.

O objetivo central é reduzir a dependência de parsers rígidos e de manutenção manual sempre que um novo layout de XML surge ou quando um emissor altera sua estrutura.

Agora o projeto inclui um motor híbrido por município para reduzir chamadas de IA:

- cada template guarda um mapa de campo -> XPath do XML
- os templates ficam no MongoDB e são buscados por `codigo_municipio`
- quando um template casa com o XML, a extração usa somente XPath (sem consumo de tokens)
- quando nenhum template casa, a IA extrai os dados e retorna também o mapa de XPaths por campo
- após fallback de IA, o template é salvo no MongoDB para reutilização nas próximas notas

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

Serviços esperados:

- API Symfony: `http://localhost:8000`
- MongoDB: `localhost:27017`

4. Testar o endpoint de extração de NFS-e

```bash
curl -X POST "http://localhost:8000/api/extract" \
  -F "codigo_municipio=3550308" \
  -F "file=@/caminho/para/nfse.xml"
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
    "fonte_extracao": "template"
  }
}
```

Se o template do município não existir ou não validar para o XML enviado, a API usa o Gemini e retorna `metadados.fonte_extracao = "gemini"`.

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
curl -X POST "http://localhost:8000/api/extract" \
  -F "codigo_municipio=3550308" \
  -F "file=@/caminho/para/nfse.xml"
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

## Variáveis de ambiente importantes

- `GEMINI_API_KEY`: chave da API Gemini.
- `GEMINI_MODEL`: modelo Gemini (quando vazio usa `gemini-2.0-flash`).
- `GEMINI_TIMEOUT_SECONDS`: timeout da chamada Gemini em segundos (`0` desabilita timeout local).
- `MONGODB_URI`: URI do MongoDB (ex.: `mongodb://mongodb:27017` dentro do Docker).
- `MONGODB_DATABASE`: database do projeto.
- `MONGODB_COLLECTION`: coleção de templates.

## Estrutura principal

- `src/Domain`: serviços de domínio e regras de normalização do núcleo da POC
- `src/Application`: casos de uso, DTOs e portas de entrada para dependências externas
- `src/Infrastructure`: adaptadores concretos, como o provider integrado ao Gemini
- `src/EntryPoint`: adaptadores de entrada HTTP expostos pela API

## Endpoints atuais

- `POST /api/extract`: recebe XML de NFS-e + `codigo_municipio`, tenta extrair via templates do MongoDB e faz fallback para IA quando necessário
- `GET /api/hello`: endpoint de scaffold mantido como base simples de exemplo e validação inicial da estrutura

### Contrato de entrada do `POST /api/extract`

- `codigo_municipio` obrigatório (form-data, query string ou header `X-Codigo-Municipio`)
- XML obrigatório:
  - upload em `file` (`multipart/form-data`) ou
  - conteúdo bruto no corpo (`application/xml`)

## Documentação

- Arquitetura e decisões de desenho em `docs/arquitetura.md`
- Fluxo de extração com Gemini em `docs/extracao-nfse-gemini.md`
- Descritivo da POC e contexto de negócio em `docs/apresentacao-poc.md`
