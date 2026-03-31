# Apresentação da POC

## Visão geral

Esta POC foi idealizada para validar uma abordagem mais adaptável para extração de dados de NFS-e no Brasil utilizando IA.

O problema de origem é conhecido: hoje não existe um layout único de NFS-e em operação no país. Cada prefeitura, provedor ou padrão legado pode expor estruturas diferentes de XML, nomes de campos distintos e variações de semântica que acabam exigindo manutenções recorrentes no parser tradicional.

Na prática, isso gera um custo operacional alto. Sempre que surge um novo layout, ou quando algum emissor altera sua estrutura, o fluxo atual tende a quebrar e exige que um desenvolvedor pare o que está fazendo para mapear manualmente o XML, ajustar regras de parsing e publicar uma nova versão. Essa POC busca validar uma alternativa mais resiliente: usar IA para interpretar diferentes formatos de NFS-e e transformar a resposta em um contrato padronizado de saída.

## Hipótese que está sendo validada

A hipótese central deste projeto é que um modelo de IA consegue reduzir significativamente o esforço de manutenção necessário para suportar a diversidade de layouts de NFS-e, mantendo uma interface de consumo única para o restante do sistema.

Com isso, a aplicação deixa de depender exclusivamente de mapeamentos rígidos e passa a operar com uma camada de extração mais flexível, capaz de absorver variações estruturais sem exigir, a cada novo caso, uma intervenção manual imediata da engenharia.

## Por que essa proposta ainda faz sentido em 2026

Existe um movimento real de unificação das NFS-e no Brasil conduzido pelo Governo Federal ao longo de 2026. Ainda assim, essa POC continua válida por três motivos principais:

1. A transição para um modelo único não acontece de forma instantânea, e os sistemas precisam continuar lidando com a heterogeneidade atual durante esse período.
2. Mesmo com a padronização federal, há um passivo documental e operacional que permanece relevante, incluindo notas emitidas em modelos anteriores e integrações que ainda não migraram.
3. A capacidade de extrair e normalizar documentos por IA continua sendo útil como mecanismo de contingência, onboarding acelerado de novos emissores e suporte a cenários onde a qualidade ou a consistência do XML não é ideal.

Ou seja, a unificação reduz o problema no médio prazo, mas não elimina a necessidade de uma estratégia robusta para conviver com a realidade do legado e com o período de transição.

## Escopo atual da POC

Hoje, a POC recebe um XML de NFS-e e retorna um JSON padronizado com os principais dados do documento, desacoplando o consumidor interno das variações de layout de origem.

O fluxo atual é:

1. A API recebe um XML via endpoint HTTP.
2. O caso de uso aciona uma porta de extração.
3. A infraestrutura usa o provider integrado ao Gemini para interpretar o XML.
4. A camada de domínio normaliza o retorno em um contrato único.

Esse desenho permite avaliar rapidamente a viabilidade técnica da proposta sem prender a regra de negócio a um fornecedor ou a um formato específico de documento.

## Evolução natural da iniciativa

Durante a validação da POC, surgiu uma necessidade complementar: comparar o XML transmitido com a nota ou fatura efetivamente gerada em PDF, validando se os valores estão consistentes entre as duas representações.

Essa frente amplia o problema original. Não se trata apenas de extrair dados de XML, mas de validar coerência documental entre fontes diferentes. Para isso, uma evolução natural do projeto é incorporar OCR ou IA especializada em entendimento de documentos para leitura do PDF, extraindo campos relevantes e confrontando-os com os dados estruturados do XML.

Essa melhoria pode apoiar cenários como:

- validação de valor bruto, líquido, ISS e deduções;
- comparação entre tomador, prestador e identificadores fiscais;
- detecção de divergências entre documento emitido e documento transmitido;
- reforço de compliance e auditoria operacional.

## Valor para o negócio e para a engenharia

O principal ganho esperado desta POC é reduzir a dependência de manutenção manual sempre que um novo layout aparece ou quando alguma prefeitura altera o formato atual.

Na perspectiva de engenharia, isso significa menos interrupções ad hoc para criar parsers específicos, menor acoplamento a estruturas locais e maior velocidade para absorver variações de mercado.

Na perspectiva de negócio, isso significa maior continuidade operacional, menor risco de quebra em integrações e uma base mais preparada para lidar tanto com o cenário atual de fragmentação quanto com o período de migração para o padrão federal.

## Escolha arquitetural: DDD + Hexagonal

Mesmo sendo uma POC, a solução foi estruturada com princípios de DDD e Arquitetura Hexagonal porque o problema tem alta chance de crescer em complexidade.

DDD foi escolhido para manter o foco no domínio do problema, e não no fornecedor de IA, no framework ou no formato do transporte HTTP. A preocupação central aqui é extrair, normalizar e validar documentos fiscais de serviço. Organizar o projeto em torno desse domínio ajuda a preservar linguagem ubíqua, casos de uso claros e regras de negócio isoladas.

Arquitetura Hexagonal entra para proteger o núcleo da aplicação das dependências externas. O endpoint HTTP, o provider de IA, futuras integrações com OCR e qualquer outro mecanismo de entrada ou saída ficam posicionados como adaptadores em volta da aplicação. No centro, permanecem os casos de uso, contratos e serviços de domínio.

Na prática, isso traz vantagens objetivas para esta POC:

1. Permite trocar o provider atual de IA sem reescrever o fluxo principal.
2. Facilita adicionar novas entradas e saídas, como validação de PDF com OCR.
3. Mantém a regra de normalização e comparação desacoplada de detalhes de infraestrutura.
4. Melhora testabilidade, porque os casos de uso podem ser exercitados com doubles das portas.

## Por que Symfony

Symfony foi a escolha de framework por oferecer uma base muito flexível, madura e pouco intrusiva para esse tipo de arquitetura.

Neste projeto, o framework funciona como fundação técnica, não como centro da aplicação. O container de dependência, o roteamento, a organização de configuração e a possibilidade de controlar explicitamente como cada serviço é ligado favorecem uma implementação em que o domínio continua sendo a prioridade.

Essa escolha conversa bem com a proposta de DDD + Hexagonal, porque o Symfony permite customização profunda sem forçar a aplicação a seguir um único jeito de estruturar regras, casos de uso ou dependências.

## Por que não Laravel

Laravel não foi adotado porque, apesar de ser extremamente produtivo, ele é mais opinativo em relação a como a aplicação deve ser organizada e como várias decisões de infraestrutura e fluxo acabam sendo tomadas.

Para uma POC cujo objetivo é justamente preservar separação forte entre domínio, aplicação e adaptadores, a flexibilidade do Symfony oferece menos atrito arquitetural. A decisão não parte de uma comparação superficial entre frameworks, mas de aderência ao tipo de desenho que o projeto precisava: um núcleo de negócio protegido, com liberdade para plugar fornecedores de IA, mecanismos de validação e novos canais sem brigar com a estrutura do framework.

## Resumo executivo

Esta POC valida o uso de IA como mecanismo de extração e padronização de NFS-e em um cenário brasileiro ainda fragmentado. A proposta busca reduzir manutenção manual, acelerar a adaptação a novos layouts e criar uma base preparada para evoluir de extração para validação documental mais ampla, incluindo comparação entre XML e PDF com apoio de OCR.

Arquiteturalmente, a escolha por DDD + Hexagonal com Symfony sustenta esse objetivo ao manter o domínio protegido, os adaptadores substituíveis e a evolução da solução aberta para novos provedores, novas entradas e novas estratégias de validação.
