<?php

namespace App\Form;

use App\Entity\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la vidéo',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Introduction au module'],
            ])
            ->add('videoFile', FileType::class, [
                'label' => 'Fichier vidéo',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-control', 'accept' => 'video/*'],
                'constraints' => [
                    new File([
                        'maxSize' => '100M',
                        'mimeTypes' => [
                            'video/mp4',
                            'video/webm',
                            'video/ogg',
                            'video/avi',
                            'video/x-msvideo',
                            'video/quicktime',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier vidéo valide (MP4, WebM, OGG, AVI, MOV).',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description optionnelle'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
