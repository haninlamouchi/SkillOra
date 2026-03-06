<?php

namespace App\Form;

use App\Entity\Challenge;
use App\Entity\Groupe;
use App\Entity\LivrableChallenge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class LivrableChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichier', FileType::class, [
            'label' => 'Fichier ZIP (optionnel si GitHub fourni)',
            'required' => false,
            'mapped' => false,
        ])
        ->add('githubUrl', UrlType::class, [
            'label' => 'Lien GitHub du projet (optionnel)',
            'required' => false,
            'attr' => [
                'placeholder' => 'https://github.com/username/repository',
            ],
        ])
            ->add('dateSoumission', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('groupe', EntityType::class, [
                'class' => Groupe::class,
                'choice_label' => 'nomGroupe',
            ])
            ->add('challenge', EntityType::class, [
                'class' => Challenge::class,
                'choice_label' => 'titre',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LivrableChallenge::class,
        ]);
    }
}
