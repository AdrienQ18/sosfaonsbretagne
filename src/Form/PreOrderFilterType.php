<?php

namespace App\Form;

use App\Enum\PreOrderStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreOrderFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Recherche globale',
            ])

            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut',
                'placeholder' => 'Tous',
                'choices' => [
                    'En attente' => PreOrderStatus::EN_ATTENTE,
                    'Validée' => PreOrderStatus::VALIDEE,
                    'En attente de paiement' => PreOrderStatus::EN_ATTENTE_PAIEMENT,
                    'Payée' => PreOrderStatus::PAYEE,
                    'Refusée' => PreOrderStatus::REFUSEE,
                ],
                'choice_value' => fn (?PreOrderStatus $choice) => $choice?->value,
            ])

            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email du client',
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
        return 'pre_order_filter';
    }
}
