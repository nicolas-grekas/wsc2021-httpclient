<?php

namespace App;

use Symfony\Contracts\HttpClient\ResponseInterface;

class Package
{
    private $name;
    private $versions;
    private $response;

    public function __construct(string $name, ResponseInterface $response)
    {
        $this->name = $name;
        $this->response = $response;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersions(): array
    {
        if (null === $this->versions) {
            $this->versions = array_keys($this->response->toArray()['packages'][$this->name]);
            $this->response = null;
        }

        return $this->versions;
    }
}