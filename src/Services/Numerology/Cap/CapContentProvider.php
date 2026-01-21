<?php

declare(strict_types=1);

namespace App\Services\Numerology\Cap;

use Symfony\Component\HttpKernel\KernelInterface;

final class CapContentProvider
{
    private array $data;

    public function __construct(KernelInterface $kernel)
    {
        $path = $kernel->getProjectDir() . '/assets/data/cap_fr.json';

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException('Impossible de lire cap_fr.json');
        }

        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $this->data = $data;
    }

    public function getPair(int $aspiration, int $expression): ?array
    {
        $key = $aspiration . '-' . $expression;
        return $this->data['pairs'][$key] ?? null;
    }

    public function getAspiration(int $n): ?string
    {
        return $this->data['aspirations'][(string) $n] ?? null;
    }

    public function getExpression(int $n): ?string
    {
        return $this->data['expressions'][(string) $n] ?? null;
    }

    public function getPairParagraphs(int $aspiration, int $expression) : ?array
    {
        $pair = $this->getPair($aspiration, $expression);
        if (!$pair) {
            return null;
        }

        $text = $pair['text'] ?? null;

        if (is_string($text)) {
            return preg_split("/\R\R+/", trim($text)) ?: null;
        }

        if (is_array($text)) {
            return array_values(array_filter(array_map('trim', $text), fn($p) => $p !== ''));
        }

        return null;
    }
}
