# Apresentação da POC

## Visão geral

Esta POC foi idealizada para validar uma abordagem mais adaptável para extração de dados de NFS-e no Brasil utilizando IA.

O problema de origem continua válido: não existe um layout único de NFS-e em operação no país. Cada prefeitura, provedor ou padrão legado pode expor estruturas diferentes de XML, nomes de campos distintos e variações de semântica.

Para reduzir custo e latência, a POC evoluiu para um modelo híbrido com templates por município.

## Hipótese validada até agora

A hipótese central foi refinada para o seguinte modelo:

1. Reutilizar conhecimento estrutural por município via templates XPath persistidos no MongoDB.
2. Chamar IA somente quando não houver template compatível para o XML recebido.
3. Reaproveitar o resultado do fallback para aprender e salvar um novo template automaticamente.

Na prática, isso reduz dependência de extração por IA em chamadas recorrentes, mantendo flexibilidade para layouts novos.

## Fluxo funcional atual

1. A API recebe XML e `codigo_municipio`.
2. O caso de uso busca templates desse município no MongoDB.
3. Se houver template válido, a extração é feita localmente por XPath.
4. Se não houver template válido, o Gemini é acionado.
5. O retorno da IA traz dados normalizados e mapa de `xpaths` por campo.
6. Um novo template é persistido para próximas requisições semelhantes.

## Por que essa proposta ainda faz sentido em 2026

Existe movimento de unificação de NFS-e no Brasil, mas a estratégia segue relevante por quatro motivos:

1. A migração não é instantânea e há convivência de layouts durante a transição.
2. Há passivo de documentos legados e integrações ainda não migradas.
3. O fallback por IA acelera onboarding de layouts novos sem bloquear operação.
4. O cache de templates por município reduz custos operacionais mesmo em cenários mistos.

## Valor para o negócio e para a engenharia

Ganhos observados e esperados:

- menor custo com tokens de IA em cenários recorrentes;
- menor latência média de extração para layouts já aprendidos;
- menos manutenção manual para mapear XML por município;
- maior continuidade operacional diante de novos layouts.

## Escolha arquitetural: DDD + Hexagonal

Mesmo em POC, a solução foi mantida com princípios de DDD + Arquitetura Hexagonal para sustentar evolução.

DDD mantém o foco no domínio de extração/normalização fiscal.
Hexagonal protege o núcleo dos adaptadores externos:

- entrada HTTP;
- repositório MongoDB;
- provider Gemini.

Com isso, a aplicação continua testável e substituível por contrato (portas), sem acoplamento rígido a fornecedor.

## Evolução natural da iniciativa

As próximas frentes mais prováveis são:

1. versionamento e governança de templates por município;
2. comparação XML x PDF com OCR para validação documental;
3. métricas de qualidade de extração e taxa de acerto por template;
4. política de fallback e retentativa por município/fornecedor.

## Resumo executivo

A POC saiu de uma estratégia somente IA para um modelo híbrido template+IA, com aprendizado contínuo por município no MongoDB. O resultado é uma base mais eficiente em custo e tempo, mantendo a flexibilidade necessária para lidar com a heterogeneidade real da NFS-e no Brasil.
