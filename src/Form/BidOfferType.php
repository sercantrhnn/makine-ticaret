<?php

namespace App\Form;

use App\Entity\BidOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BidOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', MoneyType::class, [
                'label' => 'Teklif Fiyatı',
                'currency' => false,
                'scale' => 2,
                'constraints' => [new Assert\NotBlank(), new Assert\Positive()],
                'attr' => ['class' => 'w-full']
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Para Birimi',
                'choices' => [
                    'TRY (₺)' => 'TRY',
                    'USD ($)' => 'USD',
                    'EUR (€)' => 'EUR',
                    'GBP (£)' => 'GBP',
                ],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'İletişim Telefonu',
                'required' => false,
            ])
            ->add('contactEmail', TextType::class, [
                'label' => 'İletişim E-postası',
                'required' => false,
                'constraints' => [new Assert\Email(['mode' => 'html5'])],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Mesajınız',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BidOffer::class,
        ]);
    }
}


