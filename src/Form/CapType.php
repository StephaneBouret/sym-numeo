<?php

namespace App\Form;

use App\Dto\CapInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstNames', TextType::class, [
                'label' => 'Prénoms figurant à votre état-civil',
                'help' => 'Prénoms séparés par des espaces. Tirets autorisés.',
                'attr' => ['placeholder' => 'Lyna Marie Maëlys'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'help' => 'Nom de naissance (tirets autorisés).',
                'attr' => ['placeholder' => 'Favreau-Tavares'],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CapInput::class,
            'csrf_protection' => true,
        ]);
    }
}
