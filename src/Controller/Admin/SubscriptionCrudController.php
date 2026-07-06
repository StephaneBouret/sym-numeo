<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Abonnement')
            ->setEntityLabelInPlural('Abonnements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Abonnements')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l\'abonnement')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'abonnement')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user', 'Utilisateur')
                ->setFormTypeOption('disabled', true),
            EmailField::new('email', 'Email')
                ->setFormTypeOption('disabled', true),
            TextField::new('title', 'Titre')
                ->hideOnForm(),
            TextareaField::new('description', 'Description')
                ->hideOnIndex()
                ->hideOnForm(),
            ChoiceField::new('status', 'Statut')
                ->setChoices(SubscriptionStatus::choices())
                ->renderAsBadges([
                    SubscriptionStatus::PENDING->value => 'info',
                    SubscriptionStatus::ACTIVE->value => 'success',
                    SubscriptionStatus::EXPIRED->value => 'danger',
                    SubscriptionStatus::CANCELLED->value => 'secondary',
                    SubscriptionStatus::SUSPENDED->value => 'warning',
                ])
                ->hideOnForm(),
            MoneyField::new('priceCents', 'Prix')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->setFormTypeOption('disabled', true),
            BooleanField::new('isLifetime', 'À vie')
                ->renderAsSwitch(false)
                ->hideOnForm(),
            DateTimeField::new('startsAt', 'Début')
                ->setFormTypeOption('disabled', true),
            DateTimeField::new('endsAt', 'Fin')
                ->setFormTypeOption('disabled', true),
            TextField::new('paymentReference', 'Référence paiement')
                ->hideOnForm(),
            DateTimeField::new('reminder30SentAt', 'Relance J-30 envoyée')
                ->hideOnForm(),
            DateTimeField::new('reminder15SentAt', 'Relance J-15 envoyée')
                ->hideOnForm(),
            DateTimeField::new('termsAcceptedAt', 'CGV acceptées')
                ->hideOnIndex()
                ->hideOnForm(),
            DateTimeField::new('immediateAccessRequestedAt', 'Accès immédiat demandé')
                ->hideOnIndex()
                ->hideOnForm(),
            DateTimeField::new('withdrawalRightWaivedAt', 'Rétractation renoncée')
                ->hideOnIndex()
                ->hideOnForm(),
            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')
                ->hideOnForm()
                ->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add('status')
            ->add('isLifetime')
            ->add('startsAt')
            ->add('endsAt')
            ->add('createdAt');
    }

    public function configureActions(Actions $actions): Actions
    {
        $grantFreeYear = Action::new('grantFreeYear', 'Offrir 1 an', 'fa fa-gift')
            ->linkToCrudAction('grantFreeYear')
            ->displayIf(static function (Subscription $subscription): bool {
                return SubscriptionStatus::ACTIVE === $subscription->getStatus()
                    || SubscriptionStatus::EXPIRED === $subscription->getStatus()
                    || SubscriptionStatus::SUSPENDED === $subscription->getStatus();
            })
            ->addCssClass('btn btn-info');

        $grantLifetime = Action::new('grantLifetime', 'Offrir à vie', 'fa fa-infinity')
            ->linkToCrudAction('grantLifetime')
            ->displayIf(static function (Subscription $subscription): bool {
                return SubscriptionStatus::ACTIVE === $subscription->getStatus()
                    || SubscriptionStatus::EXPIRED === $subscription->getStatus()
                    || SubscriptionStatus::SUSPENDED === $subscription->getStatus();
            })
            ->addCssClass('btn btn-success');

        $suspendSubscription = Action::new('suspendSubscription', 'Suspendre', 'fa fa-pause')
            ->linkToCrudAction('suspendSubscription')
            ->displayIf(static function (Subscription $subscription): bool {
                return SubscriptionStatus::ACTIVE === $subscription->getStatus();
            })
            ->addCssClass('btn btn-warning');

        $cancelSubscription = Action::new('cancelSubscription', 'Annuler', 'fa fa-ban')
            ->linkToCrudAction('cancelSubscription')
            ->displayIf(static function (Subscription $subscription): bool {
                return SubscriptionStatus::ACTIVE === $subscription->getStatus()
                    || SubscriptionStatus::SUSPENDED === $subscription->getStatus();
            })
            ->addCssClass('btn btn-danger');

        $reactivateSubscription = Action::new('reactivateSubscription', 'Réactiver', 'fa fa-play')
            ->linkToCrudAction('reactivateSubscription')
            ->displayIf(static function (Subscription $subscription): bool {
                return SubscriptionStatus::SUSPENDED === $subscription->getStatus();
            })
            ->addCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, $grantFreeYear)
            ->add(Crud::PAGE_EDIT, $grantLifetime)
            ->add(Crud::PAGE_EDIT, $suspendSubscription)
            ->add(Crud::PAGE_EDIT, $cancelSubscription)
            ->add(Crud::PAGE_EDIT, $reactivateSubscription)
            ->disable(Action::NEW);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Subscription) {
            return;
        }

        if ($entityInstance->isLifetime()) {
            $entityInstance->setEndsAt(null);

            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        if (!$entityInstance->getEndsAt()) {
            $referenceDate = $entityInstance->getStartsAt()
                ?? $entityInstance->getCreatedAt()
                ?? new \DateTimeImmutable();

            $entityInstance->setEndsAt(
                $referenceDate->modify('+1 year')
            );
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function grantFreeYear(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $subscription = $this->getSubscriptionFromContext($context, $subscriptionRepository);

        if (!$subscription instanceof Subscription) {
            return $this->redirectToSubscriptionIndex();
        }

        if (!$this->isStatusAllowed($subscription, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::SUSPENDED,
        ])) {
            $this->addFlash('warning', 'Seuls les abonnements actifs, expirés ou suspendus peuvent bénéficier de ce geste commercial.');

            return $this->redirectToSubscriptionIndex();
        }

        $subscription
            ->setPriceCents(0)
            ->setTitle('Abonnement offert - 1 an')
            ->setDescription('Abonnement offert pour une durée d\'un an dans le cadre d\'un geste commercial.')
            ->activateForOneYear(new \DateTimeImmutable(), 'COMMERCIAL_GESTURE');

        $entityManager->flush();

        $this->addFlash('success', 'L\'abonnement a été basculé en gratuité d\'un an.');

        return $this->redirectToSubscriptionIndex();
    }

    public function grantLifetime(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $subscription = $this->getSubscriptionFromContext($context, $subscriptionRepository);

        if (!$subscription instanceof Subscription) {
            return $this->redirectToSubscriptionIndex();
        }

        if (!$this->isStatusAllowed($subscription, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::SUSPENDED,
        ])) {
            $this->addFlash('warning', 'Seuls les abonnements actifs, expirés ou suspendus peuvent bénéficier de ce geste commercial.');

            return $this->redirectToSubscriptionIndex();
        }

        $subscription
            ->setPriceCents(0)
            ->setTitle('Abonnement offert - À vie')
            ->setDescription('Abonnement offert à vie dans le cadre d\'un geste commercial.')
            ->activateLifetime('COMMERCIAL_GESTURE');

        $entityManager->flush();

        $this->addFlash('success', 'L\'abonnement a été basculé en gratuité à vie.');

        return $this->redirectToSubscriptionIndex();
    }

    public function suspendSubscription(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $subscription = $this->getSubscriptionFromContext($context, $subscriptionRepository);

        if (!$subscription instanceof Subscription) {
            return $this->redirectToSubscriptionIndex();
        }

        if (!$this->isStatusAllowed($subscription, [SubscriptionStatus::ACTIVE])) {
            $this->addFlash('warning', 'Seuls les abonnements actifs peuvent être suspendus.');

            return $this->redirectToSubscriptionIndex();
        }

        $subscription->suspend();

        $entityManager->flush();

        $this->addFlash('success', 'L\'abonnement a été suspendu.');

        return $this->redirectToSubscriptionIndex();
    }

    public function cancelSubscription(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $subscription = $this->getSubscriptionFromContext($context, $subscriptionRepository);

        if (!$subscription instanceof Subscription) {
            return $this->redirectToSubscriptionIndex();
        }

        if (!$this->isStatusAllowed($subscription, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::SUSPENDED,
        ])) {
            $this->addFlash('warning', 'Seuls les abonnements actifs ou suspendus peuvent être annulés.');

            return $this->redirectToSubscriptionIndex();
        }

        $subscription->cancel();

        $entityManager->flush();

        $this->addFlash('success', 'L\'abonnement a été annulé.');

        return $this->redirectToSubscriptionIndex();
    }

    public function reactivateSubscription(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $subscription = $this->getSubscriptionFromContext($context, $subscriptionRepository);

        if (!$subscription instanceof Subscription) {
            return $this->redirectToSubscriptionIndex();
        }

        if (!$this->isStatusAllowed($subscription, [SubscriptionStatus::SUSPENDED])) {
            $this->addFlash('warning', 'Seuls les abonnements suspendus peuvent être réactivés.');

            return $this->redirectToSubscriptionIndex();
        }

        if (
            !$subscription->isLifetime()
            && null !== $subscription->getEndsAt()
            && $subscription->getEndsAt() < new \DateTimeImmutable()
        ) {
            $this->addFlash('warning', 'Cet abonnement est arrivé à expiration pendant sa suspension. Il faut plutôt offrir 1 an ou un accès à vie.');

            return $this->redirectToSubscriptionIndex();
        }

        $subscription->reactivate();

        $entityManager->flush();

        $this->addFlash('success', 'L\'abonnement a été annulé.');

        return $this->redirectToSubscriptionIndex();
    }

    private function getSubscriptionFromContext(
        AdminContext $context,
        SubscriptionRepository $subscriptionRepository,
    ): ?Subscription {
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            $this->addFlash('warning', 'Abonnement introuvable.');

            return null;
        }

        $subscription = $subscriptionRepository->find($entityId);

        if (!$subscription instanceof Subscription) {
            $this->addFlash('warning', 'Abonnement introuvable.');

            return null;
        }

        return $subscription;
    }

    /**
     * @param SubscriptionStatus[] $allowedStatuses
     */
    private function isStatusAllowed(Subscription $subscription, array $allowedStatuses): bool
    {
        return in_array($subscription->getStatus(), $allowedStatuses, true);
    }

    private function redirectToSubscriptionIndex(): RedirectResponse
    {
        return $this->redirectToRoute('admin_subscription_index');
    }
}
