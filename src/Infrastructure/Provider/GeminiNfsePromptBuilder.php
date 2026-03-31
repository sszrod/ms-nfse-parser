<?php

declare(strict_types=1);

namespace MsNfseParser\Infrastructure\Provider;

final class GeminiNfsePromptBuilder
{
    public function buildExtractionPrompt(string $xml): string
    {
        return <<<PROMPT
Voce recebera um XML de NFS-e brasileiro, com modelos que variam por prefeitura.
Extraia os dados e devolva APENAS JSON valido, sem markdown e sem texto adicional.
Se uma informacao nao existir no XML, retorne null.

Regras de assertividade:
- Retorne os valores em "nfse" e, alem disso, retorne em "xpaths" o caminho exato de cada campo extraido.
- Em "xpaths", use XPath absoluto e estavel com local-name(), por exemplo: /*[local-name()='CompNfse']/*[local-name()='Nfse']/*[local-name()='InfNfse']/*[local-name()='Numero'].
- Nao use prefixos de namespace no XPath (evite ns:Tag). Prefira sempre local-name().
- Para cada campo de "nfse":
    - Se valor != null, o XPath correspondente deve localizar esse valor no XML.
    - Se valor == null, retorne XPath null para o campo.
- Antes de responder, confira consistencia valor x XPath.
- Nunca invente campos fora do schema abaixo.

Estrutura obrigatoria:
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
    "confianca": "number|null"
    },
    "xpaths": {
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
                "valor_servicos": "string|null",
                "valor_iss": "string|null",
                "aliquota_iss": "string|null"
            },
            "totais": {
                "valor_bruto": "string|null",
                "valor_deducoes": "string|null",
                "valor_liquido": "string|null"
            }
        }
  }
}

XML da NFS-e:
{$xml}
PROMPT;
    }
}
