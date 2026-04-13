<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    private ?string $icon = null;

    #[ORM\Column(length: 50)]
    private ?string $color = 'violet';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?int $sortOrder = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'category')]
    private Collection $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getColorClasses(): array
    {
        return match($this->color) {
            'violet' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-600', 'border' => 'border-violet-200', 'gradient' => 'from-violet-500 to-purple-600'],
            'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-200', 'gradient' => 'from-blue-500 to-cyan-500'],
            'pink' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600', 'border' => 'border-pink-200', 'gradient' => 'from-pink-500 to-rose-500'],
            'green' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'gradient' => 'from-emerald-500 to-teal-500'],
            'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-200', 'gradient' => 'from-orange-500 to-amber-500'],
            'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-200', 'gradient' => 'from-red-500 to-pink-500'],
            'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200', 'gradient' => 'from-indigo-500 to-blue-600'],
            'cyan' => ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-600', 'border' => 'border-cyan-200', 'gradient' => 'from-cyan-500 to-blue-500'],
            default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'gradient' => 'from-gray-500 to-gray-600'],
        };
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setCategory($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            if ($service->getCategory() === $this) {
                $service->setCategory(null);
            }
        }

        return $this;
    }

    public function getServiceCount(): int
    {
        return $this->services->count();
    }
}
