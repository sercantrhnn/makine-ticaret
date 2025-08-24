<?php

namespace App\Entity;

use App\Repository\ProductsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ProductsRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
#[ORM\HasLifecycleCallbacks]
class Products
{
    use TimestampableEntity;
    use BlameableEntity;
    use SoftDeleteableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modelType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $listingNo = null;

    #[ORM\Column(length: 50, options: ['default' => 'satilik'], nullable: true)]
    private ?string $productStatus = 'satilik';

    #[ORM\Column(length: 50, options: ['default' => 'sifir'], nullable: true)]
    private ?string $productType = 'sifir';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    /** @var Collection<int, \App\Entity\ProductImages> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: \App\Entity\ProductImages::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['isPrimary' => 'DESC', 'sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, \App\Entity\ProductVideos> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: \App\Entity\ProductVideos::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $videos;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Companies::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?\App\Entity\Companies $company = null;

    #[ORM\ManyToOne(targetEntity: Categories::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Categories $category = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->videos = new ArrayCollection();
        $this->productStatus = 'satilik'; // Ürün durumu varsayılan olarak satılık
        $this->productType = 'sifir'; // Ürün türü varsayılan olarak sıfır
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModelType(): ?string
    {
        return $this->modelType;
    }

    public function setModelType(?string $modelType): static
    {
        $this->modelType = $modelType;

        return $this;
    }

    public function getListingNo(): ?string
    {
        return $this->listingNo;
    }

    public function setListingNo(?string $listingNo): static
    {
        $this->listingNo = $listingNo;

        return $this;
    }

    public function getProductStatus(): ?string
    {
        return $this->productStatus ?? 'satilik'; // Null ise satılık döndür
    }

    public function setProductStatus(?string $productStatus): static
    {
        $this->productStatus = $productStatus ?? 'satilik'; // Null ise satılık olarak set et

        return $this;
    }

    public function getProductType(): ?string
    {
        return $this->productType ?? 'sifir'; // Null ise sıfır döndür
    }

    public function setProductType(?string $productType): static
    {
        $this->productType = $productType ?? 'sifir'; // Null ise sıfır olarak set et

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): static
    {
        $this->detail = $detail;

        return $this;
    }

    /** @return Collection<int, \App\Entity\ProductImages> */
    public function getImages(): Collection { return $this->images; }

    public function addImage(\App\Entity\ProductImages $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }
        return $this;
    }

    public function removeImage(\App\Entity\ProductImages $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, \App\Entity\ProductVideos> */
    public function getVideos(): Collection { return $this->videos; }

    public function addVideo(\App\Entity\ProductVideos $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setProduct($this);
        }
        return $this;
    }

    public function removeVideo(\App\Entity\ProductVideos $video): static
    {
        if ($this->videos->removeElement($video)) {
            if ($video->getProduct() === $this) {
                $video->setProduct(null);
            }
        }
        return $this;
    }

    public function getCompany(): ?\App\Entity\Companies
    {
        return $this->company;
    }

    public function setCompany(?\App\Entity\Companies $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getCategory(): ?\App\Entity\Categories
    {
        return $this->category;
    }

    public function setCategory(?\App\Entity\Categories $category): static
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setListingNoValue(): void
    {
        if ($this->listingNo === null) {
            // Yıl + Ay + Gün + Saat + Dakika + Saniye + Mikrosaniye formatında benzersiz liste no oluştur
            $this->listingNo = 'LN' . date('YmdHis') . substr(microtime(), 2, 3);
        }
    }
}
