<?php

namespace App\Command;

use App\Repository\SubscriptionRepository;
use App\Services\SubscriptionReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:subscription:send-expiration-reminders',
    description: 'Envoie les relances avant expiration des abonnements annuels.'
)]
final class SendSubscriptionExpirationRemindersCommand extends Command
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly SubscriptionReminderService $subscriptionReminderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $total = 0;

        foreach ([30, 15] as $days) {
            $subscriptions = $this->subscriptionRepository->findSubscriptionsToRemindInDays($days);

            foreach ($subscriptions as $subscription) {
                $this->subscriptionReminderService->sendReminder($subscription, $days);
                ++$total;

                $output->writeln(sprintf(
                    'Relance J-%d envoyée à %s.',
                    $days,
                    $subscription->getEmail()
                ));

                sleep(5);
            }
        }

        $output->writeln(sprintf('%d relance(s) envoyée(s).', $total));

        return Command::SUCCESS;
    }
}
