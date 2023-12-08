<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PublicCommand\Server\Update;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:server:update',
    description: 'Update server',
)]
final class AppServerUpdateCommand extends Command
{
    /**
     * @param Update $serverUpdate
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        protected readonly Update $serverUpdate,
        protected readonly LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->addOption(
            'current',
            null,
            null,
            'If this option set it command only updates server credentials'
        );
        $this->addOption(
            'email',
            null,
            InputOption::VALUE_OPTIONAL,
            'Email to authorize'
        );
        $this->addOption(
            'password',
            null,
            InputOption::VALUE_OPTIONAL,
            'Password to authorize'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->serverUpdate->execute($input, $output);
        } catch (
            ClientExceptionInterface
            | InvalidArgumentException
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | DecodingExceptionInterface
            | TransportExceptionInterface $e
        ) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
