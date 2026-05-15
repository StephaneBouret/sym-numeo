<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findBlockingSubscriptionForUser(User $user): ?Subscription
    {
        $subscriptions = $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($subscriptions as $subscription) {
            // Bloquant si actif (inclut lifetime)
            if ($subscription->isActive()) {
                return $subscription;
            }

            // Bloquant si suspendu
            if ($subscription->isSuspended()) {
                return $subscription;
            }
        }

        return null;
    }

    public function findLatestPendingForUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SubscriptionStatus::PENDING)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCurrentForUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->orderBy('s.endsAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Subscription[]
     */
    public function findSubscriptionsToRemindInDays(int $days): array
    {
        $now = new \DateTimeImmutable('today');
        $targetStart = $now->modify(sprintf('+%d days', $days))->setTime(0, 0);
        $targetEnd = $now->modify(sprintf('+%d days', $days))->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.isLifetime = false')
            ->andWhere('s.endsAt BETWEEN :start AND :end')
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('start', $targetStart)
            ->setParameter('end', $targetEnd)
            ->orderBy('s.endsAt', 'ASC');

        if ($days === 30) {
            $qb->andWhere('s.reminder30SentAt IS NULL');
        }

        if ($days === 15) {
            $qb->andWhere('s.reminder15SentAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Subscription[]
     */
    public function findExpiredAnnualSubscriptions(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.isLifetime = false')
            ->andWhere('s.endsAt IS NOT NULL')
            ->andWhere('s.endsAt < :now')
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('now', $now)
            ->orderBy('s.endsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveLifetimeOrSuspendedForUser(User $user): ?Subscription
    {
        $subscriptions = $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($subscriptions as $subscription) {
            if ($subscription->isSuspended()) {
                return $subscription;
            }

            if ($subscription->isActive() && $subscription->isLifetime()) {
                return $subscription;
            }
        }

        return null;
    }

    //    /**
    //     * @return Subscription[] Returns an array of Subscription objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Subscription
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
