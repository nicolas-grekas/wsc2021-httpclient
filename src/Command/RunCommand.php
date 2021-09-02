<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:run',
    description: 'Rock-n-Roll with HttpClient!',
)]
class RunCommand extends Command
{
    private const BAR = ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'];

    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->client = $this->client->withOptions(['base_uri' => 'https://repo.packagist.org/']);
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $start = microtime(true);

        $packages = $this->client->request('GET', 'packages.json')->toArray();

        $providers = [];

        foreach ($packages['provider-includes'] as $link => $hash) {
            $providers[] = $this->client->request('GET', str_replace('%hash%', $hash['sha256'], $link));
        }

        $allPackages = [];
        foreach ($providers as $provider) {
            foreach ($provider->toArray()['providers'] as $packageName => $hash) {
                $allPackages[$packageName] = sprintf('p2/%s.json', $packageName);
            }
        }

        while ($packagesBatch = array_splice($allPackages, -500)) {
            $responses = [];
            foreach ($packagesBatch as $packageName => $url) {
                $responses[$url] = $this->client->request('GET', $url);
            }

            foreach ($responses as $url => $response) {
                if (200 !== $response->getStatusCode()) {
                    echo 4;
                } else {
                    echo '.';
                }
            }
        }

        $io->note(sprintf('in %.3fms', 1000 * (microtime(true) - $start)));

        return Command::SUCCESS;
    }

    /**
     * @see https://http2.akamai.com/demo/
     */
    private function http2demo(SymfonyStyle $io)
    {
        $size = 0;
        for ($i = 0; $i < 379; ++$i) {
            $responses[] = $this->client->request('GET', "https://http2.akamai.com/demo/tile-$i.png", ['user_data' => $i]);
        }

        $order = [];
        foreach ($this->client->stream($responses) as $response => $chunk) {
            if ($chunk->isFirst()) {
                $size += $response->getHeaders()['content-length'][0];
                $order[] = $response->getInfo('user_data');
            }
        }

        dump($order);

        $io->write(sprintf('Total size is <info>%d</> bytes.', $size));
    }

    private function streamDemo(SymfonyStyle $io)
    {
        $output = fopen('ubuntu-18.04.1-desktop-amd64.iso', 'a');
        $response = $this->client->request('GET', 'http://releases.ubuntu.com/18.04.1/ubuntu-18.04.1-desktop-amd64.iso', [
            'buffer' => $output,
            'headers' => [
                'Range' => fstat($output)['size'].'-',
            ],
        ]);

        $progressBar = $io->createProgressBar();
        $progressBar->setFormat('%bar%  %message%');
        $progressBar->setBarCharacter('✔');
        $progressBar->setMessage('Hello');
        $progressBar->setBarWidth(1);
        $progressBar->start();

        $step = 0;

        while (true) {
            foreach ($this->client->stream($response, 0.05) as $chunk) {
                $progressBar->setMessage(sprintf('%dkb/s', $response->getInfo('speed_download') / 1024));
                $progressBar->setProgressCharacter(self::BAR[++$step % 8]);
                $progressBar->advance();

                if ($chunk->isTimeout()) {
                    continue 2;
                }
                if ($chunk->isLast()) {
                    break 2;
                }

                $response->getInfo('pause_handler')(0.1);
            }
        }

        $progressBar->finish();
    }
}
