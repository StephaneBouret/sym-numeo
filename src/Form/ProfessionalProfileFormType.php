<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\ResolvedProfessionalIdentity;
use App\Entity\ProfessionalProfile;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfessionalProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ResolvedProfessionalIdentity $fallback */
        $fallback = $options['fallback_identity'];

        $builder
            ->add('logo', ProfessionalLogoFormType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
            ])
            ->add('professionalName', TextType::class, [
                'label' => 'Nom professionnel ou cabinet',
                'required' => false,
                'empty_data' => null,
                'help' => sprintf('Valeur utilisée par défaut : %s', $fallback->name),
                'attr' => ['placeholder' => $fallback->name],
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'empty_data' => null,
                'attr' => ['placeholder' => 'Uniquement si elle diffère du nom professionnel'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'inputmode' => 'numeric',
                    'maxlength' => 17,
                    'placeholder' => '14 chiffres',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email professionnelle',
                'required' => false,
                'empty_data' => null,
                'help' => sprintf('Valeur utilisée par défaut : %s', $fallback->email),
                'attr' => ['placeholder' => $fallback->email],
            ])
            ->add('phone', PhoneNumberType::class, [
                'label' => 'Téléphone professionnel',
                'required' => false,
                'default_region' => 'FR',
                'format' => PhoneNumberFormat::NATIONAL,
                'number_type' => PhoneNumberType::NUMBER_TYPE_TEL,
                'help' => null !== $fallback->phone ? sprintf('Valeur utilisée par défaut : %s', $fallback->phone) : null,
                'attr' => ['placeholder' => $fallback->phone ?? 'Téléphone'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse professionnelle',
                'required' => false,
                'empty_data' => null,
                'help' => null !== $fallback->address ? sprintf('Valeur utilisée par défaut : %s', $fallback->address) : null,
                'attr' => ['placeholder' => $fallback->address ?? 'Adresse'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'empty_data' => null,
                'help' => null !== $fallback->postalCode ? sprintf('Valeur utilisée par défaut : %s', $fallback->postalCode) : null,
                'attr' => ['placeholder' => $fallback->postalCode ?? 'Code postal'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'empty_data' => null,
                'help' => null !== $fallback->city ? sprintf('Valeur utilisée par défaut : %s', $fallback->city) : null,
                'attr' => ['placeholder' => $fallback->city ?? 'Ville'],
            ])
            ->add('websiteUrl', UrlType::class, [
                'label' => 'Site Internet',
                'required' => false,
                'empty_data' => null,
                'default_protocol' => 'https',
                'attr' => ['placeholder' => 'https://www.exemple.fr'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfessionalProfile::class,
        ]);

        $resolver->setRequired('fallback_identity');
        $resolver->setAllowedTypes(
            'fallback_identity',
            ResolvedProfessionalIdentity::class
        );
    }
}
