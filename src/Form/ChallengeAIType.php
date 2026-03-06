<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChallengeAIType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Développement Web' => 'Développement Web',
                    'Développement Mobile' => 'Développement Mobile',
                    'Data Science' => 'Data Science',
                    'Intelligence Artificielle' => 'Intelligence Artificielle',
                    'DevOps' => 'DevOps',
                    'Cybersécurité' => 'Cybersécurité',
                    'Base de données' => 'Base de données',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une catégorie']),
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Débutant' => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé' => 'Avancé',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un niveau']),
                ],
            ])
            ->add('technologies', TextType::class, [
                'label' => 'Technologies',
                'help' => 'Ex: Symfony, MySQL, Docker',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Symfony, MySQL, Docker',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez spécifier les technologies']),
                ],
            ])
            ->add('duree', ChoiceType::class, [
                'label' => 'Durée',
                'choices' => [
                    '1 semaine' => '1 semaine',
                    '2 semaines' => '2 semaines',
                    '3 semaines' => '3 semaines',
                    '1 mois' => '1 mois',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une durée']),
                ],
            ])
            ->add('theme', TextareaType::class, [
                'label' => 'Thème ou contexte (optionnel)',
                'help' => 'Ex: Système de gestion de bibliothèque, Application e-commerce',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Décrivez brièvement le contexte ou le thème souhaité...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}