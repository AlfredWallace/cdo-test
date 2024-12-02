<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    use CreatedAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1)]
    private ?string $type = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $number = null;

    #[ORM\Column]
    private ?float $htProduct = null;

    #[ORM\Column]
    private ?float $vatProduct = null;

    #[ORM\Column]
    private ?float $htTransport = null;

    #[ORM\Column]
    private ?float $vatTransport = null;

    #[ORM\Column]
    private ?float $totalTtc = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getHtProduct(): ?float
    {
        return $this->htProduct;
    }

    public function setHtProduct(float $htProduct): static
    {
        $this->htProduct = $htProduct;

        return $this;
    }

    public function getVatProduct(): ?float
    {
        return $this->vatProduct;
    }

    public function setVatProduct(float $vatProduct): static
    {
        $this->vatProduct = $vatProduct;

        return $this;
    }

    public function getHtTransport(): ?float
    {
        return $this->htTransport;
    }

    public function setHtTransport(float $htTransport): static
    {
        $this->htTransport = $htTransport;

        return $this;
    }

    public function getVatTransport(): ?float
    {
        return $this->vatTransport;
    }

    public function setVatTransport(float $vatTransport): static
    {
        $this->vatTransport = $vatTransport;

        return $this;
    }

    public function getTotalTtc(): ?float
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(float $totalTtc): static
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }
}
