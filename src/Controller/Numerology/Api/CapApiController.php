<?php

namespace App\Controller\Numerology\Api;

use App\Services\Numerology\Cap\CapCalculator;
use App\Services\Numerology\Cap\CapContentProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CapApiController extends AbstractController
{
    #[Route('/api/cap', name: 'api_cap', methods: ['POST'])]
    public function __invoke(
        Request $request,
        ValidatorInterface $validator,
        CapCalculator $calculator,
        CapContentProvider $contentProvider
    ): JsonResponse {
        $payload = json_decode($request->getContent() ?: '[]', true);

        $firstNames = (string)($payload['firstNames'] ?? '');
        $lastName   = (string)($payload['lastName'] ?? '');
        $birthDate  = (string)($payload['birthDate'] ?? '');

        if ($firstNames === '' || $lastName === '' || $birthDate === '') {
            return $this->json(['ok' => false, 'error' => 'Champs manquants.'], 422);
        }

        try {
            $dt = new \DateTimeImmutable($birthDate);
        } catch (\Throwable) {
            return $this->json(['ok' => false, 'error' => 'Date invalide.'], 422);
        }

        $result = $calculator->calculate($firstNames, $lastName, $dt);

        $a = (int) ($result['aspiration'] ?? 0);
        $e = (int) ($result['expression'] ?? 0);

        $aspirationText = $contentProvider->getAspiration($a);
        $expressionText = $contentProvider->getExpression($e);

        // Paire
        $pair = $contentProvider->getPair($a, $e);
        $pairParagraphs = $contentProvider->getPairParagraphs($a, $e);

        return $this->json([
            'ok' => true,
            'data' => $result,
            'content' => [
                'aspiration' => $aspirationText,
                'expression' => $expressionText,
                'pair' => [
                    'exists' => $pair !== null,
                    'axe' => $pair['axe'] ?? null,
                    'equilibriumKey' => $pair['equilibriumKey'] ?? null,
                    'paragraphs' => $pairParagraphs,
                ],
            ],
        ]);
    }
}
