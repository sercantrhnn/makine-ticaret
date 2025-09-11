<?php

namespace App\Entity;

use App\Repository\BidMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BidMessageRepository::class)]
#[ORM\Table(name: 'bid_message')]
#[ORM\HasLifecycleCallbacks]
class BidMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BidOffer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BidOffer $offer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getOffer(): ?BidOffer { return $this->offer; }
    public function setOffer(BidOffer $offer): self { $this->offer = $offer; return $this; }
    public function getSender(): ?User { return $this->sender; }
    public function setSender(User $sender): self { $this->sender = $sender; return $this; }
    public function getReceiver(): ?User { return $this->receiver; }
    public function setReceiver(User $receiver): self { $this->receiver = $receiver; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}


