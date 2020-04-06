<?php

namespace App\Entity;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PackageRepository")
 */
class Package
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=175, unique=true)
     */
    private string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $description;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $version;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private ?\DateTimeInterface $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Release")
     */
    private Collection $releases;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUpdatedAt(): ?CarbonImmutable
    {
        return $this->updatedAt ? CarbonImmutable::instance($this->updatedAt) : null;
    }
}
