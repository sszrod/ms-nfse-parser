# Extração de NFS-e com Gemini

## Objetivo

Adicionar uma rota REST `POST /api/extract` para receber um XML de NFS-e e retornar um JSON padronizado com os dados da nota fiscal de serviço.

A extração é feita pela API REST do Gemini e a chave é lida da variável de ambiente `GEMINI_API_KEY`.

## Papel do Gemini na arquitetura

O Gemini entra como adaptador de infraestrutura, não como centro da regra de negócio.

Na prática, isso significa:

- o caso de uso depende da porta `NfseExtractorPort`;
- a implementação concreta atual dessa porta é `GeminiNfseExtractorProvider`;
- a normalização final continua no domínio, em `NfseDataNormalizerService`.

Esse desenho preserva a proposta de DDD + Hexagonal e permite substituir o provider atual por outro modelo ou serviço sem reescrever o fluxo principal.

## Fluxo da extração

1. `EntryPoint` (`ExtractNfseController`) recebe o arquivo XML no campo `file` (multipart/form-data) ou no corpo da requisição.
2. O controller valida se o XML é válido e chama `ExtractNfseUseCase`.
3. `Application` delega a extração para a porta `NfseExtractorPort`.
4. `Infrastructure` usa `GeminiNfseExtractorProvider` para chamar a API do Gemini.
5. `Domain` normaliza o retorno em um contrato JSON único (`NfseDataNormalizerService`).

Mesmo quando o modelo responde com nomes de campos alternativos ou formatos numéricos distintos, o normalizador tenta consolidar a saída em um payload estável.

## Configuração de ambiente

No arquivo `.env`:

```dotenv
GEMINI_API_KEY=
GEMINI_MODEL=
```

- `GEMINI_API_KEY`: obrigatória em execução real.
- `GEMINI_MODEL`: opcional. Quando vazio, o provider usa `gemini-2.0-flash`.

## Contrato entre aplicação e infraestrutura

O contrato entre o caso de uso e o provider é definido por `Application/Port/NfseExtractorPort`.

Isso permite:

- testar o caso de uso com doubles;
- trocar o Gemini por outro provider no futuro;
- manter a dependência externa isolada em `Infrastructure`.

## Contrato de entrada

### POST /api/extract

Aceita uma das formas:

1. `multipart/form-data` com arquivo no campo `file`.
2. `application/xml` com XML no corpo da requisição.

## Contrato de saída (JSON padronizado)

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

- Unitário: `ExtractNfseUseCaseTest` valida o payload normalizado.
- Integração: `NfseExtractApiTest` valida `POST /api/extract` para sucesso e erro de XML inválido.

No ambiente de teste, `NfseExtractorPort` é mapeado para `FakeGeminiNfseExtractorProvider` em `config/services_test.yaml`, evitando chamadas reais para o Gemini.

## Limitações atuais

Esta POC valida a extração estruturada a partir de XML, mas ainda não cobre:

- leitura de PDF com OCR;
- comparação automática entre XML e documento renderizado;
- políticas de confiança, auditoria ou conciliação mais avançadas.

Esses pontos fazem parte da evolução natural da proposta e se encaixam no mesmo desenho arquitetural, entrando como novos adaptadores e novos casos de uso.
