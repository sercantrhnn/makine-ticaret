<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ad',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'Adınızı girin'
                ],
                'required' => true,
            ])
            ->add('surname', TextType::class, [
                'label' => 'Soyad',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'Soyadınızı girin'
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-posta',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'ornek@email.com'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Lütfen e-posta adresinizi girin',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefon (Opsiyonel)',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => '0555 123 45 67'
                ],
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Lütfen şifre girin',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Şifreniz en az {{ limit }} karakter olmalıdır',
                        'max' => 4096,
                    ]),
                ],
                'first_options' => [
                    'label' => 'Şifre',
                    'attr' => [
                        'placeholder' => 'Şifrenizi girin'
                    ]
                ],
                'second_options' => [
                    'label' => 'Şifre Tekrar',
                    'attr' => [
                        'placeholder' => 'Şifrenizi tekrar girin'
                    ]
                ],
                'invalid_message' => 'Şifreler eşleşmiyor.',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Kullanım şartlarını kabul etmelisiniz.',
                    ]),
                ],
                'label' => 'Kullanım şartlarını kabul ediyorum',
                'attr' => [
                    'class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
