<?php

declare(strict_types=1);

namespace App\Command;

use App\ServiceApi\Actions\GetScheduledUID;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:db:getScheduled',
    description: 'Get scheduled db dump from service. This returns project UID',
)]
class GetScheduledCommand extends Command
{
    public function __construct(
        protected readonly GetScheduledUID $getScheduledUID,
        protected readonly LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $data = $this->getScheduledUID->get();

            $output->writeln($data);
        } catch (
            Exception
            | ClientExceptionInterface
            | DecodingExceptionInterface
            | ServerExceptionInterface
            | TransportExceptionInterface
            | RedirectionExceptionInterface $e
        ) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
