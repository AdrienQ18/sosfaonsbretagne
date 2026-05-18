<?php

namespace App\Form;

use App\Enum\DonationStatus;
use App\Enum\DonorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Recherche globale: ',
            ])

            ->add('donorType', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Tous',
                'choices' => [
                    'Particulier' => DonorType::PARTICULIER,
                    'Entreprise' => DonorType::ENTREPRISE,
                ],
                'choice_value' => fn (?DonorType $choice) => $choice?->value,
            ])

            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut de la donation: ',
                'placeholder' => 'Tous',
                'choices' => [
                    'Validée' => DonationStatus::DONATION_VALIDEE,
                    'Refusée' => DonationStatus::DONATION_REFUSEE,
                    'Passée' => DonationStatus::DONATION_PASSEE,
                ],
                'choice_value' => fn (?DonationStatus $choice) => $choice?->value,
            ])

            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email: ',
            ])

            ->add('companySiret', TextType::class, [
                'required' => false,
                'label' => 'Siret: ',
            ])
            ->add('dateStart', DateType::class, [
                'required' => false,
                'label' => 'Date début',
                'widget' => 'single_text',
            ])
            ->add('dateEnd', DateType::class, [
                'required' => false,
                'label' => 'Date fin',
                'widget' => 'single_text',
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'donation_filter';
    }
}
