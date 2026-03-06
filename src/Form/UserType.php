<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Entrez votre nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Entrez votre prénom'],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => 'exemple@email.com'],
            ])
            ->add('password', PasswordType::class, [
                'label'    => false,
                'required' => !$isEdit,
                'mapped'   => false,  // jamais mappé directement — géré dans le controller
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => $isEdit ? 'Laisser vide pour conserver le mot de passe actuel' : '••••••••',
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('dateNaissance', DateType::class, [
                'label'    => false,
                'widget'   => 'single_text',
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('photoFile', VichImageType::class, [
                'label'        => false,
                'required'     => !$isEdit,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri'    => false,
            ]);

   if ($isEdit) {
    $builder
        ->add('role', ChoiceType::class, [
            'label'    => false,
            'disabled' => true, // ← non modifiable
            'choices'  => [
                'Étudiant'            => 'etudiant',
                'Membre'             => 'membre',
                'Responsable de club' => 'responsable_club',
                'Administrateur'     => 'admin',
            ],
            'attr' => ['class' => 'form-control', 'style' => 'pointer-events:none; background:#f1eaea; color:#999;'],
        ])
        ->add('dateInscription', DateTimeType::class, [
            'label'    => false,
            'widget'   => 'single_text',
            'required' => false,
            'mapped'   => false,
            'attr'     => [
                'class'    => 'form-control',
                'readonly' => 'readonly',
                'style'    => 'background:#f1eaea; color:#999; cursor:not-allowed;'
            ],
        ]);
}
}
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}