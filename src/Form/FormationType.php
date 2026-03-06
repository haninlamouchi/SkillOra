<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Titre de la formation'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Description de la formation'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF, WebP).',
                    ]),
                ],
            ])
            ->add('lienRessources', UrlType::class, [
                'label' => 'Lien Ressources',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://...'],
            ])

            ->add('terminee', ChoiceType::class, [
                'label' => 'Statut de la formation',
                'choices' => [
                    'En cours' => false,
                    'Terminée' => true,
                ],
                'expanded' => false, 
                'multiple' => false,
            ])
            ->add('isPaid', ChoiceType::class, [
                'label' => 'Accès',
                'choices' => [
                    'Gratuite' => false,
                    'Payante'  => true,
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'id'          => 'prixField',
                    'class'       => 'form-control',
                    'placeholder' => '0.00',
                    'min'         => '0',
                    'step'        => '0.01',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
