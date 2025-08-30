<?php

namespace App\Form;

use App\Entity\Companies;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CompaniesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Firma Adı',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' ' . $user->getSurname() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Firma Sahibi',
                'placeholder' => 'Firma sahibini seçin...',
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('about', null, [
                'label' => 'Açıklama',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm', 'rows' => 3]
            ])
            ->add('short_name', null, [
                'label' => 'Kısa Ad',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('title', null, [
                'label' => 'Başlık',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('activityType', null, [
                'label' => 'Faaliyet Türü',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('foundedYear', null, [
                'label' => 'Kuruluş Yılı',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('tradeRegistryNo', null, [
                'label' => 'Ticaret Sicil No',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('mersisNo', null, [
                'label' => 'MERSİS No',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('adress', null, [
                'label' => 'Adres',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm', 'rows' => 3]
            ])
            ->add('phone', null, [
                'label' => 'Telefon',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('email', null, [
                'label' => 'E-posta',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('website', null, [
                'label' => 'Website',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('mapsUrl', null, [
                'label' => 'Maps URL',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (Max 5MB)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Logo dosyası 5MB\'dan büyük olamaz. Lütfen daha küçük bir dosya seçin.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Lütfen geçerli bir resim dosyası yükleyin (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'attr' => ['class' => 'mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500']
            ])
            ->add('backgroundPhotoFile', FileType::class, [
                'label' => 'Arka Plan Fotoğrafı (Max 5MB)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Arka plan fotoğrafı 5MB\'dan büyük olamaz. Lütfen daha küçük bir dosya seçin.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Lütfen geçerli bir resim dosyası yükleyin (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('catalogFile', FileType::class, [
                'label' => 'Katalog PDF (Max 25MB)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '25M',
                        'maxSizeMessage' => 'Katalog dosyası 25MB\'dan büyük olamaz. Lütfen daha küçük bir dosya seçin.',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Lütfen geçerli bir PDF dosyası yükleyin',
                    ])
                ],
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Companies::class,
        ]);
    }
}
