<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EvaluationLivrableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Notes par critère
            ->add('noteCode', NumberType::class, [
                'label' => 'Qualité du code',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.5,
                    'placeholder' => '0 - 20'
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 20]),
                ],
            ])
            ->add('noteFonctionnalites', NumberType::class, [
                'label' => 'Fonctionnalités',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.5,
                    'placeholder' => '0 - 20'
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 20]),
                ],
            ])
            ->add('noteDesign', NumberType::class, [
                'label' => 'Design / UX',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.5,
                    'placeholder' => '0 - 20'
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 20]),
                ],
            ])
            ->add('noteDocumentation', NumberType::class, [
                'label' => 'Documentation',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.5,
                    'placeholder' => '0 - 20'
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 20]),
                ],
            ])
            
            // Feedback structuré
            ->add('pointsForts', TextareaType::class, [
                'label' => 'Points forts',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => '• Architecture bien conçue
- Code propre et lisible
- ...'
                ],
                'required' => false,
            ])
            ->add('pointsAmeliorer', TextareaType::class, [
                'label' => 'Points à améliorer',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => '• Validation des formulaires à renforcer
- Gestion des erreurs à améliorer
- ...'
                ],
                'required' => false,
            ])
            ->add('suggestions', TextareaType::class, [
                'label' => 'Suggestions / Conseils',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => '• Ajouter Symfony Validator
- Créer des tests unitaires
- ...'
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}