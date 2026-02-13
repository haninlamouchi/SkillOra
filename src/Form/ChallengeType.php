<?php

namespace App\Form;

use App\Entity\Challenge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class ChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('dateDebut', DateType::class, [
            'widget' => 'single_text',
            ])
            ->add('dateFin', DateType::class, [
            'widget' => 'single_text',
            ])
             ->add('image', FileType::class, [
            'mapped' => false,
            'required' => false,
            'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => ['image/jpeg', 'image/png'],
                'mimeTypesMessage' => 'Veuillez uploader une image valide',
            ])
            ],
            ])
            ->add('fichierCahierCharges', FileType::class, [
            'mapped' => false,
            'required' => false,
            'constraints' => [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => ['application/pdf'],
                'mimeTypesMessage' => 'Veuillez uploader un fichier PDF',
            ])
            ],
            ]);

            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Challenge::class,
        ]);
    }
}
