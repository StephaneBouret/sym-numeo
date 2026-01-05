<?php

namespace App\Services\Numerology\Cap;

final class CapCalculator
{
    // Table pythagoricienne à aligner Excel
    private const LETTER_VALUES = [
        'A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5,'F'=>6,'G'=>7,'H'=>8,'I'=>9,
        'J'=>1,'K'=>2,'L'=>3,'M'=>4,'N'=>5,'O'=>6,'P'=>7,'Q'=>8,'R'=>9,
        'S'=>1,'T'=>2,'U'=>3,'V'=>4,'W'=>5,'X'=>6,'Y'=>7,'Z'=>8,
    ];

    // Maîtres-nombres
    private const MASTER_NUMBERS = [11,22,33,44,55,66,77,88,99];

    public function calculate(string $firstNames, string $lastName, \DateTimeImmutable $birthDate): array
    {
        $identity = $this->normalize($firstNames.' '.$lastName);

        $aspiration = $this->reduce(
            (int)$birthDate->format('j') + (int)$birthDate->format('n') + (int)$birthDate->format('Y')
        );

        $expression = $this->reduce($this->sumLetters($identity));

        // Règle exacte Axe/Point de vigilance
        $axe = $this->reduce($aspiration + $expression);
        $vigilance = $this->reduce(abs($aspiration - $expression));

        return [
            'aspiration' => $aspiration,
            'expression' => $expression,
            'axe' => $axe,
            'vigilance' => $vigilance,
        ];
    }

    private function sumLetters(string $s): int
    {
        $total = 0;
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $ch) {
            if (!isset(self::LETTER_VALUES[$ch])) {
                continue;
            }
            $total += self::LETTER_VALUES[$ch];
        }
        return $total;
    }

    private function reduce(int $n): int
    {
        while (true) {
            if ($n < 10 || in_array($n, self::MASTER_NUMBERS, true)) {
                return $n;
            }
            $n = array_sum(array_map('intval', str_split((string)$n)));
        }
    }

    private function normalize(string $s): string
    {
        $s = trim($s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = strtoupper($s);
        $s = preg_replace("/[^A-Z \-']/", ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
