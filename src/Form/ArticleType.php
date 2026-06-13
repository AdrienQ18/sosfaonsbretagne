<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requiresDiameter', CheckboxType::class, [
                'label' => 'Article nécessitant un diamètre',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 8,
                    'placeholder' => "Exemple :Hauteur : 200 mm Largeur : 130 mm Profondeur : 140 mm",
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image de l’article',
                'mapped' => false,
                'required' => false,
                'help' => 'Format conseillé : image carrée, JPG, PNG, WebP ou SVG.',
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png,.webp,.svg,image/svg+xml',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
