<?php

namespace App\Form;

use App\Enum\AlertStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlertFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Recherche',
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut',
                'placeholder' => 'Tous',
                'choices' => [
                    'Signalement passé' => AlertStatus::SIGNALEMENT_PASSEE,
                    'Validé' => AlertStatus::SIGNALEMENT_VALIDEE,
                    'Refusé' => AlertStatus::SIGNALEMENT_REFUSEE,
                ],
                'choice_value' => fn (?AlertStatus $choice) => $choice?->value,
            ])
            ->add('type', TextType::class, [
                'required' => false,
                'label' => 'Type',
            ])
            ->add('localisation', TextType::class, [
                'required' => false,
                'label' => 'Localisation',
            ])
            ->add('cultureType', TextType::class, [
                'required' => false,
                'label' => 'Culture',
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
        return 'alert_filter';
    }
}
