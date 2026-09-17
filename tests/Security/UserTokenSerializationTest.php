<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\ProfessionalLogo;
use App\Entity\ProfessionalProfile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class UserTokenSerializationTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        parent::tearDown();
    }

    public function testPostAuthenticationTokenCanBeSerializedWhenProfessionalLogoHasFileObject(): void
    {
        $user = $this->createUserWithProfessionalLogoFile();

        $this->assertFaultyGraphIsPresent($user);

        $token = new PostAuthenticationToken($user, 'main', ['ROLE_USER']);

        $serializedToken = serialize($token);

        self::assertNotSame('', $serializedToken);
    }

    public function testTwoFactorTokenCanBeSerializedWhenWrappedTokenUserHasProfessionalLogoFileObject(): void
    {
        $user = $this->createUserWithProfessionalLogoFile();

        $this->assertFaultyGraphIsPresent($user);

        $authenticatedToken = new PostAuthenticationToken($user, 'main', ['ROLE_USER']);
        $twoFactorToken = new TwoFactorToken($authenticatedToken, null, 'main', ['email']);

        $serializedToken = serialize($twoFactorToken);

        self::assertNotSame('', $serializedToken);
    }

    private function createUserWithProfessionalLogoFile(): User
    {
        $user = (new User())
            ->setEmail('token-serialization@example.test')
            ->setFirstname('Camille')
            ->setLastname('Martin')
            ->setPassword('test-password-hash')
        ;

        $profile = new ProfessionalProfile($user);

        $logo = new ProfessionalLogo();
        $logo->setImageFile(new File($this->createTemporaryPng()));
        $logo->setImageName('logo-token-test.png');

        $profile->setLogo($logo);

        return $user;
    }

    private function assertFaultyGraphIsPresent(User $user): void
    {
        $profile = $user->getProfessionalProfile();

        self::assertInstanceOf(ProfessionalProfile::class, $profile);
        self::assertSame($user, $profile->getUser());

        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);
        self::assertSame($profile, $logo->getProfessionalProfile());
        self::assertInstanceOf(File::class, $logo->getImageFile());
    }

    private function createTemporaryPng(): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'token-logo');

        if (false === $temporaryFile) {
            throw new \RuntimeException('Impossible de créer le fichier temporaire du logo.');
        }

        $pngPath = $temporaryFile.'.png';

        if (!rename($temporaryFile, $pngPath)) {
            throw new \RuntimeException('Impossible de préparer le fichier PNG temporaire.');
        }

        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true,
        );

        if (false === $contents) {
            throw new \RuntimeException('Le contenu PNG de test est invalide.');
        }

        if (false === file_put_contents($pngPath, $contents)) {
            throw new \RuntimeException('Impossible d\'écrire le fichier PNG temporaire.');
        }

        $this->temporaryFiles[] = $pngPath;

        return $pngPath;
    }
}
