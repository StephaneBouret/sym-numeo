<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\ProfessionalLogo;
use App\Entity\ProfessionalProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProfessionalProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(User $user, ProfessionalProfile $profile, ?UploadedFile $logoFile, bool $flush = true): ?ProfessionalProfile
    {
        if (null !== $logoFile) {
            $logo = $profile->getLogo() ?? new ProfessionalLogo();
            $logo->setImageFile($logoFile);
            $profile->setLogo($logo);
        }

        if (!$profile->hasBusinessData()) {
            if (null === $profile->getId()) {
                return null;
            }

            $this->deleteForUser($user, $flush);

            return null;
        }

        $profile->setUser($user);
        $this->em->persist($profile);

        if ($flush) {
            $this->em->flush();
        }

        return $profile;
    }

    public function removeLogo(User $user, bool $flush = true): bool
    {
        $profile = $user->getProfessionalProfile();

        if (!$profile instanceof ProfessionalProfile) {
            return false;
        }

        $logo = $profile->getLogo();

        if (!$logo instanceof ProfessionalLogo) {
            return false;
        }

        $profile->setLogo(null);
        $this->em->remove($logo);

        if ($flush) {
            $this->em->flush();
        }

        return true;
    }

    public function deleteForUser(User $user, bool $flush = true): void
    {
        $profile = $user->getProfessionalProfile();

        if (!$profile instanceof ProfessionalProfile) {
            return;
        }

        $logo = $profile->getLogo();

        if (null !== $logo) {
            $profile->setLogo(null);
            $this->em->remove($logo);
        }

        $user->setProfessionalProfile(null);
        $this->em->remove($profile);

        if ($flush) {
            $this->em->flush();
        }
    }
}
