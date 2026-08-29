<?php

namespace App\Services;

/**
 * Checklist de validação automática (Parte 4).
 *
 * Recebe o JSON consolidado (proposta + complementares) e o Markdown gerado,
 * devolve uma lista de problemas classificados como "block" (impede exportação)
 * ou "warn" (apenas alerta). O controller decide o que fazer com o resultado.
 */
class ContractValidator
{
    /** @var array<int,array{level:string,message:string}> */
    private array $issues = [];

    /**
     * @param array  $proposal      DADOS_PROPOSTA (JSON extraído)
     * @param array  $complementary DADOS_COMPLEMENTARES
     * @param string $markdown      Contrato gerado
     * @return array{blocked:bool, issues:array}
     */
    public function validate(array $proposal, array $complementary, string $markdown = ''): array
    {
        $this->issues = [];

        $total = $this->num($proposal['valor_total'] ?? null);
        $fp = $proposal['forma_pagamento'] ?? [];
        $entrada = $this->num($fp['entrada_valor'] ?? null);
        $parcelas = $this->num($fp['parcelas_total'] ?? null);
        $entrega = $this->num($fp['entrega_valor'] ?? null);

        // 1. Entrada + parcelas + entrega ≠ valor total
        if ($total > 0) {
            $soma = $entrada + $parcelas + $entrega;
            if ($this->diff($soma, $total) > 0.01) {
                $this->block(sprintf(
                    'Entrada + parcelas + entrega (%s) ≠ valor total do contrato (%s).',
                    $this->money($soma), $this->money($total)
                ));
            }
        }

        // 2. Soma dos percentuais de pagamento ≠ 100,00%
        $pctSum = $this->num($fp['entrada_pct'] ?? null)
                + $this->num($fp['parcelas_pct'] ?? null)
                + $this->num($fp['entrega_pct'] ?? null);
        if ($pctSum > 0 && $this->diff($pctSum, 100) > 0.05) {
            $this->block(sprintf('Percentuais de pagamento somam %.2f%%, não 100,00%%.', $pctSum));
        }

        // 3. Valor unitário × quantidade ≠ total das parcelas
        $qtd = (int)$this->num($fp['parcelas_quantidade'] ?? null);
        $unit = $this->num($fp['parcelas_valor_unitario'] ?? null);
        if ($qtd > 0 && $unit > 0 && $parcelas > 0) {
            if ($this->diff($qtd * $unit, $parcelas) > 0.01) {
                $this->block(sprintf(
                    'Valor unitário × quantidade (%s) ≠ total das parcelas (%s).',
                    $this->money($qtd * $unit), $this->money($parcelas)
                ));
            }
        }

        // 4. Percentuais de segregação fiscal (Cl. 2.2) não somam 100%
        $nn = $proposal['notas_negociacao'] ?? [];
        $fiscalSum = $this->num($nn['pct_construtora'] ?? null)
                   + $this->num($nn['pct_material'] ?? null)
                   + $this->num($nn['pct_fornecedores'] ?? null);
        if ($fiscalSum > 0 && $this->diff($fiscalSum, 100) > 0.05) {
            $this->block(sprintf('Segregação fiscal (Cl. 2.2) soma %.2f%%, não 100%%.', $fiscalSum));
        }

        // 5. Prazo em dias ≠ meses × 30
        $meses = (int)$this->num($proposal['capa']['prazo_meses'] ?? null);
        if ($meses > 0) {
            $prazoDias = (int)$this->num($proposal['prazo_dias'] ?? ($meses * 30));
            if ($prazoDias !== $meses * 30) {
                $this->block(sprintf('Prazo em dias (%d) ≠ meses × 30 (%d).', $prazoDias, $meses * 30));
            }
        }

        // 6. Valor por extenso divergente do valor numérico
        if ($total > 0 && $markdown !== '') {
            $extenso = $this->numberToWords($total);
            // heurística: as três primeiras palavras significativas do extenso
            // devem aparecer no corpo do contrato próximas ao valor total.
            $needle = $this->firstWords($extenso, 3);
            if ($needle !== '' && mb_stripos($this->normalize($markdown), $this->normalize($needle)) === false) {
                $this->warn(sprintf(
                    'Confira o valor por extenso do total: esperado começar com "%s" (%s).',
                    $needle, $this->money($total)
                ));
            }
        }

        // 7. Grupo com valor > 0 ausente da Cl. 1.2
        if ($markdown !== '') {
            $body = $this->extractClause($markdown, '1.2', '1.3');
            foreach (($proposal['grupos'] ?? []) as $g) {
                $sub = $this->num($g['subtotal'] ?? null);
                $nome = trim((string)($g['nome'] ?? ''));
                if ($sub > 0 && $nome !== '' && $body !== '') {
                    if (!$this->mentions($body, $nome)) {
                        $this->block(sprintf('Grupo "%s" (valor > 0) não apareceu na Cláusula 1.2.', $nome));
                    }
                }
            }

            // 8. Item zerado ausente da Cl. 7
            $cl7 = $this->extractClause($markdown, '7.1', '8.1');
            $zerados = array_merge(
                $proposal['exclusoes']['itens_zerados'] ?? [],
                array_map(fn($g) => $g['nome'] ?? '', array_filter(
                    $proposal['grupos'] ?? [],
                    fn($g) => $this->num($g['subtotal'] ?? null) === 0.0 && trim((string)($g['nome'] ?? '')) !== ''
                ))
            );
            foreach (array_unique(array_filter($zerados)) as $item) {
                if ($cl7 !== '' && !$this->mentions($cl7, (string)$item)) {
                    $this->warn(sprintf('Item zerado "%s" não localizado na Cláusula 7.', $item));
                }
            }

            // 9. Marcador [[PENDENTE]] remanescente
            if (preg_match_all('/\[\[PENDENTE[^\]]*\]\]/', $markdown, $m)) {
                $this->block(sprintf('Há %d marcador(es) [[PENDENTE]] no texto.', count($m[0])));
            }
        }

        // 10. CPF do contratante inválido
        foreach ($this->contractantes($complementary) as $i => $c) {
            $cpf = preg_replace('/\D/', '', (string)($c['cpf'] ?? ''));
            $nome = trim((string)($c['nome'] ?? ''));
            if ($cpf !== '' && !$this->validCpf($cpf)) {
                $this->block(sprintf('CPF inválido para o contratante%s.', $nome !== '' ? " ({$nome})" : ' ' . ($i + 1)));
            }
        }

        // 11. Data de assinatura anterior à data da proposta
        $dataProposta = $this->date($proposal['capa']['data'] ?? null);
        $dataAssin = $this->date($complementary['assinatura']['data'] ?? null);
        if ($dataProposta && $dataAssin && $dataAssin < $dataProposta) {
            $this->block('Data de assinatura é anterior à data da proposta.');
        }

        // 12. Proposta com mais de 30 dias (alerta, não bloqueio)
        if ($dataProposta) {
            $hoje = new \DateTimeImmutable('today');
            $dias = (int)$dataProposta->diff($hoje)->format('%a');
            if ($hoje > $dataProposta && $dias > 30) {
                $this->warn(sprintf('Proposta com %d dias — validade possivelmente vencida.', $dias));
            }
        }

        return [
            'blocked' => $this->hasBlock(),
            'issues'  => $this->issues,
        ];
    }

    // ---------------------------------------------------------------
    // Helpers públicos reutilizáveis
    // ---------------------------------------------------------------

    public function validCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int)$cpf[$i] * (($t + 1) - $i);
            }
            $d = ((10 * $sum) % 11) % 10;
            if ((int)$cpf[$t] !== $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * Número por extenso em reais (pt-BR).
     */
    public function numberToWords(float $value): string
    {
        $inteiro = (int)floor($value);
        $centavos = (int)round(($value - $inteiro) * 100);

        $texto = $this->intToWords($inteiro) . ' ' . ($inteiro === 1 ? 'real' : 'reais');
        if ($centavos > 0) {
            $texto .= ' e ' . $this->intToWords($centavos) . ' ' . ($centavos === 1 ? 'centavo' : 'centavos');
        }
        return $texto;
    }

    // ---------------------------------------------------------------
    // Internos
    // ---------------------------------------------------------------

    private function contractantes(array $complementary): array
    {
        if (!empty($complementary['contratantes']) && is_array($complementary['contratantes'])) {
            return $complementary['contratantes'];
        }
        if (!empty($complementary['contratante']) && is_array($complementary['contratante'])) {
            return [$complementary['contratante']];
        }
        return [];
    }

    private function block(string $msg): void { $this->issues[] = ['level' => 'block', 'message' => $msg]; }
    private function warn(string $msg): void { $this->issues[] = ['level' => 'warn', 'message' => $msg]; }
    private function hasBlock(): bool
    {
        foreach ($this->issues as $i) {
            if ($i['level'] === 'block') return true;
        }
        return false;
    }

    private function num($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v;
        // aceita "1.234,56", "40,00%", "R$ 350.000,00"
        $s = preg_replace('/[^\d,.\-]/', '', (string)$v);
        // se tem vírgula, assume formato pt-BR
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? (float)$s : 0.0;
    }

    private function diff(float $a, float $b): float { return abs($a - $b); }

    private function money(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }

    private function date($v): ?\DateTimeImmutable
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        foreach (['Y-m-d', 'd/m/Y'] as $fmt) {
            $d = \DateTimeImmutable::createFromFormat($fmt, substr($v, 0, 10));
            if ($d) return $d->setTime(0, 0);
        }
        return null;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = strtr($s, [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
            'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c',
        ]);
        return preg_replace('/\s+/', ' ', $s);
    }

    private function mentions(string $haystack, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') return true;
        $h = $this->normalize($haystack);
        // casa se qualquer palavra significativa (>3 letras) do grupo aparecer
        foreach (preg_split('/\s+/', $this->normalize($needle)) as $word) {
            if (mb_strlen($word) > 3 && str_contains($h, $word)) {
                return true;
            }
        }
        return str_contains($h, $this->normalize($needle));
    }

    private function extractClause(string $markdown, string $from, string $to): string
    {
        $pat = '/' . preg_quote($from, '/') . '(.*?)' . preg_quote($to, '/') . '/s';
        if (preg_match($pat, $markdown, $m)) {
            return $m[1];
        }
        return '';
    }

    private function firstWords(string $text, int $n): string
    {
        $parts = preg_split('/\s+/', trim($text));
        return implode(' ', array_slice($parts, 0, $n));
    }

    private function intToWords(int $n): string
    {
        if ($n === 0) return 'zero';
        if ($n < 0) return 'menos ' . $this->intToWords(-$n);

        $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove',
            'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos',
            'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        $parts = [];
        $escalas = [
            1000000000 => ['bilhão', 'bilhões'],
            1000000    => ['milhão', 'milhões'],
            1000       => ['mil', 'mil'],
        ];

        foreach ($escalas as $valor => $nomes) {
            if ($n >= $valor) {
                $q = intdiv($n, $valor);
                $n %= $valor;
                if ($valor === 1000 && $q === 1) {
                    $parts[] = 'mil';
                } else {
                    $parts[] = $this->intToWords($q) . ' ' . ($q === 1 ? $nomes[0] : $nomes[1]);
                }
            }
        }

        if ($n > 0) {
            if ($n === 100) {
                $parts[] = 'cem';
            } elseif ($n < 20) {
                $parts[] = $unidades[$n];
            } elseif ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                $parts[] = $dezenas[$d] . ($u > 0 ? ' e ' . $unidades[$u] : '');
            } else {
                $c = intdiv($n, 100);
                $r = $n % 100;
                $parts[] = $centenas[$c] . ($r > 0 ? ' e ' . $this->intToWords($r) : '');
            }
        }

        return implode(' e ', $parts);
    }
}
