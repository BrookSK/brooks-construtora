-- Migration: Atualiza o template padrão com formatação organizada e novas variáveis
-- Data: 2026-08-19

UPDATE `contract_templates`
SET `prompt_template` = 'Você é um advogado especialista em direito da construção civil e contratos de obras no Brasil. Redija com linguagem jurídica clara, objetiva e profissional o OBJETO do contrato de prestação de serviços de engenharia/construção civil com base nas seguintes informações:

**DADOS DO CLIENTE:**
- Nome/Razão Social: {{cliente_nome}}
- CPF/CNPJ: {{cliente_documento}}
- Telefone: {{cliente_telefone}}
- E-mail: {{cliente_email}}

**INFORMAÇÕES DA OBRA:**
- Tipo: {{tipo_obra}}
- Endereço: {{endereco}}
- Cidade: {{cidade}}
- Objetivo: {{objetivo}}
- Área: {{area_m2}} m²

**BRIEFING DA NEGOCIAÇÃO:**
{{briefing}}

**CONDIÇÕES COMERCIAIS:**
- Valor Total: R$ {{valor_contrato}}
- Entrada: R$ {{entrada}}
- Desconto: {{desconto}}
- Parcelas: {{parcelas}}x
- Detalhes do Parcelamento: {{parcelamento}}
- Prazo de Início: {{data_inicio}}
- Prazo de Conclusão: {{data_conclusao}}
- Prazo em dias: {{prazo_dias}} dias corridos

**CLÁUSULAS ESPECIAIS:**
{{clausulas}}

Redija exclusivamente a cláusula do OBJETO do contrato, detalhando com precisão o escopo dos serviços a serem executados, os materiais envolvidos quando relevante, e as condições de execução. O texto deve ter entre 3 e 6 parágrafos, ser juridicamente sólido e adequado para ser inserido diretamente em um contrato formal. Não inclua outras cláusulas (pagamento, rescisão, etc.), apenas o OBJETO.'
WHERE `is_default` = 1;
