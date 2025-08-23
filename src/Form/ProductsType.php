<?php

namespace App\Form;

use App\Entity\Products;
use App\Entity\Companies;
use App\Entity\Categories;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Count;

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
            ->add('status', null, [
                'required' => false,
            ])
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
            ->add('imageFiles', FileType::class, [
                'label' => 'Ürün Fotoğrafları (Max 5 adet, her biri 5MB)',
                'required' => false,
                'mapped' => false,
                'multiple' => true,
                'constraints' => [
                    new Count([
                        'max' => 5,
                        'maxMessage' => 'En fazla 5 fotoğraf yükleyebilirsiniz.',
                    ]),
                    new \Symfony\Component\Validator\Constraints\All([
                        'constraints' => [
                            new File([
                                'maxSize' => '5M',
                                'maxSizeMessage' => 'Her fotoğraf 5MB\'dan büyük olamaz.',
                                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                                'mimeTypesMessage' => 'Lütfen geçerli resim dosyaları yükleyin (JPEG, PNG, GIF, WebP)',
                            ])
                        ]
                    ])
                ],
                'attr' => ['class' => 'mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500', 'accept' => 'image/*', 'multiple' => 'multiple']
            ])
            ->add('videoFiles', FileType::class, [
                'label' => 'Ürün Videoları (Max 3 adet, her biri 25MB)',
                'required' => false,
                'mapped' => false,
                'multiple' => true,
                'constraints' => [
                    new Count([
                        'max' => 3,
                        'maxMessage' => 'En fazla 3 video yükleyebilirsiniz.',
                    ]),
                    new \Symfony\Component\Validator\Constraints\All([
                        'constraints' => [
                            new File([
                                'maxSize' => '25M',
                                'maxSizeMessage' => 'Her video 25MB\'dan büyük olamaz.',
                                'mimeTypes' => ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv'],
                                'mimeTypesMessage' => 'Lütfen geçerli video dosyaları yükleyin (MP4, AVI, MOV, WMV, FLV)',
                            ])
                        ]
                    ])
                ],
                'attr' => ['class' => 'mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500', 'accept' => 'video/*', 'multiple' => 'multiple']
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
