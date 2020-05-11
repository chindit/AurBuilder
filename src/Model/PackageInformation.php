<?php

namespace App\Model;

final class PackageInformation
{
    private int $id;
    private string $name;
    private string $description;
    private string $version;
    private string $url;
    private \DateTimeImmutable $lastModified;
    private bool $inRespository;
    private bool $requested;
    private string $actualVersion;

    public function __construct(
        int $id,
        string $name,
        string $url,
        string $version,
        int $lastModified,
        string $description,
        string $actualVersion = ''
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->version = $version;
        $this->lastModified = (new \DateTimeImmutable())->setTimestamp($lastModified);
        $this->description = $description;
        $this->inRespository = false;
        $this->requested = false;
        $this->actualVersion = $actualVersion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getActualVersion(): string
    {
        return $this->actualVersion;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isInRepository(): bool
    {
        return $this->inRespository;
    }

    public function setInRepository(bool $state): self
    {
        $this->inRespository = $state;

        return $this;
    }

    public function isRequested(): bool
    {
        return $this->requested;
    }

    public function setRequested(bool $requested): self
    {
        $this->requested = $requested;

        return $this;
    }
}
