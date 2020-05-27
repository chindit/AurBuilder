<?php

namespace App\Entity;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ReleaseRepository")
 * @ORM\Table(name="package_release")
 */
class Release
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private string $lastVersion;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private string $newVersion;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLastVersion(): ?string
    {
        return $this->lastVersion;
    }

    public function setLastVersion(string $lastVersion): self
    {
        $this->lastVersion = $lastVersion;

        return $this;
    }

    public function getNewVersion(): ?string
    {
        return $this->newVersion;
    }

    public function setNewVersion(string $newVersion): self
    {
        $this->newVersion = $newVersion;

        return $this;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->updatedAt);
    }
}
