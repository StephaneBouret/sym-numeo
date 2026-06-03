<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class PasswordResetService
{
    public function __construct(
        protected TokenGeneratorInterface $tokenGenerator,
        protected EntityManagerInterface $em,
        protected UrlGeneratorInterface $urlGenerator,
        protected SendMailService $email
    ) {}

    public function processPasswordReset(User $user): void
    {
        $token = $this->tokenGenerator->generateToken();
        $now = new \DateTimeImmutable();
        $user->setResetToken($token)
            ->setResetTokenCreatedAt($now);
        $this->em->flush();

        $url = $this->urlGenerator->generate('app_reset_pw', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = [
            'url' => $url,
            'user' => $user
        ];

        $this->email->sendMail(
            'Infos de l\'application Sym Numeo',
            $user->getEmail(),
            'Réinitialisation de mot de passe',
            'password_reset',
            $context,
            null
        );
    }

    public function getUserByResetToken(string $token, UserRepository $userRepository): ?User
    {
        $token = trim($token);

        if ('' === $token) {
            return null;
        }

        return $userRepository->findOneBy(['resetToken' => $token]);
    }

    public function isTokenExpired(User $user, int $expirationInHours = 3): bool
    {
        $resetTokenAt = $user->getResetTokenCreatedAt();
        $now = new \DateTimeImmutable();

        if (null === $resetTokenAt) {
           return true;
        }
        return $now > $resetTokenAt->modify("+{$expirationInHours} hour");
    }

    public function updatePassword(User $user, string $plainPassword, UserPasswordHasherInterface $hasher): void
    {
        $hashedPassword = $hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->invalidateTrustedDevices();

        $user->setResetToken(null)
            ->setResetTokenCreatedAt(null);

        $this->em->flush();
    }
}
