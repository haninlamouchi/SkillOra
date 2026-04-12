<?php

namespace App\Form;

use App\Entity\DemandeClub;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Vich\UploaderBundle\Form\Type\VichImageType;

class DemandeClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('logoFile', VichImageType::class, [
                'label'        => 'Logo du club',
                'required'     => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->add('dateCreation')
            ->add('siteWeb')

            // ── NOUVEAUX CHAMPS ──

            ->add('cvFile', FileType::class, [
                'label'       => 'Votre CV (PDF, max 5 Mo)',
                'required'    => true,
                'mapped'      => false,
                'attr'        => ['accept' => 'application/pdf'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez uploader votre CV.',
                    ]),
                    new File([
                        'maxSize'          => '5M',
                        'maxSizeMessage'   => 'Le fichier ne doit pas dépasser 5 Mo.',
                        'mimeTypes'        => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF uniquement.',
                    ]),
                ],
            ])
            ->add('linkedin', UrlType::class, [
                'label'       => 'Profil LinkedIn (optionnel)',
                'required'    => false,
                'attr'        => ['placeholder' => 'https://linkedin.com/in/votre-profil'],
                'constraints' => [
                    new Url([
                        'message' => 'Veuillez entrer une URL valide.',
                    ]),
                    new Regex([
                        'pattern' => '/^https:\/\/(www\.)?linkedin\.com\/in\/.+/',
                        'message' => 'L\'URL doit être un profil LinkedIn valide.',
                    ]),
                    new Length([
                        'max'        => 255,
                        'maxMessage' => 'L\'URL ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeClub::class,
            'attr'       => ['novalidate' => 'novalidate'],
        ]);
    }
}