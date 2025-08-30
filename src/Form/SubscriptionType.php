<?php

namespace App\Form;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Yeni abonelik ekleme durumunda sadece aboneliği olmayan kullanıcıları listele
        $usersWithoutSubscription = $this->subscriptionRepository->findUsersWithoutSubscription();
        
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choices' => $usersWithoutSubscription,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' ' . $user->getSurname() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Kullanıcı',
                'placeholder' => 'Kullanıcı seçin...',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Aylık' => 'monthly',
                    'Yıllık' => 'yearly',
                    'Yaşam Boyu' => 'lifetime'
                ],
                'label' => 'Abonelik Tipi',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Aktif' => 'active',
                    'Beklemede' => 'pending',
                    'Süresi Dolmuş' => 'expired',
                    'İptal Edilmiş' => 'cancelled'
                ],
                'label' => 'Durum',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Başlangıç Tarihi',
                'widget' => 'single_text',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Fiyat',
                'currency' => 'TRY',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('currency', ChoiceType::class, [
                'choices' => [
                    'Türk Lirası (TRY)' => 'TRY',
                    'Amerikan Doları (USD)' => 'USD',
                    'Euro (EUR)' => 'EUR'
                ],
                'label' => 'Para Birimi',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Açıklama',
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm', 'rows' => 3]
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Kredi Kartı' => 'credit_card',
                    'Banka Havalesi' => 'bank_transfer',
                    'PayPal' => 'paypal',
                    'İyzico' => 'iyzico',
                    'Nakit' => 'cash'
                ],
                'label' => 'Ödeme Yöntemi',
                'required' => false,
                'placeholder' => 'Ödeme yöntemi seçin...',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('transactionId', TextType::class, [
                'label' => 'İşlem ID',
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('autoRenew', CheckboxType::class, [
                'label' => 'Otomatik Yenileme',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded']
            ])
            ->add('features', ChoiceType::class, [
                'choices' => [
                    'Sınırsız' => 'unlimited',
                    'Sınırlı' => 'limited'
                ],
                'multiple' => false,
                'expanded' => true,
                'label' => 'Abonelik Özellikleri',
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}