<?php

namespace App\Form;

use App\Entity\DemandeAdhesion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DemandeAdhesionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeCandidature', ChoiceType::class, [
                'label' => 'Type de candidature',
                'choices' => [
                    'Membre' => 'membre',
                    'Responsable' => 'responsable',
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un type de candidature']),
                ],
            ])
            ->add('messageMotivation', TextareaType::class, [
                'label' => 'Message de motivation',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Expliquez pourquoi vous souhaitez rejoindre ce club...',
                ],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV (PDF)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
            ])
            ->add('lettreMotivation', FileType::class, [
                'label' => 'Lettre de motivation (PDF)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeAdhesion::class,
        ]);
    }
}
