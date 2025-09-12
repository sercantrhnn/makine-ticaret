<?php

namespace App\Form;

use App\Entity\PurchaseRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productName', TextType::class, [
                'label' => 'Ürün adı',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-posta',
                'required' => true,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Açıklama',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Talep Gönder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseRequest::class,
        ]);
    }
}


