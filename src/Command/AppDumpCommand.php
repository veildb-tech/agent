<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DumpProcessor;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Exception\ShellProcessorException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:db:dump',
    description: 'Create DB dump',
)]
final class AppDumpCommand extends Command
{
    /**
     * @param DumpProcessor     $dumpProcessor
     * @param LoggerInterface   $logger
     * @param string|null       $name
     */
    public function __construct(
        protected readonly DumpProcessor $dumpProcessor,
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
            'db',
            null,
            InputOption::VALUE_REQUIRED,
            'Database name'
        )->addOption(
            'path',
            null,
            InputOption::VALUE_OPTIONAL,
            'Path for backup file'
        )->addOption(
            'engine',
            null,
            InputOption::VALUE_OPTIONAL,
            'Db Engine ( mysql | postgres )',
            'mysql'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NoSuchEngineException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->dumpProcessor->dump(
                $input->getOption('db'),
                $input->getOption('path'),
                $input->getOption('engine'),
            );
        } catch (ShellProcessorException $e) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
