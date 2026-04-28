<?php

namespace App\Entity;

use App\Repository\VoucherRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
class Voucher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Voucher code is required.')]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: 'Discount is required.')]
    #[Assert\Positive(message: 'Discount must be greater than 0.')]
    private ?string $discountPercentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $maxDiscountAmount = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'Start date is required.')]
    private ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'End date is required.')]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 999999])]
    private int $usageLimit = 999999;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $usageCount = 0;

    #[ORM\ManyToOne(targetEntity: FeaturedService::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FeaturedService $featuredService = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }
    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getDiscountPercentage(): ?string
    {
        return $this->discountPercentage;
    }
    public function setDiscountPercentage(string $discountPercentage): static
    {
        $this->discountPercentage = $discountPercentage;
        return $this;
    }

    public function getMaxDiscountAmount(): ?string
    {
        return $this->maxDiscountAmount;
    }
    public function setMaxDiscountAmount(?string $maxDiscountAmount): static
    {
        $this->maxDiscountAmount = $maxDiscountAmount;
        return $this;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }
    public function setStartDate(DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }
    public function setEndDate(DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }
    public function setUsageLimit(int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }
    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }
    public function incrementUsage(): static
    {
        ++$this->usageCount;
        return $this;
    }

    public function getFeaturedService(): ?FeaturedService
    {
        return $this->featuredService;
    }
    public function setFeaturedService(?FeaturedService $featuredService): static
    {
        $this->featuredService = $featuredService;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isValid(): bool
    {
        $now = new DateTimeImmutable();
        return $this->isActive
            && $this->startDate <= $now
            && $this->endDate >= $now
            && $this->usageCount < $this->usageLimit;
    }
}
