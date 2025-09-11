<?php

namespace App\Form;

use App\Entity\BidMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BidMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => false,
            'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2])],
            'attr' => ['rows' => 3, 'placeholder' => 'Mesajınızı yazın...']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BidMessage::class,
        ]);
    }
}


