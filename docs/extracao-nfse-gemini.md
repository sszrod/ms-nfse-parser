# Extracao de NFS-e com Gemini

## Objetivo

Adicionar uma rota REST `POST /api/extract` para receber um XML de NFS-e e retornar um JSON padronizado com os dados da nota fiscal de servico.

A extracao e feita pela API REST do Gemini e a chave e lida da variavel de ambiente `GEMINI_API_KEY`.

## Fluxo em camadas

1. `EntryPoint` (`ExtractNfseController`) recebe o arquivo XML no campo `file` (multipart/form-data) ou no corpo da requisicao.
2. O controller valida se o XML e valido e chama `ExtractNfseUseCase`.
3. `Application` delega a extracao para a porta `NfseExtractorPort`.
4. `Infrastructure` usa `GeminiNfseExtractorProvider` para chamar a API do Gemini.
5. `Domain` normaliza o retorno em um contrato JSON unico (`NfseDataNormalizerService`).

## Configuracao de ambiente

No arquivo `.env`:

```dotenv
GEMINI_API_KEY=
GEMINI_MODEL=
```

- `GEMINI_API_KEY`: obrigatoria em execucao real.
- `GEMINI_MODEL`: opcional. Quando vazio, o provider usa `gemini-2.0-flash`.

## Contrato de entrada

### POST /api/extract

Aceita uma das formas:

1. `multipart/form-data` com arquivo no campo `file`.
2. `application/xml` com XML no corpo da requisicao.

## Contrato de saida (JSON padronizado)

```json
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
    "fonte_extracao": "gemini",
    "confianca": "number|null"
  }
}
```

## Exemplo de uso

```bash
curl -X POST "http://localhost:8000/api/extract" \
  -F "file=@/caminho/para/nfse.xml"
```

## Testes

- Unitario: `ExtractNfseUseCaseTest` valida o payload normalizado.
- Integracao: `NfseExtractApiTest` valida `POST /api/extract` para sucesso e erro de XML invalido.

No ambiente de teste, `NfseExtractorPort` e mapeado para `FakeGeminiNfseExtractorProvider` em `config/services_test.yaml`, evitando chamadas reais para o Gemini.
