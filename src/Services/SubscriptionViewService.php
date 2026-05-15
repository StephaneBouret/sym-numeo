<?php

namespace App\Services;

use App\Entity\Subscription;

class SubscriptionViewService
{
    public function build(?Subscription $subscription): array
    {
        if (!$subscription) {
            return [
                'remainingDays' => null,
                'progress' => 0,
                'isLifetime' => false,
            ];
        }

        if ($subscription->isLifetime()) {
            return [
                'remainingDays' => null,
                'progress' => 100,
                'isLifetime' => true,
            ];
        }

        $startsAt = $subscription->getStartsAt();
        $endsAt = $subscription->getEndsAt();

        if (!$startsAt || !$endsAt) {
            return [
                'remainingDays' => null,
                'progress' => 0,
                'isLifetime' => false,
            ];
        }

        $today = new \DateTimeImmutable('today');

        $startDay = $startsAt->setTime(0, 0, 0);
        $endDay = $endsAt->setTime(0, 0, 0);

        $totalDays = max(1, $startDay->diff($endDay)->days);
        $elapsedDays = min($totalDays, $startDay->diff($today)->days);
        $remainingDays = max(0, $today->diff($endDay)->days);

        $progress = (int) round(($elapsedDays / $totalDays) * 100);

        return [
            'remainingDays' => $remainingDays,
            'progress' => $progress,
            'isLifetime' => false,
        ];
    }
}
