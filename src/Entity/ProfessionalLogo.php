<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProfessionalLogoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ProfessionalLogoRepository::class)]
#[Vich\Uploadable]
class ProfessionalLogo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Image(
        maxSize: '2M',
        maxSizeMessage: 'Le logo est trop lourd. Le maximum autorisé est {{ limit }} {{ suffix }}.',
        maxWidth: 2000,
        maxWidthMessage: 'Le logo est trop large ({{ width }} px). La largeur maximale est {{ max_width }} px.',
        maxHeight: 1200,
        maxHeightMessage: 'Le logo est trop haut ({{ height }} px). La hauteur maximale est {{ max_height }} px.',
        mimeTypes: [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ],
        mimeTypesMessage: 'Le type MIME du fichier n\'est pas valide ({{ type }}). Les formats autorisés sont {{ types }}',
        extensions: ['png', 'jpg', 'jpeg', 'webp'],
        extensionsMessage: 'L\'extension du logo doit être .png, .jpg, .jpeg ou .webp.',
        detectCorrupted: true,
        corruptedMessage: 'Le fichier semble corrompu. Merci de choisir une autre image.'
    )]
    #[Vich\UploadableField(mapping: 'professional_logos', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'logo')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProfessionalProfile $professionalProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProfessionalProfile(): ?ProfessionalProfile
    {
        return $this->professionalProfile;
    }

    public function setProfessionalProfile(?ProfessionalProfile $professionalProfile): static
    {
        $this->professionalProfile = $professionalProfile;

        if (null !== $professionalProfile && $professionalProfile->getLogo() !== $this) {
            $professionalProfile->setLogo($this);
        }

        return $this;
    }

    public function hasImage(): bool
    {
        return null !== $this->imageFile || null !== $this->imageName;
    }
}
