<?php

namespace App\Form;

use App\Entity\Alert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type de signalement',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Exemple : fauchage à venir, faon aperçu...',
                ],
            ])

            ->add('surface', NumberType::class, [
                'label' => 'Surface de la parcelle (ha)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : 3,5',
                    'min' => 0,
                ],
            ])

            ->add('interventionDate', DateTimeType::class, [
                'label' => 'Date et heure estimées de la fauche',
                'required' => true,
                'widget' => 'single_text',
            ])

            ->add('localisation', TextType::class, [
                'label' => 'Localisation de la parcelle',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Commune, lieu-dit ou adresse approximative',
                ],
            ])

            ->add('gpsLatitude', TextType::class, [
                'label' => 'Latitude GPS',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : 48.123456',
                ],
            ])

            ->add('gpsLongitude', TextType::class, [
                'label' => 'Longitude GPS',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : -1.654321',
                ],
            ])

            ->add('cultureType', TextType::class, [
                'label' => 'Type de culture',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Exemple : prairie, ray-grass, luzerne...',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description de la demande',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ajoutez les informations utiles : accès à la parcelle, horaires, urgence, observations...',
                    'rows' => 6,
                ],
            ])

            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Alert::class,
        ]);
    }
}
