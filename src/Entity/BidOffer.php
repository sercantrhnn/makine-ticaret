<?php

namespace App\Entity;

use App\Repository\BidOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BidOfferRepository::class)]
#[ORM\Table(name: 'bid_offer')]
#[ORM\HasLifecycleCallbacks]
class BidOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bids::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Bids $bid = null;

    #[ORM\ManyToOne(targetEntity: Products::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Products $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $bidder = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getBid(): ?Bids { return $this->bid; }
    public function setBid(Bids $bid): self { $this->bid = $bid; return $this; }
    public function getProduct(): ?Products { return $this->product; }
    public function setProduct(Products $product): self { $this->product = $product; return $this; }
    public function getBidder(): ?User { return $this->bidder; }
    public function setBidder(User $bidder): self { $this->bidder = $bidder; return $this; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(User $owner): self { $this->owner = $owner; return $this; }
    public function getPrice(): ?string { return $this->price; }
    public function setPrice(?string $price): self { $this->price = $price; return $this; }
    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $currency): self { $this->currency = $currency; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): self { $this->message = $message; return $this; }
    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function setContactPhone(?string $contactPhone): self { $this->contactPhone = $contactPhone; return $this; }
    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): self { $this->contactEmail = $contactEmail; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}


