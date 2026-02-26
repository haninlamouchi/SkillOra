<?php

namespace App\Form;

use App\Entity\Publication;
use App\Enum\TypeContenu;
use App\Enum\StatusPublication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la publication',
                'attr'  => [
                    'placeholder' => 'Entrez le titre...',
                    'class'       => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre ne peut pas être vide.']),
                    new Length([
                        'min'        => 3,
                        'max'        => 200,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ0-9\s\-\'\",\.!?:;()]+$/u',
                        'message' => 'Le titre contient des caractères invalides.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr'  => [
                    'placeholder' => 'Entrez le contenu de votre publication...',
                    'rows'        => 6,
                    'class'       => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le contenu ne peut pas être vide.']),
                    new Length([
                        'min'        => 10,
                        'max'        => 5000,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le contenu ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('typecontenu', EnumType::class, [
                'class'        => TypeContenu::class,
                'label'        => 'Type de contenu',
                'choice_label' => fn(TypeContenu $type) => $type->getLabel(),
                'placeholder'  => 'Sélectionner un type',
                'attr'         => ['class' => 'form-control', 'id' => 'typecontenu'],
                'required'     => false,
            ])
            ->add('imageFile', VichFileType::class, [
                'label'        => 'Fichier (Image / Vidéo)',
                'required'     => false,
                'allow_delete' => false,
                'download_uri' => false,
                'attr'         => [
                    'class'  => 'form-control',
                ],
            ]);

        if ($options['show_status']) {
            $builder->add('status', EnumType::class, [
                'class'        => StatusPublication::class,
                'label'        => 'Statut de la publication',
                'choice_label' => fn(StatusPublication $status) => $status->getLabel(),
                'attr'         => ['class' => 'form-control'],
                'required'     => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => Publication::class,
            'show_status' => false,
        ]);
    }
}