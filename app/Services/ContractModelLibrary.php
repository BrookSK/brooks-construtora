<?php

namespace App\Services;

/**
 * Biblioteca do modelo-base de contrato (Anexo A) e do prompt de sistema (Parte 2).
 *
 * Mantida em PHP — e não em SQL — porque o texto do modelo usa caracteres de
 * caixa (┌ │ └ ─) e chaves {{ }} que são frágeis de escapar em INSERTs.
 * O ContractController usa estas constantes para semear a tabela
 * `contract_base_templates` na primeira execução.
 */
class ContractModelLibrary
{
    /**
     * MODELO_CONTRATO — Anexo A (Empreitada / Administração e Gerenciamento).
     * Texto jurídico fixo palavra por palavra; variáveis entre {{ }};
     * instruções condicionais entre <!-- -->.
     */
    public const MODELO_EXECUCAO = <<<'MODELO'
[LOGO]

┌──────────────────────────────────────────────────────────────────────┐
│      CONTRATO DE EMPREITADA ADMINISTRAÇÃO E GERENCIAMENTO DE OBRA     │
└──────────────────────────────────────────────────────────────────────┘

PARTES:

┌──────────────────────────────────────────────────────────────────────┐
│ CONTRATANTE: {{contratante.nome}}, {{contratante.nacionalidade}},     │
│ {{contratante.estado_civil}} portador do CPF: {{contratante.cpf}}     │
│ residente e domiciliado na {{contratante.logradouro}},                │
│ {{contratante.numero}}, apto {{contratante.unidade}},                 │
│ {{contratante.bairro}}, {{contratante.cidade}}- {{contratante.uf}},   │
│ CEP: {{contratante.cep}}, E-mail: {{contratante.email}}               │
└──────────────────────────────────────────────────────────────────────┘

<!-- CONDICIONAL: havendo segundo contratante, replicar o bloco acima
     integralmente e usar CONTRATANTES no plural em todo o documento. -->

┌──────────────────────────────────────────────────────────────────────┐
│ CONTRATADA: {{contratada.razao_social}}, pessoa jurídica inscrita no  │
│ CNPJ/ME nº {{contratada.cnpj}}, com sede na                           │
│ {{contratada.endereco_sede}}.                                         │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│                        CLÁUSULA 1ª – OBJETO:                          │
└──────────────────────────────────────────────────────────────────────┘

1.1.    O presente contrato tem por objeto a execução das obras civis, ampliações e serviços de engenharia/gerenciamento para a residência referente ao Projeto {{projeto.codigo}} - {{projeto.nome}} localizada na {{obra.logradouro}}, {{obra.numero}} - apartamento {{obra.unidade}} {{obra.bairro}}/{{obra.uf}}. CEP {{obra.cep}}, abrangendo todas as etapas físicas pactuadas no orçamento de referência:

1.2.    Escopo Integrado do Projeto: Integram a execução direta deste contrato os seguintes grupos de serviços:

<!-- BLOCO DINÂMICO — CLÁUSULA 1.2
     Gerar uma alínea por grupo da proposta com subtotal MAIOR QUE ZERO,
     na ordem: Serviços Preliminares → Serviços Iniciais/Canteiro → Fase 1
     (Demolições → Construção/Alvenaria → Infras Secas e Instalações →
     Impermeabilização) → Fase 2 (Acabamentos) → Serviços de Engenharia →
     demais grupos.

     Use a BIBLIOTECA DE REDAÇÃO abaixo como texto-base de cada alínea.
     Ajuste apenas o que a proposta efetivamente mudar (serviços a mais,
     serviços a menos, limites dimensionais, ambientes citados).
     Não reescreva a redação por estilo.

     Grupo ausente na proposta = alínea suprimida, com as letras reordenadas
     sem deixar buraco. Grupo novo = alínea nova ao final, no mesmo padrão. -->

a)      Serviços Preliminares e Iniciais: Emissão de ART/RRT, acompanhamento técnico, seguro de riscos de engenharia com cobertura de responsabilidade civil cruzada para terceiros e elaboração de laudo técnico cautelar de vizinhança; fornecimento de placas de sinalização e equipamentos de proteção individual e coletiva (EPIs/EPCs).

b)      Serviços Iniciais e Canteiro: Execução das proteções necessárias nas áreas que não sofrerão intervenção (incluindo portas, janelas, caixilhos, pisos e elevadores) e desenvolvimento de cronograma executivo/planejamento com acompanhamento por plataforma tecnológica.

c)      Demolições (Fase 1): Execução de demolição e remoção de elementos não estruturais existentes (revestimentos, pisos, contrapisos, forros, louças, metais, caixilhos, portas e janelas) com destinação adequada de resíduos; fornecimento e locação de caçambas estacionárias; disponibilização de equipamentos e maquinários para demolição.

d)      Construção e Alvenaria (Fase 1): Execução de novas alvenarias, reenquadros de vãos (portas, janelas e nichos); execução de paredes de gesso acartonado (drywall) com perfis metálicos; execução de nichos em alvenaria ou drywall; regularização de caimentos em pisos de áreas molhadas; e testes de estanqueidade em áreas impermeabilizadas.

e)      Sistemas de Infras Secas e Instalações (Fase 1): Execução da infraestrutura elétrica completa (considerando pontos 220V em todos os ambientes); infraestrutura hidráulica completa (redes de água fria, água quente, ventilação e monocomandos para ducha higiênica); infraestrutura para climatização (drenos, eletrodutos, pontos elétricos e nichos, excluindo furos técnicos em elementos estruturais); e instalação de louças e metais sanitários fornecidos pelo Contratante.

f)      Impermeabilização (Fase 1): Execução de sistemas de impermeabilização em áreas molhadas (banheiros, cozinhas, áreas de serviço) e testes de estanqueidade por meio de enchimento e monitoramento técnico.

g)      Serviços de Acabamentos (Fase 2): Execução de forros em gesso acartonado convencional e forros RU (resistente à umidade) para áreas molhadas, sancas, cortineiros e rebaixos; assentamento de pisos e revestimentos cerâmicos até o limite de {{acabamentos.limite_peca}} nos ambientes de {{acabamentos.ambientes}}; execução de acabamentos em meia esquadria (45°); e preparação de superfícies e aplicação de pintura técnica. A aquisição da argamassa e do rejunte necessários à instalação dos pisos será de responsabilidade exclusiva do CONTRATANTE, devendo os respectivos produtos ser adquiridos conforme as especificações, orientações e recomendações fornecidas pela loja, fabricante ou fornecedor das peças. Tal exigência tem como finalidade preservar as condições técnicas de instalação e a garantia oferecida pelo fabricante dos pisos, não cabendo à CONTRATADA responsabilidade por perda de garantia, incompatibilidade de materiais, defeitos, danos, descolamentos, manchas, fissuras ou quaisquer outros problemas decorrentes da utilização de argamassa, rejunte ou produtos diferentes daqueles recomendados pelo fabricante ou fornecedor.

h)      Engenharia e Logística: Serviços de coordenação, planejamento, engenharia, logística e controle do cronograma de serviços exclusivos executados pela {{contratada.nome_fantasia}}.

<!-- FIM DO BLOCO DINÂMICO 1.2 -->

1.3. Documentos Integrantes: Integram este contrato, para todos os fins de direito, a Proposta Comercial/Orçamento Projeto {{projeto.codigo}} - {{projeto.nome}}, o memorial descritivo, projetos executivos aprovados e o cronograma físico-financeiro.

┌──────────────────────────────────────────────────────────────────────┐
│      CLÁUSULA 2ª – DO MODELO DE ADMINISTRAÇÃO E INTERMEDIAÇÃO         │
└──────────────────────────────────────────────────────────────────────┘

2.1.    Atuação como Administradora: A CONTRATADA atuará como Administradora e Auxiliar do CONTRATANTE na coordenação geral da obra e na gestão técnica de fornecedores e parceiros terceirizados.

2.2.    Modelo de Faturamento e Segregação Fiscal: Em estrita observância ao planejamento tributário e operacional pactuado no Projeto {{projeto.codigo}} - {{projeto.nome}}:
        a) Uma parcela aproximada de {{fiscal.pct_construtora}} ({{fiscal.pct_construtora_extenso}}) do faturamento total será emitida diretamente pela CONTRATADA, que atuará contratualmente como administradora da obra.
        b) O presente contrato contempla a emissão de Nota Fiscal pela {{contratada.nome_fantasia}} correspondente a {{fiscal.pct_construtora}} do valor contratual, referente exclusivamente aos serviços administrativos e de engenharia, {{fiscal.pct_material}} do valor será emitido para materiais de construção e os {{fiscal.pct_fornecedores}} restantes serão destinados a fornecedores e prestadores, com pagamentos realizados diretamente pelo CONTRATANTE, conforme orientação do setor financeiro. A relação comercial e documental referente a esses valores ocorrerá diretamente entre o CONTRATANTE e cada loja, fornecedor ou prestador, de acordo com as condições próprias de cada contratação.

<!-- CONDICIONAL: incluir o item 2.2 (e alíneas a e b) SOMENTE se os três
     percentuais de segregação fiscal estiverem preenchidos. Se qualquer um
     estiver vazio/zero, suprimir todo o item 2.2 e manter apenas o 2.1.
     Não gerar [[PENDENTE]] para esses percentuais. -->

┌──────────────────────────────────────────────────────────────────────┐
│   CLÁUSULA 3ª – DO PREÇO, DAS CONDIÇÕES DE PAGAMENTO E DO             │
│                    COMPLIANCE FINANCEIRO                              │
└──────────────────────────────────────────────────────────────────────┘

3.1.    Valor Total do Contrato: O valor global ajustado para a execução das obrigações da CONTRATADA e aquisições vinculadas é de {{valor.total}} ({{valor.total_extenso}}), preço fixo, com margens e tributos inclusos no modelo de faturamento previsto na Cláusula Segunda.

3.2.    Estrutura de Desembolso / Forma de Pagamento: O montante total será quitado pelo CONTRATANTE à CONTRATADA seguindo rigorosamente o seguinte fluxo financeiro:

3.2.1. Entrada (Sinal de Negócio): O valor de {{entrada.valor}} ({{entrada.extenso}}), equivalente a {{entrada.pct}} ({{entrada.pct_extenso}}) do valor total, será pago à vista no ato de assinatura do presente instrumento.

3.2.2. Parcelas Mensais Fixas (Saldo Residual): O valor de {{parcelas.total}} ({{parcelas.total_extenso}}), equivalente a {{parcelas.pct}} ({{parcelas.pct_extenso}}) do valor total, será quitado em {{parcelas.quantidade}} ({{parcelas.quantidade_extenso}}) parcelas mensais, fixas e sucessivas, no valor individual de {{parcelas.valor_unitario}} ({{parcelas.valor_unitario_extenso}}) cada, com vencimento a partir do {{parcelas.mes_inicio}}, considerando o {{parcelas.mes_carencia}} com parcela mensal zerada (R$ 0,00), de acordo com o cronograma de desembolso.

<!-- CONDICIONAL 3.2.2: se a projeção de desembolso não tiver mês de carência,
     encerrar a frase após "...cada, com vencimento a partir do
     {{parcelas.mes_inicio}}, de acordo com o cronograma de desembolso."
     Se as parcelas tiverem valores diferentes entre si, substituir a partir de
     "no valor individual" por uma tabela de vencimentos e valores. -->

3.2.3. Parcela na Entrega (Chaves): O valor de {{entrega.valor}} ({{entrega.extenso}}), equivalente a {{entrega.pct}} ({{entrega.pct_extenso}}) do valor total, será pago integralmente no ato de entrega formal das chaves.

3.3.    Regime de Parcelas Fixas (Independência de Medições): O regime de pagamento baseia-se em parcelas mensais fixas estipuladas no cronograma financeiro, independentes de medições físicas diárias de evolução de campo, visando assegurar o fluxo de caixa do canteiro e a previsibilidade financeira de ambas as partes.

3.4.    Condição de Mobilização: A efetiva mobilização do canteiro de obras e o início da contagem do prazo de execução ficam estritamente condicionados ao recebimento e compensação bancária integral da parcela de Entrada descrita no item 3.2.1.

3.5.    Penalidades por Atraso nos Pagamentos: Qualquer atraso no pagamento de qualquer das parcelas sujeitará o CONTRATANTE à incidência de multa moratória de {{multa.mora_pct}} ({{multa.mora_pct_extenso}}) sobre o valor em aberto, acrescida de juros de mora de {{multa.juros_pct}} ({{multa.juros_pct_extenso}}) ao mês pro rata die e correção monetária.

<!-- CONDICIONAL: incluir o parágrafo abaixo SOMENTE se multa.atraso_diario_pct
     E multa.teto_pct estiverem preenchidos. Se qualquer um estiver vazio/zero,
     suprimir todo este parágrafo. Não gerar [[PENDENTE]] para esses campos. -->
Penalidade por Atraso Injustificado na Obra: O atraso injustificado e imputável exclusivamente à CONTRATADA na entrega da obra sujeitará a construtora à multa diária equivalente a {{multa.atraso_diario_pct}} ({{multa.atraso_diario_pct_extenso}}) do valor contratual, limitada ao teto global de {{multa.teto_pct}} ({{multa.teto_pct_extenso}}).

┌──────────────────────────────────────────────────────────────────────┐
│   CLÁUSULA 4ª – DAS GARANTIAS ESPECÍFICAS E LIMITAÇÃO DE             │
│                       RESPONSABILIDADE                               │
└──────────────────────────────────────────────────────────────────────┘

4.1.    Conformidade Técnica: A CONTRATADA obriga-se a executar os serviços com observância às normas técnicas vigentes da Associação Brasileira de Normas Técnicas (ABNT), incluindo ABNT NBR 6122, NBR 6118, NBR 9575 e NBR 9574.

4.2.    Prazos de Garantia: Os prazos de garantia contam-se a partir da entrega física das chaves e assinatura do Termo de Vistoria e Recebimento:
        a) Solidez e Segurança (Estrutural): {{garantia.solidez_prazo}}, vícios redibitórios ou falhas graves que comprometam a estabilidade e segurança.

4.3.    Condições de Validade da Garantia: A eficácia da garantia condiciona-se ao uso adequado do imóvel pelo CONTRATANTE, à inexistência de modificações/reparos executados por terceiros sem prévia anuência por escrito da CONTRATADA, e ao cumprimento do Manual do Proprietário.

4.4.    Exclusões de Garantia: Estão excluídos da garantia danos causados por mau uso, falta de manutenção preventiva, eventos climáticos extraordinários, ou vícios intrínsecos de materiais faturados diretamente por terceiros (prevalecendo nestes casos a garantia do fabricante).

4.5.    Assistência Técnica e Sistema {{sistema.nome}}: Quaisquer solicitações de assistência técnica durante o período de garantia deverão ser formalizadas exclusivamente por meio do sistema {{sistema.nome}} ou canais administrativos oficiais da CONTRATADA em horário comercial. A CONTRATADA realizará análise e manifestação técnica em até 10 (dez) dias úteis.

4.6.    Limitação de Responsabilidade: A responsabilidade contratual da CONTRATADA restringe-se à correção técnica dos serviços diretamente executados por ela, não sendo devidas indenizações por danos indiretos, lucros cessantes ou impossibilidade temporária de uso do imóvel durante reparos, ressalvadas as hipóteses de dolo ou culpa grave comprovada.

┌──────────────────────────────────────────────────────────────────────┐
│                    CLÁUSULA 5ª – DAS OBRIGAÇÕES                      │
└──────────────────────────────────────────────────────────────────────┘

5.1.    Obrigações da CONTRATADA:
        a) Executar os serviços descritos na Cláusula Primeira em observância às normas ABNT e ao projeto aprovado.
        b) Manter equipe qualificada, assumindo total responsabilidade trabalhista, previdenciária, securitária e de segurança do trabalho (EPIs), inexistindo qualquer vínculo empregatício entre os prepostos da CONTRATADA e o CONTRATANTE.
        c) Responsabilizar-se por danos materiais causados diretamente por seus empregados ao imóvel ou a terceiros.
        d) Manter o local da obra limpo e organizado.
        e) Alimentar continuamente o sistema {{sistema.nome}} com diário de obra, imagens e relatórios.

5.2.    Obrigações do CONTRATANTE:
        a) Efetuar os pagamentos estipulados no orçamento nas datas avençadas.
        b) Fornecer todos os projetos executivos, sondagem SPT, informações técnicas e o projeto estrutural da edificação existente.
        c) Fornecer e arcar com os insumos de água e energia elétrica necessários ao funcionamento do canteiro.
        d) Garantir o livre acesso das equipes da CONTRATADA ao local da obra.
        e) Outorgar a procuração administrativa e financeira necessária à atuação da CONTRATADA como administradora da obra, nos termos da Cláusula Segunda.
        f) Regularização e Licenciamento: Responsabilizar-se integralmente pela aprovação de projetos e regularização do empreendimento perante a Prefeitura Municipal, Condomínio ({{condominio.nome}}), CETESB e demais órgãos, obtendo e custeando alvarás, licenças, taxas, emolumentos e o respectivo "Habite-se".

<!-- CONDICIONAL 5.2.f: sem condomínio informado, remover "Condomínio
     ({{condominio.nome}})," da enumeração. -->

┌──────────────────────────────────────────────────────────────────────┐
│      CLÁUSULA 6ª – POLÍTICA DE BOA VIZINHANÇA E REGRAS DA OBRA       │
└──────────────────────────────────────────────────────────────────────┘

6.1.    Normas Condominiais: O CONTRATANTE declara ciência de que a obra sujeita-se às regras internas do Condomínio {{condominio.nome}} e à política de boa vizinhança (horários de ruído, transporte e carga/descarga).

<!-- CONDICIONAL 6.1: sem condomínio informado, suprimir o item 6.1 inteiro e
     renumerar os subsequentes. -->

6.2.    Canais Exclusivos de Comunicação: Toda comunicação técnica, relatórios e solicitações ocorrerão obrigatoriamente via sistema {{sistema.nome}} e canais administrativos em horário comercial.

6.3.    Ocupação Antecipada do Imóvel:

6.3.1. A CONTRATADA não executa, finaliza ou mantém equipes operacionais no imóvel caso o CONTRATANTE, seus familiares ou terceiros venham a residir ou habitar o local antes da entrega formal das chaves e emissão do Termo de Vistoria e Recebimento.

6.3.2. Paralisação por Mudança Antecipada: Caso o CONTRATANTE decida mudar-se ou ocupar o imóvel antes do encerramento dos serviços contratados — seja por conveniência própria, atrasos na montagem de marcenaria/mobiliário fornecido por terceiros ou qualquer outro motivo —, as atividades de gerenciamento e execução presencial da CONTRATADA serão imediatamente paralisadas e dadas por encerradas no estágio físico em que se encontrarem, desonerando a CONTRATADA de qualquer penalidade por atraso.

6.3.3. Condições para Aplicação da Última Demão de Pintura: A execução dos serviços de pintura obedecerá ao seguinte rito operacional:
a)      A CONTRATADA aplicará a primeira demão de pintura e liberará o imóvel desocupado para a montagem de marcenaria, armários e móveis planejados contratados diretamente pelo CONTRATANTE junto a terceiros;
b)      A aplicação da última demão de pintura (demão final de acabamento) ocorrerá exclusivamente após a conclusão da montagem da marcenaria e desde que o imóvel permaneça integralmente desocupado;
c)      Caso o CONTRATANTE passe a residir no imóvel antes da aplicação da última demão de pintura, a CONTRATADA não executará a pintura final com moradores no local, hipótese em que o CONTRATANTE decairá do direito à referida etapa, sem qualquer direito a abatimento proporcional do preço contratual;
d)      Se o CONTRATANTE desocupar o imóvel posteriormente e requerer o retorno da equipe para a execução da última demão de pintura, tal serviço será objeto de orçamento aditivo extraordinário, cobrando-se os custos adicionais relativos à proteção de mobiliário, cobrimento de móveis, isolamento de superfícies e mobilização de pessoal.

┌──────────────────────────────────────────────────────────────────────┐
│            CLÁUSULA 7ª – EXCLUSÕES TÉCNICAS DEFINITIVAS              │
└──────────────────────────────────────────────────────────────────────┘

7.1.    Itens Não Contemplados: Em estrita conformidade com a Proposta Comercial {{projeto.codigo}}, estão expressamente excluídos deste contrato e do preço global:

Projetos de Engenharia Não Contratados: {{exclusoes.projetos}}

7.1.1. Equipamentos e Sistemas Especializados: {{exclusoes.equipamentos}}

7.1.2. Conexões e Equipamentos de Ar-Condicionado: Ligações técnicas junto às concessionárias, energização definitiva, comissionamento dos sistemas, fornecimento e instalação da rede frigorígena (tubos de cobre), isolamento térmico, carga de gás, instalação de evaporadoras/condensadoras e testes de funcionamento de climatização (estando inclusa apenas a infraestrutura seca de drenos e eletrodutos).

7.1.3. Materiais de Acabamento: Fornecimento e aquisição de revestimentos, pisos, porcelanatos, tintas, louças sanitárias, metais, bancadas, cubas, iluminação/luminárias decorativas, tomadas e interruptores (estando inclusa exclusivamente a mão de obra para assentamento ou instalação).

7.1.4. Marcenaria e Decoração: {{exclusoes.marcenaria_decoracao}}

7.1.5. Taxas, Tributos e Regularizações: Recolhimento de CNO, INSS da obra, taxas de Habite-se, alvará de construção, alvará de reforma perante a Prefeitura Municipal de {{obra.cidade}} e Condomínio, taxas de licenciamento, averbações, emolumentos e ligações definitivas de concessionárias.

<!-- BLOCO DINÂMICO — CLÁUSULA 7
     Preencher as variáveis {{exclusoes.*}} consolidando, sem repetir:
     (i) todo item da proposta com valor R$ 0,00;
     (ii) tudo marcado como "não incluso" nas notas dos grupos;
     (iii) a lista "Não incluem:" do resumo final da proposta.
     Manter a redação enxuta, em enumeração corrida, no mesmo tom dos
     subitens fixos acima. Criar 7.1.6 em diante somente se algo não couber
     em nenhum dos subitens existentes. -->

┌──────────────────────────────────────────────────────────────────────┐
│                   CLÁUSULA 8ª – PRAZO E VISTORIA                     │
└──────────────────────────────────────────────────────────────────────┘

8.1. Prazo Global: O prazo total para a execução dos serviços e entrega da obra é de {{prazo.meses}} ({{prazo.meses_extenso}}) meses ({{prazo.dias}} dias), a contar do início efetivo das obras.

8.2. Premissas Suspensivas de Início: A contagem do prazo de {{prazo.meses}} meses terá início no primeiro dia útil subsequente ao cumprimento cumulativo de:
a)      Compensação bancária do valor integral da Entrada (sinal);
b)      Entrega formal pelo CONTRATANTE de todos os projetos executivos necessários à execução;
c)      Obtenção das devidas autorizações e alvará de reforma perante a Prefeitura de {{obra.cidade}} e o Condomínio.

┌──────────────────────────────────────────────────────────────────────┐
│                  CLÁUSULA 9ª – RESCISÃO CONTRATUAL                   │
└──────────────────────────────────────────────────────────────────────┘

9.1 Rescisão por Justa Causa: O descumprimento de qualquer cláusula facultará à parte inocente notificar a parte inadimplente para sanar a irregularidade no prazo de 10 (dez) dias. Não sanada a falta, o contrato poderá ser rescindido, aplicando-se multa rescisória de 10% (dez por cento) sobre o saldo financeiro remanescente do contrato.

9.2 Rescisão Imotivada: Qualquer das partes poderá rescindir este contrato mediante aviso prévio por escrito com antecedência mínima de 30 (trinta) dias.

┌──────────────────────────────────────────────────────────────────────┐
│                   CLÁUSULA 10ª – DISPOSIÇÕES FINAIS                  │
└──────────────────────────────────────────────────────────────────────┘

10.1.   Seguro de Riscos de Engenharia: A CONTRATADA manterá vigente, às suas expensas, Seguro de Risco de Engenharia e Responsabilidade Civil com cobertura adequada durante o período de execução da obra, figurando o CONTRATANTE como beneficiário.

10.2.   Direito de Imagem e Registros {{sistema.nome}}: A CONTRATADA fica autorizada a realizar registros fotográficos, videográficos e tours virtuais 3D da obra para inclusão no sistema {{sistema.nome}}, portfólio técnico e divulgação institucional, garantido o anonimato do CONTRATANTE, vedada a menção a dados pessoais ou financeiros.

┌──────────────────────────────────────────────────────────────────────┐
│                         CLÁUSULA 11ª – FORO                          │
└──────────────────────────────────────────────────────────────────────┘

11.1.   Fica eleito o Foro de {{foro.comarca}} para dirimir questões deste contrato.

{{assinatura.cidade}}, {{assinatura.data_extenso}}.

[LOGO]
                         ________________________
                         {{contratante.nome}}

                         ________________________
                         {{contratada.nome_fantasia}}

Testemunha: _____________________
Nome:
CPF:

Testemunha: _______________________
Nome:
CPF:
MODELO;

    /**
     * Prompt de sistema (Parte 2). O ContractController injeta o MODELO_CONTRATO
     * entre as tags <MODELO_CONTRATO> e </MODELO_CONTRATO> no momento da geração.
     */
    public const SYSTEM_PROMPT = <<<'PROMPT'
PAPEL
Você é o redator contratual da construtora. Sua função é converter uma Proposta Comercial/Orçamento em um Contrato de Empreitada, Administração e Gerenciamento de Obra, usando EXCLUSIVAMENTE o modelo contratual fornecido como base estrutural e os dados da proposta como conteúdo.

ENTRADAS
1. MODELO_CONTRATO — texto integral do contrato padrão, com marcações {{variavel}}.
2. DADOS_PROPOSTA — JSON extraído do PDF do orçamento.
3. DADOS_COMPLEMENTARES — JSON com dados do contratante, obra e condomínio.

REGRAS INVIOLÁVEIS
1. Não altere a redação jurídica do modelo. Preserve palavra por palavra todo texto que não seja campo variável, incluindo cláusulas de garantia, limitação de responsabilidade, rescisão, foro e política de boa vizinhança.
2. Preserve a estrutura: mesma sequência de cláusulas, mesma numeração, mesmos títulos, mesmas alíneas.
3. Nunca invente dado. Se não constar na proposta nem nos dados complementares, escreva [[PENDENTE: descrição]].
4. Nunca recalcule valores por conta própria. Use os valores exatos da proposta. Se a checagem aritmética falhar, gere assim mesmo e reporte a inconsistência na seção RELATÓRIO ao final (fora do corpo do contrato).
5. Todos os valores em reais no formato R$ 0.000,00, seguidos do valor por extenso entre parênteses. Percentuais com duas casas: 40,00% (quarenta por cento).
6. Datas em português: "São Paulo/SP, 27 de agosto de 2026".
7. Não acrescente cláusulas novas, comentários, notas de rodapé ou explicações dentro do contrato.

MAPEAMENTO CAMPO A CAMPO (proposta → contrato)
- Capa "Projeto" (ex.: P 0019 JUAN E YASMINI) → Cl. 1.1, 1.3, 2.2, 7.1 (nome do projeto)
- Capa "Prazo" (ex.: 8 meses) → Cl. 8.1 (prazo global, em meses e dias = meses × 30)
- Capa "Contrato" (Execução / Administração) → seleção do modelo-base
- Capa "Data" e "Revisão" → metadados e Cl. 1.3 (identificação da proposta)
- Valor Total do Contrato → Cl. 3.1 (valor global)
- Notas de Negociação (ex.: 20% NF / 30% material / 50% fornecedores) → Cl. 2.2 a) e b)
- Entrada (sinal) → Cl. 3.2.1 (valor e %)
- Parcelas Mensais → Cl. 3.2.2 (valor total, %, nº de parcelas, valor individual)
- Parcela na Entrega → Cl. 3.2.3 (valor e %)
- Projeção de Desembolso Mensal (M1..Mn) → Cl. 3.2.2 (mês de início e mês de carência)
- Composição por Etapa / Grupos com valor > 0 → Cl. 1.2 (alíneas do escopo)
- Itens com valor R$ 0,00 → Cl. 7.1 (exclusões)
- Nota de argamassa/rejunte por conta do cliente → Cl. 1.2 g)
- Nota de peças até 1,20 m × 1,20 m → Cl. 1.2 g) (limite dimensional)
- Nota de ar-condicionado (o que não está incluso) → Cl. 7.1.2
- Nota de coordenação/terceirizados → Cl. 1.2 h)

MAPEAMENTO A PARTIR DE DADOS_COMPLEMENTARES (não vêm da proposta)
- contratante(s) → bloco PARTES e assinatura (nome, nacionalidade, estado civil, CPF, endereço, e-mail)
- obra → Cl. 1.1 (logradouro, número, unidade, bairro, cidade/UF, CEP)
- condominio.nome → Cl. 5.2.f e 6.1
- contratada (razao_social, cnpj, endereco_sede, nome_fantasia) → bloco PARTES, Cl. 2.2 b), assinatura
- multa.mora_pct / multa.juros_pct / multa.atraso_diario_pct / multa.teto_pct → Cl. 3.5 ({{multa.mora_pct}}, {{multa.juros_pct}}, {{multa.atraso_diario_pct}}, {{multa.teto_pct}})
- garantia.solidez_prazo → Cl. 4.2 a) ({{garantia.solidez_prazo}})
- sistema.nome → Cl. 4.5, 5.1.e, 6.2, 10.2 ({{sistema.nome}})
- foro.comarca → Cl. 11.1 ({{foro.comarca}})
- assinatura.cidade / assinatura.data → fecho do contrato
- Para os percentuais (multa/juros/teto/fiscal), gere também o valor por extenso entre parênteses, como manda a regra 5. Se um desses campos vier vazio em DADOS_COMPLEMENTARES, use [[PENDENTE: descrição]].

CONSTRUÇÃO DA CLÁUSULA 1.2 (ESCOPO)
- Percorra os grupos da proposta na ordem: Serviços Preliminares → Serviços Iniciais/Canteiro → Fase 1 (Demolições, Construção/Alvenaria, Infras Secas e Instalações, Impermeabilização) → Fase 2 (Acabamentos) → Serviços de Engenharia.
- Gere uma alínea (a, b, c...) por grupo que tenha valor maior que zero.
- Cada alínea deve ter: título em negrito com a fase entre parênteses, seguido de resumo em prosa corrida dos itens do grupo. Condense as descrições longas em linguagem contratual, sem perder nenhum serviço contratado e sem incluir serviço que não esteja na proposta.
- Grupos com subtotal R$ 0,00 NÃO viram alínea de escopo: vão para a Cláusula 7.
- Se a proposta trouxer grupo inexistente no modelo, crie uma alínea nova ao final da sequência, seguindo o mesmo padrão de redação.
- Se um grupo do modelo não existir na proposta, suprima a alínea e reordene as letras sem deixar buracos.

CONSTRUÇÃO DA CLÁUSULA 7 (EXCLUSÕES)
- Consolide, sem repetir: (i) todo item com valor R$ 0,00; (ii) tudo listado nas notas dos grupos como "não incluso"; (iii) a lista "Não incluem:" do resumo final; (iv) taxas, tributos e regularizações do modelo padrão.
- Mantenha os subitens 7.1.1 a 7.1.5 do modelo e distribua as exclusões da proposta dentro deles. Só crie subitem novo (7.1.6...) se algo não couber.

CONSTRUÇÃO DA CLÁUSULA 3.2.2 (PARCELAS)
- Nº de parcelas, valor unitário e mês de início vêm da Projeção de Desembolso.
- Se a projeção indicar mês de carência (parcela R$ 0,00), o texto deve explicitar o mês de carência e o mês do primeiro vencimento, como no modelo.
- Se as parcelas tiverem valores diferentes entre si, substitua a redação de "parcelas fixas" por uma tabela de vencimentos e valores, mantendo a cláusula 3.3 apenas se o regime continuar sendo de parcelas independentes de medição.

CONDICIONAIS
- Sem condomínio informado: suprimir a menção ao condomínio nas Cl. 5.2.f e 6.1 e ajustar a redação para citar apenas Prefeitura e demais órgãos.
- Dois contratantes: replicar o bloco de qualificação em PARTES e o bloco de assinatura, e usar "CONTRATANTES" no plural em todo o documento.
- SEGREGAÇÃO FISCAL (Cl. 2.2): só inclua o item 2.2 e suas alíneas a) e b) se os três percentuais (fiscal.pct_construtora, fiscal.pct_material, fiscal.pct_fornecedores) vierem PREENCHIDOS com valor. Se qualquer um deles estiver vazio, nulo ou zero, SUPRIMA integralmente o item 2.2 (a e b), mantendo apenas o item 2.1 (atuação como administradora). Nunca escreva [[PENDENTE]] para os percentuais fiscais — a ausência significa que a cláusula não se aplica e deve ser omitida.
- PENALIDADE POR ATRASO NA OBRA (Cl. 3.5, parágrafo "Penalidade por Atraso Injustificado na Obra"): só inclua esse parágrafo se AMBOS multa.atraso_diario_pct e multa.teto_pct vierem PREENCHIDOS com valor. Se qualquer um estiver vazio, nulo ou zero, SUPRIMA integralmente esse parágrafo, mantendo o restante do item 3.5 (multa moratória e juros). Nunca escreva [[PENDENTE]] para a multa diária ou o teto — a ausência significa que essa penalidade não se aplica e o parágrafo deve ser omitido.

SAÍDA
Retorne dois blocos separados:
1. <CONTRATO> — o contrato completo em Markdown estruturado, pronto para o template de diagramação.
2. <RELATORIO> — lista de: campos pendentes; divergências aritméticas encontradas; itens da proposta que não foram alocados em nenhuma cláusula; cláusulas do modelo suprimidas e o motivo. Este bloco não faz parte do contrato e é exibido apenas ao usuário interno.
PROMPT;

    /**
     * Prompt de extração da proposta (Etapa 1 → 2). Retorna JSON estruturado.
     */
    public const EXTRACTION_PROMPT = <<<'PROMPT'
Analise o PDF anexado — uma Proposta Comercial/Orçamento de obra gerada pela construtora. Extraia TODOS os dados e retorne APENAS um JSON válido (sem markdown), com string vazia "" onde não encontrar o dado. NUNCA invente valores.

Estrutura obrigatória:
{
  "capa": {
    "projeto_codigo": "",        // ex.: "P 0019"
    "projeto_nome": "",          // ex.: "JUAN E YASMINI"
    "prazo_meses": "",           // número, ex.: "8"
    "contrato_tipo": "",         // "Execução" | "Administração" | "Gerenciamento"
    "data": "",                  // AAAA-MM-DD
    "revisao": "",               // ex.: "001"
    "area_total": "",            // m², somente número
    "custo_m2": "",              // somente número
    "responsavel": "",
    "arquiteto": "",
    "pagina": ""                 // página onde a capa foi lida
  },
  "valor_total": "",             // somente número, ex.: "350000.00"
  "forma_pagamento": {
    "entrada_valor": "", "entrada_pct": "",
    "parcelas_total": "", "parcelas_pct": "", "parcelas_quantidade": "", "parcelas_valor_unitario": "",
    "entrega_valor": "", "entrega_pct": ""
  },
  "projecao_desembolso": [
    { "mes": "M1", "valor": "" }
  ],
  "notas_negociacao": {
    "pct_construtora": "", "pct_material": "", "pct_fornecedores": "",
    "texto_livre": ""
  },
  "grupos": [
    {
      "nome": "",                // ex.: "Serviços Preliminares"
      "fase": "",                // ex.: "Fase 1"
      "subtotal": "",            // somente número; 0 = exclusão
      "itens": [""],             // descrições dos serviços do grupo
      "notas": [""],             // notas técnicas / "não incluso"
      "pagina": ""
    }
  ],
  "exclusoes": {
    "nao_incluem": [""],         // lista "Não incluem:" do resumo final
    "itens_zerados": [""]        // itens com R$ 0,00
  },
  "acabamentos": {
    "limite_peca": "",           // ex.: "1,20 m × 1,20 m"
    "ambientes": ""
  },
  "confianca_baixa": [""]        // nomes dos campos com baixa confiança de extração
}

Regras: valores monetários e percentuais somente com números (use ponto decimal, sem R$ nem %). Datas em AAAA-MM-DD. Para cada grupo, informe a página de origem.
PROMPT;
}
