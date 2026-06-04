<?php

namespace App\Form;

use App\Entity\Availability;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email* : ',
                'attr' => [
                    'autocomplete' => 'email',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe* : ',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'password-input',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un mot de passe.',
                    ),

                    new Length(
                        min: 12,
                        max: 4096,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                    ),

                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
                    ),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom* : ',
                'attr' => [
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom* : ',
                'attr' => [
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone* : ',
                'attr' => [
                    'autocomplete' => 'tel',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse* : ',
                'attr' => [
                    'autocomplete' => 'street-address',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville* : ',
                'attr' => [
                    'autocomplete' => 'address-level2',
                ],
            ])
            ->add('zipcode', TextType::class, [
                'label' => 'Code postal* : ',
                'attr' => [
                    'autocomplete' => 'postal-code',
                ],
            ])
            ->add('birthday', BirthdayType::class, [
                'label' => 'Date de naissance* : ',
                'attr' => [
                    'autocomplete' => 'bday',
                ],
            ])
            ->add('benevole', CheckboxType::class, [
                'label' => 'Voulez-vous être inscrit en tant que bénévole ?',
                'mapped' => false,
                'required' => false,
            ])
            ->add('availabilitys', EntityType::class, [
                'label' => 'Vos disponibilités* : ',
                'class' => Availability::class,
                'choice_label' => 'label', // Le champ à afficher dans le select
                'choice_value' => 'id',   // Le champ à utiliser comme valeur
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions d\'utilisation.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
