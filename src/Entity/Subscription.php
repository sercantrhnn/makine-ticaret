<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null; // monthly, yearly, lifetime

    #[ORM\Column(length: 100)]
    private ?string $status = null; // active, expired, cancelled, pending

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currency = 'TRY';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $features = null; // unlimited, limited

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoRenew = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastRenewalDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextRenewalDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->status = 'pending';
        $this->autoRenew = true;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getFeatures(): ?string
    {
        return $this->features;
    }

    public function setFeatures(?string $features): static
    {
        $this->features = $features;

        return $this;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;

        return $this;
    }

    public function getLastRenewalDate(): ?\DateTimeInterface
    {
        return $this->lastRenewalDate;
    }

    public function setLastRenewalDate(?\DateTimeInterface $lastRenewalDate): static
    {
        $this->lastRenewalDate = $lastRenewalDate;

        return $this;
    }

    public function getNextRenewalDate(): ?\DateTimeInterface
    {
        return $this->nextRenewalDate;
    }

    public function setNextRenewalDate(?\DateTimeInterface $nextRenewalDate): static
    {
        $this->nextRenewalDate = $nextRenewalDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Aboneliğin aktif olup olmadığını kontrol eder
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->endDate && 
               $this->endDate > new \DateTime();
    }

    /**
     * Aboneliğin süresi dolmuş mu kontrol eder
     */
    public function isExpired(): bool
    {
        return $this->endDate && $this->endDate <= new \DateTime();
    }

    /**
     * Aboneliğin kalan gün sayısını getirir
     */
    public function getDaysRemaining(): int
    {
        if (!$this->endDate) {
            return 0;
        }

        $now = new \DateTime();
        $diff = $this->endDate->diff($now);
        
        return $this->endDate > $now ? $diff->days : 0;
    }

    /**
     * Abonelik tipinin Türkçe karşılığını getirir
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'monthly' => 'Aylık',
            'yearly' => 'Yıllık',
            'lifetime' => 'Yaşam Boyu',
            default => 'Bilinmiyor'
        };
    }

    /**
     * Abonelik durumunun Türkçe karşılığını getirir
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Aktif',
            'expired' => 'Süresi Dolmuş',
            'cancelled' => 'İptal Edilmiş',
            'pending' => 'Beklemede',
            default => 'Bilinmiyor'
        };
    }

    /**
     * Abonelik özelliklerinin Türkçe karşılığını getirir
     */
    public function getFeaturesLabel(): string
    {
        return match($this->features) {
            'unlimited' => 'Sınırsız',
            'limited' => 'Sınırlı',
            default => 'Belirtilmemiş'
        };
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}