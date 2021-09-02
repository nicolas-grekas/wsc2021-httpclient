<?php

namespace App;

class Package
{
    private $name;
    private $versions;

    public function __construct(string $name, array $packagistData)
    {
        $this->name = $name;
        $this->versions = $packagistData;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersions(): array
    {
        return $this->versions;
    }
}