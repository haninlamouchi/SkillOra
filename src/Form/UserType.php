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
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Enter your last name'],
            ])
            ->add('prenom', TextType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Enter your first name'],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'placeholder' => 'example@email.com'],
            ])
            ->add('password', PasswordType::class, [
                'label'    => false,
                'required' => !$isEdit,
                'mapped'   => false,  // jamais mappé directement — géré dans le controller
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => $isEdit ? 'Leave blank to keep current password' : '••••••••',
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label'    => false,
                'mapped'   => !$isEdit,   // en édition : pas mappé → role jamais écrasé
                'disabled' => $isEdit,    // visuellement grisé en édition
                'choices'  => [
                    'Student'       => 'etudiant',
                    'Member'        => 'membre',
                    'Club Manager'  => 'responsable_club',
                    'Administrator' => 'admin',
                ],
                'attr' => ['class' => 'form-control'],
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
            ->add('dateInscription', DateTimeType::class, [
                'label'    => false,
                'widget'   => 'single_text',
                'required' => false,
                'mapped'   => !$isEdit,   // en édition : pas mappé → dateInscription jamais écrasée
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('photoFile', VichImageType::class, [
                'label'        => false,
                'required'     => !$isEdit,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri'    => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}