<?php

namespace App\Command;

use App\Service\DatabaseProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:db:process',
    description: 'Start processing database by database id and temporary database name',
)]
class CreateBackupCommand extends Command
{
    private DatabaseProcessor $databaseProcessor;

    public function __construct(
        DatabaseProcessor $databaseProcessor,
        string $name = null
    ) {
        parent::__construct($name);
        $this->databaseProcessor = $databaseProcessor;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('database_uid', InputArgument::REQUIRED, 'Database UUID from the service')
            ->addArgument('db_name', InputArgument::REQUIRED, 'Temporary database name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->databaseProcessor->process(
            $input->getArgument('database_uid'),
            $input->getArgument('db_name')
        );

        $output->write('project1');

        return Command::SUCCESS;
    }
}
