<?php

namespace App\Entity;

use App\Repository\CategoriesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: CategoriesRepository::class)]
#[ORM\Table(
    name: 'categories',
    indexes: [
        new ORM\Index(name: 'idx_categories_mpath', columns: ['mpath'])
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_categories_slug', columns: ['slug'])
    ]
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
#[Gedmo\Tree(type: 'materializedPath', activateLocking: false)]
#[ORM\HasLifecycleCallbacks]
class Categories
{
    use TimestampableEntity;
    use BlameableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    // Materialized path alanı (ağacın yolunu tutar, örn: /bahce-makinalari/ot-bicme/elektrikli/)
    #[ORM\Column(length: 1024, nullable: true)]
    #[Gedmo\TreePath(separator: '/')]
    private ?string $mpath = null;

    // Seviye bilgisi (kök=0, çocuk=1, ...)
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $children;

    /** @var Collection<int, \App\Entity\Products> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: \App\Entity\Products::class)]
    private Collection $products;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // ——— Getters / Setters ———
    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getSlug(): ?string 
    { 
        if ($this->slug === null && $this->name !== null) {
            $this->slug = $this->generateSlug($this->name);
        }
        return $this->slug; 
    }
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }

    public function getMpath(): ?string { return $this->mpath; }
    public function setMpath(?string $mpath): static { $this->mpath = $mpath; return $this; }

    public function getLevel(): int
    {
        // Gedmo TreeLevel doldurur; null ise manuel hesap
        if ($this->level !== null) {
            return (int)$this->level;
        }
        $level = 0;
        $p = $this->parent;
        while ($p !== null) { $level++; $p = $p->getParent(); }
        return $level;
    }
    public function setLevel(?int $level): static { $this->level = $level; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }

    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }
    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }
    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, \App\Entity\Products> */
    public function getProducts(): Collection { return $this->products; }
    public function addProduct(\App\Entity\Products $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }
        return $this;
    }
    public function removeProduct(\App\Entity\Products $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }
        return $this;
    }

    // Yardımcılar
    public function isRoot(): bool { return $this->parent === null; }
    public function isLeaf(): bool { return $this->children->isEmpty(); }

    public function getFullPath(): string
    {
        $path = [$this->name];
        $p = $this->parent;
        while ($p !== null) { array_unshift($path, $p->getName()); $p = $p->getParent(); }
        return implode(' > ', $path);
    }

    public function __toString(): string
    {
        return $this->name ?? 'Kategori';
    }
    
    /**
     * Türkçe karakterleri ve özel karakterleri temizleyerek slug oluşturur
     */
    private function generateSlug(string $text): string
    {
        // Türkçe karakterleri değiştir
        $text = str_replace(
            ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'Ö', 'Ş', 'Ü'],
            ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'],
            $text
        );
        
        // Küçük harfe çevir
        $text = strtolower($text);
        
        // Sadece harf, rakam ve boşluk bırak
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        
        // Boşlukları tire ile değiştir
        $text = preg_replace('/\s+/', '-', $text);
        
        // Birden fazla tireyi tek tire yap
        $text = preg_replace('/-+/', '-', $text);
        
        // Başta ve sonda tire varsa kaldır
        $text = trim($text, '-');
        
        // Boş string ise 'kategori' döndür
        return $text ?: 'kategori';
    }
    
    /**
     * Tree level hesaplar
     */
    private function calculateLevel(): int
    {
        if ($this->parent === null) {
            return 0;
        }
        
        $level = 0;
        $parent = $this->parent;
        while ($parent !== null) {
            $level++;
            $parent = $parent->getParent();
        }
        
        return $level;
    }
    
    /**
     * Materialized path hesaplar
     */
    private function calculateMpath(): string
    {
        if ($this->parent === null) {
            return '/' . $this->slug;
        }
        
        $path = [];
        $current = $this;
        
        while ($current !== null) {
            array_unshift($path, $current->getSlug());
            $current = $current->getParent();
        }
        
        return '/' . implode('/', $path);
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
        
        // Slug oluştur
        if ($this->slug === null && $this->name !== null) {
            $this->slug = $this->generateSlug($this->name);
        }
        
        // Tree level hesapla
        if ($this->level === null) {
            $this->level = $this->calculateLevel();
        }
        
        // Materialized path hesapla
        if ($this->mpath === null) {
            $this->mpath = $this->calculateMpath();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
        
        // Slug güncelle
        if ($this->name !== null) {
            $this->slug = $this->generateSlug($this->name);
        }
        
        // Tree level güncelle
        $this->level = $this->calculateLevel();
        
        // Materialized path güncelle
        $this->mpath = $this->calculateMpath();
    }
}
