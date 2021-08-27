<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    public function __construct(
        private HttpClientInterface $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $start = microtime(true);

        $response = $this->client->request('GET', 'https://symfony.com/all-versions.json');

        dump($response->toArray());

        $io->note(sprintf('in %.3fms', 1000 * (microtime(true) - $start)));

        return Command::SUCCESS;
    }
}
