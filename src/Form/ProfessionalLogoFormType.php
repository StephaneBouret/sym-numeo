<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ProfessionalLogoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFile', FileType::class, [
            'label' => false,
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new Assert\Image(
                    maxSize: '2M',
                    maxSizeMessage: 'Le logo est trop lourd. Le maximum autorisé est {{ limit }} {{ suffix }}.',
                    maxWidth: 2000,
                    maxWidthMessage: 'Le logo est trop large ({{ width }} px). La largeur maximale est {{ max_width }} px.',
                    maxHeight: 1200,
                    maxHeightMessage: 'Le logo est trop haut ({{ height }} px). La hauteur maximale est {{ max_height }} px.',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Le format du logo n\'est pas autorisé. Utilisez un fichier PNG ou JPG.',
                    extensions: ['png', 'jpg', 'jpeg', 'webp'],
                    extensionsMessage: 'L\'extension du logo doit être .png, .jpg, .jpeg ou .webp.',
                    detectCorrupted: true,
                    corruptedMessage: 'Le fichier semble corrompu. Merci de choisir une autre image.'
                ),
            ],
            'attr' => [
                'accept' => 'image/png,image/jpeg,image/webp',
            ],
        ]);
    }
}
