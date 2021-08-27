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

        $this->http2demo($io);

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
}
