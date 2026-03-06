<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType; // ← ajouter

class ClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('logoFile', VichImageType::class, [ // ← remplacer 'logo'
                'label'        => 'Logo du club',
                'required'     => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->add('dateCreation')
            ->add('siteWeb')
        ;

        if (!$options['disable_responsable']) {
            $builder->add('responsable', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'id',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'          => Club::class,
            'disable_responsable' => false,
            'attr'                => ['novalidate' => 'novalidate'],
            'csrf_protection'     => true,
            'csrf_field_name'     => '_token',
            'csrf_token_id'       => 'club',
        ]);
    }
}