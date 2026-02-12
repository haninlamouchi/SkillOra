<?php

namespace App\Form;

use App\Entity\Publication;
use App\Enum\TypeContenu;
use App\Enum\StatusPublication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Publication Title',
                'attr' => [
                    'placeholder' => 'Enter the title...',
                    'class' => 'form-control',
                    'minlength' => 3,
                    'maxlength' => 200,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'The title cannot be empty.']),
                    new Length([
                        'min' => 3,
                        'max' => 200,
                        'minMessage' => 'The title must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The title cannot exceed {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ0-9\s\-\'\",\.!?:;()]+$/u',
                        'message' => 'The title contains invalid characters.',
                    ]),
                ],
                'required' => true,
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'placeholder' => 'Enter the content of your publication...',
                    'rows' => 6,
                    'class' => 'form-control',
                    'minlength' => 10,
                    'maxlength' => 5000,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'The content cannot be empty.']),
                    new Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'The content must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The content cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'required' => true,
            ])
            ->add('typecontenu', EnumType::class, [
                'class' => TypeContenu::class,
                'label' => 'Content Type',
                'choice_label' => fn(TypeContenu $type) => $type->getLabel(),
                'placeholder' => 'Select a type',
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'typecontenu'
                ],
                'required' => true,
            ])
            ->add('fichier', FileType::class, [
                'label' => 'File (Image or Video)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'fichier-upload',
                    'accept' => 'image/*,video/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'video/mp4',
                            'video/mpeg',
                            'video/quicktime',
                            'video/x-msvideo',
                            'video/webm',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF, WEBP) or video (MP4, MPEG, MOV, AVI, WEBM).',
                    ])
                ],
            ]);

        if ($options['show_status']) {
            $builder->add('status', EnumType::class, [
                'class' => StatusPublication::class,
                'label' => 'Publication Status',
                'choice_label' => fn(StatusPublication $status) => $status->getLabel(),
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => true
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'show_status' => false,
        ]);
    }
}