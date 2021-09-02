<?php

namespace App;

use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackagistApi
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->client = new RetryableHttpClient($client->withOptions([
            'base_uri' => 'https://repo.packagist.org/',
        ]));
    }


    public function getAllPackages(): \Generator
    {
        $packages = $this->client->request('GET', 'packages.json')->toArray();

        $providers = [];

        foreach ($packages['provider-includes'] as $link => $hash) {
            $providers[] = $this->client->request('GET', str_replace('%hash%', $hash['sha256'], $link));
        }

        foreach ($providers as $provider) {
            foreach ($provider->toArray()['providers'] as $packageName => $hash) {
                yield $this->getPackage($packageName);
            }
        }
    }

    public function getPackage(string $name)
    {
        $url = sprintf('p/%s.json', $name);
        $response = $this->client->request('GET', $url);

        return new Package($name, $response->toArray());
    }
}