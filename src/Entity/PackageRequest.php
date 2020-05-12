<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PackageRequestRepository")
 */
class PackageRequest
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=175)
     */
    private string $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $approved;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $createdAt;

    public function __construct()
    {
        $this->approved = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function approve(): self
    {
        $this->approved = true;

        return $this;
    }

	/**
	 * Required by admin
	 */
    public function setApproved(bool $approved): self
    {
    	$this->approved = $approved;

    	return $this;
    }

    public function getVersion(): string
    {
        return '0.0';
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
