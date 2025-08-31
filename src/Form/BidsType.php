<?php

namespace App\Form;

use App\Entity\Bids;
use App\Entity\Companies;
use App\Entity\Products;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BidsType extends AbstractType
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $userProducts = [];

        if ($user) {
            foreach ($user->getCompanies() as $company) {
                foreach ($company->getProducts() as $product) {
                    $userProducts[] = $product;
                }
            }
        }

        $builder
            ->add('product', EntityType::class, [
                'class' => Products::class,
                'choice_label' => function(Products $product) {
                    return $product->getName() . ' (' . $product->getBrand() . ' - ' . $product->getModelType() . ')';
                },
                'choices' => $userProducts,
                'label' => 'Ürün',
                'placeholder' => 'Ürün seçiniz',
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500',
                    'id' => 'product_select'
                ]
            ])
            ->add('starting_price', MoneyType::class, [
                'label' => 'Başlangıç Fiyatı',
                'required' => true,
                'currency' => 'TRY',
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('end_time', DateTimeType::class, [
                'label' => 'İhale Bitiş Tarihi',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500',
                    'min' => date('Y-m-d\TH:i')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Açıklama',
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500',
                    'rows' => 4,
                    'placeholder' => 'İhale hakkında detaylı açıklama yazabilirsiniz...'
                ]
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bids::class,
        ]);
    }
}
