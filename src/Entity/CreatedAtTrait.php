<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
trait CreatedAtTrait
{
    #[ORM\Column(type: 'datetime')]
    protected ?\DateTimeImmutable $createdAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}