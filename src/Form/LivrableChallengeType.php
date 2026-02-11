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

class LivrableChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichier', FileType::class, [
                'label' => 'Fichier (PDF, ZIP, DOCX)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                        'application/pdf',
                        'application/zip',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    'mimeTypesMessage' => 'Veuillez uploader un fichier valide',
                    ])
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
