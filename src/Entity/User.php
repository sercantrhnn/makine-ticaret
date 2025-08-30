<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Bu e-posta adresi zaten kullanılıyor.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surname = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /** @var Collection<int, Companies> */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Companies::class)]
    private Collection $companies;

    /** @var Collection<int, Subscription> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->companies = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        // Abonelik durumuna göre rol ekle
        if ($this->hasActiveSubscription()) {
            $roles[] = 'ROLE_SUBSCRIBER';
            
            // Abonelik tipine göre ek roller
            $activeSubscription = $this->getActiveSubscription();
            if ($activeSubscription) {
                switch ($activeSubscription->getType()) {
                    case 'yearly':
                        $roles[] = 'ROLE_PREMIUM_SUBSCRIBER';
                        break;
                    case 'lifetime':
                        $roles[] = 'ROLE_LIFETIME_SUBSCRIBER';
                        break;
                }
            }
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Companies>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Companies $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setOwner($this);
        }

        return $this;
    }

    public function removeCompany(Companies $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getOwner() === $this) {
                $company->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Kullanıcının aktif aboneliği var mı kontrol eder
     */
    public function hasActiveSubscription(): bool
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kullanıcının aktif aboneliğini getirir
     */
    public function getActiveSubscription(): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isActive()) {
                return $subscription;
            }
        }
        return null;
    }

    /**
     * Kullanıcının abonelik durumunu getirir
     */
    public function getSubscriptionStatus(): string
    {
        if ($this->hasActiveSubscription()) {
            return 'active';
        }
        
        // Son aboneliği kontrol et
        $lastSubscription = $this->getLastSubscription();
        if ($lastSubscription && $lastSubscription->isExpired()) {
            return 'expired';
        }
        
        return 'none';
    }

    /**
     * Kullanıcının son aboneliğini getirir
     */
    public function getLastSubscription(): ?Subscription
    {
        $subscriptions = $this->subscriptions->toArray();
        if (empty($subscriptions)) {
            return null;
        }
        
        usort($subscriptions, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        return $subscriptions[0];
    }

    /**
     * Kullanıcının abonelik tipini getirir
     */
    public function getSubscriptionType(): ?string
    {
        $activeSubscription = $this->getActiveSubscription();
        return $activeSubscription ? $activeSubscription->getType() : null;
    }

    /**
     * Kullanıcının abonelik kalan gün sayısını getirir
     */
    public function getSubscriptionDaysRemaining(): int
    {
        $activeSubscription = $this->getActiveSubscription();
        return $activeSubscription ? $activeSubscription->getDaysRemaining() : 0;
    }

    /**
     * Kullanıcının sahip olduğu tüm ürünleri getirir
     * @return Collection<int, Products>
     */
    public function getAllProducts(): Collection
    {
        $allProducts = new ArrayCollection();
        
        foreach ($this->companies as $company) {
            foreach ($company->getProducts() as $product) {
                $allProducts->add($product);
            }
        }
        
        return $allProducts;
    }

    /**
     * Kullanıcının belirli bir şirketindeki ürünleri getirir
     * @param Companies $company
     * @return Collection<int, Products>
     */
    public function getProductsByCompany(Companies $company): Collection
    {
        if ($this->companies->contains($company)) {
            return $company->getProducts();
        }
        
        return new ArrayCollection();
    }

    /**
     * Kullanıcının ürün sayısını getirir
     * @return int
     */
    public function getTotalProductCount(): int
    {
        $count = 0;
        foreach ($this->companies as $company) {
            $count += $company->getProducts()->count();
        }
        return $count;
    }

    /**
     * Kullanıcının şirket sayısını getirir
     * @return int
     */
    public function getCompanyCount(): int
    {
        return $this->companies->count();
    }

    /**
     * Kullanıcının abonelik özelliklerine erişimi var mı kontrol eder
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }
        
        $activeSubscription = $this->getActiveSubscription();
        if (!$activeSubscription || !$activeSubscription->getFeatures()) {
            return false;
        }
        
        return in_array($feature, $activeSubscription->getFeatures());
    }

    /**
     * Kullanıcının maksimum şirket sayısını getirir (abonelik bazında)
     */
    public function getMaxCompanyCount(): int
    {
        if (!$this->hasActiveSubscription()) {
            return 1; // Ücretsiz kullanıcılar sadece 1 şirket
        }
        
        $activeSubscription = $this->getActiveSubscription();
        if (!$activeSubscription) {
            return 1;
        }
        
        switch ($activeSubscription->getType()) {
            case 'monthly':
                return 3;
            case 'yearly':
                return 10;
            case 'lifetime':
                return -1; // Sınırsız
            default:
                return 1;
        }
    }

    /**
     * Kullanıcının maksimum ürün sayısını getirir (abonelik bazında)
     */
    public function getMaxProductCount(): int
    {
        if (!$this->hasActiveSubscription()) {
            return 10; // Ücretsiz kullanıcılar 10 ürün
        }
        
        $activeSubscription = $this->getActiveSubscription();
        if (!$activeSubscription) {
            return 10;
        }
        
        switch ($activeSubscription->getType()) {
            case 'monthly':
                return 100;
            case 'yearly':
                return 500;
            case 'lifetime':
                return -1; // Sınırsız
            default:
                return 10;
        }
    }

    /**
     * Kullanıcının şirket ekleyip ekleyemeyeceğini kontrol eder
     */
    public function canAddCompany(): bool
    {
        $maxCompanies = $this->getMaxCompanyCount();
        if ($maxCompanies === -1) {
            return true; // Sınırsız
        }
        
        return $this->getCompanyCount() < $maxCompanies;
    }

    /**
     * Kullanıcının ürün ekleyip ekleyemeyeceğini kontrol eder
     */
    public function canAddProduct(): bool
    {
        $maxProducts = $this->getMaxProductCount();
        if ($maxProducts === -1) {
            return true; // Sınırsız
        }
        
        return $this->getTotalProductCount() < $maxProducts;
    }
}