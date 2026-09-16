<?php

namespace App\Services;

/**
 * Paginação da revista feita NO SERVIDOR (determinística).
 *
 * Motivação: a paginação por JavaScript media a altura do conteúdo no
 * navegador e cortava em pontos diferentes entre Chrome (Android) e Safari
 * (iPhone). Calculando a quebra no PHP, todos os dispositivos recebem o MESMO
 * HTML já dividido — não há medição client-side nem divergência por navegador.
 *
 * A estimativa é baseada nas métricas reais do CSS da folha (595x842):
 *  - Fonte do corpo ~11.5px, line-height 1.75  → ~20px por linha
 *  - Largura útil do texto na coluna do convidado ~515px → ~90 chars por linha
 *  - Cada parágrafo tem margin-bottom ~10px
 * Os números são propositalmente conservadores para nunca estourar a folha.
 */
class MagazinePaginator
{
    /** Altura útil de uma folha (842) menos o padding vertical (~80). */
    // Valores propositalmente CONSERVADORES. O Safari iOS renderiza o texto um
    // pouco mais alto que o Chrome/Android; se a estimativa ficar no limite, a
    // última linha vaza e o overflow:hidden corta no iPhone. Superestimando a
    // altura (menos texto por página, com folga embaixo) a divisão nunca corta
    // em nenhum navegador — e continua idêntica entre os aparelhos.
    private const USABLE_HEIGHT = 680;

    /** Altura de uma linha (line-height real ~20px, superestimado p/ folga). */
    private const LINE_HEIGHT = 21;

    /** Espaço abaixo de cada parágrafo (margin-bottom, superestimado). */
    private const PARAGRAPH_SPACING = 12;

    /** Caracteres por linha (subestimado → parágrafo conta mais linhas). */
    private const CHARS_PER_LINE = 82;

    /**
     * Divide uma lista de parágrafos em páginas, respeitando a altura
     * disponível na PRIMEIRA página (menor, por causa do cabeçalho/autor) e
     * nas páginas seguintes (folha cheia).
     *
     * @param string[] $paragraphs   Parágrafos já limpos (sem linhas vazias).
     * @param int      $firstPageReserved  Altura já ocupada na 1ª página por
     *                                     cabeçalho, label, foto do autor, etc.
     * @return string[][]  Cada item é um array de parágrafos daquela página.
     */
    public static function paginateParagraphs(array $paragraphs, int $firstPageReserved = 0, int $lastPageReserved = 0): array
    {
        $paragraphs = array_values(array_filter(
            array_map('trim', $paragraphs),
            static fn ($p) => $p !== ''
        ));

        if (empty($paragraphs)) {
            return [];
        }

        $pages = [];
        $current = [];
        $available = self::USABLE_HEIGHT - max(0, $firstPageReserved);
        // Garante um mínimo razoável de espaço na 1ª página.
        if ($available < 200) {
            $available = 200;
        }
        $usedHeight = 0;

        $count = count($paragraphs);
        foreach ($paragraphs as $index => $paragraph) {
            $height = self::estimateParagraphHeight($paragraph);

            // Se não cabe na página atual (e já há algo nela), fecha a página.
            if ($current && ($usedHeight + $height) > $available) {
                $pages[] = $current;
                $current = [];
                $usedHeight = 0;
                $available = self::USABLE_HEIGHT; // páginas seguintes: folha cheia
            }

            // Parágrafo maior que uma folha inteira: quebra por linhas de texto.
            if ($height > $available && empty($current)) {
                foreach (self::splitLongParagraph($paragraph, $available) as $chunk) {
                    $pages[] = [$chunk];
                }
                $available = self::USABLE_HEIGHT;
                $usedHeight = 0;
                continue;
            }

            $current[] = $paragraph;
            $usedHeight += $height;
        }

        if ($current) {
            $pages[] = $current;
        }

        // Reserva na ÚLTIMA página (ex.: imagens no rodapé do layout). Se o que
        // sobrou não cabe junto com o bloco reservado, cria uma página extra
        // vazia de texto para o bloco reservado descer sozinho.
        if ($lastPageReserved > 0 && !empty($pages)) {
            $lastIdx = count($pages) - 1;
            $lastHeight = 0;
            foreach ($pages[$lastIdx] as $p) {
                $lastHeight += self::estimateParagraphHeight($p);
            }
            if (($lastHeight + $lastPageReserved) > self::USABLE_HEIGHT) {
                $pages[] = []; // bloco reservado (imagens) vai numa folha própria
            }
        }

        return $pages;
    }

    /**
     * Altura útil de uma folha (para o chamador calcular reservas).
     */
    public static function usableHeight(): int
    {
        return self::USABLE_HEIGHT;
    }

    /**
     * Estima a altura (px) de um parágrafo pelo número de linhas que ele ocupa.
     */
    public static function estimateParagraphHeight(string $paragraph): int
    {
        $length = self::visualLength($paragraph);
        $lines = (int) max(1, ceil($length / self::CHARS_PER_LINE));
        return ($lines * self::LINE_HEIGHT) + self::PARAGRAPH_SPACING;
    }

    /**
     * Quebra um parágrafo excepcionalmente longo em pedaços que cabem numa
     * folha, cortando em espaços (não no meio de palavras).
     *
     * @return string[]
     */
    private static function splitLongParagraph(string $paragraph, int $available): array
    {
        $maxLines = (int) max(1, floor(($available - self::PARAGRAPH_SPACING) / self::LINE_HEIGHT));
        $maxChars = $maxLines * self::CHARS_PER_LINE;

        $words = preg_split('/\s+/', $paragraph) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($words as $word) {
            $candidate = $buffer === '' ? $word : $buffer . ' ' . $word;
            if (self::visualLength($candidate) > $maxChars && $buffer !== '') {
                $chunks[] = $buffer;
                $buffer = $word;
            } else {
                $buffer = $candidate;
            }
        }
        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks ?: [$paragraph];
    }

    /**
     * Comprimento "visual" do texto (multibyte), usado para estimar linhas.
     */
    private static function visualLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }
}
