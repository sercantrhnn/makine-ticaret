<?php

namespace App\Form;

use App\Entity\Products;
use App\Entity\Companies;
use App\Entity\Categories;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('brand')
            ->add('modelType')
            ->add('listingNo')
            ->add('status')
            ->add('type')
            ->add('origin')
            ->add('detail')
            ->add('company', EntityType::class, [
                'class' => Companies::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Firma seçiniz',
            ])
            ->add('category', EntityType::class, [
                'class' => Categories::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Kategori seçiniz',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
        ]);
    }
}
