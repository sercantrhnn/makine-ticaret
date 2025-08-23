<?php

namespace App\Form;

use App\Entity\Companies;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompaniesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('about')
            ->add('short_name')
            ->add('title')
            ->add('activityType')
            ->add('foundedYear')
            ->add('tradeRegistryNo')
            ->add('mersisNo')
            ->add('adress')
            ->add('phone')
            ->add('email')
            ->add('website')
            ->add('mapsUrl')
            ->add('logoPath')
            ->add('backgroundPhotoPath')
            ->add('catalogPath')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Companies::class,
        ]);
    }
}
