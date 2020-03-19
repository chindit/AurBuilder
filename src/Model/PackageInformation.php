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

    public function __construct(
        int $id,
        string $name,
        string $url,
        string $version,
        int $lastModified,
        string $description
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->version = $version;
        $this->lastModified = (new \DateTimeImmutable())->setTimestamp($lastModified);
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
