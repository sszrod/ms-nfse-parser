# Extração de NFS-e com Gemini

## Objetivo

Documentar como o Gemini é usado no fluxo atual de extração de NFS-e.

No estado atual do projeto, o Gemini atua como fallback quando não existe template compatível para o município informado.

## Papel do Gemini na arquitetura

O Gemini entra como adaptador de infraestrutura, não como centro da regra de negócio.

Na prática:

- o caso de uso depende da porta `NfseExtractorPort`;
- a implementação atual dessa porta é `GeminiNfseExtractorProvider`;
- a construção do prompt está isolada em `GeminiNfsePromptBuilder`;
- a normalização final continua no domínio (`NfseDataNormalizerService`).

## Fluxo atual com fallback

1. `POST /api/extract` recebe XML e `codigo_municipio`.
2. A aplicação tenta extrair via templates XPath do MongoDB.
3. Se nenhum template validar, chama Gemini.
4. A resposta da IA é normalizada.
5. O mapa de `xpaths` retornado pela IA é usado para salvar novo template.

## Contrato de entrada do endpoint

### POST /api/extract

Aceita uma das formas de XML:

1. `multipart/form-data` com arquivo em `file`.
2. `application/xml` com XML no corpo.

Campo obrigatório adicional:

- `codigo_municipio` (form-data, query string ou header `X-Codigo-Municipio`).

## Contrato esperado da resposta do Gemini

A resposta textual da IA deve ser JSON válido contendo:

- objeto `nfse` com dados extraídos;
- objeto `metadados`;
- objeto `xpaths` com mapeamento de XPath por campo extraído.

Exemplo simplificado:

```json
{
  "nfse": {
    "numero": "123",
    "prestador": {
      "cnpj": "00000000000100"
    }
  },
  "metadados": {
    "fonte_extracao": "gemini"
  },
  "xpaths": {
    "nfse": {
      "numero": "/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero']",
      "prestador": {
        "cnpj": "/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='PrestadorServico']/*[local-name()='IdentificacaoPrestador']/*[local-name()='Cnpj']"
      }
    }
  }
}
```

Observações:

- os XPaths devem ser absolutos e estáveis;
- quando um campo não existir no XML, o valor esperado é `null`;
- o sistema valida se o XPath resolve no XML antes de persistir no template.

## Configuração de ambiente

No `.env`:

```dotenv
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3-flash
GEMINI_TIMEOUT_SECONDS=0
```

- `GEMINI_API_KEY`: obrigatória em execução real.
- `GEMINI_MODEL`: opcional; quando vazio o provider usa `gemini-2.0-flash`.
- `GEMINI_TIMEOUT_SECONDS`:
  - `> 0`: aplica timeout em segundos;
  - `0`: desabilita timeout local da chamada HTTP.

## Erros comuns

1. `GEMINI_API_KEY nao configurada.`
- chave ausente no ambiente carregado da aplicação.

2. `Failed to open stream: HTTP request failed!`
- indisponibilidade temporária da API;
- chave inválida ou sem permissão;
- problema de rede/TLS no container.

3. latência alta
- indica fallback para Gemini em vez de extração por template;
- verificar se template do município existe e se está válido para o XML.

## Testes

- Unitário: `ExtractNfseUseCaseTest` cobre fallback e persistência de template.
- Integração: `NfseExtractApiTest` valida contrato HTTP sem chamada real ao Gemini no ambiente de teste.

## Limitações atuais

- ainda não há estratégia de retry/backoff para falhas transitórias do Gemini;
- confiança/score da IA ainda não orienta política de persistência;
- não há versionamento formal de templates por município.
