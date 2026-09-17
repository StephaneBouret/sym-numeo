<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;

final class UserActiveSubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function findActiveForUser(User $user): ?Subscription
    {
        return $this->subscriptionRepository->findActiveForUser($user);
    }

    public function hasActiveSubscription(User $user): bool
    {
        return null !== $this->findActiveForUser($user);
    }
}
