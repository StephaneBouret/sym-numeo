<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\ProfessionalLogo;
use App\Entity\ProfessionalProfile;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PractitionerSpaceControllerTest extends WebTestCase
{
    /** @var list<string> */
    private array $createdUserEmails = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    /** @var list<string> */
    private array $uploadedLogoNamesToRemove = [];

    private static int $emailSequence = 0;

    protected function tearDown(): void
    {
        $this->cleanupDatabase();
        $this->cleanupFiles();

        parent::tearDown();
    }

    public function testFirstLogoUploadPersistsFileAndKeepsSessionUsable(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('first-upload');
        $client->loginUser($user, 'main');

        $filesBeforeUpload = $this->listUploadFileNames();

        $crawler = $client->request('GET', '/mon-espace');
        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_practitioner_space');

        $form = $crawler->selectButton('Enregistrer')->form([
            'professional_profile_form[professionalName]' => 'Cabinet Regression',
            'professional_profile_form[legalName]' => 'Cabinet Regression SAS',
            'professional_profile_form[siret]' => '12345678901234',
            'professional_profile_form[email]' => 'cabinet-regression@example.test',
            'professional_profile_form[address]' => '12 rue des Tests',
            'professional_profile_form[postalCode]' => '75001',
            'professional_profile_form[city]' => 'Paris',
            'professional_profile_form[websiteUrl]' => 'https://www.example.test',
        ]);
        $imageFileField = $form['professional_profile_form[logo][imageFile]'];

        self::assertInstanceOf(FileFormField::class, $imageFileField);

        $imageFileField->upload(
            $this->createTemporaryPng('first-logo')
        );

        $client->submit($form);

        self::assertResponseRedirects('/mon-espace');

        $reloadedUser = $this->reloadUser($user);
        $profile = $reloadedUser->getProfessionalProfile();

        self::assertInstanceOf(ProfessionalProfile::class, $profile);
        self::assertSame('Cabinet Regression', $profile->getProfessionalName());

        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);

        $imageName = $this->rememberLogoFile($logo);

        self::assertFileExists($this->professionalLogoPath($imageName));

        $this->assertUploadDirectoryContainsSameFiles([
            ...$filesBeforeUpload,
            $imageName,
        ]);

        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testLogoReplacementPersistsNewFileDeletesOldFileAndKeepsSessionUsable(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('replacement');
        $oldProfile = $this->createProfileWithLogo($user, 'old-logo.png');
        $oldLogo = $oldProfile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $oldLogo);

        $oldImageName = $this->rememberLogoFile($oldLogo);
        $filesBeforeReplacement = $this->listUploadFileNames();

        $client->loginUser($user, 'main');

        $crawler = $client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_practitioner_space');

        $form = $crawler->selectButton('Enregistrer')->form([
            'professional_profile_form[professionalName]' => 'Cabinet Remplace',
            'professional_profile_form[legalName]' => 'Cabinet Remplace SAS',
            'professional_profile_form[siret]' => '12345678901234',
            'professional_profile_form[email]' => 'cabinet-remplace@example.test',
            'professional_profile_form[address]' => '12 rue des Tests',
            'professional_profile_form[postalCode]' => '75001',
            'professional_profile_form[city]' => 'Paris',
            'professional_profile_form[websiteUrl]' => 'https://www.remplace.example.test',
        ]);

        $imageFileField = $form['professional_profile_form[logo][imageFile]'];

        self::assertInstanceOf(FileFormField::class, $imageFileField);

        $imageFileField->upload(
            $this->createTemporaryPng('new-logo')
        );

        $client->submit($form);

        self::assertResponseRedirects('/mon-espace');

        $reloadedUser = $this->reloadUser($user);
        $profile = $reloadedUser->getProfessionalProfile();

        self::assertInstanceOf(ProfessionalProfile::class, $profile);

        $newLogo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $newLogo);

        $newImageName = $this->rememberLogoFile($newLogo);

        self::assertNotSame($oldImageName, $newImageName);
        self::assertFileExists($this->professionalLogoPath($newImageName));
        self::assertFileDoesNotExist($this->professionalLogoPath($oldImageName));

        $logos = $this->entityManager()
            ->getRepository(ProfessionalLogo::class)
            ->findBy([
                'professionalProfile' => $profile,
            ]);

        self::assertCount(1, $logos);
        self::assertSame($newImageName, $logos[0]->getImageName());

        $expectedFiles = array_values(
            array_diff($filesBeforeReplacement, [$oldImageName])
        );
        $expectedFiles[] = $newImageName;

        $this->assertUploadDirectoryContainsSameFiles($expectedFiles);

        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testLogoDeleteRemovesAssociationEntityAndFile(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('delete-success');
        $profile = $this->createProfileWithLogo($user, 'logo-to-delete.png');
        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);

        $logoId = $this->persistedLogoId($logo);
        $imageName = $this->rememberLogoFile($logo);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->loginUser($user, 'main');
        $csrfToken = $this->renderedDeleteLogoToken($client);

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'success' => true,
            'message' => 'Le logo professionnel a bien été supprimé.',
        ], $this->jsonPayload($client));

        $reloadedUser = $this->reloadUser($user);
        $profile = $reloadedUser->getProfessionalProfile();

        self::assertInstanceOf(ProfessionalProfile::class, $profile);
        self::assertNull($profile->getLogo());
        self::assertNull($this->entityManager()->getRepository(ProfessionalLogo::class)->find($logoId));
        self::assertFileDoesNotExist($this->professionalLogoPath($imageName));

        $this->assertUploadDirectoryContainsSameFiles(array_values(array_diff($filesBeforeDelete, [$imageName])));

        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testLogoDeleteRejectsInvalidCsrfTokenWithoutChangingLogo(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('delete-invalid-csrf');
        $profile = $this->createProfileWithLogo($user, 'logo-invalid-csrf.png');
        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);

        $userId = $this->persistedUserId($user);
        $logoId = $this->persistedLogoId($logo);
        $imageName = $this->rememberLogoFile($logo);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->loginUser($user, 'main');

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => 'invalid-token',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([
            'success' => false,
            'message' => 'Le jeton de sécurité est invalide.',
        ], $this->jsonPayload($client));

        $this->assertPersistedLogoIsIntact($userId, $logoId, $imageName);
        $this->assertUploadDirectoryContainsSameFiles($filesBeforeDelete);
        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testAnonymousUserCannotDeleteLogo(): void
    {
        $client = $this->createBrowser();
        $client->catchExceptions(true);
        $user = $this->createUser('delete-anonymous');
        $profile = $this->createProfileWithLogo($user, 'logo-anonymous.png');
        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);

        $userId = $this->persistedUserId($user);
        $logoId = $this->persistedLogoId($logo);
        $imageName = $this->rememberLogoFile($logo);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => 'invalid-token',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseRedirects('/login');

        $this->assertPersistedLogoIsIntact($userId, $logoId, $imageName);
        $this->assertUploadDirectoryContainsSameFiles($filesBeforeDelete);
    }

    public function testLogoDeleteRequiresActiveSubscriptionWithoutChangingLogo(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('without-active-subscription');
        $profile = $this->createProfileWithLogo($user, 'logo-without-subscription.png');
        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);

        $userId = $this->persistedUserId($user);
        $logoId = $this->persistedLogoId($logo);
        $imageName = $this->rememberLogoFile($logo);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->loginUser($user, 'main');
        $csrfToken = $this->renderedDeleteLogoToken($client);

        $this->cancelUserSubscriptions($user);

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([
            'success' => false,
            'message' => 'Un abonnement actif est nécessaire.',
        ], $this->jsonPayload($client));

        $this->assertPersistedLogoIsIntact($userId, $logoId, $imageName);
        $this->assertUploadDirectoryContainsSameFiles($filesBeforeDelete);
        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testLogoDeleteWithoutLogoReturnsNotFoundAndKeepsProfile(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('delete-without-logo');
        $this->createProfileWithoutLogo($user);

        $userId = $this->persistedUserId($user);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->loginUser($user, 'main');
        $csrfToken = $this->renderedDeleteLogoToken($client);

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([
            'success' => false,
            'message' => 'Aucun logo professionnel n\'est enregistré.',
        ], $this->jsonPayload($client));

        $this->assertUserProfileStillExistsWithoutLogo($userId);
        $this->assertUploadDirectoryContainsSameFiles($filesBeforeDelete);
        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testLogoDeleteOnlyTargetsAuthenticatedUserProfile(): void
    {
        $client = $this->createBrowser();
        $authenticatedUser = $this->createUser('delete-authenticated-user');
        $authenticatedProfile = $this->createProfileWithLogo($authenticatedUser, 'authenticated-user-logo.png');
        $authenticatedLogo = $authenticatedProfile->getLogo();

        $otherUser = $this->createUser('delete-other-user');
        $otherProfile = $this->createProfileWithLogo($otherUser, 'other-user-logo.png');
        $otherLogo = $otherProfile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $authenticatedLogo);
        self::assertInstanceOf(ProfessionalLogo::class, $otherLogo);

        $authenticatedLogoId = $this->persistedLogoId($authenticatedLogo);
        $authenticatedImageName = $this->rememberLogoFile($authenticatedLogo);
        $otherUserId = $this->persistedUserId($otherUser);
        $otherLogoId = $this->persistedLogoId($otherLogo);
        $otherImageName = $this->rememberLogoFile($otherLogo);
        $filesBeforeDelete = $this->listUploadFileNames();

        $client->loginUser($authenticatedUser, 'main');
        $csrfToken = $this->renderedDeleteLogoToken($client);

        $client->request('DELETE', '/mon-espace/logo', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'success' => true,
            'message' => 'Le logo professionnel a bien été supprimé.',
        ], $this->jsonPayload($client));

        self::assertNull($this->entityManager()->getRepository(ProfessionalLogo::class)->find($authenticatedLogoId));
        self::assertFileDoesNotExist($this->professionalLogoPath($authenticatedImageName));

        $this->assertPersistedLogoIsIntact($otherUserId, $otherLogoId, $otherImageName);
        $this->assertUploadDirectoryContainsSameFiles(array_values(array_diff(
            $filesBeforeDelete,
            [$authenticatedImageName],
        )));
        $this->assertAuthenticatedProfilePageIsStillAccessible($client);
    }

    public function testPractitionerSpaceRendersExpectedStimulusDeleteContract(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('stimulus-contract');
        $profile = $this->createProfileWithLogo($user, 'stimulus-contract-logo.png');
        $logo = $profile->getLogo();

        self::assertInstanceOf(ProfessionalLogo::class, $logo);
        $this->rememberLogoFile($logo);

        $client->loginUser($user, 'main');
        $crawler = $client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_practitioner_space');

        $root = $this->professionalPreviewRoot($crawler);
        $csrfToken = $root->attr('data-professional-preview-delete-logo-token-value');

        self::assertSame('/mon-espace/logo', $root->attr('data-professional-preview-delete-logo-url-value'));
        self::assertIsString($csrfToken);
        self::assertNotSame('', $csrfToken);

        $deleteSection = $crawler->filter('[data-professional-preview-target="deleteLogoSection"]');
        self::assertCount(1, $deleteSection);
        self::assertNull($deleteSection->attr('hidden'));

        $deleteButton = $crawler->filter('button[data-professional-preview-target="deleteLogoButton"]');
        self::assertCount(1, $deleteButton);
        self::assertSame('professional-preview#deleteLogo', $deleteButton->attr('data-action'));
        self::assertSame('professional-logo-delete-status', $deleteButton->attr('aria-describedby'));

        $deleteStatus = $crawler->filter('#professional-logo-delete-status');
        self::assertCount(1, $deleteStatus);
        self::assertSame('status', $deleteStatus->attr('role'));
        self::assertSame('polite', $deleteStatus->attr('aria-live'));

        $logoInput = $crawler->filter('input[name="professional_profile_form[logo][imageFile]"]');
        self::assertCount(1, $logoInput);
        self::assertSame('logoInput', $logoInput->attr('data-professional-preview-target'));
        self::assertSame('change->professional-preview#changeLogo', $logoInput->attr('data-action'));
    }

    public function testPractitionerSpaceHidesDeleteSectionWithoutLogo(): void
    {
        $client = $this->createBrowser();
        $user = $this->createUser('stimulus-contract-without-logo');
        $this->createProfileWithoutLogo($user);

        $client->loginUser($user, 'main');
        $crawler = $client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_practitioner_space');

        $deleteSection = $crawler->filter('[data-professional-preview-target="deleteLogoSection"][hidden]');

        self::assertCount(1, $deleteSection);
    }

    private function renderedDeleteLogoToken(KernelBrowser $client): string
    {
        $crawler = $client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_practitioner_space');

        $token = $this->professionalPreviewRoot($crawler)->attr('data-professional-preview-delete-logo-token-value');

        self::assertIsString($token);
        self::assertNotSame('', $token);

        return $token;
    }

    private function createBrowser(): KernelBrowser
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        return $client;
    }

    private function createUser(string $label, bool $withActiveSubscription = true): User
    {
        $email = $this->uniqueEmail($label);
        $phone = PhoneNumberUtil::getInstance()->parse('0102030405', 'FR');

        $user = (new User())
            ->setEmail($email)
            ->setFirstname('Camille')
            ->setLastname('Martin')
            ->setAdress('10 rue des Tests')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setPhone($phone)
            ->setPassword('test-password-hash');

        $this->createdUserEmails[] = $email;

        $em = $this->entityManager();
        $em->persist($user);

        if ($withActiveSubscription) {
            $now = new \DateTimeImmutable('-1 day');

            $subscription = (new Subscription())
                ->setUser($user)
                ->setEmail($email)
                ->setStatus(SubscriptionStatus::ACTIVE)
                ->setPriceCents(10000)
                ->setTitle('Abonnement praticien annuel')
                ->setDescription('Abonnement créé par un test fonctionnel.')
                ->setIsLifetime(false)
                ->setStartsAt($now)
                ->setEndsAt($now->modify('+1 year'))
                ->setPaymentReference('TEST-'.$label)
                ->setTermsAcceptedAt($now)
                ->setImmediateAccessRequestedAt($now)
                ->setWithdrawalRightWaivedAt($now);

            $em->persist($subscription);
        }

        $em->flush();

        return $user;
    }

    private function cancelUserSubscriptions(User $user): void
    {
        $userId = $this->persistedUserId($user);
        $em = $this->entityManager();

        $subscriptions = $em->getRepository(Subscription::class)->findBy([
            'user' => $user,
        ]);

        foreach ($subscriptions as $subscription) {
            $subscription->setStatus(SubscriptionStatus::CANCELLED);
        }

        $em->flush();
        $em->clear();

        self::assertNull($em->getRepository(Subscription::class)->findOneBy([
            'user' => $userId,
            'status' => SubscriptionStatus::ACTIVE,
        ]));
    }

    private function createProfileWithLogo(User $user, string $clientOriginalName): ProfessionalProfile
    {
        $this->ensureUploadDirectoryExists();

        $profile = (new ProfessionalProfile($user))
            ->setProfessionalName('Cabinet '.$clientOriginalName)
            ->setEmail('cabinet-'.$this->persistedUserId($user).'@example.test')
            ->setPostalCode('75001')
            ->setCity('Paris');

        $logo = new ProfessionalLogo();
        $logo->setImageFile(new UploadedFile(
            $this->createTemporaryPng('prepared-logo'),
            $clientOriginalName,
            'image/png',
            null,
            true,
        ));

        $profile->setLogo($logo);

        $em = $this->entityManager();
        $em->persist($profile);
        $em->flush();

        return $profile;
    }

    private function createProfileWithoutLogo(User $user): ProfessionalProfile
    {
        $profile = (new ProfessionalProfile($user))
            ->setProfessionalName('Cabinet sans logo')
            ->setPostalCode('75001')
            ->setCity('Paris');

        $em = $this->entityManager();
        $em->persist($profile);
        $em->flush();

        return $profile;
    }

    private function uniqueEmail(string $label): string
    {
        $normalizedLabel = preg_replace('/[^a-z0-9]+/i', '-', strtolower($label)) ?: 'user';
        $normalizedLabel = trim($normalizedLabel, '-');
        $testToken = preg_replace('/[^a-z0-9]+/i', '', (string) ($_SERVER['TEST_TOKEN'] ?? $_ENV['TEST_TOKEN'] ?? 'local')) ?: 'local';

        return sprintf(
            'practitioner-%s-%s-%d-%d@example.test',
            $normalizedLabel,
            strtolower($testToken),
            getmypid(),
            ++self::$emailSequence,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonPayload(KernelBrowser $client): array
    {
        $payload = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        return $payload;
    }

    private function reloadUser(User $user): User
    {
        $userId = $this->persistedUserId($user);
        $em = $this->entityManager();

        $em->clear();

        $reloadedUser = $em->getRepository(User::class)->find($userId);

        self::assertInstanceOf(User::class, $reloadedUser);

        return $reloadedUser;
    }

    private function assertAuthenticatedProfilePageIsStillAccessible(KernelBrowser $client): void
    {
        $client->request('GET', '/profil');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_profile');
    }

    private function assertPersistedLogoIsIntact(int $userId, int $logoId, string $imageName): void
    {
        $em = $this->entityManager();

        $em->clear();

        $logo = $em->getRepository(ProfessionalLogo::class)->find($logoId);

        self::assertInstanceOf(ProfessionalLogo::class, $logo);
        self::assertSame($imageName, $logo->getImageName());
        self::assertFileExists($this->professionalLogoPath($imageName));

        $profile = $logo->getProfessionalProfile();

        self::assertInstanceOf(ProfessionalProfile::class, $profile);
        self::assertSame($userId, $profile->getUser()?->getId());
    }

    private function assertUserProfileStillExistsWithoutLogo(int $userId): void
    {
        $em = $this->entityManager();

        $em->clear();

        $user = $em->getRepository(User::class)->find($userId);

        self::assertInstanceOf(User::class, $user);
        self::assertInstanceOf(ProfessionalProfile::class, $user->getProfessionalProfile());
        self::assertNull($user->getProfessionalProfile()->getLogo());
    }

    private function professionalPreviewRoot(Crawler $crawler): Crawler
    {
        $root = $crawler->filter('main[data-controller~="professional-preview"]');

        self::assertCount(1, $root);

        return $root;
    }

    /**
     * @return list<string>
     */
    private function rememberedUploadedLogoNames(): array
    {
        return array_values(array_unique($this->uploadedLogoNamesToRemove));
    }

    private function rememberLogoFile(ProfessionalLogo $logo): string
    {
        $imageName = $logo->getImageName();

        self::assertIsString($imageName);
        self::assertNotSame('', $imageName);

        $this->uploadedLogoNamesToRemove[] = $imageName;

        return $imageName;
    }

    private function persistedUserId(User $user): int
    {
        $id = $user->getId();

        self::assertIsInt($id);

        return $id;
    }

    private function persistedLogoId(ProfessionalLogo $logo): int
    {
        $id = $logo->getId();

        self::assertIsInt($id);

        return $id;
    }

    private function createTemporaryPng(string $prefix): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), $prefix);

        if (false === $temporaryFile) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire.');
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

    private function professionalLogoUploadDirectory(): string
    {
        $testToken = (string) ($_SERVER['TEST_TOKEN'] ?? $_ENV['TEST_TOKEN'] ?? '');

        return $this->projectDir().'/var/test-uploads/professional-logos'.$testToken;
    }

    private function professionalLogoPath(string $imageName): string
    {
        if ($imageName !== basename($imageName)) {
            throw new \InvalidArgumentException('Le nom de fichier du logo ne doit pas contenir de chemin.');
        }

        return $this->professionalLogoUploadDirectory().DIRECTORY_SEPARATOR.$imageName;
    }

    /**
     * @return list<string>
     */
    private function listUploadFileNames(): array
    {
        $this->ensureUploadDirectoryExists();

        $entries = scandir($this->professionalLogoUploadDirectory());

        if (false === $entries) {
            return [];
        }

        $files = array_values(array_filter(
            $entries,
            fn (string $entry): bool => !in_array($entry, ['.', '..'], true)
                && is_file($this->professionalLogoUploadDirectory().DIRECTORY_SEPARATOR.$entry),
        ));

        sort($files);

        return $files;
    }

    /**
     * @param list<string> $expectedFiles
     */
    private function assertUploadDirectoryContainsSameFiles(array $expectedFiles): void
    {
        $actualFiles = $this->listUploadFileNames();

        sort($expectedFiles);

        self::assertSame($expectedFiles, $actualFiles);
    }

    private function ensureUploadDirectoryExists(): void
    {
        $directory = $this->professionalLogoUploadDirectory();

        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le répertoire d\'upload de test "%s".', $directory));
        }
    }

    private function cleanupDatabase(): void
    {
        if ([] === $this->createdUserEmails || !class_exists(User::class)) {
            return;
        }

        $registry = static::getContainer()->get(ManagerRegistry::class);

        $manager = $registry->getManager();

        if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
            $manager = $registry->resetManager();
        }

        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        foreach ($this->createdUserEmails as $email) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user instanceof User) {
                continue;
            }

            foreach ($user->getSubscriptions() as $subscription) {
                $manager->remove($subscription);
            }

            $profile = $user->getProfessionalProfile();

            if ($profile instanceof ProfessionalProfile) {
                $logo = $profile->getLogo();

                if ($logo instanceof ProfessionalLogo) {
                    $imageName = $logo->getImageName();

                    if (is_string($imageName) && '' !== $imageName) {
                        $this->uploadedLogoNamesToRemove[] = $imageName;
                    }

                    $manager->remove($logo);
                }

                $manager->remove($profile);
            }

            $manager->remove($user);
        }

        $manager->flush();
    }

    private function cleanupFiles(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        foreach ($this->rememberedUploadedLogoNames() as $imageName) {
            if ($imageName !== basename($imageName)) {
                continue;
            }

            $path = $this->professionalLogoPath($imageName);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function projectDir(): string
    {
        return static::getContainer()->getParameter('kernel.project_dir');
    }
}
