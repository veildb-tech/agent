<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DatabaseProcessor;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:db:process',
    description: 'Start processing database by database id and temporary database name',
)]
final class AppProcessCommand extends Command
{
    /**
     * @param DatabaseProcessor $databaseProcessor
     * @param LoggerInterface   $logger
     * @param string|null       $name
     */
    public function __construct(
        protected readonly DatabaseProcessor $databaseProcessor,
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
            'uid',
            'u',
            InputOption::VALUE_REQUIRED,
            'Database UUID from the service'
        )->addOption(
            'db',
            null,
            InputOption::VALUE_REQUIRED,
            'Temporary database name'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->databaseProcessor->process(
                $input->getOption('uid'),
                $input->getOption('db')
            );
        } catch (NoSuchEngineException | DecodingExceptionInterface | TransportExceptionInterface $e) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
